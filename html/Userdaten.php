<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

?>
<!doctype html>
<html>
 <head>
  <title><?php echo EVENTNAME ?> Helferdaten ändern</title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>  
  <meta name="viewport" content="width=480" />
 </head>
 <body>

<?php


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];


/// Helferdaten Aendern
////////////////////////////////////////////////////////

if (isset($_POST['change'])) {
    $messages = [];


    // Eingaben überprüfen:


    if (strlen($_POST['helfer-newpasswort']) < 8 and $_POST['helfer-newpasswort'] != "") {
        $messages[] = 'Neues Passwort zu kurz';
    }
    //if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
    //  $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
    //}
    $HelferName = $_POST['helfer-name'];
    $HelferEmail = $_POST['helfer-email'];
    $HelferHandy = $_POST['helfer-handy'];
    $HelferNewPasswort  = $_POST['helfer-newpasswort'];
    if (empty($messages)) {
        // Helferdaten Ändern
        HelferdatenAendern($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID, $HelferLevel);
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}



////////////////////////////////////////////////////////////////
// Helferdate holen
///////////////////////////////////////////////////////////////

$db_erg = Helferdaten($db_link, $HelferID);


while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferEmail = $zeile['Email'];
    $HelferHandy = $zeile['Handy'];
}

/// Logout
////////////////////////////////////////////////////////
if (isset($_POST['logout'])) {
    unset($_SESSION["HelferID"]);
    //$_POST['login'] = 1;
}

/// Login
////////////////////////////////////////////////////////
if (isset($_POST['login'])) {
    $messages = [];
    // Eingaben überprüfen:
    //if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
    //  $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
    //}

    $HelferName = $_POST['helfer-name'];
    $HelferEmail = $_POST['helfer-email'];
    $HelferPasswort = $_POST['helfer-passwort'];

    if (empty($messages)) {
        HelferLogin($db_link, $HelferEmail, $HelferPasswort, 0);
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



    
          <table class="commontable">
            <tr>
                <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button> Helferdaten <?php echo EVENTNAME; ?></th>
            </tr>
<form method="post">
            <tr>     
              <td>Name</td></tr><tr><td>
              <input name="helfer-name" type="text" value="<?php echo htmlspecialchars($HelferName ?? '')?>" required>
              </td>
            </tr>
            <tr>
              <td>Email</td></tr><tr><td>     
              <input name="helfer-email" type="email " value="<?php echo htmlspecialchars($HelferEmail ?? '')?>" required>
              </td>
            </tr>
            <tr>
              <td>Handy</td></tr><tr><td>     
              <input name="helfer-handy" type="tel" value="<?php echo htmlspecialchars($HelferHandy ?? '')?>" >
              </td>
            </tr>
            <tr>
              <td>Altes Helfer Passwort</td></tr><tr><td>     
              <input name="helfer-passwort" type="password" value="<?php echo htmlspecialchars($HelferPasswort ?? '')?>" >
              </td>
            </tr>
            <tr>
              <td>Neues Helfer Passwort</td></tr><tr><td>     
              <input name="helfer-newpasswort" type="text" value="<?php echo htmlspecialchars($HelferPasswort ?? '')?>" >
              </td>
            </tr>
           
          </table>

          <p><button name="change" style="width:150px !important" value="1">&Auml;ndern</button></p>
 </form> 
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button>
 </body>
</html>
