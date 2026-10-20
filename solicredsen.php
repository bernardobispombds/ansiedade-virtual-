<?php
// api/solicitar_redefinicao.php
// Recebe { "email": "..." } e envia um link de redefinição de senha.
// Sempre responde com sucesso (mesmo que o e-mail não exista) para não
// revelar quais e-mails estão cadastrados.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/conexao.php';   // deve definir $pdo (PDO)

// ====== CONFIGURAÇÃO ======
const SITE_URL        = 'https://SEU-DOMINIO.com.br';   // sem barra no final
const REMETENTE       = 'nao-responda@SEU-DOMINIO.com.br';
const VALIDADE_MIN    = 60;   // validade do link, em minutos
const MAX_POR_HORA    = 3;    // pedidos por e-mail por hora
// ==========================

function responder(int $codigo, array $dados): void {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['erro' => 'Método não permitido.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($entrada['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    responder(400, ['erro' => 'Informe um e-mail válido.']);
}

$resposta_generica = ['ok' => true];

try {
    $st = $pdo->prepare('SELECT id, nome FROM usuarios WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $usuario = $st->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        responder(200, $resposta_generica);
    }

    // Limite de pedidos por usuário na última hora
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM redefinicoes_senha
         WHERE usuario_id = ? AND criado_em > (NOW() - INTERVAL 1 HOUR)'
    );
    $st->execute([$usuario['id']]);
    if ((int)$st->fetchColumn() >= MAX_POR_HORA) {
        responder(200, $resposta_generica);
    }

    // Invalida links anteriores ainda não usados
    $pdo->prepare('UPDATE redefinicoes_senha SET usado = 1 WHERE usuario_id = ? AND usado = 0')
        ->execute([$usuario['id']]);

    // Token aleatório; no banco guardamos apenas o hash
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);

    $pdo->prepare(
        'INSERT INTO redefinicoes_senha (usuario_id, token_hash, expira_em)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . (int)VALIDADE_MIN . ' MINUTE))'
    )->execute([$usuario['id'], $hash]);

    $link  = SITE_URL . '/redefinir-senha.html?token=' . $token;
    $nome  = htmlspecialchars($usuario['nome'] ?? '', ENT_QUOTES, 'UTF-8');

    $assunto = '=?UTF-8?B?' . base64_encode('Redefinir sua senha — Ansiedade Virtual') . '?=';
    $corpo = "<p>Olá, {$nome}!</p>"
           . "<p>Recebemos um pedido para redefinir a senha da sua conta no Ansiedade Virtual.</p>"
           . "<p><a href=\"{$link}\">Clique aqui para criar uma nova senha</a></p>"
           . "<p>Este link vale por " . VALIDADE_MIN . " minutos e só pode ser usado uma vez.</p>"
           . "<p>Se você não fez esse pedido, pode ignorar este e-mail: sua senha continua a mesma.</p>";

    $cabecalhos = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: Ansiedade Virtual <' . REMETENTE . '>',
    ];

    @mail($email, $assunto, $corpo, implode("\r\n", $cabecalhos));

} catch (Throwable $e) {
    error_log('solicitar_redefinicao: ' . $e->getMessage());
    // Mesmo em erro interno, não vazamos detalhes
}

responder(200, $resposta_generica);
