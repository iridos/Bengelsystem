<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once 'konfiguration.php';
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
?>
<!doctype html>
<html lang=de>
<head>
  <title>Helfer <?php echo EVENTNAME ?> Home</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <script src=js/helferdb.js></script>
<meta name="viewport" content="width=480" />
<meta charset="utf-8">
</head>
<body>
<div style="width: 100%;">

<table class="commontable" >
  <tr onclick="window.location.href='Info.php';">
    <th><img src="Bilder/Info.jpeg" style="width:30px;height:30px;"> &nbsp; <b><?php echo EVENTNAME ?></b></th>
  </tr>
  <tr onclick="window.location.href='Userdaten.php';">
    <td > <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> 
    <b>
<?php
    echo "Helfer $HelferName";
if ($HelferIsAdmin) {
    echo " (Admin)";
}
?> 
    </b>  </td>
  </tr>
  <?php
    if ($HelferIsAdmin) {
        ?>
  <tr onclick="window.location.href='Admin.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Admin Menü</b></td>

  </tr>
        <?php
    }
    ?>
  <tr onclick="window.location.href='MeineSchichten.php';">
    <td>
        <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> <b>Nächste Helferschichten:</b>

                <ul style="display: block; list-style-type: none; margin-left: 20px;margin-top: 0px;margin-bottom: 0px">
<?php
                    //<li>Fr 08:00 Leitung Halle</li>
                    //<li>So 12:00 Abbau</li>
/// Die 3 nächsten Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////
//$HelferID=72;

$db_erg = AlleSchichtenEinesHelfersVonJetzt($db_link, $HelferID);


  $iSQLCount = mysqli_num_rows($db_erg);
  //$iSQLCount = 3;


$iCount = 0;
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC) and $iCount < 3) {
    echo "<li>" . $zeile['Ab'] . " " . $zeile['Was'] . "</li>";
    $iCount++;
}


?>
                </ul>

    </td>
  </tr>

  <!--
  <tr onclick="window.location.href='Ereignisse.php';">
    <td>
        <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> <b>Nächste Ereignisse:</b>
 
                <ul style="display: block; list-style-type: none; margin-left: 20px;margin-top: 0px;margin-bottom: 0px">
                    <li>Sa 20:00 Show im Milchwerk</li>
                    <li>So 15:00 Gaukelgames</li>
                </ul>
 
    </td>
  </tr>
  <tr onclick="window.location.href='Workshop.php';">
    <td>
        <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> <b>Nächste Workshops:</b>

                <ul style="display: block; list-style-type: none; margin-left: 20px;margin-top: 0px;margin-bottom: 0px">
                    <li>Sa 14:00 8 Bälle für Anfänger</li>
                    <li>Sa 15:00 Devilstick Hubschrauber beidseitig</li>
                </ul>

    </td>
  </tr>
  <tr onclick="window.location.href='Wichtig.php';">
    <td>
        <img src="Bilder/PfeilRunter.jpeg" style="width:30px;height:30px;"> <b>Wichtig:</b>
                <ul style="display: block; list-style-type: none; margin-left: 20px;margin-top: 0px;margin-bottom: 0px">
                    <li>Warnung vor Sturm ab 21 Uhr</li>
                </ul>

    </td>
  </tr>
    -->
  <tr onclick="window.location.href='AlleSchichten.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b>Schicht Hinzufügen</b></td>

  </tr>

  <tr onclick="window.location.href='Kalender.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Kalenderansicht</b></td>

  </tr>
  <tr onclick="window.location.href='ReadLog.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Logs</b></td>

  </tr>
  </tr>
  <tr onclick="window.location.href='index.php?logout=1';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Logout</b></td>

  </tr>
 
</table>

</body>
</html>
