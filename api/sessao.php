<?php
// Inicia a sessão e oferece as funções que protegem as páginas.
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
          || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Use no topo das páginas que exigem login.
function exigir_login() {
    if (empty($_SESSION['id_usuario'])) {
        header('Location: cadastro.html');
        exit;
    }
}

// Use no topo das páginas que só o administrador pode ver.
function exigir_admin() {
    if (empty($_SESSION['id_usuario']) || empty($_SESSION['is_admin'])) {
        header('Location: cadastro.html');
        exit;
    }
}

// O PostgreSQL pode devolver booleanos como true/false ou 't'/'f'.
function pg_bool($v) {
    return $v === true || $v === 't' || $v === 1 || $v === '1';
}
