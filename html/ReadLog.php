<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require_once 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

?>
<!doctype html>
<html>
<head>
  <title>Helfer - Logs </title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>

<!--meta name="viewport" content="width=480" /-->
<?php

  // print top of page if we are logged in and not redirecting
  echo " </head> \n <body> \n";
  echo '<button name="BackHelferdaten" value="1"  onclick="window.location.href = \'index.php\';"><b>&larrhk;</b></button>' . "\n";
  echo "<b>" . EVENTNAME . "</b><br>";
  echo '<H1> Helferdaten - Log </H1>';
  echo '<p>Hier werden alle Aktionen, die einen Helfer betreffen aufgelistet</p>';
  echo '<table class="commontable">' . "\n";

  //check for admin status
  $HelferID = $_SESSION["HelferID"];
  $AdminID = $_SESSION["AdminID"];
  $db_link = mysqli_connect(MYSQL_HOST, MYSQL_BENUTZER, MYSQL_KENNWORT, MYSQL_DATENBANK);
  DatenbankAufDeutsch($db_link);

  $db_erg = Helferdaten($db_link, $HelferID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}

  // Wir suchen nach HelferID:<zahl> - wenn das Admin Flag gesetzt ist, kann man den Suchstring auf etwas anderes aendern
  $regex = "\(HelferID:$HelferID\)";
if ($HelferIsAdmin) {
    if (isset($_POST['suche']) || isset($_GET['suche'])) {
        $regex = $_POST['suche'] . $_GET['suche'];
    }
    echo "<form>";
    echo "Nur Admins: Suchbegriff (Per Default der selbe Suchtext, wie bei nicht-Admin Helfern):<input name='suche' type='text' size=35 value='$regex'><br> \n";
    echo "Der Suchbegriff ist ein <a href='https://de.wikipedia.org/wiki/Regul%C3%A4rer_Ausdruck'>Regul&auml;rer Ausdruck</a><br><br>";
    echo "</form>";
}

  $regex = "/" . "$regex" . "/";
foreach (file(LOGFILE) as $line) {
    // echo "not: $line<br>\n";
    if (preg_match($regex, $line, $matches)) {
        echo "<tr><td> $line</td></tr>";
    }
}
?>
</table>
</body>
</html>
