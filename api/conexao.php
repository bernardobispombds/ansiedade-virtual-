<?php
// Conexão com o PostgreSQL do Render.
// A URL do banco vem da variável de ambiente DATABASE_URL (configurada no painel do Render).
$url = getenv('DATABASE_URL');
if (!$url) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['erro' => 'DATABASE_URL não configurada']));
}

$p = parse_url($url);
$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
    $p['host'],
    $p['port'] ?? 5432,
    ltrim($p['path'], '/')
);

try {
    $pdo = new PDO($dsn, urldecode($p['user']), urldecode($p['pass']), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['erro' => 'Erro ao conectar no banco']));
}
