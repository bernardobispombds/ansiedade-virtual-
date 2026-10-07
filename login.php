<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

$stmt = $pdo->prepare('SELECT id_usuario, nome, email, senha FROM usuario WHERE email = :email');
$stmt->execute([':email' => $email]);
$u = $stmt->fetch();

if (!$u || !password_verify($senha, $u['senha'])) {
    http_response_code(401);
    exit(json_encode(['erro' => 'E-mail ou senha incorretos.']));
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = $u['id_usuario'];
$_SESSION['nome']       = $u['nome'];

echo json_encode(['id_usuario' => $u['id_usuario'], 'nome' => $u['nome'], 'email' => $u['email']]);
