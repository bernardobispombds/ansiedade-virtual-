<?php
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados    = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($dados['email'] ?? '');
$nome     = (string) ($dados['nome'] ?? '');
$telefone = (string) ($dados['telefone'] ?? '');
$senha    = (string) ($dados['senha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || trim($nome) === '') {
    http_response_code(400);
    exit(json_encode(['erro' => 'Informe o e-mail e o nome completo.']));
}
if (strlen($senha) < 8) {
    http_response_code(400);
    exit(json_encode(['erro' => 'A senha deve ter 8 ou mais caracteres.']));
}

function normalizar_nome(string $s): string
{
    return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)), 'UTF-8');
}
function so_digitos(string $s): string
{
    return preg_replace('/\D+/', '', $s);
}

$falhou = ['erro' => 'Não foi possível confirmar os dados. Confira e tente novamente.'];

try {
    // Limite: 5 tentativas erradas por e-mail a cada 15 minutos
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM tentativas_redefinicao
         WHERE email = lower(:email) AND criado_em > now() - interval '15 minutes'"
    );
    $stmt->execute([':email' => $email]);
    if ((int) $stmt->fetchColumn() >= 5) {
        http_response_code(429);
        exit(json_encode(['erro' => 'Muitas tentativas. Aguarde 15 minutos e tente de novo.']));
    }

    // Contas de administrador não podem ser redefinidas por aqui
    $stmt = $pdo->prepare(
        'SELECT id_usuario, nome, telefone FROM usuario
         WHERE lower(email) = lower(:email) AND ativo = true AND is_admin = false
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    $confere = false;
    if ($u) {
        $confere = hash_equals(normalizar_nome($u['nome']), normalizar_nome($nome));

        // Se a conta tem telefone cadastrado, ele também precisa conferir
        $telConta = so_digitos((string) ($u['telefone'] ?? ''));
        if ($confere && $telConta !== '') {
            $telInformado = so_digitos($telefone);
            $confere = strlen($telInformado) >= 8
                && hash_equals(substr($telConta, -8), substr($telInformado, -8));
        }
    }

    if (!$confere) {
        $pdo->prepare('INSERT INTO tentativas_redefinicao (email) VALUES (lower(:email))')
            ->execute([':email' => $email]);
        http_response_code(400);
        exit(json_encode($falhou));
    }

    $pdo->prepare('UPDATE usuario SET senha = :senha WHERE id_usuario = :id')
        ->execute([
            ':senha' => password_hash($senha, PASSWORD_DEFAULT),
            ':id'    => $u['id_usuario'],
        ]);
    $pdo->prepare('DELETE FROM tentativas_redefinicao WHERE email = lower(:email)')
        ->execute([':email' => $email]);

    echo json_encode(['mensagem' => 'Senha alterada com sucesso.']);
} catch (Throwable $e) {
    error_log('redefinir_direto: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor. Tente novamente.']);
}
