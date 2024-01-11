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
  <title>Helfer <?php echo EVENTNAME ?></title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>

  <meta name="viewport" content="width=480" />
 </head>
 <body>
<div style="width: 100%;">
<?php

/// Detailinformation zu ausgewaehlten Schicht Holen

if (isset($_GET['InfoAlleSchichtID'])) {
    $InfoAlleSchichtID = $_GET['InfoAlleSchichtID'];
    unset($InfoMeineSchichtID);
    //echo "<b>". $SchichtID . "</b><br>";

    $zeile = DetailSchicht($db_link, $InfoAlleSchichtID);

    $Was = $zeile['Was'];
    $Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
    $LeiterHandy =  $zeile['Handy'];
    $LeiterEmail =  $zeile['Email'];
    $Info = $zeile['Info'];



    // Beteiligte Helfer Holen
    $db_erg = BeteiligteHelfer($db_link, $InfoAlleSchichtID);


    $x = 0;

    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $MitHelferID[$x] = $zeile['HelferID'];
        $MitHelfer[$x] = $zeile['Name'];
        $MitHelferHandy[$x] = $zeile['Handy'];
        $x++;
    }
}


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

if (isset($_POST['HelferID'])) {
    $HelferID = $_POST['HelferID'];
}
if (isset($_POST['ShowHelfer'])) {
    $HelferID = $_POST['HelperSearch'];
}

$_SESSION["HelferID"] = $HelferID;




















?>



<form method="post" action="DetailsSchichten.php#Info">  
<?php





/// Ausgabe auf Deutsch umstellen
/////////////////////////////////////////////////////////////////////////

    DatenbankAufDeutsch($db_link);

/// Alle Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////


echo '<table id="customers">';



            echo "<th>" . $Was . "</th>";
            echo "<tr><td>";

            //echo "<p><button name='Del' value='CloseInfo'><b>&larrhk;</b></button><br>";
            echo "<b>Beschreibung:</b><br>";
            echo $Info . "<br><br>";
            echo "<b>Ort:</b><br>" . $Wo . "<br><br>";
            echo "<b>Dauer:</b><br>" . $Dauer . "<br><br>";
            echo "<b>Ansprechparter:</b><br>" . $Leiter . ", ";
            echo $LeiterHandy . ", ";
            echo $LeiterEmail . "<br><br>";
            echo "<b>Helfer der Schicht:</b><br>";
            $x = 0;
            $arrayLength = count($MitHelfer);
while ($x < $arrayLength) {
    echo "ID:" . $MitHelferID[$x] . ", ";
    echo $MitHelfer[$x] . ", ";
    echo $MitHelferHandy[$x] . "<br>";
    $x++;
}

            echo "</td></tr>\n";



    echo "</table>";









mysqli_free_result($db_erg);


?>
 
 </form> 
 </div>
 
 </body>
</html>
