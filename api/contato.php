<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

$nome     = trim((string) ($dados['nome'] ?? ''));
$email    = trim((string) ($dados['email'] ?? ''));
$assunto  = trim((string) ($dados['assunto'] ?? ''));
$mensagem = trim((string) ($dados['mensagem'] ?? ''));

if ($nome === '' || mb_strlen($nome) > 100
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150
    || $mensagem === '' || mb_strlen($mensagem) > 3000) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Preencha o nome, um e-mail válido e uma mensagem de até 3000 caracteres.']));
}
$assunto = mb_substr($assunto, 0, 100);

// IP real da pessoa (o Render coloca o endereço original em X-Forwarded-For)
$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
$ip = mb_substr($ip, 0, 45);

try {
    // limite simples contra spam: 5 mensagens por hora por IP
    $q = $pdo->prepare("SELECT COUNT(*) FROM contato WHERE ip = :ip AND data_hora > NOW() - INTERVAL '1 hour'");
    $q->execute([':ip' => $ip]);
    if ((int) $q->fetchColumn() >= 5) {
        http_response_code(429);
        exit(json_encode(['erro' => 'Você enviou muitas mensagens em pouco tempo. Tente de novo mais tarde.']));
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contato (id_usuario, nome, email, assunto, mensagem, ip)
         VALUES (:u, :n, :e, :a, :m, :ip)'
    );
    $stmt->execute([
        ':u'  => !empty($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null,
        ':n'  => $nome,
        ':e'  => $email,
        ':a'  => $assunto !== '' ? $assunto : null,
        ':m'  => $mensagem,
        ':ip' => $ip !== '' ? $ip : null,
    ]);

    http_response_code(201);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log('Erro ao salvar contato: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor. Tente novamente.']);
}
