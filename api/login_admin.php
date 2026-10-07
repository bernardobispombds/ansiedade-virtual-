<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
require __DIR__ . '/log.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($dados['email'] ?? '');
$senha = $dados['senha'] ?? '';

$stmt = $pdo->prepare('SELECT id_usuario, nome, email, senha, is_admin, ativo FROM usuario WHERE email = :email');
$stmt->execute([':email' => $email]);
$u = $stmt->fetch();

if (!$u) {
    registrar_login($pdo, null, $email, false, 'email_nao_encontrado');
    http_response_code(401);
    exit(json_encode(['erro' => 'E-mail ou senha incorretos.']));
}
if (!password_verify($senha, $u['senha'])) {
    registrar_login($pdo, (int) $u['id_usuario'], $email, false, 'senha_incorreta');
    http_response_code(401);
    exit(json_encode(['erro' => 'E-mail ou senha incorretos.']));
}
if (!pg_bool($u['ativo'])) {
    registrar_login($pdo, (int) $u['id_usuario'], $email, false, 'conta_desativada');
    http_response_code(403);
    exit(json_encode(['erro' => 'Esta conta está desativada.']));
}
if (!pg_bool($u['is_admin'])) {
    registrar_login($pdo, (int) $u['id_usuario'], $email, false, 'nao_admin');
    http_response_code(403);
    exit(json_encode(['erro' => 'Esta conta não tem permissão de administrador.']));
}

session_regenerate_id(true);
$_SESSION['id_usuario'] = (int) $u['id_usuario'];
$_SESSION['nome']       = $u['nome'];
$_SESSION['is_admin']   = true;
registrar_login($pdo, (int) $u['id_usuario'], $email, true);

echo json_encode(['id_usuario' => $u['id_usuario'], 'nome' => $u['nome']]);
