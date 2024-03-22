<?php

namespace Bengelsystem;

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
?>
<!doctype html>
<html>
 <head>
  <title>Admin <?php echo EVENTNAME ?> - Schichten editieren</title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>

  <meta name="viewport" content="width=480" />
 </head>
 <body>
<div><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button> <h4 style="display: inline;">Admin: Schichten editieren - 
<?php echo "<b>" . EVENTNAME . "</b>"; ?>
</h4>
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

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;" name="AliasHelferID" id="AliasHelferID" onchange="submit()">';
    $zeilen = HelferListe();
    foreach ($zeilen as $zeile) {
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
    echo "AliasHelfer: $AliasHelferID<br>";
} elseif (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
} else {
    HelferAuswahlButton($db_link, $AliasHelferID);
    exit;
}
HelferAuswahlButton($db_link, $AliasHelferID);


$_SESSION["AliasHelferID"] = $AliasHelferID;
$AdminID = $_SESSION["AdminID"];

$zeilen = Helferdaten($AliasHelferID);

foreach ($zeilen as $zeile) {
    $HelferName = $zeile['Name'];
}

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
        $db_erg = HelferVonSchichtLoeschen($AliasHelferID, $EinzelSchichtID, $HelferID);
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



<form method="post" action="AdminMeineSchichten.php#Info">  
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
        $db_erg = HelferSchichtZuweisen($AliasHelferID, $SchichtId, $HelferID);

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


$schichten = AlleSchichtenEinesHelfers($AliasHelferID);

  $iSQLCount = count($schichten);
  //$iSQLCount = 3;

echo '<table class="commontable">';

  echo "<thead>";
  echo "<tr>";
  echo "<th colspan=1>" . "Schichten von $HelferName (" . $iSQLCount . " Schichten)</th>";
  //echo "</tr><tr>";
  //echo "<th></th>";
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
    echo "<td>" . "<p><button title='Schicht entfernen' name='Del' value='" . $schicht['EinzelSchichtID'] . "'>-</button></p>" . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";

$iAlleSchichtenCount = AlleSchichtenCount();
$iBelegteSchichtenCount = AlleBelegteSchichtenCount();

echo '<table class="commontable" onclick="window.location.href=\'AdminAlleSchichten.php\'">';
    echo "<tr>";
        echo "<th>" . "Alle Schichten der Con (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")</th>";
    echo "</tr>";
echo "</table>";

?>
 
 </form> 
 </div>
 
 </body>
</html>
