<?php
/**
 * config/email.php
 *
 * Configurações de envio de e-mail via Gmail + PHPMailer.
 *
 * IMPORTANTE: Preencha as credenciais abaixo antes de usar.
 * Nunca suba este arquivo para o GitHub com dados reais!
 * Adicione config/email.php ao seu .gitignore.
 */

return [
    // ── Credenciais Gmail ─────────────────────────────────────────────────────
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'lucas.meirelles0411@gmail.com',       // ← seu e-mail Gmail
    'password' => 'mbpd nxct dwdo foxy',      // ← senha de app de 16 caracteres
    'from'     => 'lucas.meirelles0411@gmail.com',       // ← remetente (mesmo e-mail)
    'from_name'=> 'Capitão Muzzarela',

    // ── URL base do sistema (usada nos links do e-mail) ───────────────────────
    // Em produção, substitua pelo domínio real. Ex: 'https://capitaomuzzarela.com.br'
    'app_url'  => 'http://localhost/CapitaoMuzzarela',

    // ── Expiração do token de redefinição (em minutos) ────────────────────────
    'token_expiracao_minutos' => 60,
];
