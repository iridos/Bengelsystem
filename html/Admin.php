<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
require '_functions.php';
$db_link = ConnectDB();
$pagename  = "Admin-Funktionen";  // name of this page
$backlink  = "index.php";         // back button in table header from table header
$header = PageHeader($pagename);
$tablehead = TableHeader($pagename,$backlink);
require '_login.php';

if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
$AliasHelferID = 0;

if (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
}

if (isset($_POST["AliasHelferID"])) {
    $AliasHelferID = $_POST["AliasHelferID"];
    header("Location: " . $_SERVER['PHP_SELF']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST from _login.php after login
    //echo var_dump($_POST);
    //header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($AliasHelferID != 0) {
    $_SESSION["AliasHelferID"] = $AliasHelferID;
}
$db_erg = Helferdaten($db_link, $HelferID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}
echo $header; // muss nach redirect-headern fuer POST ausgegeben werden
echo $tablehead; // variablen aus _login.php
?>


<table class="commontable">
  <tr>
  <tr onclick="window.location.href='AdminHelferLevel.php';">
    <td>
     <a class="fallbacklink" href='AdminHelferLevel.php'>
        <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
        <b>HelferLevel verwalten und Accounterstellung</b>
     </a>
    </td>
  </tr>
  <tr>
  <tr onclick="window.location.href='AdminDienste.php';">
    <td>
     <a class="fallbacklink" href='AdminDienste.php'>
        <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
        <b> Dienste und Schichten verwalten</b>
     </a>
    </td>
  </tr>
    <!--tr onclick="window.location.href='CreateHelfer.php';"> <td>
    <img src="Bilder/More.jpeg" style="width:30px;height:30px;"><b>Seite zur selbst-Registrierung</b>
    </td> </tr-->

    <tr onclick="window.location.href='EmailZuToken.php';"> <td>
    <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;"><b>persönliche Einladungslink(s) generieren</b>
    </td> </tr>
  <tr onclick="window.location.href='AdminHelferUebersicht.php';">
    <td>
       <a class="fallbacklink" href='AdminHelferUebersicht.php'>
          <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
          <b>Helferübersicht und -verwaltung<!--br>(Anm: dieses Menü soll die Punkte unterhalb ablösen)</b-->
       </a>
    </td>
  </tr>

<!--   <th>
      <b>Als Admin &auml;ndern:<b> 
      <form style="display:inline-block;" method=post>
      <select style="height:33px;width:350px;font-size:20" name="AliasHelferID" id="AliasHelferID" onchange="submit()">
-->
<!--?php
    $db_erg = HelferListe($db_link);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if ($AliasHelferID != $zeile['HelferID']) {
        echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
    } else {
        echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
        $selectedSet = true;
    }
}
if( ! isset($selectedSet) or ! $selectedSet) {
  echo "<option value='none' selected='selected'>Bitte auswählen</optionen>";
}


?--><!--
    </select></form>
    </b>
    </td>
   </th>
<tr>
<tr onclick="window.location.href='AdminUserdaten.php';">
    <td>
      <img src="Bilder/dot.png" width="30px" height="2px">
      <img src="Bilder/PfeilRechts.jpeg" style="width:30px;height:30px;">
      <b> Personendaten &auml;ndern</b>
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
-->
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
