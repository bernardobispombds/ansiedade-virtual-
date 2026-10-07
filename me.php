<?php
require __DIR__ . '/sessao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'logado' => !empty($_SESSION['id_usuario']),
    'nome'   => $_SESSION['nome'] ?? null,
]);
