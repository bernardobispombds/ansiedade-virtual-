<?php
require __DIR__ . '/sessao.php';
header('Content-Type: application/json; charset=utf-8');

$_SESSION = [];
session_destroy();
echo json_encode(['ok' => true]);
