<!doctype html>
<html>
 <head>
  <title>Drop Am See 2023</title>
  
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
   
  
  <meta name="viewport" content="width=480" />
 </head>
 <body>

<?php
SESSION_START();
//$HelferID = $_SESSION["HelferId"];

require_once 'konfiguration.php';
require 'SQL.php';

$db_link = mysqli_connect(
    MYSQL_HOST,
    MYSQL_BENUTZER,
    MYSQL_KENNWORT,
    MYSQL_DATENBANK
);

if (isset($_POST['sent'])) {
    $messages = [];

    $HelferName = $_POST['helfer-name'];
    $HelferEmail = $_POST['helfer-email'];
    $HelferHandy = $_POST['helfer-handy'];
    $HelferLevel = $_POST['helfer-level'];
    $HelferPasswort = $_POST['helfer-passwort'];
    ;
    $HelferPasswort2 = $_POST['helfer-passwort2'];
    ;

    //echo $HelferName;
    //echo $HelferEmail;
    //echo $HelferHandy;

    // Eingaben überprüfen:

    //if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
    // $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
    //}

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
            //$insertID = mysql_insert_id();
            //echo "InserId = ".$insertID;

            // Erfolg vermelden und Skript beenden, damit Formular nicht erneut ausgegeben wird
            echo "Helfer mit Emailadresse " . $HelferEmail . " Angelegt.<br><br>";
            $HelferName = '';
            $HelferEmail = '';
            $HelferHandy = '';
            $HelferPasswort = '';
            $HelferPasswort2 = '';

            //die('<div class="Helfer wurde angelegt.</div>');
        } else {
            echo "Helfer konnte nicht Angelegt werden, möglichweise exisistiert die Emailadresse " . $HelferEmail . " bereits.<br><br>";
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


?>

<p>Hier k&ouml;nnen Sie sich selbst einen Account als Helfer anlegen.</p>
<form method="post">

  <table id="customers">
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
              <tr><td>Helferlevel </td></tr>
           <tr><td>    
              <select name="helfer-level">
<?php
$db_erg = HelferLevel($db_link);
$selected = "";
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferLevel = $zeile['HelferLevel'];
    $HelferLevelBeschreibung = $zeile['HelferLevelBeschreibung'];
    if ($HelferLevel == 1) {
        $selected = " selected " ;
    };
    echo "<option value='$HelferLevel' $selected>$HelferLevelBeschreibung</option>";
    $selected = "";
}
?>
              </select>
              </td>
            </tr>
          </table>

    <br>
    <button name="sent" value="1">Helfer Anlegen</button>
    
  
</form>


  
<?php

mysqli_free_result($db_erg);
?>
  
 </body>
</html>
