<?php

namespace Bengelsystem;

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
  <title>Admin <?php echo EVENTNAME ?></title>
  <link rel="stylesheet" href="css/style_common.css"/>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>

<meta name="viewport" content="width=480" />
</head>
<body>
<img src="Bilder/Info.jpeg" width="30px"> Die Ausdrucke sind noch im Aufbau. "Ausdrucke(alles)" versucht alles relevante aus der Datenbank auf einer Seite anzuzeigen, damit auch bei Ausfall der DB noch ein PDF/Ausdruck die Info hat. Ausdrucke Schichten(I) und (II) ist für Papier-Schichten für Teilnehmer mit Abreiss-Zettel gedacht und wurden von 2 Leuten zeitgleich für Tübingen geschrieben und muss noch vereinigt werden. 
<hr>
 
<?php

$AliasHelferID = 0;

if (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
}

if (isset($_POST["AliasHelferID"])) {
    $AliasHelferID = $_POST["AliasHelferID"];
}

if ($AliasHelferID != 0) {
    $_SESSION["AliasHelferID"] = $AliasHelferID;
}

$db_erg = Helferdaten($HelferID);
foreach ($db_erg as $zeile) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}

?>

<div style="width: 100%;">

<table class="commontable">
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button> &nbsp; <b>Ausdrucke HelferDB</b>
  </th>
<tr onclick="window.location.href='Ausdrucke-alles.php';">
    <td > <img src="Bilder/More.jpeg" style="width:30px;height:30px;"> <b>Ausdrucke(alles)</b>  </td> 
    </tr>
    <tr onclick="window.location.href='TeilnehmerSchichtenAusdruck.php';">
    <td > <img src="Bilder/More.jpeg" style="width:30px;height:30px;"> <b>Ausdruck Schichten(I)</b>  </td> </tr>
    <tr onclick="window.location.href='TeilnehmerSchichtenAusdruck2.php';">
    <td > <img src="Bilder/More.jpeg" style="width:30px;height:30px;"> <b>Ausdruck Schichten(II)</b>  </td> </tr>
</table>
<button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button> 
</body>
</html>
