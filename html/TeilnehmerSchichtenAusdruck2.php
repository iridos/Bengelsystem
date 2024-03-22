<?php

namespace Bengelsytem;

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
  <title>testAlle Helferschichten 2</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <link rel="stylesheet" href="css/style_print.css" media="print"/>
  <meta name="viewport" content="width=480" />
</head>
<body>


<div style="width: 100%;">


<?php
setlocale(LC_ALL, 'de_DE.UTF-8') or die("Locale not installed");

$unixtime = strtotime('2023-09-15');
for ($day = 0; $day < 3; $day++) {
    $datestring = date('Y-m-d', $unixtime + $day * 24 * 60 * 60);
    echo "<h1>" . strftime('%A, %e. %B %Y', $unixtime + $day * 24 * 60 * 60) . "</h1>";
    $db_erg = GetDiensteForDay(2, $datestring);
    foreach ($db_erg as $zeile) {
        $db_erg2 = GetSchichtenForDienstForDay($zeile["DienstId"], $datestring);
        echo "<table id='customers'>";
        echo "<tr><th colspan=3>" . $zeile["Was"] . "</th></tr>";
        $schichten = 0;
        $OldVon = 0;
        $OldSoll = 0;
        foreach ($db_erg2 as $zeile2) {
            if ($zeile2["Von"] != $OldVon && $schichten != 0) {
                while ($schichten < $OldSoll) {
                    $schichten++;
                    echo "<tr>";
                    echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $OldSoll . "</strong> " . date('H:i', strtotime($OldVon)) . " - " . date('H:i', strtotime($OldBis)) . "</td>";
                    echo "<td rowspan='3' valign='top'></td>";
                    echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($OldVon)) . " <strong>" . $zeile["Was"] . "</strong></td>";
                    echo "</tr>";
                    echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
                    echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
                    echo "<tr height='10mm'></tr>";
                }
                $schichten = 0;
            }
            $schichten++;
            echo "<tr>";
            echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $zeile2["Soll"] . "</strong> " . date('H:i', strtotime($zeile2["Von"])) . " - " . date('H:i', strtotime($zeile2["Bis"])) . "</td>";
            echo "<td rowspan='3' valign='top'><strong>" . $zeile2["Name"] . "</strong> " . $zeile2["Handy"] . "</td>";
            echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($zeile2["Von"])) . " <strong>" . $zeile["Was"] . "</strong></td>";
            echo "</tr>";
            echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
            echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
            echo "<tr height='10mm'></tr>";
            $OldVon = $zeile2["Von"];
            $OldBis = $zeile2["Bis"];
            $OldSoll = $zeile2["Soll"];
            if (empty($zeile2["Name"])) {
                while ($schichten < $OldSoll) {
                    $schichten++;
                    echo "<tr>";
                    echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $OldSoll . "</strong> " . date('H:i', strtotime($OldVon)) . " - " . date('H:i', strtotime($OldBis)) . "</td>";
                    echo "<td rowspan='3' valign='top'></td>";
                    echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($OldVon)) . " <strong>" . $zeile["Was"] . "</strong></td>";
                    echo "</tr>";
                    echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
                    echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
                    echo "<tr height='10mm'></tr>";
                }
            }
        }

        echo "</table>";
    }
}

$unixtime = strtotime('2023-09-15');
for ($day = 0; $day < 3; $day++) {
    $datestring = date('Y-m-d', $unixtime + $day * 24 * 60 * 60);
    echo "<h1>" . strftime('%A, %e. %B %Y', $unixtime + $day * 24 * 60 * 60) . "</h1>";
    $db_erg = GetDiensteForDay(1, $datestring);
    while ($db_erg as $zeile) {
        $db_erg2 = GetSchichtenForDienstForDay($zeile["DienstId"], $datestring);
        echo "<table id='customers'>";
        echo "<tr><th colspan=3>" . $zeile["Was"] . "</th></tr>";
        $schichten = 0;
        $OldVon = 0;
        $OldSoll = 0;
        foreach ($db_erg2 as $zeile2) {
            if ($zeile2["Von"] != $OldVon && $schichten != 0) {
                while ($schichten < $OldSoll) {
                    $schichten++;
                    echo "<tr>";
                    echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $OldSoll . "</strong> " . date('H:i', strtotime($OldVon)) . " - " . date('H:i', strtotime($OldBis)) . "</td>";
                    echo "<td rowspan='3' valign='top'></td>";
                    echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($OldVon)) . " <strong>" . $zeile["Was"] . "</strong></td>";
                    echo "</tr>";
                    echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
                    echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
                    echo "<tr height='10mm'></tr>";
                }
                $schichten = 0;
            }
            $schichten++;
            echo "<tr>";
            echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $zeile2["Soll"] . "</strong> " . date('H:i', strtotime($zeile2["Von"])) . " - " . date('H:i', strtotime($zeile2["Bis"])) . "</td>";
            echo "<td rowspan='3' valign='top'><strong>" . $zeile2["Name"] . "</strong> " . $zeile2["Handy"] . "</td>";
            echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($zeile2["Von"])) . " <strong>" . $zeile["Was"] . "</strong></td>";
            echo "</tr>";
            echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
            echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
            echo "<tr height='10mm'></tr>";
            $OldVon = $zeile2["Von"];
            $OldBis = $zeile2["Bis"];
            $OldSoll = $zeile2["Soll"];
            if (empty($zeile2["Name"])) {
                while ($schichten < $OldSoll) {
                    $schichten++;
                    echo "<tr>";
                    echo "<td rowspan='3' style='width:0.1%; white-space:nowrap;' valign='top'><strong>" . $schichten . "/" . $OldSoll . "</strong> " . date('H:i', strtotime($OldVon)) . " - " . date('H:i', strtotime($OldBis)) . "</td>";
                    echo "<td rowspan='3' valign='top'></td>";
                    echo "<td width='50%'>" . strftime('%a %H:%M', strtotime($OldVon)) . " <strong>" . $zeile["Was"] . "</strong></td>";
                    echo "</tr>";
                    echo "<tr><td><i>" . $zeile["Wo"] . "</i></td></tr>";
                    echo "<tr><td>" . $zeile["Info"] . "</td></tr>";
                    echo "<tr height='10mm'></tr>";
                }
            }
        }

        echo "</table>";
    }
}

?>

</body>
</html>
