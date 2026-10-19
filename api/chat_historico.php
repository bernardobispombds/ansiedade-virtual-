<?php
require __DIR__ . '/../sessao.php';
require __DIR__ . '/../conexao.php';

if (empty($_SESSION['id_usuario']) || !pg_bool($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    exit;
}
session_write_close();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$stmt = $pdo->query(
    'SELECT a.id_chat, a.id_usuario, a.pergunta, a.data_hora, r.texto AS resposta
       FROM atendimento a
       LEFT JOIN resposta r ON r.id_chat = a.id_chat
      ORDER BY a.id_chat DESC
      LIMIT 100'
);
echo json_encode(array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)), JSON_UNESCAPED_UNICODE);
