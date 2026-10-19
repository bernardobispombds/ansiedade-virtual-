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

    // ---------- Conexão com o PostgreSQL (PDO) ----------
    // Se o seu projeto já tem um arquivo de conexão, troque este bloco por ele.
    $url = getenv('DATABASE_URL');
    if (!$url) { falhar('Servidor sem banco configurado.', 'variável DATABASE_URL não definida'); }
    if (!extension_loaded('pdo_pgsql')) {
        falhar('PHP sem suporte a PostgreSQL.', 'extensões carregadas com "pdo": ' . implode(', ', array_filter(get_loaded_extensions(), function ($x) { return stripos($x, 'pdo') !== false || stripos($x, 'pg') !== false || stripos($x, 'sql') !== false; })));
    }

    $p = parse_url($url);
    if (!$p || empty($p['host'])) { falhar('DATABASE_URL inválida.', 'não foi possível interpretar a URL'); }
    $dsn = 'pgsql:host=' . $p['host']
         . ';port=' . ($p['port'] ?? 5432)
         . ';dbname=' . ltrim($p['path'] ?? '', '/')
         . ';sslmode=prefer';
    $pdo = new PDO($dsn, urldecode($p['user'] ?? ''), urldecode($p['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS humor (
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
        foreach ($pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'humor' ORDER BY ordinal_position") as $c) {
            $cols[] = $c['column_name'] . ' (' . $c['data_type'] . ')';
        }
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
        $st = $pdo->prepare('SELECT id_humor, nivel, nota, data_hora FROM humor
                             WHERE id_usuario = :u AND data_hora >= :i
                             ORDER BY data_hora ASC, id_humor ASC');
        $st->execute([':u' => $idUsuario, ':i' => $inicio]);
        $registros = [];
        foreach ($st as $l) {
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
            $st = $pdo->prepare('DELETE FROM humor WHERE id_humor = :id AND id_usuario = :u');
            $st->execute([':id' => $id, ':u' => $idUsuario]);
            responder(200, ['ok' => true]);
        }

        $nivel = (int) ($corpo['nivel'] ?? 0);
        if ($nivel < 1 || $nivel > 5) { responder(400, ['erro' => 'Escolha como você está se sentindo.']); }
        $nota = mb_substr(trim((string) ($corpo['nota'] ?? '')), 0, 300);
        if ($nota === '') { $nota = null; }

        $st = $pdo->prepare('INSERT INTO humor (id_usuario, nivel, nota, data_hora) VALUES (:u, :n, :t, :d)');
        $st->execute([':u' => $idUsuario, ':n' => $nivel, ':t' => $nota, ':d' => $agora->format('Y-m-d H:i:s')]);
        responder(201, ['ok' => true]);
    }

    responder(405, ['erro' => 'Método não permitido.']);
} catch (Throwable $e) {
    falhar('Erro interno no servidor.', get_class($e) . ': ' . $e->getMessage());
}
