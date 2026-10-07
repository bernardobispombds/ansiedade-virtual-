<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

$nome     = trim($dados['nome'] ?? '');
$email    = trim($dados['email'] ?? '');
$senha    = $dados['senha'] ?? '';
$telefone = trim($dados['telefone'] ?? '');

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Preencha o nome, um e-mail válido e uma senha com 6 ou mais caracteres.']));
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO usuario (nome, email, senha, telefone)
         VALUES (:nome, :email, :senha, :telefone)
         RETURNING id_usuario'
    );
    $stmt->execute([
        ':nome'     => $nome,
        ':email'    => $email,
        ':senha'    => password_hash($senha, PASSWORD_DEFAULT),
        ':telefone' => $telefone !== '' ? $telefone : null,
    ]);
    $id = $stmt->fetchColumn();

    // já deixa a pessoa logada depois de cadastrar
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $id;
    $_SESSION['nome']       = $nome;
    $_SESSION['is_admin']   = false;

    http_response_code(201);
    echo json_encode(['id_usuario' => $id, 'nome' => $nome, 'email' => $email]);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { // e-mail já existe (UNIQUE)
        http_response_code(409);
        exit(json_encode(['erro' => 'Este e-mail já está cadastrado.']));
    }
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor. Tente novamente.']);
}
