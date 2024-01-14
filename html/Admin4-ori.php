<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once ('../hidden/konfiguration.php');
include 'SQL.php';
$db_link=ConnectDB();
include '_login.php';

if($AdminStatus != 1) {
 //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
 echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
 exit;
}
?>
<!doctype html>
<head>
  <title>Admin Stochercon</title>
  <link rel="stylesheet" href="style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="style_mobile.css" media="screen and (max-width:780px)"/>

<meta name="viewport" content="width=480" />
</head>
<body>

<?php

DatenbankAufDeutsch($db_link);

$AliasHelferID=0;

//echo "AliasHelfer=$AliasHelferID <br>";
if(isset($_SESSION["AliasHelferID"]))
{
	$AliasHelferID = $_SESSION["AliasHelferID"];
}

//echo "AliasHelfer=$AliasHelferID <br>";

if(isset($_POST["AliasHelfer"]))
{
	$AliasHelferID = $_POST["AliasHelfer"];
	//echo "post<br>";
}

if($AliasHelferID!=0)
{
	$_SESSION["AliasHelferID"]=$AliasHelferID;
}
//echo "AliasHelfer=$AliasHelferID <br>";

$db_erg = Helferdaten($db_link,$HelferID);
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
{
    $HelferName=$zeile['Name']; 
    $HelferIsAdmin=$zeile['Admin'];
}

?>

<div style="width: 100%;">

<table id="customers" >
  <tr>
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button> &nbsp; <b>Admin Stochercon 2023</b>
  </th>
  </tr>
  <tr> 
   <td><b>Helfer &auml;ndern:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;font-size:20" name="AliasHelfer" id="AliasHelfer" onchange="submit()">
<?php
	$db_erg = HelferListe($db_link);
	while($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
	{
		if ($AliasHelferID!=$zeile['HelferID'])	
		{
			echo "<option value='".$zeile['HelferID']."'>".$zeile['Name']."</optionen>";
		}
		else
		{
			echo "<option value='".$zeile['HelferID']."' selected='selected'>".$zeile['Name']."</optionen>";
		}
	}


    ?>
    </select></form>
    </td>
   </tr>
<tr onclick="window.location.href='AdminUserdaten4.php';">
    <td><img src="Bilder/PfeilRunter.jpeg" style="width:30px;height:30px;"><b> Helferdaten &auml;ndern</b></td>

  </tr>
  <tr onclick="window.location.href='AdminMeineSchichten4.php';">
    <td><img src="Bilder/PfeilRunter.jpeg" style="width:30px;height:30px;"><b> Schichten Anzeigen/Löschen</b></td>
  </tr>
  <tr onclick="window.location.href='AdminAlleSchichten4.php';">
    <td><img src="Bilder/PfeilRunter.jpeg" style="width:30px;height:30px;"><b> Schichten Hinzufügen</b></td>
  </tr>
  <tr><th>Weiters</th></tr>
    <tr onclick="window.location.href='EmailZuToken.php';">
    <td>
    <img src="Bilder/More.jpeg" style="width:30px;height:30px;"><b>Einladungs-Links erstellen/versenden</b>
    </td>
    </tr>
    <tr onclick="window.location.href='Ausdrucke4.php';">
    <td > <img src="Bilder/More.jpeg" style="width:30px;height:30px;"> 
    <b>Ausdrucke</b>  
  </td>
  </tr>

  <tr onclick="window.location.href='AdminDienste4.php';">
    <td><img src="Bilder/PfeilRunter.jpeg" style="width:30px;height:30px;"><b> Dienste und Schichten</b></td>
  </tr>
   
</table>
<button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button> 
</body>
</html>
