<?php
// Inicia a sessão e oferece a função que protege as páginas.
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
