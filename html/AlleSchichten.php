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
  <title>Helfer <?php echo EVENTNAME ?> Alle Schichten</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
 
  <script src="js/jquery-3.7.1.min.js" type="text/javascript"></script>
  <script src="js/helferdb.js" type="text/javascript"></script>
  <script> collapse_table_rows();
 </script>
 
 </head>
 <body>
 <button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button>   
<?php echo "<b>" . EVENTNAME . "</b>"; ?>
 <h1> Alle Schichten / Schichten hinzuf&uuml;gen </h1>
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

if (isset($_GET['ZeitBereich'])) {
    $ZeitBereich = $_GET['ZeitBereich'];
} else {
    $ZeitBereich = 0;
}



// Helferliste Anzeigen
////////////////////////////////////////////////////////

?>


<form method="post" action="AlleSchichten.php">
<?php



// Neu Schicht fuer Helfer Eintragen
///////////////////////////////////////////////////////////
if (isset($_POST['plusschicht'])) {
    $messages = [];
    $SchichtID = $_POST['plusschicht'];
    // Nutzer hat hier zuletzt etwas geändert und wir klappen das deshalb auf,
    // indem wir unten target=active setzen
    $_SESSION["SchichtIdAktiv"] = $SchichtID;
    if (empty($messages)) {
        // Helfer Schicht zuweisen
        $db_erg = HelferSchichtZuweisen($HelferID, $SchichtId);

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
            $db_erg = HelferVonSchichtLoeschen_SchichtID($HelferID, $SchichtID);
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


// Zusammenfassung Eigener Schichten
 $zeile = SchichtenSummeEinesHelfers($HelferID);

    //"Mein Dienstplan"
    echo '<table class="commontable"><tr class="header"><th onclick="window.location.href=\'MeineSchichten.php\'">';
    echo '<img src="Bilder/PfeilRechts2.png" style="width:30px;height:30px;align:middle;">' .  " Mein Dienstplan (";
    echo $zeile['Anzahl'];
    echo " Schichten, ";
    echo $zeile['Dauer'] / 3600;
    echo " Stunden)";
    echo '</th></tr></table><br><br>';
/// Schichten Auswahl
////////////////////////////////////////////////////////
$addschicht = $_SESSION["addschicht"];
$dienstsort = $_SESSION["dienstsort"];



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


if ($addschicht != '0') {
    echo '<table class="commontable">';
    require('_zeitbereich.php');
    $Bereich = AusgabeZeitbereichZeile($start_date, $ZeitBereich, $TageNamenDeutsch, "AlleSchichten.php");
    $MeinVon = $Bereich['MeinVon'];
    $MeinBis = $Bereich['MeinBis'];
    $db_erg = AlleSchichtenImZeitbereich($MeinVon, $MeinBis, -1);

    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount();
    $iBelegteSchichtenCount = AlleBelegteSchichtenCount();
    echo '</table>';
        echo "<button type='button' onclick='expand_all_table_rows();'>Alles Ausklappen</button>";

    // "Alle Schichten der Con"
    echo '<table  class="commontable">';
    echo "<tr class='header'>";
    echo "<th colspan='7'>Alle Schichten der Con (" . $iBelegteSchichtenCount . "/" . $iAlleSchichtenCount . ")</th></tr>";

    echo "</tr>";

    $OldTag = "";
    $OldWas = "";
    // um Zeilen mit von mir belegten Schichten hervorzuheben
    $MeineDienste = SchichtIdArrayEinesHelfers($HelferID);
    //print_r($MeineDienste);

    echo '</table>';
    // Tabelle mit allen Diensten und Schichten
    echo '<table  class="commontable collapsible">';
    foreach ($MeineDienste as $zeile) {
        if ($dienstsort == '1') {
            $Tag = $zeile['Tag'];

            if ($Tag != $OldTag) {
                echo "<tr class='header'><th colspan='5' >";
                echo $Tag;
                echo "</th></tr>";
                $OldTag = $Tag;
            }
        } else {
            $Was = $zeile['Was'];

            if ($Was != $OldWas) {
                // + in <span> becomes - when rows are opened
                echo "<tr class='header'><th  colspan='7' style='width:100%'><span>+</span> ";
                echo $Was;
                echo "</th>";
                /*
                echo "<th style='width:100px'>". "Von" . "</th>";
                echo "<th style='width:130px'>". "Bis" . "</th>";
                echo "<th style='width:90px'>". "Ist/Soll" . "</th>";
                echo "<th style='width:90px'>". "Add" . "</th>";
                */
                echo "</tr>";
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
             $regtext  = 'Meine!';
        } else {
            // dummy-style, um SchichtID unsichtbar im Tag anzuzeigen
            $rowstyle = 'dbinfo="SchichtID:' . $zeile['SchichtID'] . ';helferlvl:' . $HelferLevel . '" ';
            $regtext  = '';
        }
        if ($_SESSION["SchichtIdAktiv"] == $zeile['SchichtID']) {
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
                // buttons sind in der selben Zelle
        echo "<td width='30px'>" . "<button width='20px' name='plusschicht' value='" . $zeile['SchichtID'] . "'>+</button>" . "";
        echo "&nbsp;&nbsp;<button width='120px' name='minusschicht' value='" . $zeile['SchichtID'] . "'>&ndash;</button> $regtext" . "</td>";
        echo "</tr>\n";
    }
    echo "</table>";
}

?>

 </form>
 </div>

 </body>
</html>
