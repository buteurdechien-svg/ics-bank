<?php
/**
 * ICS Bank — envoi d'e-mails vers Mailpit (SMTP local, aucun envoi réel).
 * Sert à livrer les codes OTP de test et les notifications.
 */
declare(strict_types=1);

final class Mailer
{
    /** Envoie un message simple en SMTP brut vers Mailpit. */
    public static function send(string $to, string $subject, string $body): bool
    {
        $cfg  = config()['mail'];
        $from = $cfg['from'];

        $sock = @fsockopen($cfg['host'], $cfg['port'], $errno, $errstr, 4);
        if (!$sock) {
            error_log("Mailpit injoignable: $errstr ($errno)");
            return false;
        }

        $read = function () use ($sock): string { return (string)fgets($sock, 512); };
        $cmd  = function (string $c) use ($sock, $read): string { fwrite($sock, $c . "\r\n"); return $read(); };

        $read();                                  // bannière 220
        $cmd('EHLO ics-bank.local');
        $cmd('MAIL FROM:<' . $from . '>');
        $cmd('RCPT TO:<' . $to . '>');
        $cmd('DATA');

        $headers  = "From: ICS Bank <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= 'Subject: ' . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        fwrite($sock, $headers . "\r\n" . $body . "\r\n.\r\n");
        $read();

        $cmd('QUIT');
        fclose($sock);
        return true;
    }

    /** Envoie un code OTP de test. */
    public static function sendOtp(string $toPhone, string $code): bool
    {
        // Les OTP sont adressés à une boîte fictive, visible uniquement dans Mailpit.
        $to = 'otp+' . preg_replace('/\D/', '', $toPhone) . '@ics-bank.local';
        $body = "ICS Bank — code de verification (laboratoire)\n\n"
              . "Votre code a usage unique est : $code\n"
              . "Il expire dans 10 minutes.\n\n"
              . "Ceci est une banque fictive de formation. Aucun SMS reel n'est envoye.";
        return self::send($to, 'ICS Bank - Code de verification', $body);
    }
}
