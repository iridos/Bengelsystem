<?php

namespace Bengelsystem;

// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();

?>
<!doctype html>
<html>
<head></head>
<body> 

<?php

if (isset($_GET['token'])) {
    // E-Mail-Adresse des Nutzers
    $encrypted_data = $_GET['token'];
    $encrypted_iv = $_GET['iv'];
    echo "token: $encrypted_data<br> \n";
    echo "iv:    $encrypted_iv<br> \n";

    // Erstellen eines Cipher-Objekts für die Verschlüsselung
    $cipher_method = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher_method);
    $iv = base64_decode($encrypted_iv);
    $cipher_options = OPENSSL_RAW_DATA;
    $cipher_key = openssl_digest($secret_key, 'SHA256', true);

    // Entschlüsseln des verschlüsselten Textes
    $decoded_cipher_text = base64_decode($encrypted_data);
    $iv = base64_decode($encrypted_iv);
    $iv = substr($decoded_cipher_text, 0, $iv_length);
    $cipher_text = substr($decoded_cipher_text, $iv_length);
    $decrypted_email = openssl_decrypt($cipher_text, $cipher_method, $cipher_key, $cipher_options, $iv);

    // Verifikationsstring überprüfen und entfernen
    $verification_length = strlen($secret_verification);
    if (substr($decrypted_email, -$verification_length) == $secret_verification) {
        $decrypted_email = substr($decrypted_email, 0, -$verification_length);
    }

    // Ausgabe der entschlüsselten E-Mail-Adresse
    echo "E-Mail-Adresse: ", $decrypted_email, "<br>\n";
} else {
    echo "Kein g&uuml;ltiges Token!<br>";
}

?>
</body></html>
