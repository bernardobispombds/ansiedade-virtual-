<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['id_usuario']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    exit(json_encode(['erro' => 'Acesso restrito a administradores.']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['erro' => 'Método não permitido']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$acao  = $dados['acao'] ?? '';
$id    = (int) ($dados['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit(json_encode(['erro' => 'Identificador inválido.']));
}

$situacoes = [
    'aprovar'   => 'concluida',
    'recusar'   => 'recusada',
    'andamento' => 'andamento',
    'concluir'  => 'concluida',
];

try {
    if ($acao === 'desativar_usuario') {
        if ($id === (int) $_SESSION['id_usuario']) {
            http_response_code(400);
            exit(json_encode(['erro' => 'Você não pode desativar a sua própria conta.']));
        }
        $pdo->prepare('UPDATE usuario SET ativo = FALSE WHERE id_usuario = :id AND is_admin = FALSE')
            ->execute([':id' => $id]);
    } elseif ($acao === 'reativar_usuario') {
        $pdo->prepare('UPDATE usuario SET ativo = TRUE WHERE id_usuario = :id')
            ->execute([':id' => $id]);
    } elseif (isset($situacoes[$acao])) {
        $pdo->prepare('UPDATE solicitacao SET situacao = :s WHERE id_solicitacao = :id')
            ->execute([':s' => $situacoes[$acao], ':id' => $id]);
    } else {
        http_response_code(400);
        exit(json_encode(['erro' => 'Ação desconhecida.']));
    }
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor.']);
}
