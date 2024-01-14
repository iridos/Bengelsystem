<html>
<html>
<head>
  <title>Helfer Stochercon Home</title>
  <link rel="stylesheet" href="style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="style_mobile.css" media="screen and (max-width:780px)"/>
  <script src=js/helferdb.js></script>
<meta name="viewport" content="width=480" />
</head>
<body>

<?php
SESSION_START();

require_once ('../hidden/konfiguration.php');
include 'SQL.php';


$db_link = mysqli_connect (
                     MYSQL_HOST, 
                     MYSQL_BENUTZER, 
                     MYSQL_KENNWORT, 
                     MYSQL_DATENBANK
                    );

DatenbankAufDeutsch($db_link);

/// Logout
////////////////////////////////////////////////////////
if(isset($_GET['logout']))
{
		unset($_SESSION["HelferID"]);
		//$_POST['login'] = 1;
} 

/// Login
////////////////////////////////////////////////////////
if(isset($_POST['login'])) 
{
	$messages = [];
	// Eingaben überprüfen:
	//if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
	//  $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
	//}
        if (isset ($_POST['helfer-name'])) {	
	  $HelferName = $_POST['helfer-name'];
        }
	$HelferEmail = $_POST['helfer-email'];
	$HelferPasswort = $_POST['helfer-passwort']; 

	if(empty($messages)) 
	{
		HelferLogin($db_link,$HelferEmail,$HelferPasswort, 0 );
	} 
	else 
	{
		// Fehlermeldungen ausgeben:
		echo '<div class="error"><ul>';
		foreach($messages as $message) {
		echo '<li>'.htmlspecialchars($message).'</li>';
		}
		echo '</ul></div>';
	}
	
}

if(!isset($_SESSION["HelferID"]))
{

?>
<form method="post" action="#Info">

  <fieldset>
    <legend>Login</legend>
    
    <table border="0" style="border: 0px solid black;">
            <tr> 	
              <td style="border: 0px solid black;">Email</td></tr><tr><td style="border: 0px solid black;">
              <input name="helfer-email" type="text" size=35 value="<?=htmlspecialchars($HelferEmail??'')?>" required>
              </td>
            <tr>
            <tr> 	
              <td style="border: 0px solid black;">Passwort</td></tr>
              <tr><td style="border: 0px solid black;">
              <input name="helfer-passwort" id="helfer-passwort" type="password" size=35 value="<?=htmlspecialchars($HelferHandy??'')?>" required>
              </td><td style="border: 0px solid black;">
              <input type="button" value="Passwort zeigen" style="width:180px !important" onclick="showPassword('helfer-passwort')">
              </td>
            <tr>
	</table>
    
    
  </fieldset>
  
  <p><button style="width: 100px" name="login" value="1">Login</button></p>


 </form> 
<?php
 exit;	
}


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

$db_erg = Helferdaten($db_link,$HelferID);
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
{
    $HelferName=$zeile['Name']; 
    $HelferIsAdmin=$zeile['Admin'];
}

?>

<div style="width: 100%;">

<table id="customers" >
  <tr onclick="window.location.href='Info4.php';">
    <th><img src="Bilder/Info.jpeg" style="width:30px;height:30px;"> &nbsp; <b>Stochercon 2023</b></th>
  </tr>
  <tr onclick="window.location.href='Userdaten4.php';">
    <td > <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> 
    <b>
<?php
        if($HelferIsAdmin)
        {
            echo "Admin ";
        }
        else
        {
            echo "Helfer ";
        }
        echo $HelferName;
?> 
    </b>  </td>
  </tr>
  <tr onclick="window.location.href='MeineSchichten4.php';">
    <td>
        <img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"> <b>Nächste Helferschichten:</b>

                <ul style="display: block; list-style-type: none; margin-left: 20px;margin-top: 0px;margin-bottom: 0px">  
<?php      
                    //<li>Fr 08:00 Leitung Halle</li>
                    //<li>So 12:00 Abbau</li>
/// Die 3 nächsten Schichten Des Helfers Anzeigen
////////////////////////////////////////////////////////
//$HelferID=72;
	
$db_erg = AlleSchichtenEinesHelfersVonJetzt($db_link,$HelferID);


  $iSQLCount = mysqli_num_rows($db_erg);
  //$iSQLCount = 3;


$iCount=0;
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC) and $iCount<3)
{
    echo "<li>". $zeile['Ab'] . " ". $zeile['Was'] . "</li>";
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
  <tr onclick="window.location.href='AlleSchichten4.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b>Schicht Hinzufügen</b></td>

  </tr>

  <tr onclick="window.location.href='Kalender.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Kalenderansicht</b></td>

  </tr>
  <tr onclick="window.location.href='ReadLog.php';">
    <td><img src="Bilder/PfeilRechts2.jpeg" style="width:30px;height:30px;"><b> Logs</b></td>

  </tr>
  <?php
  if ($HelferIsAdmin)
  {
  ?>    
  <tr onclick="window.location.href='Admin4.php';">
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
