<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

$eventname = EVENTNAME; // as var for heredoc
$header = <<< HEADER
<!doctype html>
<html>
 <head>
  <title>Meine Schichten $eventname </title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
 </head>
 <body>
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button>
<b>$eventname</b>
<div style="width: 100%;">
HEADER;

/// Detailinformation zu ausgewaehlten Schicht Holen
////////////////////////////////////////////////////////
if (isset($_POST['CloseInfo'])) {
    unset($InfoMeineSchichtID);
    unset($InfoAlleSchichtID);
}
if (isset($_POST['InfoMeineSchichtID'])) {
    $InfoMeineSchichtID = $_POST['InfoMeineSchichtID'];
    unset($InfoAlleSchichtID);

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

$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

/// Schicht Löschen
////////////////////////////////////////////////////////

if (isset($_POST['Del'])) {
    $messages = [];

    $EinzelSchichtID = $_POST['Del'];
    $db_erg = HelferVonSchichtLoeschen($db_link, $HelferID, $EinzelSchichtID);
    header("Location: " . $_SERVER['PHP_SELF']);
}

// Neu Schicht fuer Helfer Eintragen
///////////////////////////////////////////////////////////
if (isset($_POST['sent'])) {
    $messages = [];
    $SchichtId = $_POST['sent'];

    // Helfer Schicht zuweisen
    $db_erg = HelferSchichtZuweisen($db_link, $HelferID, $SchichtId);
    header("Location: " . $_SERVER['PHP_SELF']);
}


/// Alle Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////


$db_erg = AlleSchichtenEinesHelfers($db_link, $HelferID);

if (! $db_erg) {
    echo "AlleSchichten des Helfes ungültige Abfrage";
    die('Ungültige Abfrage: ' . mysqli_error());
}

  $iSQLCount = mysqli_num_rows($db_erg);
  echo $header;
  echo '<form method="post" action="MeineSchichten.php#Info">';

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

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    //echo '<tr title="Details anzeigen" onclick="parent.DetailsSchichten.location.href=\'DetailsSchichten.php?InfoAlleSchichtID='.$zeile['SchichtID'].'#Info\';" >';
    echo '<tr title="Details anzeigen" onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . $zeile['SchichtID'] . '#Info\';" >';
    echo "<td>" . $zeile['Was'] . "</td>";
    echo "<td>" . $zeile['Ab'] . "</td>";
    echo "<td>" . $zeile['Bis'] . "</td>";
    echo "<td>" . "<button title='Schicht entfernen' name='Del' value='" . $zeile['EinzelSchichtID'] . "'>-</button>" . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";

$iAlleSchichtenCount = AlleSchichtenCount($db_link);
$iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);

echo '<table class="commontable" onclick="window.location.href=\'AlleSchichten.php\'">';
    echo "<tr>";
        echo "<th>" . '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' . " Alle Schichten der Con (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")</th>";
    echo "</tr>";
echo "</table>";


mysqli_free_result($db_erg);

?>
 </form> 
 </div>
 </body>
</html>
