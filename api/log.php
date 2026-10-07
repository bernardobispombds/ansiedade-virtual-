<?php
// Registro das tentativas de login (aparece no painel do administrador).

function dispositivo(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $tipo = preg_match('/Mobile|Android|iPhone|iPad/i', $ua) ? 'Celular' : 'Computador';
    if (strpos($ua, 'Edg/') !== false)                          $nav = 'Edge';
    elseif (strpos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) $nav = 'Opera';
    elseif (strpos($ua, 'Firefox/') !== false)                  $nav = 'Firefox';
    elseif (strpos($ua, 'Chrome/') !== false)                   $nav = 'Chrome';
    elseif (strpos($ua, 'Safari/') !== false)                   $nav = 'Safari';
    else                                                        $nav = 'Outro';
    return $tipo . ' · ' . $nav;
}

function registrar_login(PDO $pdo, $idUsuario, string $email, bool $sucesso, ?string $motivo = null): void {
    try {
        $pdo->prepare(
            'INSERT INTO login_log (id_usuario, email_tentado, sucesso, motivo, dispositivo)
             VALUES (:u, :e, :s, :m, :d)'
        )->execute([
            ':u' => $idUsuario,
            ':e' => mb_substr($email, 0, 150),
            ':s' => $sucesso ? 't' : 'f',
            ':m' => $motivo,
            ':d' => dispositivo(),
        ]);
    } catch (PDOException $e) {
        // se o registro falhar, o login continua funcionando
    }
}
