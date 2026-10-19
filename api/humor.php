<?php
// API do "Meu humor": GET lista os últimos 30 dias, POST registra ou apaga.
// Diagnóstico: abra /api/humor.php?debug=1 (logado) para ver o erro real.
ini_set('display_errors', '0');
ob_start();

$DEBUG = isset($_GET['debug']);

function responder($codigo, $dados) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function falhar($mensagem, $detalhe = '') {
    global $DEBUG;
    error_log('humor.php: ' . $mensagem . ' | ' . $detalhe);
    $r = ['erro' => $mensagem];
    if ($DEBUG) { $r['detalhe'] = $detalhe; }
    responder(500, $r);
}

// Erros fatais do PHP viram JSON (com detalhe no modo debug).
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        falhar('Erro interno no servidor.', $e['message'] . ' em ' . basename($e['file']) . ':' . $e['line']);
    }
});

try {
    require __DIR__ . '/sessao.php';

    if (empty($_SESSION['id_usuario'])) {
        responder(401, ['erro' => 'Faça login para continuar.']);
    }
    $idUsuario = (int) $_SESSION['id_usuario'];
    session_write_close();

    // ---------- Conexão com o PostgreSQL ----------
    // Se o seu projeto já tem um arquivo de conexão, troque este bloco por ele.
    $url = getenv('DATABASE_URL');
    if (!$url) { falhar('Servidor sem banco configurado.', 'variável DATABASE_URL não definida'); }
    if (!function_exists('pg_connect')) { falhar('PHP sem extensão pgsql.', 'função pg_connect inexistente'); }
    $db = @pg_connect($url);
    if (!$db) { falhar('Não foi possível conectar ao banco.', 'pg_connect falhou'); }

    pg_query($db, "CREATE TABLE IF NOT EXISTS humor (
        id_humor   SERIAL PRIMARY KEY,
        id_usuario INTEGER NOT NULL,
        nivel      SMALLINT NOT NULL CHECK (nivel BETWEEN 1 AND 5),
        nota       VARCHAR(300),
        data_hora  TIMESTAMP NOT NULL
    )");

    $agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $hoje  = $agora->format('Y-m-d');

    // ---------- Diagnóstico ----------
    if ($DEBUG && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $cols = [];
        $rc = pg_query($db, "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'humor' ORDER BY ordinal_position");
        while ($c = pg_fetch_assoc($rc)) { $cols[] = $c['column_name'] . ' (' . $c['data_type'] . ')'; }
        responder(200, [
            'debug' => true,
            'php' => PHP_VERSION,
            'id_usuario' => $idUsuario,
            'hoje' => $hoje,
            'colunas_tabela_humor' => $cols,
        ]);
    }

    // ---------- GET: últimos 30 dias ----------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $inicio = (clone $agora)->modify('-29 days')->format('Y-m-d') . ' 00:00:00';
        $res = pg_query_params(
            $db,
            'SELECT id_humor, nivel, nota, data_hora FROM humor
             WHERE id_usuario = $1 AND data_hora >= $2
             ORDER BY data_hora ASC, id_humor ASC',
            [$idUsuario, $inicio]
        );
        if (!$res) { falhar('Erro ao buscar registros.', pg_last_error($db)); }
        $registros = [];
        while ($l = pg_fetch_assoc($res)) {
            $registros[] = [
                'id_humor'  => (int) $l['id_humor'],
                'nivel'     => (int) $l['nivel'],
                'nota'      => $l['nota'],
                'data_hora' => substr($l['data_hora'], 0, 19),
            ];
        }
        responder(200, ['hoje' => $hoje, 'registros' => $registros]);
    }

    // ---------- POST: registrar ou apagar ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $corpo = json_decode(file_get_contents('php://input'), true);
        if (!is_array($corpo)) { responder(400, ['erro' => 'Requisição inválida.']); }

        if (($corpo['acao'] ?? '') === 'apagar') {
            $id = (int) ($corpo['id'] ?? 0);
            if ($id <= 0) { responder(400, ['erro' => 'Registro inválido.']); }
            $res = pg_query_params($db, 'DELETE FROM humor WHERE id_humor = $1 AND id_usuario = $2', [$id, $idUsuario]);
            if (!$res) { falhar('Erro ao apagar.', pg_last_error($db)); }
            responder(200, ['ok' => true]);
        }

        $nivel = (int) ($corpo['nivel'] ?? 0);
        if ($nivel < 1 || $nivel > 5) { responder(400, ['erro' => 'Escolha como você está se sentindo.']); }
        $nota = mb_substr(trim((string) ($corpo['nota'] ?? '')), 0, 300);
        if ($nota === '') { $nota = null; }

        $res = pg_query_params(
            $db,
            'INSERT INTO humor (id_usuario, nivel, nota, data_hora) VALUES ($1, $2, $3, $4)',
            [$idUsuario, $nivel, $nota, $agora->format('Y-m-d H:i:s')]
        );
        if (!$res) { falhar('Não foi possível registrar agora.', pg_last_error($db)); }
        responder(201, ['ok' => true]);
    }

    responder(405, ['erro' => 'Método não permitido.']);
} catch (Throwable $e) {
    falhar('Erro interno no servidor.', get_class($e) . ': ' . $e->getMessage());
}
