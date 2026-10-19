<?php
require __DIR__ . '/../sessao.php';
require __DIR__ . '/../conexao.php';

if (empty($_SESSION['id_usuario']) || !pg_bool($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
    exit;
}
session_write_close(); // libera a sessão, senão o resto do painel trava

@ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) { ob_end_flush(); }
set_time_limit(60);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

function enviar(array $dados, ?int $id = null): void {
    if ($id !== null) echo "id: $id\n";
    echo 'data: ' . json_encode($dados, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
}

$ultimoId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? $_GET['last_id'] ?? 0);
if ($ultimoId === 0) {
    $ultimoId = (int) $pdo->query('SELECT COALESCE(MAX(id_chat), 0) FROM atendimento')->fetchColumn();
}

// Perguntas recentes que ainda esperam resposta da IA
$pendentes = [];
$p = $pdo->prepare(
    "SELECT a.id_chat FROM atendimento a
       LEFT JOIN resposta r ON r.id_chat = a.id_chat
      WHERE r.id_chat IS NULL AND a.id_chat <= :l
        AND a.data_hora > NOW() - INTERVAL '10 minutes'"
);
$p->execute([':l' => $ultimoId]);
foreach ($p->fetchAll(PDO::FETCH_COLUMN) as $id) $pendentes[(int) $id] = true;

$novas = $pdo->prepare(
    'SELECT id_chat, id_usuario, pergunta, data_hora
       FROM atendimento WHERE id_chat > :l ORDER BY id_chat ASC LIMIT 100'
);

$inicio = time();
while (time() - $inicio < 30 && !connection_aborted()) {
    $novas->execute([':l' => $ultimoId]);
    foreach ($novas->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ultimoId = (int) $row['id_chat'];
        $pendentes[$ultimoId] = true;
        enviar(['tipo' => 'pergunta'] + $row, $ultimoId);
    }

    if ($pendentes) {
        $ids = implode(',', array_map('intval', array_keys($pendentes)));
        $r = $pdo->query("SELECT id_chat, texto FROM resposta WHERE id_chat IN ($ids)");
        foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
            unset($pendentes[(int) $row['id_chat']]);
            enviar(['tipo' => 'resposta', 'id_chat' => (int) $row['id_chat'], 'texto' => $row['texto']]);
        }
    }

    echo ": ping\n\n";
    flush();
    usleep(500000);
}
