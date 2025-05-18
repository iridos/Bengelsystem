<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
// das hier muss nicht unbedingt eine Adminseite sein
if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
?>
<!doctype html>
<html>
 <head>
  <title>Admin <?php echo EVENTNAME ?></title>

  <link rel="stylesheet" href="css/style_common.css"/>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
  <script src="<?php echo JQUERY ?>" type="text/javascript"></script>
  <script src="js/helferdb.js" type="text/javascript"></script> 
  <script>
   collapse_table_rows();
 </script>
 </head>
 <body>
<div style="width: 100%;">
<?php


DatenbankAufDeutsch($db_link);

//$DienstID =$_SESSION["DienstID"];
//$SchichtID =$_SESSION["SchichtID"];

$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

$_SESSION["HelferID"] = $HelferID;


if (isset($_POST['ShowSchicht'])) {
    $SchichtID = $_POST['SchichtSearch'];
}
if (isset($_POST['SchichtSearch'])) {
    $SchichtID = $_POST['SchichtSearch'];
}

if (isset($_POST['ShowSchichten'])) {
    $DienstID = $_POST['DienstSearch'];
}

if (isset($_POST['DienstSearch'])) {
    $DienstID = $_POST['DienstSearch'];
    $SchichtID = 0;
}




// Dienste Anzeigen
////////////////////////////////////////////////////////

echo "<br><br><table class='commontable' style='page-break-before:always'>";
?>
  <tr class="header">
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>  &nbsp; <b>&Uuml;bersicht Helfer und Ihre Schichten</b></th>
  </tr>
</table>
<table class="commontable collapsible">
<?php
// Function to output helper information
function outputHelperInformation($HelferUeberschrift, $OldAliasHelferID, $dauer, $EinzelDienstStundenZeile,$HelferHandy)
{
    echo "$HelferUeberschrift </th><th> <img style='vertical-align:middle;width:30px;height:30px;' src='Bilder/PfeilRechts.jpeg'> $dauer Stunden</th>";
    echo "<th>$HelferHandy</th>";
    echo "<th ><div style='display:table'><form style='display:table-cell' action='AdminAlleSchichten.php' method='post'>";
    echo "<button width='120px' name='AliasHelferID' value='" . $OldAliasHelferID . "'>+</button></form>\n";
    echo "&nbsp;";
    echo "<form style='display:table-cell' action='AdminMeineSchichten.php' method='post'>";
    echo "<button width='120px' name='AliasHelferID' value='" . $OldAliasHelferID . "'>👁</button></form>";
    echo "<button width='120px' name='AliasHelferID' value='" . $OldAliasHelferID . "'>&ndash;</button></form>";
    echo "</div></th>";
    echo "$EinzelDienstStundenZeile</td></tr>\n ";
}

// Helferlevel holen, wir listen die Level nacheinander auf
$alleHelferLevel = alleHelferLevel($db_link);
asort($alleHelferLevel); // sort, jetzt sind dauerhelfer lvl1 oben, lvl2 danach
foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
    echo "<tr class='header infoheader'><th colspan=4>$HelferLevelBeschreibung (Lvl: $HelferLevelIteration)</th></tr>";
    $db_erg = AlleHelferSchichtenUebersicht($db_link, $HelferLevelIteration);
    $dauer = 0;
    $i = 0;
    $OldAliasHelferID = "-1";
    $EinzelDienstStundenZeile = ""; // Tabellenzeile mit EinzelDienstStunden
    $HelferUeberschrift = "";


    echo "<tr class='header infoheader'><th>Accountdaten</th><th>Schichten anzeigen</th><th>Handy</th><th>Schichten ändern</th></tr>";
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
            $HelferName = $zeile["Name"];
            $HelferLevel = $zeile["HelferLevel"];
            $AliasHelferID = $zeile["AliasHelferID"];
            $HelferHandy = $zeile["Handy"];
        if ($AliasHelferID != $OldAliasHelferID) {
            if ($EinzelDienstStundenZeile != "") {
                 outputHelperInformation($HelferUeberschrift, $OldAliasHelferID, $dauer, $EinzelDienstStundenZeile,$HelferHandy);
            }
                $dauer = 0;
                $EinzelDienstStundenZeile = "";
                $HelferUeberschrift = " <tr class='header'> <th width='15%'> <form id='form_" . $AliasHelferID . "' method='post' action='AdminUserdaten.php'><input type='hidden' name='AliasHelferID' value='" . $AliasHelferID . "'/><div onclick=\"document.getElementById('form_" . $AliasHelferID . "').submit();\"/><img style='vertical-align:middle;width:30px;height:30px;' src='Bilder/PfeilRechts.jpeg'>&nbsp;$HelferName </div></form>";
                $OldHelferName = $HelferName;
                $OldAliasHelferID = $AliasHelferID;
                $i += 1;
        }
            $EinzelDienstStundenZeile .= "<tr><td style='width:100px'> " . (int)$zeile["Dauer"] . "</td><td>";
            $EinzelDienstStundenZeile .= $zeile["Was"];
            $EinzelDienstStundenZeile .= "</td></tr>";
            $dauer = $dauer + (int)$zeile["Dauer"];
    }
    if ($EinzelDienstStundenZeile != "") {
                 outputHelperInformation($HelferUeberschrift, $OldAliasHelferID, $dauer, $EinzelDienstStundenZeile,$HelferHandy);
    }
}
echo "</table>";

?>


 </div>
 
 </body>
</html>
