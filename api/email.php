<?php
// Envio de e-mail pela API do Resend (HTTP). No Render, SMTP costuma ser bloqueado
// em planos gratuitos, então usamos uma API em vez de mail()/SMTP.
//
// Variáveis de ambiente (painel do Render > Environment):
//   RESEND_API_KEY    chave da API do Resend
//   EMAIL_REMETENTE   ex.: Ansiedade Virtual <nao-responda@seudominio.com.br>
function enviar_email(string $para, string $assunto, string $html): bool
{
    $chave = getenv('RESEND_API_KEY');
    $de    = getenv('EMAIL_REMETENTE');
    if (!$chave || !$de) {
        error_log('enviar_email: RESEND_API_KEY ou EMAIL_REMETENTE não configurados');
        return false;
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $chave,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'from'    => $de,
            'to'      => [$para],
            'subject' => $assunto,
            'html'    => $html,
        ]),
    ]);
    $resposta = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        error_log("enviar_email: Resend respondeu $status: $resposta");
        return false;
    }
    return true;
}
