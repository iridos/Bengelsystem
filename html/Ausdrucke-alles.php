<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once 'konfiguration.php';
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

?>
<!doctype html>
<html>
<head>
  <title>Admin <?php echo EVENTNAME ?></title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <link rel="stylesheet" href="css/style_print.css" media="print"/>

<meta name="viewport" content="width=480" />
</head>
<body>


<div style="width: 100%;">

<table class="commontable" >
  <tr>
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>  &nbsp; <b>Übersicht Dienst DAS 2023</b></th>
  </tr>
</table>

<?php

echo '<table class="commontable" >';

$db_erg = GetDiensteChilds($db_link, 0);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    echo "<tr><th>";
    echo $zeile["Was"];
    echo "</th></tr>";

    $db_erg2 = GetDiensteChilds($db_link, $zeile["DienstID"]);
    while ($zeile = mysqli_fetch_array($db_erg2, MYSQLI_ASSOC)) {
        echo "<tr><td>";
        echo $zeile["Was"];
            echo "</td></tr>";
    }
}

echo "</table>";




$db_erg = AlleSchichtenImZeitbereich($db_link, "2000-05-18 00:00:00", "2200-05-19 00:00:00");

$OldWas = "";
echo "<br><br><table class='commontable' style='page-break-before:always'>";
?>
  <tr>
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>  &nbsp; <b>Übersicht Schichten der Dienste DAS 2023</b></th>
  </tr>
<?php
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $Was = $zeile["Was"];

    if ($Was != $OldWas) {
            echo "</table>";
        //echo '<table class="commontable" style="page-break-before:always">';
        echo '<table class="commontable">';
        echo "<tr><th colspan=3>";
            echo $Was;
        echo "</th></tr>";
                $OldWas = $Was;
    }

    echo "<tr><td style='width:100px'>";
    echo $zeile["Ab"];
    echo "</td><td style='width:100px'>";
    echo $zeile["Bis"];
    echo "</td><td>";

        $db_erg2 = BeteiligteHelfer($db_link, $zeile["SchichtID"]);
    while ($zeile = mysqli_fetch_array($db_erg2, MYSQLI_ASSOC)) {
            echo $zeile["Name"];
        echo " ";
        echo $zeile["Handy"];
            echo ",";
    }
    echo "</td></tr>";
}

echo "</table>";

$OldHelferName = "";

echo "<br><br><table class='commontable' style='page-break-before:always'>";
?>
  <tr>
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>  &nbsp; <b>Übersicht Helfer und Ihre Schichten DAS 2023</b></th>
  </tr>
<?php
$db_erg = AlleHelferSchichtenUebersicht($db_link);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferName = $zeile["Name"];

    if ($HelferName != $OldHelferName) {
            echo "</table>";
            //echo '<table class="commontable" style="page-break-before:always">';
            echo '<table class="commontable">';
            echo "<tr><th colspan=3>";
            echo $HelferName;
            echo "</th></tr>";
            $OldHelferName = $HelferName;
    }

        echo "<tr><td style='width:100px'>";
    echo (int)$zeile["Dauer"];
        echo "</td><td>";
    echo $zeile["Was"];
        echo "</td></tr>";
}


echo "</table>";

?>
  


<?php

mysqli_free_result($db_erg);
?>


</body>
</html>
