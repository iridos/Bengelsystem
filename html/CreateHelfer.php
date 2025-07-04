<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
// Das hier wird über eine Art Token den Zugriff auf CreateHelfer erlauben
// Jedes Token ist mit einem Helferlevel verknüpft, in dem dann Helfer angelegt
$linkcode = $_GET['linkcode'] ?? '';
if (empty($linkcode)) {
    die("<br>Fehlender Einladungscode.<br>");
}
$HelferLevelDaten = HelferLevelAusEinladung($db_link, $linkcode);
if ($HelferLevelDaten === false) {
    die("<br>Ungültiger Einladungscode.");
}
$HelferLevel = $HelferLevelDaten['HelferLevel'];
$HelferLevelBeschreibung = $HelferLevelDaten['HelferLevelBeschreibung'];
?>
<!doctype html>
<html>
<head>
  <title><?php echo EVENTNAME ?></title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>


  <meta name="viewport" content="width=480" />
</head>
<body>

<?php

if (isset($_POST['sent'])) {
    $messages = [];

    $HelferName = $_POST['helfer-name'];
    $HelferEmail = $_POST['helfer-email'];
    $HelferHandy = $_POST['helfer-handy'];
    $HelferPasswort = $_POST['helfer-passwort'];
    $HelferPasswort2 = $_POST['helfer-passwort2'];

    if (!filter_var($HelferEmail, FILTER_VALIDATE_EMAIL)) {
        $messages[] = 'Bitte prüfen Sie die eingegebene E-Mail-Adresse.';
    }

    //if(!filter_var($HelferHandy, FILTER_VALIDATE_INT)) {
    //  $messages[] = 'Bitte prüfen Sie die eingegebene Handynummer';
    //}

    if ($HelferPasswort != $HelferPasswort2) {
        $messages[] = 'Passwörter stimmen nicht überein';
        $HelferPasswort = "";
        $HelferPasswort2 = "";
    }
    if (strlen($HelferPasswort) < 8) {
        $messages[] = 'Passwörter zu kurz';
        $HelferPasswort = "";
        $HelferPasswort2 = "";
    }


    if (empty($messages)) {
        $db_erg = CreateHelfer($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel);
        if ($db_erg) {

            // Erfolg vermelden und Skript beenden, damit Formular nicht erneut ausgegeben wird
            echo "Account mit Emailadresse " . $HelferEmail . " Angelegt.<br><br>";
            $HelferName = '';
            $HelferEmail = '';
            $HelferHandy = '';
            $HelferPasswort = '';
            $HelferPasswort2 = '';

        } else {
            echo "Account konnte nicht Angelegt werden, möglichweise exisistiert die Emailadresse " . $HelferEmail . " bereits.<br><br>";
        }
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}


echo "<p>Hier k&ouml;nnen Sie sich selbst einen Account im Level: $HelferLevelBeschreibung ($HelferLevel)  anlegen.<br>";
echo 'Danach zum <a href="index.php">Login</a></p>';
?>

<form method="post">

  <table class="commontable">
            <tr>
                <th>Helferdaten</th>
            </tr>
            <tr>
              <td>Name</td>
        </tr>
        <tr><td>
              <input name="helfer-name" type="text" value="<?php echo htmlspecialchars($HelferName ?? '')?>" required>
            </td></tr>
            <tr>
          <td>Email</td>
           </tr>
           <tr><td>
              <input name="helfer-email" type="email " value="<?php echo htmlspecialchars($HelferEmail ?? '')?>" required>
              </td></tr>
            <tr>
          <td>Handy</td>
           </tr>
           <tr><td>
              <input name="helfer-handy" type="tel" value="<?php echo htmlspecialchars($HelferHandy ?? '')?>" >
              </td>
            </tr>
            <tr>
              <td>Passwort</td></tr>
              <tr><td>
              <input name="helfer-passwort" type="password" value="<?php echo htmlspecialchars($HelferPasswort ?? '')?>" required>
              </td>
            </tr>
             <tr><td>Passwort wiederholen </td></tr>
           <tr><td>
              <input name="helfer-passwort2" type="password" value="<?php echo htmlspecialchars($HelferPasswort2 ?? '')?>" required>
              </td>
            </tr>
          </table>
    <br>
    <button name="sent" value="1">Account Anlegen</button>


</form>
</body>
</html>
