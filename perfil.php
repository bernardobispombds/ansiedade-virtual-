<?php
// API do perfil: GET devolve os dados da conta e um resumo do humor;
// POST {acao:"dados"} atualiza nome/e-mail/telefone;
// POST {acao:"senha"} troca a senha (pede a senha atual).
ini_set('display_errors', '0');
require __DIR__ . '/sessao.php';
require __DIR__ . '/conexao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder($codigo, $dados) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['id_usuario'])) {
    responder(401, ['erro' => 'Faça login para continuar.']);
}
$id = (int) $_SESSION['id_usuario'];

function buscar_usuario(PDO $pdo, $id) {
    $st = $pdo->prepare('SELECT nome, email, telefone FROM usuario WHERE id_usuario = :id');
    $st->execute([':id' => $id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ---------- GET: dados + resumo do humor ----------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $u = buscar_usuario($pdo, $id);
        if (!$u) {
            responder(404, ['erro' => 'Conta não encontrada.']);
        }

        // Resumo do humor (a tabela pode ainda não existir se a pessoa nunca registrou).
        $humor = ['total' => 0, 'media30' => null, 'ultimo' => null];
        try {
            $st = $pdo->prepare("SELECT COUNT(*) AS total, AVG(nivel) AS media
                                 FROM humor
                                 WHERE id_usuario = :id AND data_hora >= (NOW() - INTERVAL '30 days')");
            $st->execute([':id' => $id]);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            $humor['total']   = (int) $r['total'];
            $humor['media30'] = $r['media'] !== null ? round((float) $r['media'], 1) : null;

            $st = $pdo->prepare('SELECT nivel, data_hora FROM humor WHERE id_usuario = :id ORDER BY data_hora DESC, id_humor DESC LIMIT 1');
            $st->execute([':id' => $id]);
            $ult = $st->fetch(PDO::FETCH_ASSOC);
            if ($ult) {
                $humor['ultimo'] = ['nivel' => (int) $ult['nivel'], 'data_hora' => substr($ult['data_hora'], 0, 19)];
            }
        } catch (Throwable $e) {
            // sem tabela de humor ainda: segue com o resumo vazio
        }

        responder(200, [
            'usuario'  => $u,
            'is_admin' => !empty($_SESSION['is_admin']),
            'humor'    => $humor,
        ]);
    }

    // ---------- POST ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $corpo = json_decode(file_get_contents('php://input'), true);
        if (!is_array($corpo)) {
            responder(400, ['erro' => 'Requisição inválida.']);
        }
        $acao = $corpo['acao'] ?? '';

        // --- atualizar dados ---
        if ($acao === 'dados') {
            $nome     = mb_substr(trim((string) ($corpo['nome'] ?? '')), 0, 100);
            $email    = trim((string) ($corpo['email'] ?? ''));
            $telefone = trim((string) ($corpo['telefone'] ?? ''));

            if ($nome === '') {
                responder(400, ['erro' => 'Informe seu nome.']);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                responder(400, ['erro' => 'Informe um e-mail válido.']);
            }
            if ($telefone !== '' && !preg_match('/^[0-9()+\-\s]{8,20}$/', $telefone)) {
                responder(400, ['erro' => 'Telefone inválido. Use apenas números, espaços, parênteses, + ou -.']);
            }

            $st = $pdo->prepare('UPDATE usuario SET nome = :n, email = :e, telefone = :t WHERE id_usuario = :id');
            $st->execute([
                ':n'  => $nome,
                ':e'  => $email,
                ':t'  => $telefone !== '' ? $telefone : null,
                ':id' => $id,
            ]);
            $_SESSION['nome'] = $nome; // o resto do site passa a mostrar o nome novo
            responder(200, ['ok' => true, 'usuario' => buscar_usuario($pdo, $id)]);
        }

        // --- trocar senha ---
        if ($acao === 'senha') {
            $atual = (string) ($corpo['senha_atual'] ?? '');
            $nova  = (string) ($corpo['nova_senha'] ?? '');

            if (strlen($nova) < 6) {
                responder(400, ['erro' => 'A nova senha precisa ter 6 ou mais caracteres.']);
            }
            $st = $pdo->prepare('SELECT senha FROM usuario WHERE id_usuario = :id');
            $st->execute([':id' => $id]);
            $hash = $st->fetchColumn();
            if (!$hash || !password_verify($atual, $hash)) {
                responder(403, ['erro' => 'A senha atual está incorreta.']);
            }
            if (password_verify($nova, $hash)) {
                responder(400, ['erro' => 'A nova senha precisa ser diferente da atual.']);
            }

            $st = $pdo->prepare('UPDATE usuario SET senha = :s WHERE id_usuario = :id');
            $st->execute([':s' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $id]);
            session_regenerate_id(true);
            responder(200, ['ok' => true]);
        }

        responder(400, ['erro' => 'Ação inválida.']);
    }

    responder(405, ['erro' => 'Método não permitido.']);
} catch (PDOException $e) {
    if ($e->getCode() === '23505') { // e-mail já usado por outra conta (UNIQUE)
        responder(409, ['erro' => 'Este e-mail já está em uso por outra conta.']);
    }
    error_log('perfil.php: ' . $e->getMessage());
    responder(500, ['erro' => 'Erro no servidor. Tente novamente.']);
}
