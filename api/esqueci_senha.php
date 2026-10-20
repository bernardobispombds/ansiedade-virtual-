<?php
require __DIR__ . '/conexao.php';
require __DIR__ . '/email.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($dados['email'] ?? '');

// Resposta sempre igual, exista o e-mail ou não (não revela quem tem conta)
$resposta = ['mensagem' => 'Se o e-mail estiver cadastrado, enviaremos as instruções para redefinir a senha.'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Informe um e-mail válido.']));
}

try {
    $stmt = $pdo->prepare(
        'SELECT id_usuario, nome FROM usuario
         WHERE lower(email) = lower(:email) AND ativo = true
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if ($u) {
        // Limite: no máximo 3 pedidos por hora para a mesma conta
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM redefinicao_senha
             WHERE id_usuario = :id AND criado_em > now() - interval '1 hour'"
        );
        $stmt->execute([':id' => $u['id_usuario']]);

        $site = rtrim((string) getenv('SITE_URL'), '/'); // ex.: https://seusite.onrender.com
        if ($site === '') {
            error_log('esqueci_senha: SITE_URL não configurada');
        } elseif ((int) $stmt->fetchColumn() < 3) {
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare(
                "INSERT INTO redefinicao_senha (id_usuario, token_hash, expira_em)
                 VALUES (:id, :hash, now() + interval '30 minutes')"
            );
            $stmt->execute([':id' => $u['id_usuario'], ':hash' => hash('sha256', $token)]);

            $link = $site . '/redefinir-senha.html?token=' . $token;
            $nome = htmlspecialchars($u['nome'], ENT_QUOTES, 'UTF-8');
            $html = "<p>Olá, {$nome}!</p>"
                  . '<p>Recebemos um pedido para redefinir a sua senha no Ansiedade Virtual.</p>'
                  . '<p><a href="' . $link . '">Clique aqui para criar uma nova senha</a></p>'
                  . '<p>O link vale por 30 minutos e só pode ser usado uma vez. '
                  . 'Se não foi você, é só ignorar este e-mail.</p>';

            enviar_email($email, 'Redefinição de senha — Ansiedade Virtual', $html);
        }
    }
} catch (Throwable $e) {
    error_log('esqueci_senha: ' . $e->getMessage());
}

echo json_encode($resposta);
