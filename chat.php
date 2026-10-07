<?php
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['id_usuario'])) {
    http_response_code(401);
    exit(json_encode(['erro' => 'Faça login para usar o chat.']));
}
$idUsuario = (int) $_SESSION['id_usuario'];

// Respostas de demonstração. Quando a IA for conectada, é só trocar o conteúdo
// desta função (ela recebe a mensagem da pessoa e devolve o texto da resposta).
function gerar_resposta(string $mensagem, int $indice): string {
    $demo = [
        'Obrigado por compartilhar isso comigo. No momento esta é uma prévia — em breve uma IA treinada para escuta acolhedora responderá por aqui.',
        'Entendo. Assim que a IA estiver ativa, ela vai te ouvir com calma e, se for o caso, te ajudar a encontrar alguém do grupo para conversar.',
        'Anotado. Esta caixa de chat já está pronta para receber o assistente com IA — só falta conectar o modelo.',
    ];
    return $demo[$indice % count($demo)];
}

try {
    // ---------- GET: devolve o histórico da pessoa ----------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare(
            'SELECT a.id_chat, a.pergunta, r.texto AS resposta
               FROM atendimento a
               LEFT JOIN resposta r ON r.id_chat = a.id_chat
              WHERE a.id_usuario = :u
              ORDER BY a.id_chat DESC
              LIMIT 50'
        );
        $stmt->execute([':u' => $idUsuario]);
        echo json_encode(['mensagens' => array_reverse($stmt->fetchAll())]);
        exit;
    }

    // ---------- POST: salva a mensagem, gera e salva a resposta ----------
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode(['erro' => 'Método não permitido']));
    }

    $dados    = json_decode(file_get_contents('php://input'), true) ?? [];
    $mensagem = trim($dados['mensagem'] ?? '');

    if ($mensagem === '' || mb_strlen($mensagem) > 2000) {
        http_response_code(400);
        exit(json_encode(['erro' => 'Escreva uma mensagem de até 2000 caracteres.']));
    }

    $inicio = microtime(true);
    $pdo->beginTransaction();

    $q = $pdo->prepare('SELECT COUNT(*) FROM atendimento WHERE id_usuario = :u');
    $q->execute([':u' => $idUsuario]);
    $indice = (int) $q->fetchColumn();

    $ins = $pdo->prepare(
        'INSERT INTO atendimento (id_usuario, pergunta) VALUES (:u, :p) RETURNING id_chat'
    );
    $ins->execute([':u' => $idUsuario, ':p' => $mensagem]);
    $idChat = $ins->fetchColumn();

    $resposta = gerar_resposta($mensagem, $indice);
    $ms = (int) round((microtime(true) - $inicio) * 1000); // tempo de resposta em milissegundos

    $pdo->prepare(
        'INSERT INTO resposta (id_chat, tempo_de_resposta, texto) VALUES (:c, :t, :x)'
    )->execute([':c' => $idChat, ':t' => $ms, ':x' => $resposta]);

    $pdo->commit();
    echo json_encode(['resposta' => $resposta]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Erro no servidor. Tente novamente.']);
}
