<?php
// Login
// TODO code mit AdminAlleSchichten.php consolidieren
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
// zeigt login-Seite an, wenn keine Session besteht
require '_login.php';
require_once '_functions.php';
$pagename  = "Alle Schichten";             // name of this page
$backlink  = "index.php";  // back button in table header from table header
$header = PageHeader($pagename);
$tablehead = TableHeader($pagename,$backlink);
// Admin Seite setzt HelferID aus AliasHelferID, sonst bleibt wie aus _login.php gesetzt normale Seite nicht

// Nutzer hat hier zuletzt etwas geändert und wir klappen das deshalb auf
$SchichtID = $_SESSION["SchichtIdAktiv"] ?? ""; // sollte die Session Var hier auf nichts zurück gesetzt werden?
$_SESSION["AliasHelferID"] = $HelferID; // reset this for admin
$AliasHelferID=$HelferID;
$AliasHelferName=$HelferName;// revert this after putting output in function
$HelferLevelAnzeige = $_SESSION["HelferLevelAnzeige"] ?? $HelferLevel;
// POST vor HTML Ausgabe
AlleSchichtenCheckPOST($db_link,$HelferID,$AdminStatus,$AdminID);
echo $header;
echo $tablehead;
?>
  <div style="width: 100%;">
  <?php

/// Detailinformation zu ausgewaehlten Schicht Holen
////////////////////////////////////////////////////////
// Helferliste Anzeigen
////////////////////////////////////////////////////////
?>


<form method="post" action="#action">
<?php
// Zusammenfassung Eigener Schichten
 $db_erg = SchichtenSummeEinesHelfers($db_link, $HelferID);
 $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);

    //"Dienstplan"
    echo '<table class="commontable"><tr class="header"><th onclick="window.location.href=\'MeineSchichten.php\'">';
    echo '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' . "Dienstplan von $AliasHelferName (";
    echo $zeile['Anzahl'];
    echo " Schichten, ";
    echo $zeile['Dauer'] / 3600;
    echo " Stunden)";
    echo '</th></tr></table>';
/// Schichten Auswahl
////////////////////////////////////////////////////////
HelferLevelAuswahl($db_link,$HelferLevelAnzeige);


echo '<table class="commontable">';
require('_zeitbereich.php');
$Bereich = AusgabeZeitbereichZeile($start_date, $ZeitBereich, $TageNamenDeutsch, $_SERVER['PHP_SELF']);
$MeinVon = $Bereich['MeinVon'];
$MeinBis = $Bereich['MeinBis'];
$db_erg = AlleSchichtenImZeitbereich($db_link, $MeinVon, $MeinBis, $HelferLevelAnzeige);

echo "<button type='button' onclick='expand_all_table_rows();'>Alles Ausklappen</button>";

function ZeigeHelferLevelTabelle($db_link,$HelferLevel,$HelferLevelAnzeige){
    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount($db_link);
    $Belegung = AlleBelegteSchichtenCountMitSurplus($db_link);
    $iBelegteSchichtenCount = $Belegung['besetzt'];
    $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
    // "Alle Schichten der Con" (Gesamtstatistik besetzt/gewollt)
    echo '<table  class="commontable">';
    echo "<tr class='infoheader'>";
    echo "<th colspan='5'>Alles: ";
    echo "Besetzt (+Überbelegt) / Gesamt&nbsp;&nbsp;&nbsp; ";
    echo "${iBelegteSchichtenCount}(+${iueberBelegteSchichtenCount})/$iAlleSchichtenCount </th></tr>";

    $alleHelferLevel = alleHelferLevel($db_link);
    // Summe Ausgabe alle Dienste pro Helferlevel
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        $meine = "";
        if ($HelferLevelIteration == $HelferLevel) {
            $meine = "<div style='float:right'>&leftarrow; Schichten für mich zum eintragen</div>";
        } else { $meine = "<div style='float:right'>Eintragen hier nur nach Rücksprache mit Orga</div>";}
        if ($HelferLevelIteration == $HelferLevelAnzeige) {
            $meine = "$meine  Schichten werden gerade unten angezeigt";
        }
        $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelIteration);
        $Belegung = AlleBelegteSchichtenCountMitSurplus($db_link,$HelferLevelIteration);
        $iBelegteSchichtenCount = $Belegung['besetzt'];
        $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
        echo "<tr class='infoheader'><th colspan='5' >&nbsp;&nbsp; &rightarrow; Schichten  $HelferLevelBeschreibung  ";
        echo "${iBelegteSchichtenCount}(+$iueberBelegteSchichtenCount)/$iAlleSchichtenCount  $meine</th></tr>";
    }
    echo '</table>';
}
ZeigeHelferLevelTabelle($db_link,$HelferLevel,$HelferLevelAnzeige);

$OldTag = "";
$OldWas = "";
// um Zeilen mit von mir belegten Schichten hervorzuheben
$MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);
//print_r($MeineDienste);

// Tabelle mit allen Diensten und Schichten
echo '<table  class="commontable collapsible">';
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $Was = $zeile['Was'];
    if ($Was != $OldWas) { // Header ausgeben, wenn der Dienst nicht mehr der selbe ist
        // + in <span> becomes - when rows are opened
        echo "<tr class='header'><th  colspan='5' style='width:100%'><span>+</span> ";
        $SchichtID = $zeile['SchichtID'];
        $DienstID = $zeile['DienstID'];
        $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelAnzeige, $DienstID);
        $Belegung = AlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevelAnzeige, $DienstID);
        $iBelegteSchichtenCount = $Belegung['besetzt'];
        $iueberBelegteSchichtenCount = $Belegung['ueberbelegt'];
        $ueberBelegteSchichten = ($iueberBelegteSchichtenCount >0) ? "[+$iueberBelegteSchichtenCount]" : "";
        echo "$Was ($iBelegteSchichtenCount/$iAlleSchichtenCount) $ueberBelegteSchichten <!-- Abfrage $HelferLevel, $DienstID -->";
        echo "</th>";
        echo "</tr>";
        SchichtInfo($SchichtID, $InfoWas, $InfoWo, $InfoDauer, $Leiter, $LeiterHandy, $LeiterEmail, $Info);
        echo "<tr><td colspan=5 style='background:lightblue'>";
        echo "<b>Beschreibung:</b> $Info <br><br>";
        echo "<b>Ort:</b> $InfoWo <br>";
        echo "<b>Ansprechparter:</b>" . $Leiter . ", ";
        echo $LeiterHandy . ", ";
        echo "$LeiterEmail";
        echo "</td></td></tr>\n";
        $OldWas = $Was;
    }
    $Color = "red";
    if ($zeile['Ist'] > 0) {
        $Color = "yellow";
    }
    if ($zeile['Ist'] >= $zeile['Soll']) {
        $Color = "green";
    }
    $Von = $zeile['Ab'];
    $Bis = $zeile['Bis'];
    if (substr($Von, 0, 2) == substr($Bis, 0, 2)) {
        $Bis = substr($Bis, 2);
    }
    $Von = substr($Von, 2);

          // Meine Schichten gruen einfaerben
    if (in_array($zeile['SchichtID'], $MeineDienste)) {
         $rowstyle = ' style="background-color:lightgreen" ';
         $regtext  = '<br><center>Meine!</center>';
    } else {
        // dummy-style, um SchichtID unsichtbar im Tag anzuzeigen
        $rowstyle = 'dbinfo="SchichtID:' . $zeile['SchichtID'] . ';helferlvl:' . $HelferLevel . '" ';
        $regtext  = '';
    }
    if (isset($_SESSION["SchichtIdAktiv"]) && $_SESSION["SchichtIdAktiv"] == $zeile['SchichtID']) {
        $rowstyle = $rowstyle . " target='active' "; // dont collapse when the user did something
    }

            echo '<tr ' . $rowstyle . 'onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID=' . $zeile['SchichtID'] . '#Info\';" >';

    echo "<td>" . $zeile['Tag'] . "</td>";
    echo "<td>" . $Von . "</td>";
    echo "<td>" . $Bis . "</td>";
    echo "<td bgcolor='" . $Color . "'>" . $zeile['Ist'] . "/";
    echo "" . $zeile['Soll'] . "</td>";
    // durch space:nowrap wird ein Umbruch zwischen den Buttons verhindert
    // in Kombi mit width:1% wird immer der minimale Platz für die Spalte belegt
    // width:200px oder max-width:200px hat zu viel weissem Platz rechts und enge links gefuehrt
    echo "<td style='width:10%;white-space:nowrap'><button name='plusschicht' value='" . $zeile['SchichtID'] . "'>+</button>";
    echo "&nbsp;&nbsp;<button name='minusschicht' value='" . $zeile['SchichtID'] . "'>&ndash;</button> $regtext" . "</td>";
    echo "</tr>\n";
}
echo "</table>";







mysqli_free_result($db_erg);


?>

 </form>
 </div>

 </body>
</html>
