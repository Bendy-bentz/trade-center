<?php
/**
 * Configuration Email SMTP - Gmail
 * TradecenterEntreprise
 */

// Configuration SMTP Gmail'SMTP_PASS', 'wdwxhdwopkiyibee
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'mompremierrubendy08@gmail.com'); // Remplacez par votre email Gmail
define('SMTP_PASSWORD', 'wdwxhdwopkiyibee'); // Mot de passe d'application Gmail
define('SMTP_FROM_EMAIL', 'votre-email@gmail.com');
define('SMTP_FROM_NAME', 'TradeCenter Location');

// URL de base pour les liens dans les emails
define('EMAIL_BASE_URL', BASE_URL);

/**
 * Fonction d'envoi d'email via SMTP
 * Note: Cette fonction utilise la fonction mail() de PHP avec des headers configurés
 * Pour une production réelle, utilisez PHPMailer ou SwiftMailer
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Headers pour l'email
    $headers = [
        'From' => SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Reply-To' => SMTP_FROM_EMAIL,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
    ];
    
    if ($isHTML) {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
    } else {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
    }
    
    // Convertir les headers en chaîne
    $headerStr = '';
    foreach ($headers as $key => $value) {
        $headerStr .= $key . ': ' . $value . "\r\n";
    }
    
    // Encodage du sujet pour les caractères spéciaux
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // Envoi de l'email
    return mail($to, $encodedSubject, $body, $headerStr);
}

/**
 * Fonction d'envoi d'email via PHPMailer (recommandé pour production)
 * Nécessite: composer require phpmailer/phpmailer
 */
function sendEmailPHPMailer($to, $subject, $body, $toName = '') {
    // Vérifier si PHPMailer est disponible
    if (!file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        // Fallback vers la fonction mail() simple
        return sendEmail($to, $subject, $body);
    }
    
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Expéditeur
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Destinataire
        $mail->addAddress($to, $toName);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Erreur envoi email: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un template d'email professionnel
 */
function getEmailTemplate($title, $content, $buttonText = null, $buttonUrl = null) {
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f5;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; padding: 20px;">
            <!-- Header -->
            <tr>
                <td style="background: linear-gradient(135deg, #f97316, #ea580c); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                    <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">
                        🚗 TradeCenter
                    </h1>
                    <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;">
                        Location de véhicules premium
                    </p>
                </td>
            </tr>
            
            <!-- Content -->
            <tr>
                <td style="background: white; padding: 40px 30px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb;">
                    ' . $content;
    
    if ($buttonText && $buttonUrl) {
        $html .= '
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . htmlspecialchars($buttonUrl) . '" style="display: inline-block; background: linear-gradient(135deg, #f97316, #ea580c); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                            ' . htmlspecialchars($buttonText) . '
                        </a>
                    </div>';
    }
    
    $html .= '
                </td>
            </tr>
            
            <!-- Footer -->
            <tr>
                <td style="background: #1f2937; padding: 30px; text-align: center; border-radius: 0 0 12px 12px;">
                    <p style="color: #9ca3af; margin: 0 0 15px 0; font-size: 13px;">
                        © ' . date('Y') . ' TradeCenter Location. Tous droits réservés.
                    </p>
                    <p style="color: #6b7280; margin: 0; font-size: 12px;">
                        Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                    </p>
                </td>
            </tr>
        </table>
    </body>
    </html>';
    
    return $html;
}
?>
