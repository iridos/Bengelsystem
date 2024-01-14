<?php
function encode_string($key, $email, $level, $verification) {
    // String verification anfuegen am Ende, um abgeschnittene URLs zu verhindern
    $token_content = $email . '|' . $level . '|' . $verification;

    // Erstellen eines Cipher-Objekts für die Verschlüsselung
    $cipher_method = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher_method);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $cipher_options = OPENSSL_RAW_DATA;
    // sha256 hash des passworts, damit das Geheimnis lang genug ist
    $cipher_key = openssl_digest($key, 'SHA256', true);
    $cipher_text = openssl_encrypt($token_content, $cipher_method, $cipher_key, $cipher_options, $iv);
    
    if(isset($debug)) {
        echo "<br>encode: cipher:".chunk_split(bin2hex($cipher_text),4,' ')." <br>iv:".chunk_split(bin2hex($iv),4,' ')." <br>iv_length:$iv_length<br>cipher_key ".chunk_split(bin2hex($cipher_key),4,' ')."<br>";
    }

    $cipher_text = $iv . $cipher_text;
    // verschlüsselter Textes als base64 und dann noch mit urlencode, weil base64 zB + enthaelt
    $encrypted_data = urlencode(base64_encode($cipher_text));

    return $encrypted_data;
}

function decode_string($key, $encrypted_data, $verification) {
    // Entschlüsseln des verschuesselten Textes
    // erst base64 entfernen - urldecode muss wenn noetig vorher angewendet werden
    $decoded_cipher_text = base64_decode($encrypted_data);
    // iv vom Anfang abtrennen
    $cipher_method = "AES-256-CBC";
    $iv_length = openssl_cipher_iv_length($cipher_method);
    $iv = substr($decoded_cipher_text, 0, $iv_length);
    $cipher_text = substr($decoded_cipher_text, $iv_length);
    $cipher_options = OPENSSL_RAW_DATA;
    $cipher_key = openssl_digest($key, 'SHA256', true);

    if(isset($debug)){
       echo "<br>decode: cipher:".chunk_split(bin2hex($cipher_text),4,' ').  " <br>iv:".chunk_split(bin2hex($iv),4,' ').  " <br>iv_length:$iv_length<br>cipher_key ".  chunk_split(bin2hex($cipher_key),4,' ');
    }

    $decrypted_data = openssl_decrypt($cipher_text, $cipher_method, $cipher_key, $cipher_options, $iv);

    if(isset($debug)){echo "<br> decrypted_data " . $decrypted_data." end<br>";}
    $verification_length = strlen($verification);

    if(substr($decrypted_data, -$verification_length) == $verification) {
        list($email, $level, $verification_code) = explode('|', $decrypted_data);
        return array(
            'email' => $email,
            'level' => $level,
            'success' => 1
        );
    } else {
        error_log("Verschlüsseltes Token enthielt den Verifikationscode nicht: $decrypted_data<br>\n");
         return array(
            'success' => 0
        );
    }
}
?>
