<!doctype html>
<html lang=de>
<head>
  <title>Helfer Drop am See Home</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <script src=js/helferdb.js></script>
<meta name="viewport" content="width=480" />
<meta charset="utf-8">
</head>
<body>

<?php
SESSION_START();

require_once 'konfiguration.php';
require 'SQL.php';

$pdo = ConnectDB();

DatenbankAufDeutsch();

require '_login.php';

// wird von _login.php miterledigt
// TODO: hier wird HelferIsAdmin verwendet, woanders ist es AdminStatus
//$db_erg = Helferdaten($db_link,$HelferID);
//while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
//{
//    $HelferName=$zeile['Name'];
//    $HelferIsAdmin=$zeile['Admin'];
//}

?>

<div style="width: 100%;">

<table id="customers" >
  <tr onclick="window.location.href='Info.php';">
    <th><img src="Bilder/Info.jpeg" style="width:30px;height:30px;"> &nbsp; <b>Drop am See 2023</b></th>
  </tr>
  <tr onclick="window.location.href='Userdaten.php';">
    <td > <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> 
    <b>
<?php
if ($HelferIsAdmin) {
    echo "Admin ";
} else {
    echo "Helfer ";
}
        echo $HelferName;
?> 
    </b>  </td>
  </tr>
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

$schichten = AlleSchichtenEinesHelfersVonJetzt($HelferID);


  $iSQLCount = count($schichten);
  //$iSQLCount = 3;


$iCount = 0;
foreach ($schichten as $zeile) {
    echo "<li>" . $zeile['Ab'] . " " . $zeile['Was'] . "</li>";
    $iCount++;
    if(iCount>2) break;
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
  <?php
    if ($HelferIsAdmin) {
        ?>    
  <tr onclick="window.location.href='Admin.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Admin</b></td>

  </tr>
        <?php
    }
    ?>  

  </tr>
  <tr onclick="window.location.href='index.php?logout=1';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Logout</b></td>

  </tr>
    
</table>

</body>
</html>
