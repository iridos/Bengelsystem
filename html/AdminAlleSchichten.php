<?php
// Login
// Die Seite hat Extra-Funktionen, wenn ein Admin sie aufruft
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
// zeigt login-Seite an, wenn keine Session besteht
require '_login.php';
require_once '_functions.php';
$pagename  = "Alle Schichten / Schichten hinzufügen";             // name of this page
$backlink  = "AdminHelferUebersicht.php";  // back button in table header from table header
$header = PageHeader($pagename);
$tablehead = TableHeader($pagename,$backlink);
// POST vor HTML Ausgabe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Neu Schicht fuer Helfer Eintragen
    ///////////////////////////////////////////////////////////
        $messages = [];
    if (isset($_POST['plusschicht'])) {
        $SchichtID = $_POST['plusschicht'];
        // Nutzer hat hier zuletzt etwas geändert und wir klappen das deshalb auf,
        // indem wir unten target=active setzen
        $_SESSION["SchichtIdAktiv"] = $SchichtID;
        if (empty($messages)) {
            // Helfer Schicht zuweisen
            // wenn es ein Admin ist, die AdminID übergeben, ansonsten 0
            // TODO: immer AdminID angeben, die Funktionen in SQL testen, ob HelferID==AdminID
            $db_erg = HelferSchichtZuweisen($db_link, $HelferID, $SchichtID, $AdminStatus == 1 ? $AdminID : 0);

            $HelferName = '';
            $HelferEmail = '';
            $HelferHandy = '';
        } else {
            // Fehlermeldungen ausgeben:
            echo '<div class="error"><ul>';
            foreach ($messages as $message) {
                echo '<li>' . htmlspecialchars($message) . '</li>';
            }
        echo '</ul></div>';
        exit;
        }
    }

    if (isset($_POST['minusschicht'])) {
        // Mich aus Schicht entfernen
            $messages = [];
            $SchichtID = $_POST['minusschicht'];
            // Nutzer hat hier zuletzt etwas geaenndert und wir klappen das deshalb auf:
            $_SESSION["SchichtIdAktiv"] = $SchichtID;

        if (empty($messages)) {
            // Helfer aus Schicht entfernen
            $db_erg = HelferVonSchichtLoeschen_SchichtID($db_link, $HelferID, $SchichtID, $AdminStatus == 1 ? $AdminID : 0);
        } else {
            // Fehlermeldungen ausgeben:
            echo '<div class="error"><ul>';
            foreach ($messages as $message) {
                    echo '<li>' . htmlspecialchars($message) . '</li>';
            }
            echo '</ul></div>';
            exit;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
// Wenn es ein Admin ist HelferID AliasHelferID
    if ($AdminStatus == 1){
        if (isset($_POST['AliasHelferID'])) {
            $HelferID = $_POST['AliasHelferID'];
    } elseif (isset($_SESSION["AliasHelferID"])) {
        $HelferID = $_SESSION["AliasHelferID"];
        // ansonsten bleibt es die HelferID des Admins
        }
    HelferAuswahlButton($db_link, $HelferID);
    }
}
echo $header;
?>
  <a href="AdminHelferUebersicht.php">
  <button name="BackHelferdaten">
  <b>&larrhk;</b>
  </button></a>
  <?php echo "<b>" . EVENTNAME . "</b>"; ?>
  <h1> Alle Schichten / Schichten hinzuf&uuml;gen </h1>
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
    echo '<table class="commontable"><tr class="header"><th onclick="window.location.href=\'AdminMeineSchichten.php\'">';
    echo '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' . "Dienstplan von $HelferName: ";
    echo $zeile['Anzahl'];
    echo " Schichten, ";
    echo $zeile['Dauer'] / 3600;
    echo " Stunden)";
    echo '</th></tr></table>';
/// Schichten Auswahl
////////////////////////////////////////////////////////
// wird hier gegen Fehler gesetzt. bitte zu Ende implementieren
$addschicht = $_SESSION["addschicht"] ?? null;
$dienstsort = $_SESSION["dienstsort"] ?? null;


//addschicht und dienst-sort sollten wohl nach Diensten bzw Tagen sortieren
//addschicht wird gerade nie gesetzt, dienst-sort damit auch nicht
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '1') {
    $addschicht = '1';
    $dienstsort = '1';
}
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '2') {
    $addschicht = '2';
    $dienstsort = '2';
}
if (isset($_POST['addschicht']) && $_POST['addschicht'] == '0') {
    $addschicht = '0';
}

$_SESSION["addschicht"] = $addschicht;
$_SESSION["dienstsort"] = $dienstsort;

//echo "<br>Detail=".$addschicht."<br>";

if ($addschicht == '0') {
    echo "<p><b>Schichten Hinzufügen geordnet nach</b>";
    echo "<button name='addschicht' value='1'>Tage</button>";
    echo "<button name='addschicht' value='2'>Dienste</button></p>";
}

// jeder soll sich alle HelferLevel anzeigen lassen koennen
$HelferLevelAnzeige = $HelferLevel;
if (isset($_POST['helfer-level-anzeige']))
{
    $HelferLevelAnzeige = $_POST['helfer-level-anzeige'];
}

echo '<select style="width:200px" name="helfer-level-anzeige" onchange="submit()">';
$alleHelferLevel = alleHelferLevel($db_link);
foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
    $selected = ($HelferLevelIteration == $HelferLevelAnzeige) ? "selected" : "" ;
    echo "<option value='$HelferLevelIteration' $selected>$HelferLevelBeschreibung</option>";
}
echo '</select>';


if ($addschicht != '0') { // addschicht soll Darstellung nach Tagen oder Diensten sortieren, macht es aber nicht
    echo '<table class="commontable">';
    require('_zeitbereich.php');
    $Bereich = AusgabeZeitbereichZeile($start_date, $ZeitBereich, $TageNamenDeutsch, $_SERVER['PHP_SELF']);
    $MeinVon = $Bereich['MeinVon'];
    $MeinBis = $Bereich['MeinBis'];
    $db_erg = AlleSchichtenImZeitbereich($db_link, $MeinVon, $MeinBis, $HelferLevelAnzeige);

    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount($db_link);
    $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);
    echo '</table>';
        echo "<button type='button' onclick='expand_all_table_rows();'>Alles Ausklappen</button>";

    // "Alle Schichten der Con"
    echo '<table  class="commontable">';
    echo "<tr class='infoheader'>";
    echo "<th colspan='5'>Alle Schichten der Con (Besetzt/Gesamt) " . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . "</th></tr>";

    $alleHelferLevel = alleHelferLevel($db_link);
    // Summe Ausgabe alle Dienste pro Helferlevel
    foreach ($alleHelferLevel as $HelferLevelIteration => $HelferLevelBeschreibung) {
        $meine = "";
        if ($HelferLevelIteration == $HelferLevel) {
            $meine = "&leftarrow; Schichten für mich zum eintragen";
        } else { $meine = "Eintragen hier nur nach Rücksprache mit Orga";}
        if ($HelferLevelIteration == $HelferLevelAnzeige) {
            $meine = "$meine - Schichten werden gerade unten angezeigt";
        }
        $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelIteration);
        $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link, $HelferLevelIteration);
        echo "<tr class='infoheader'><th colspan='5' >&nbsp;&nbsp; &rightarrow; Schichten  $HelferLevelBeschreibung (Besetzt/Gesamt) (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")  $meine</th></tr>";
    }


    $OldTag = "";
    $OldWas = "";
    // um Zeilen mit von mir belegten Schichten hervorzuheben
    $MeineDienste = SchichtIdArrayEinesHelfers($db_link, $HelferID);
    //print_r($MeineDienste);

    echo '</table>';
    // Tabelle mit allen Diensten und Schichten
    echo '<table  class="commontable collapsible">';
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($dienstsort == '1') { // dienst-sort wird momentan nie gesetzt, also immer else-Teil ausgeführt TODO
            $Tag = $zeile['Tag'];

            if ($Tag != $OldTag) {
                echo "<tr class='header'><th colspan='5' >";
                echo $Tag;
                echo "</th></tr>";
                $OldTag = $Tag;
            }
        } else {
            $Was = $zeile['Was'];

            if ($Was != $OldWas) { // Header ausgeben, wenn der Dienst nicht mehr der selbe ist
                // + in <span> becomes - when rows are opened
                echo "<tr class='header'><th  colspan='5' style='width:100%'><span>+</span> ";
                $SchichtID = $zeile['SchichtID'];
                $DienstID = $zeile['DienstID'];
                $iAlleSchichtenCount = AlleSchichtenCount($db_link, $HelferLevelAnzeige, $DienstID);
                $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link, $HelferLevelAnzeige, $DienstID);
                echo "$Was ($iBelegteSchichtenCount/$iAlleSchichtenCount) <!-- Abfrage $HelferLevel, $DienstID -->";
                echo "</th>";
                echo "</tr>";
                SchichtInfo($SchichtID, $InfoWas, $InfoWo, $InfoDauer, $Leiter, $LeiterHandy, $LeiterEmail, $Info);
                if (true) {
                    echo "<tr><td colspan=5 style='background:lightblue'>";
                    echo "<b>Beschreibung:</b> $Info <br><br>";
                    echo "<b>Ort:</b> $InfoWo <br>";
                //echo "<b>Dauer:</b> $InfoDauer<br>"; // verschieden je nach Einzelschicht
                    echo "<b>Ansprechparter:</b>" . $Leiter . ", ";
                    echo $LeiterHandy . ", ";
                    echo "$LeiterEmail";
                    echo "</td></td></tr>\n";
                }
                $OldWas = $Was;
            }
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

        if ($dienstsort == '1') {
            echo "<td>" . $zeile['Was'] . "</td>";
        } else {
            echo "<td>" . $zeile['Tag'] . "</td>";
        }
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
}







mysqli_free_result($db_erg);


?>

 </form>
 </div>

 </body>
</html>
