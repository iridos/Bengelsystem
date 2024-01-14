<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once ('../hidden/konfiguration.php');
include 'SQL.php';
$db_link=ConnectDB();
include '_login.php';
// das hier muss nicht unbedingt eine Adminseite sein
if($AdminStatus != 1) {
 //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
 echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
 exit;
}
?>
<!doctype html>
<html>
 <head>
  <title>Admin Stochercon</title>

  <link rel="stylesheet" href="style_common.css"/>
  <link rel="stylesheet" href="style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
 </head>
 <body>
<div style="width: 100%;">
<?php


DatenbankAufDeutsch($db_link);
                    
$DienstID =$_SESSION["DienstID"];
$SchichtID =$_SESSION["SchichtID"];

$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

$_SESSION["HelferID"] = $HelferID;


 if(isset($_POST['ShowSchicht'])) {
	 $SchichtID=$_POST['SchichtSearch'];
 }
 if(isset($_POST['SchichtSearch'])) {
	 $SchichtID=$_POST['SchichtSearch'];
 }

 if(isset($_POST['ShowSchichten'])) {
	 $DienstID=$_POST['DienstSearch'];
 }

 if(isset($_POST['DienstSearch'])) {
	 $DienstID=$_POST['DienstSearch'];
     $SchichtID=0;
 }




// Dienste Anzeigen
////////////////////////////////////////////////////////

?>
<table border="0" class='commontable'>    
    <tr><th>ID</th><th>Dienst</th><th>Wo</th><th>Leiter</th><th>Gruppe</th><th>Stunden(Soll/Haben)</th><th>Helferlevel</th><th>Info</th></tr>

<?php

$db_erg = GetDienste($db_link);

$Was="";
$Wo="";
$Info="";
$Leiter="";
$Gruppe="";
$HelferLevel="";
$i=0;

while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
{
  $DienstID=$zeile['DienstID'];
  $Was=$zeile['Was'];
  $Wo=$zeile['Wo'];
  $Info=$zeile['Info'];
  $Leiter=$zeile['Leiter'];
  $Gruppe=$zeile['ElternDienstID'];
  $HelferLevel=$zeile['HelferLevel'];
  echo "<tr><td>$DienstID</td><td>$Was</td><td>$Wo</td><td>$Leiter</td><td>$Gruppe</td><td>TODO:Stunden</td><td>$HelferLevel</td><td>";
  // display:none ist eigentlich im css definiert, wird aber von irgend etwas ueberschrieben
  echo "
  <input type='checkbox' id='spoiler$i' style='display:none'/>
  <label for='spoiler$i'>Info</label>
  <div class='spoiler'>";
  echo "$Info";
  echo "</td></tr>"; 
  $i+=1;
}

mysqli_free_result( $db_erg );


//$_SESSION["DienstID"] = $DienstID; 
//$_SESSION["SchichtID"] = $SchichtID;


?>
 

 </div>
 
 </body>
</html>
