<?php
/**
 * app/services/EmailService.php
 *
 * Serviço de envio de e-mail usando PHPMailer.
 * Responsável apenas por enviar — não conhece regras de negócio.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/email.php';
    }

    /**
     * Envia o e-mail de redefinição de senha.
     *
     * @param  string $destinatario  E-mail do usuário
     * @param  string $nome          Nome do usuário
     * @param  string $token         Token de redefinição
     * @throws RuntimeException      Se o envio falhar
     */
    public function enviarRedefinicaoSenha(string $destinatario, string $nome, string $token): void
    {
        $link = $this->config['app_url']
            . '/public/api/?action=redefinir-senha&token='
            . urlencode($token);

        $expiracao = $this->config['token_expiracao_minutos'];

        $corpo = $this->templateRedefinicao($nome, $link, $expiracao);

        $this->enviar(
            $destinatario,
            $nome,
            'Redefinição de senha — Capitão Muzzarela',
            $corpo
        );
    }

    /**
     * Método genérico de envio.
     */
    private function enviar(string $para, string $paraNome, string $assunto, string $corpo): void
    {
        $mail = new PHPMailer(true);

        try {
            // Configuração do servidor
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            // Remetente e destinatário
            $mail->setFrom($this->config['from'], $this->config['from_name']);
            $mail->addAddress($para, $paraNome);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $corpo;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $corpo));

            $mail->send();
        } catch (Exception $e) {
            error_log('[EmailService] Falha ao enviar e-mail: ' . $mail->ErrorInfo);
            throw new RuntimeException('Não foi possível enviar o e-mail. Tente novamente mais tarde.');
        }
    }

    /**
     * Template HTML do e-mail de redefinição de senha.
     */
    private function templateRedefinicao(string $nome, string $link, int $expiracao): string
    {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0;padding:0;background-color:#f5f5f5;font-family:Poppins,Arial,sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f5f5f5;padding:40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='560' cellpadding='0' cellspacing='0' style='background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);'>

                            <!-- Header -->
                            <tr>
                                <td style='background-color:#c2282d;padding:32px;text-align:center;'>
                                    <h1 style='color:#ffffff;margin:0;font-size:22px;font-weight:700;letter-spacing:1px;'>
                                        🍕 CAPITÃO MUZZARELA
                                    </h1>
                                </td>
                            </tr>

                            <!-- Corpo -->
                            <tr>
                                <td style='padding:40px 32px;'>
                                    <p style='color:#1a1a1a;font-size:16px;margin:0 0 16px;'>Olá, <strong>" . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
                                    <p style='color:#4a4a4a;font-size:15px;line-height:1.6;margin:0 0 24px;'>
                                        Recebemos uma solicitação para redefinir a sua senha de acesso ao painel administrativo.
                                        Clique no botão abaixo para criar uma nova senha:
                                    </p>

                                    <!-- Botão -->
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='padding:8px 0 32px;'>
                                                <a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "'
                                                   style='background-color:#c2282d;color:#ffffff;text-decoration:none;
                                                          padding:14px 32px;border-radius:6px;font-size:15px;
                                                          font-weight:700;display:inline-block;'>
                                                    Redefinir minha senha
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='color:#4a4a4a;font-size:13px;line-height:1.6;margin:0 0 16px;'>
                                        ⏰ Este link expira em <strong>{$expiracao} minutos</strong>.
                                    </p>
                                    <p style='color:#4a4a4a;font-size:13px;line-height:1.6;margin:0;'>
                                        Se você não solicitou a redefinição de senha, ignore este e-mail.
                                        Sua senha permanecerá a mesma.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style='background-color:#f5f5f5;padding:20px 32px;text-align:center;border-top:1px solid #eeeeee;'>
                                    <p style='color:#999999;font-size:12px;margin:0;'>
                                        © " . date('Y') . " Capitão Muzzarela — Todos os direitos reservados.
                                    </p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}
