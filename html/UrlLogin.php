<?php

require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
require '_crypt.php';


$head = "<!doctype html>
<head></head> <body> ";
$foot = "</body></html>";
$db_link = ConnectDB();

// ist jetzt in _crypt.php, aber aus der anderen Datei, deshalb hier erst
// nur auskommentiert
// function Entschluessle($encrypted_data,$secret_verification,$secret_key){
//
// // Erstellen eines Cipher-Objekts für die Verschlüsselung
// $cipher_method = "AES-256-CBC";
// $iv_length = openssl_cipher_iv_length($cipher_method);
// $cipher_options = OPENSSL_RAW_DATA;
// $cipher_key = openssl_digest($secret_key, 'SHA256', true);
//
// // Entschlüsseln des verschlüsselten Textes
// // zuerst base64 entfernen. *kein* urldecode, das passiert durch get/post automatisch
// $decoded_cipher_text = base64_decode($encrypted_data);
// $iv = substr($decoded_cipher_text, 0, $iv_length);
// $cipher_text = substr($decoded_cipher_text, $iv_length);
// $decrypted_email = openssl_decrypt($cipher_text, $cipher_method, $cipher_key, $cipher_options, $iv);
//
// // Verifikationsstring überprüfen und entfernen
// $verification_length = strlen($secret_verification);
// if(substr($decrypted_email, -$verification_length) == $secret_verification) {
// $decrypted_email = substr($decrypted_email, 0, -$verification_length);
// }
// return($decrypted_email);
// }

if (isset($_GET['token']) || isset($_POST['token'])) {
    // E-Mail-Adresse des Nutzers
    $token = $_GET['token'];
    //$email = Entschluessle($token,$secret_verification,$secret_key);
    $decrypted_data = decode_string($secret_key, $token, $secret_verification);
    //error_log("decrytped_data: ". $decrypted_data);
    $email = $decrypted_data['email'];
    $helfer_level = $decrypted_data['level'];
    $success = $decrypted_data['success'];
    //error_log("email: ".$email.",level: ".$helfer_level.",success: ".$success);
} else {
    echo "$head Kein Token angegeben!<br> $foot";
    exit;
}
if ($success != 1) {
    //    if ( $db_erg ) {
    //    echo ' <meta http-equiv="Refresh" content="0; URL=index.php" />';
    //    } else {
      echo "$head Kein g&uuml;ltiges Token!<br>$foot";
      exit;
}
//}
//TODO: Variablendoppelung aufloesen und oben gleich einmal setzen
if ($success == 1 && $email != "") {
    // Ausgabe der entschlüsselten E-Mail-Adresse
    //echo "E-Mail-Adresse: ", $email, "<br>\n";
    //echo "Passwort: ",$token,"<br>\n";
    $HelferName = $email;
    $HelferEmail = $email;
    $HelferLevel = $helfer_level;
    $HelferHandy = "";
    $HelferPasswort = $token;

    if (!filter_var($HelferEmail, FILTER_VALIDATE_EMAIL)) {
        echo  'Problem mit E-Mail-Adresse.';
        exit;
    }
    error_log("2email: " . $HelferEmail . ",level: " . $helfer_level . ",success: " . $success);
    // Helfer Anlegen, wenn er nicht existiert
    if (! HelferIstVorhanden($db_link, $HelferEmail)) {
        error_log("Helfer " . $HelferEmail . " nicht vorhanden, lege an");
        error_log("CreateHelfer(db_link,$HelferName,$HelferEmail, $HelferHandy,$HelferPasswort,$HelferLevel);");
        $db_erg = CreateHelfer($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel);
    }
    // Login-Versuch, entweder direkt nach Anlegen oder wenn existiert hat
    // Login und auf Haupt-Seite gehen
    HelferLogin($db_link, $HelferEmail, $HelferPasswort, 0);
    echo '<html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
