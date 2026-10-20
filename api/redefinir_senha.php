<?php
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$token = (string) ($dados['token'] ?? '');
$senha = (string) ($dados['senha'] ?? '');

if (strlen($senha) < 8) {
    http_response_code(400);
    exit(json_encode(['erro' => 'A senha deve ter 8 ou mais caracteres.']));
}

$invalido = ['erro' => 'Link inválido ou expirado. Peça um novo link.'];

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    exit(json_encode($invalido));
}

try {
    $pdo->beginTransaction();

    // FOR UPDATE evita que o mesmo link seja usado duas vezes ao mesmo tempo
    $stmt = $pdo->prepare(
        'SELECT id, id_usuario FROM redefinicao_senha
         WHERE token_hash = :hash AND usado = false AND expira_em > now()
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        $pdo->rollBack();
        http_response_code(400);
        exit(json_encode($invalido));
    }

    $pdo->prepare('UPDATE usuario SET senha = :senha WHERE id_usuario = :id')
        ->execute([
            ':senha' => password_hash($senha, PASSWORD_DEFAULT),
            ':id'    => $pedido['id_usuario'],
        ]);

    // Invalida este link e qualquer outro pendente dessa conta
    $pdo->prepare('UPDATE redefinicao_senha SET usado = true WHERE id_usuario = :id')
        ->execute([':id' => $pedido['id_usuario']]);

    $pdo->commit();
    echo json_encode(['mensagem' => 'Senha alterada com sucesso.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('redefinir_senha: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor. Tente novamente.']);
}
