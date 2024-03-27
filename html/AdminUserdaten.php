<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;" name="AliasHelferID" id="AliasHelferID" onchange="submit()">';
    $db_erg = HelferListe($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($AliasHelferID != $zeile['HelferID']) {
                echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
        } else {
                echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
        }
    }
    echo '</select></form>';
}

if (isset($_POST['AliasHelferID'])) {
    $AliasHelferID = $_POST['AliasHelferID'];
} elseif (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
} else {
    HelferAuswahlButton($db_link, $AliasHelferID);
    exit;
}
HelferAuswahlButton($db_link, $AliasHelferID);

$_SESSION["AliasHelferID"] = $AliasHelferID;
$AdminID = $_SESSION["AdminID"];

//debug output: echo "Admin=$AdminID<br>"; echo "Helfer=$HelferID<br>"; echo "Alias=$AliasHelferID<br>";


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
    $HelferLevel = $_POST['helfer-level'];
    $HelferNewPasswort  = $_POST['helfer-newpasswort'];
    if ($_POST['IsAdmin']) {
        $HelferIsAdmin = 1;
        //echo "is Admin<br>";
    } else {
        $HelferIsAdmin = 0;
    }
    if (empty($messages)) {
        // Helferdaten Ändern
        HelferdatenAendern($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $AliasHelferID, $HelferLevel, $HelferIsAdmin, $HelferID);
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}


///////////////////////////////////////////////////////////////
// Helfer Loeschen
///////////////////////////////////////////////////////////////

if (isset($_POST['del'])) {
    HelferLoeschen($db_link, $AliasHelferID, $AdminID);
}

////////////////////////////////////////////////////////////////
// Helferdate holen
///////////////////////////////////////////////////////////////

$db_erg = Helferdaten($db_link, $AliasHelferID);


while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferEmail = $zeile['Email'];
    $HelferHandy = $zeile['Handy'];
    $HelferIsAdmin = $zeile['Admin'];
    $HelferLevel = $zeile['HelferLevel'];
}

?>



    
          <table class="commontable">
            <tr>
                <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'AdminHelferUebersicht.php';"><b>&larrhk;</b></button> Helferdaten</th>
<?php echo "<b>" . EVENTNAME . "</b>"; ?>
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
              <td>Admin  Passwort</td></tr><tr><td>     
              <input name="helfer-passwort" type="password" value="<?php echo htmlspecialchars($HelferPasswort ?? '')?>" >
              </td>
            </tr>
            <tr>
              <td>Neues Helfer Passwort</td></tr><tr><td>     
              <input name="helfer-newpasswort" type="text" value="<?php echo htmlspecialchars($HelferPasswort ?? '')?>" >
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
        
      <table class="commontable">
      <col style="width:20px">
      <tr>
      <td><input type="checkbox" name="IsAdmin" value=1 align="right" <?php if ($HelferIsAdmin == 1) {
            echo" checked";
                                                                      }?>></td>
        <td>ist Admin</td>
      </tr>
      </table>
          <p><button name="change" style="width:150px !important" value="1">&Auml;ndern</button></p>
          <p><button name="del" style="width:150px !important" value="1">Helfer L&ouml;schen</button></p> 
 </form> 
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>
 </body>
</html>
