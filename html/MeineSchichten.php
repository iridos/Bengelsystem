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
  <title>Meine Schichten <?php echo EVENTNAME ?></title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
 </head>
 <body>
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button> 
<?php echo "<b>" . EVENTNAME . "</b>"; ?>
<div style="width: 100%;">
<?php

/// Detailinformation zu ausgewaehlten Schicht Holen
////////////////////////////////////////////////////////
if (isset($_POST['CloseInfo'])) {
    unset($InfoMeineSchichtID);
    unset($InfoAlleSchichtID);
}
if (isset($_POST['InfoMeineSchichtID'])) {
    $InfoMeineSchichtID = $_POST['InfoMeineSchichtID'];
    unset($InfoAlleSchichtID);
    //echo "<b>". $SchichtID . "</b><br>";

    $zeile = DetailSchicht($InfoMeineSchichtID);

    $Was = $zeile['Was'];
    $Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
    $LeiterHandy =  $zeile['Handy'];
    $LeiterEmail =  $zeile['Email'];
    $Info = $zeile['Info'];
}


if (isset($_GET['InfoAlleSchichtID'])) {
    $InfoAlleSchichtID = $_GET['InfoAlleSchichtID'];
    unset($InfoMeineSchichtID);
    //echo "<b>". $SchichtID . "</b><br>";

    $zeile = DetailSchicht($InfoAlleSchichtID);

    $Was = $zeile['Was'];
    $Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
    $LeiterHandy =  $zeile['Handy'];
    $LeiterEmail =  $zeile['Email'];
    $Info = $zeile['Info'];



    // Beteiligte Helfer Holen
    $helfer = BeteiligteHelfer($InfoAlleSchichtID);


    $x = 0;

    foreach ($helfer as $zeile) {
        $MitHelferID[$x] = $zeile['HelferID'];
        $MitHelfer[$x] = $zeile['Name'];
        $MitHelferHandy[$x] = $zeile['Handy'];
        $x++;
    }
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
        HelferLogin($HelferEmail, $HelferPasswort, 0);
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}



if (!isset($_SESSION["HelferID"])) {
    ?>
<form method="post" action="#Info">

  <fieldset>
    <legend>Login</legend>
    
    <table border="0" style="border: 0px solid black;">
            <tr>     
              <td style="border: 0px solid black;">Email</td></tr><tr><td style="border: 0px solid black;">
              <input name="helfer-email" type="text" value="<?php echo htmlspecialchars($HelferEmail ?? '')?>" required>
              </td>
            <tr>
            <tr>     
              <td style="border: 0px solid black;">Passwort</td></tr><tr><td style="border: 0px solid black;">
              <input name="helfer-passwort" type="password" value="<?php echo htmlspecialchars($HelferHandy ?? '')?>" required>
              </td>
            <tr>
    </table>
    
    
  </fieldset>
  
  <p><button name="login" value="1">Login</button></p>


 </form> 
    <?php
    exit;
}



$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

if (isset($_POST['HelferID'])) {
    $HelferID = $_POST['HelferID'];
}
if (isset($_POST['ShowHelfer'])) {
    $HelferID = $_POST['HelperSearch'];
}

$_SESSION["HelferID"] = $HelferID;














/// Schicht Löschen
////////////////////////////////////////////////////////

if (isset($_POST['Del'])) {
    $messages = [];

    $EinzelSchichtID = $_POST['Del'];


    // Eingaben überprüfen:

    //if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
    //  $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
    //}



    if (empty($messages)) {
        $db_erg = HelferVonSchichtLoeschen($HelferID, $EinzelSchichtID);
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}



// Helferliste Anzeigen
////////////////////////////////////////////////////////

?>



<form method="post" action="MeineSchichten.php#Info">  
<?php



// Neu Schicht fuer Helfer Eintragen
///////////////////////////////////////////////////////////
if (isset($_POST['sent'])) {
    $messages = [];
    $SchichtId = $_POST['sent'];

    // Eingaben überprüfen:

    //  if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
    //    $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
    //  }


    if (empty($messages)) {
        // Helfer Schicht zuweisen
        $db_erg = HelferSchichtZuweisen($HelferID, $SchichtId);

        // Erfolg vermelden und Skript beenden, damit Formular nicht erneut ausgegeben wird
        $HelferName = '';
        $HelferEmail = '';
        $HelferHandy = '';
        //die('<div class="Helfer wurde angelegt.</div>');
    } else {
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
    }
}

/// Ausgabe auf Deutsch umstellen
/////////////////////////////////////////////////////////////////////////

    DatenbankAufDeutsch();

/// Alle Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////


$schichten = AlleSchichtenEinesHelfers($HelferID);

  $iSQLCount = count($schichten);
  //$iSQLCount = 3;

echo '<table class="commontable">';

  echo "<thead>";
  echo "<tr>";
  echo "<th colspan=4>"  . "Meine Schichten (" . $iSQLCount . " Schichten)  - " . EVENTNAME . "</th>";
  echo "</tr><tr>";
  echo "<th>Dienst</th>";
  echo "<th style='width:180px'>" . "Von" . "</th>";
  echo "<th style='width:180px'>" . "Bis" . "</th>";
  echo "<th style='width:90px'>" . "Del" . "</th>";
  echo "</tr>";
  echo "</thead>";



foreach ($schichten as $schicht) {
    //echo '<tr title="Details anzeigen" onclick="parent.DetailsSchichten.location.href=\'DetailsSchichten.php?InfoAlleSchichtID='.$schicht['SchichtID'].'#Info\';" >';
    echo '<tr title="Details anzeigen" onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . $schicht['SchichtID'] . '#Info\';" >';
    echo "<td>" . $schicht['Was'] . "</td>";
    echo "<td>" . $schicht['Ab'] . "</td>";
    echo "<td>" . $schicht['Bis'] . "</td>";
    echo "<td>" . "<button title='Schicht entfernen' name='Del' value='" . $schicht['EinzelSchichtID'] . "'>-</button>" . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";

$iAlleSchichtenCount = AlleSchichtenCount();
$iBelegteSchichtenCount = AlleBelegteSchichtenCount();

echo '<table class="commontable" onclick="window.location.href=\'AlleSchichten.php\'">';
    echo "<tr>";
        echo "<th>" . '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' . " Alle Schichten der Con (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")</th>";
    echo "</tr>";
echo "</table>";

?>
 
 </form> 
 </div>
 
 </body>
</html>
