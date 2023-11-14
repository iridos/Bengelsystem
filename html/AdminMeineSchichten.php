<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once 'konfiguration.php';
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
  <title>Admin Drop am See - Schichten editieren</title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>

  <meta name="viewport" content="width=480" />
 </head>
 <body>
<div><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button> <h4 style="display: inline;">Admin: Schichten editieren</h4>  
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

    $zeile = DetailSchicht($db_link, $InfoMeineSchichtID);

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

    $zeile = DetailSchicht($db_link, $InfoAlleSchichtID);

    $Was = $zeile['Was'];
    $Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
    $LeiterHandy =  $zeile['Handy'];
    $LeiterEmail =  $zeile['Email'];
    $Info = $zeile['Info'];



    // Beteiligte Helfer Holen
    $db_erg = BeteiligteHelfer($db_link, $InfoAlleSchichtID);


    $x = 0;

    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $MitHelferID[$x] = $zeile['HelferID'];
        $MitHelfer[$x] = $zeile['Name'];
        $MitHelferHandy[$x] = $zeile['Handy'];
        $x++;
    }
}

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;" name="AliasHelfer" id="AliasHelfer" onchange="submit()">';
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



if (isset($_POST['AliasHelfer'])) {
    $AliasHelferID = $_POST['AliasHelfer'];
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

$db_erg = Helferdaten($db_link, $AliasHelferID);

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
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
        $db_erg = HelferVonSchichtLoeschen($db_link, $AliasHelferID, $EinzelSchichtID, $HelferID);
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
        $db_erg = HelferSchichtZuweisen($db_link, $AliasHelferID, $SchichtId, $HelferID);

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

    DatenbankAufDeutsch($db_link);

/// Alle Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////


$db_erg = AlleSchichtenEinesHelfers($db_link, $AliasHelferID);

if (! $db_erg) {
    echo "AlleSchichten des Helfes ungültige Abfrage";
    die('Ungültige Abfrage: ' . mysqli_error());
}

  $iSQLCount = mysqli_num_rows($db_erg);
  //$iSQLCount = 3;

echo '<table id="customers">';

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



while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    //echo '<tr title="Details anzeigen" onclick="parent.DetailsSchichten.location.href=\'DetailsSchichten.php?InfoAlleSchichtID='.$zeile['SchichtID'].'#Info\';" >';
    echo '<tr title="Details anzeigen" onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . $zeile['SchichtID'] . '#Info\';" >';
    echo "<td>" . $zeile['Was'] . "</td>";
    echo "<td>" . $zeile['Ab'] . "</td>";
    echo "<td>" . $zeile['Bis'] . "</td>";
    echo "<td>" . "<p><button title='Schicht entfernen' name='Del' value='" . $zeile['EinzelSchichtID'] . "'>-</button></p>" . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";

$iAlleSchichtenCount = AlleSchichtenCount($db_link);
$iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);

echo '<table id="customers" onclick="window.location.href=\'AdminAlleSchichten.php\'">';
    echo "<tr>";
        echo "<th>" . "Alle Schichten der Con (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")</th>";
    echo "</tr>";
echo "</table>";


mysqli_free_result($db_erg);


?>
 
 </form> 
 </div>
 
 </body>
</html>
