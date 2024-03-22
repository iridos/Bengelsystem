<?php

namespace Bengelsystem;

// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
require '_crypt.php';

if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}

function validate_email($email)
{
    // Prüfen, ob die Email-Adresse syntaktisch gültig ist
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Prüfen, ob das @-Symbol enthalten ist
    if (strpos($email, '@') === false) {
        return false;
    }

    // Prüfen, ob der Teil nach dem @-Symbol eine gültige Domain enthält
    $parts = explode('@', $email);
    $domain = $parts[1];
    if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
        return false;
    }

    // Alle Checks bestanden
    return true;
}


// wird immer auf 2 (Teilnehmer) gesetzt und wird auch nicht gespeichert, wenn anders gesetzt
// andere level muessen jedes Mal gesetzt werden
$level = 2;

if (isset($_POST['helfer-status'])) {
    $level = $_POST['helfer-status'];
}

// nicht sicher, wo so ein default-text herkommen sollte
// oder ob es tatsächlich einen geben sollte
$email_subject = EVENTNAME . " beginnt bald!";
if (isset($_POST['email-subject'])) {
    $email_subject = $_POST['email-subject'];
}

$email_cc = "drophelfer@gmail.com";
if (isset($_POST['email-cc'])) {
    $email_cc = $_POST['email-cc'];
}

$email_text = "
Lieber Teilnehmer,
trage dich bitte mit folgendem Link für eine Stunde pro Person als Helfer bei uns ein.Wir verschicken eine Mail pro Emailaddresse, also bitte für alle, die mit dieser Emailaddresse angemeldet sind. 

Danke für deine Mithilfe!

XXtokenXX

Du kannst dich auch später wieder über den Link einloggen und die Schicht ändern. 

Viele Grüße,
dein " . EVENTNAME . " Team
";

if (isset($_POST['email-text'])) {
    $email_text = $_POST['email-text'];
}

$sendmail = 0;
if (isset($_POST['sendmail'])) {
    $sendmail = $_POST['sendmail'];
}


?>
<!doctype html>
 <head>
  <title>Helfer <?php echo EVENTNAME ?>: Email Tokens generieren</title>
  
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
 </head>
 <body>

<?php

?>

<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>
<div style="width: 100%;">
<p>
<h2> Emails mit Login-Link zur HelferDB generieren </h2>
Generiert  Tokens (bzw URLs mit Token) aus einer Liste von Email-Addressen. <br>
Bei Klick auf den generierten Link wird sofort ein Account zur Email angelegt. <br>
Als Passwort wird das Token gesetzt. Man kann sich mit dem Link danach wieder in den selben Account einloggen.
</p>
<p>
!! Emails werden erst verschickt, wenn die Checkbox unten angeklickt ist
</p>

<form method="POST" action="EmailZuToken.php">
<p>
<label for="helfer-status">Status des Accounts, den der Link erstellt:</label>
<select style="width:260px" id="helfer-status" name="helfer-status">
<!-- TODO: aus DB abfragen -->
  <option value="2"<?php if ($level == 2) {
        echo "selected";
                   }?>>Teilnehmer</option>
  <option value="1" <?php if ($level == 1) {
        echo "selected";
                    }?>>Dauerhelfer</option>
</select>
</p><p>
  Subject der Email: <br>
  <input id="email-subject" name="email-subject" type="textbox" value="<?php echo htmlspecialchars($email_subject ?? '');?>">
</p><p>
  CC (Kopie) der Email geht an: <br>
  <input id="email-cc" name="email-cc" type="textbox" value="drophelfer@gmail.com">
</p><p>
  Emailtext (XXtokenXX an die Stelle schreiben, an der der Link im Emailtext stehen soll): 
</p><p>
  <textarea id="email-text" name="email-text" rows="20" cols="80">
<?php echo htmlspecialchars($email_text ?? '');?>
</textarea>
</p>
<p>
Liste von Emails, an die Anschreiben verschickt wird (Eine Email pro Zeile, nur die Email xxx@yyy.zz, keine Leerzeichen):<br>
<textarea id="helfer-email-liste" name="helfer-email-liste" rows="20" cols="80"></textarea> <br>
</p>
<div>
<input type="checkbox" id="sendmail" name="sendmail" value="1" style="align:left;width:40px;!important">
<label for="sendmail">Emails verschicken</label>
</div>
<br>
<button name="email-liste" value="1">Token generieren</button>
</form>
</p>
<?php

if (isset($_POST['email-liste'])) {
    // TODO: check if email-text contains tokentext to substitute
    // TODO: check if subject is set
    // get email addresses from textarea
    $email_list = $_POST['helfer-email-liste'];
    // Aufteilen der Textbox in einzelne Emails

    //mit explode: jede Email in einer Zeile, andere Leerzeichen koennen zur Email werden
    // $emails = explode("\n", $email_list);

    //preg_split, um bei allen Leerzeichen zu trennen
    $email_array = preg_split('/\s+/', $email_list); // Trennzeichen: 1 oder mehr Whitespace-Zeichen
    foreach ($email_array as $email) {
        $email = trim($email);
        $encrypted_data = encode_string($secret_key, $email, $level, $secret_verification);
        $token_url = "$urlprefix?token=$encrypted_data";
        // Ausgabe des verschluesselten Textes in der URL
        $decrypted_data = decode_string($secret_key, urldecode($encrypted_data), $secret_verification);
        $email_subst_text = str_replace('XXtokenXX', $token_url, $email_text);
        if ($sendmail != 1) {
            // keine Emails verschicken, wir gebeben die Inhalte unten als Text aus
            echo "Verschicken nicht ausgew&auml;hlt. Zeige Emails an:<br>";
            echo $sendmail . "<br>";
            echo "=======================================<br>";
            echo "To: " . $decrypted_data['email'] . " (level: " . $decrypted_data['level'] . "):<br>";
            echo "CC: " . $email_cc . "<br>";
            echo "<pre>" . $email_subst_text . "</pre><br>";
            echo "$email: <a href='$token_url'> $token_url</a> (check: " . $decrypted_data['email'] . ", lv: " . $decrypted_data['level'] . ")<br>";
        } else {
            // Email verschicken - send mail
            $to = $decrypted_data['email'];
            $from = "root";
            $headers = 'From: ' . $from . "\r\n";
            $headers .= 'CC: ' . $email_cc . "\r\n";
            if (mail($to, $email_subject, $email_subst_text, $headers)) {
                echo "Die E-Mail an $to wurde erfolgreich versendet.";
                error_log(date('Y-m-d H:i') . "(AdminID:$AdminID) Name: $HelferName (HelferID:$HelferID) hat Email mit Link verschickt an: $to mit CC an: $email_cc\n", 3, LOGFILE);
            } else {
                echo "Beim Versenden der E-Mail an $to ist ein Fehler aufgetreten.";
            }
        }
    }
}


?>

</body>
</html>


<!--Einzelne Email
<p>
<form method="GET">
    <input id="helfer-email" name="helfer-email" type="textbox" value="<?php echo htmlspecialchars($HelferEmail ?? '')?>">
    <button name="sent" value="1">Token generieren</button>
</form>
</p>
-->

<?php
//single email
// if(isset($_GET['helfer-email'])) {
// $email = $_GET['helfer-email'];
// echo "email: $email <br> \n";
// // encode
// $encrypted_data=encode_string($secret_key, $email,$secret_verification);
// $decrypted_data = decode_string($secret_key, $encrypted_data,$secret_verification);
// // Ausgabe des verschlüsselten Textesin der URL
// echo "$email: <a href='$linktext'> $urlprefix/$linktext</a>(check: $decrypted_data ) <br>";
// exit;
// }

?>
