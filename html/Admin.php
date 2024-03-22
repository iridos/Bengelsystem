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

<?php

DatenbankAufDeutsch();

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

$zeilen = Helferdaten($HelferID);
foreach ($zeilen as $zeile) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}

?>

<div style="width: 100%;">

<table class="commontable">
    <th>
       <button name="BackHelferdaten" value="1" onclick="window.location.href = 'index.php';">
          <b>&larrhk;</b>
       </button> &nbsp; 
       <b>Admin HelferDB <?php echo  EVENTNAME; ?></b>
  </th>
  <tr> 


  <tr onclick="window.location.href='AdminDienste.php';">
    <td>
     <a class="fallbacklink" href='AdminDienste.php'>
        <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
        <b> Dienste und Schichten verwalten</b>
     </a>
    </td>
  </tr>
    <tr onclick="window.location.href='CreateHelfer.php';"> <td>
    <img src="Bilder/More.jpeg" style="width:30px;height:30px;"><b>Seite zur Helfer selbst-Registrierung</b>
    </td> </tr>

    <tr onclick="window.location.href='EmailZuToken.php';"> <td>
    <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;"><b>Helfer per Link einladen</b>
    </td> </tr>
  <tr onclick="window.location.href='AdminHelferUebersicht.php';">
    <td>
       <a class="fallbacklink" href='AdminHelferUebersicht.php'>
          <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
          <b>Helferübersicht und als Admin &auml;ndern</b>
       </a>
    </td>
  </tr>

   <th>
      <b>Helfer als Admin &auml;ndern:<b> 
      <form style="display:inline-block;" method=post>
      <select style="height:33px;width:350px;font-size:20" name="AliasHelferID" id="AliasHelferID" onchange="submit()">
<?php
    $zeilen = HelferListe();
foreach ($zeilen as $zeile) {
    if ($AliasHelferID != $zeile['HelferID']) {
        echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
    } else {
        echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
    }
}


?>
    </select></form>
    </b>
    </td>
   </th>
<tr><!--td-->
<!--<table class="innertable" style="padding:15px"><!-
- inner table for indent--> 
<tr onclick="window.location.href='AdminUserdaten.php';">
    <!--td class="invis"></td-->
    <td>
      <img src="Bilder/dot.png" width="30px" height="2px">
      <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
      <b> Helferdaten &auml;ndern</b>
    </td>
  </tr>
  <tr onclick="window.location.href='AdminMeineSchichten.php';">
    <td>
        <img src="Bilder/dot.png" width="30px" height="2px">
        <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
        <b>Schichten Anzeigen/Löschen</b>
    </td>
  </tr>
  <tr onclick="window.location.href='AdminAlleSchichten.php';">
    <td>
        <img src="Bilder/dot.png" width="30px" height="2px">
        <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
        <b> Schichten Hinzufügen</b>
    </td>
  </tr>
<!--</table></td> </tr>  inner table for indent end-->
  <!--<tr><th>Weiteres</th></tr>-->
    <tr onclick="window.location.href='Kalender-all.php';">
    <td><img src="Bilder/More.jpeg" style="width:30px;height:30px"><b> Admin Kalenderansicht</b> </td>
    </tr>
    <tr onclick="window.location.href='Ausdrucke.php';">
    <td > <img src="Bilder/More.jpeg" style="width:30px;height:30px;"> <b>Ausdrucke</b>  </td> 
    </tr>
</table>
<p>
   <img src="Bilder/Info.jpeg" width="25px" height="25px">
   Dienst: z.B. Badgekontrolle Eingang A. Schicht: ein Dienst zu einer bestimmten Zeit zB 9-12 Uhr
</p>
<button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';">
  <b>&larrhk;</b>
</button>
</body>
</html>
