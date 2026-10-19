<?php
// Detalhes de um usuário para o painel do administrador.
// GET api/admin_usuario.php?id=123  ->  conta, resumo de acessos, últimos acessos e solicitações.
// Os registros de humor NÃO são expostos: o site promete que só o próprio usuário os vê.
ini_set('display_errors', '0');
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder($codigo, $dados) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['is_admin'])) {
    responder(403, ['erro' => 'Acesso restrito a administradores.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, ['erro' => 'Método não permitido']);
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    responder(400, ['erro' => 'Identificador inválido.']);
}

// Mesmo formato de data do painel ("Hoje, 14:05", "Ontem, 09:30", "07/10/2026, 03:28").
function quando($ts, $curto = false) {
    if (!$ts) return '—';
    $tz   = new DateTimeZone('America/Sao_Paulo');
    $d    = new DateTime($ts);
    $d->setTimezone($tz);
    $hoje = new DateTime('today', $tz);
    $dia  = clone $d;
    $dia->setTime(0, 0);
    $dif  = (int) $hoje->diff($dia)->format('%r%a');
    $hora = $d->format('H:i');
    if ($dif === 0)  return 'Hoje, ' . $hora;
    if ($dif === -1) return 'Ontem, ' . $hora;
    return $d->format('d/m/Y') . ($curto ? '' : ', ' . $hora);
}

function motivo_texto($m) {
    $mapa = [
        'senha_incorreta'      => 'Senha incorreta',
        'email_nao_encontrado' => 'E-mail não encontrado',
        'conta_desativada'     => 'Conta desativada',
        'nao_admin'            => 'Sem permissão de admin',
    ];
    return $mapa[$m] ?? 'Falha';
}

function situacao_info($s) {
    switch ($s) {
        case 'pendente':  return ['tag pendente', 'Pendente'];
        case 'andamento': return ['tag andamento', 'Em andamento'];
        case 'recusada':  return ['tag falha', 'Recusada'];
        default:          return ['tag', 'Concluída'];
    }
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- conta ---
    $st = $pdo->prepare('SELECT id_usuario, nome, email, telefone, data_cadastro, ativo, is_admin
                           FROM usuario WHERE id_usuario = :id');
    $st->execute([':id' => $id]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        responder(404, ['erro' => 'Usuário não encontrado.']);
    }

    // --- resumo de acessos ---
    $st = $pdo->prepare('SELECT COUNT(*) FILTER (WHERE sucesso)     AS ok,
                                COUNT(*) FILTER (WHERE NOT sucesso) AS falhas,
                                MAX(data_hora) FILTER (WHERE sucesso) AS ultimo
                           FROM login_log WHERE id_usuario = :id');
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);

    // --- últimos acessos ---
    $st = $pdo->prepare('SELECT data_hora, sucesso, motivo, dispositivo
                           FROM login_log WHERE id_usuario = :id
                          ORDER BY data_hora DESC LIMIT 20');
    $st->execute([':id' => $id]);
    $acessos = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $l) {
        $ok = pg_bool($l['sucesso']);
        $acessos[] = [
            'quando'     => quando($l['data_hora']),
            'dispositivo'=> $l['dispositivo'] ?: '—',
            'ok'         => $ok,
            'resultado'  => $ok ? 'Sucesso' : motivo_texto($l['motivo']),
        ];
    }

    // --- solicitações ---
    $st = $pdo->prepare('SELECT pedido, situacao, data_hora
                           FROM solicitacao WHERE id_usuario = :id
                          ORDER BY data_hora DESC LIMIT 50');
    $st->execute([':id' => $id]);
    $solicitacoes = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
        list($classe, $texto) = situacao_info($s['situacao']);
        $solicitacoes[] = [
            'pedido'  => $s['pedido'],
            'quando'  => quando($s['data_hora']),
            'classe'  => $classe,
            'situacao'=> $texto,
        ];
    }

    responder(200, [
        'usuario' => [
            'id_usuario' => (int) $u['id_usuario'],
            'nome'       => $u['nome'],
            'email'      => $u['email'],
            'telefone'   => $u['telefone'] ?: '—',
            'cadastro'   => quando($u['data_cadastro']),
            'ativo'      => pg_bool($u['ativo']),
            'is_admin'   => pg_bool($u['is_admin']),
        ],
        'resumo' => [
            'acessos_ok'    => (int) $r['ok'],
            'acessos_falha' => (int) $r['falhas'],
            'ultimo_acesso' => quando($r['ultimo']),
            'solicitacoes'  => count($solicitacoes),
        ],
        'acessos'      => $acessos,
        'solicitacoes' => $solicitacoes,
    ]);
} catch (PDOException $e) {
    error_log('admin_usuario.php: ' . $e->getMessage());
    responder(500, ['erro' => 'Erro no servidor.']);
}
