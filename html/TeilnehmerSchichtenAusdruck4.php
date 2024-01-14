<html>
<head>
  <title>Admin Stochercon</title>

  <link rel="stylesheet" href="style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="style_mobile.css" media="screen and (max-width:780px)"/>
  <link rel="stylesheet" href="style_print.css" media="print"/>

<meta name="viewport" content="width=480" />
</head>
<body>


<div style="width: 100%;">

<?php

SESSION_START();

require_once('../hidden/konfiguration.php');
include 'SQL.php';

$db_link = mysqli_connect (
			MYSQL_HOST,
			MYSQL_BENUTZER,
			MYSQL_KENNWORT,
			MYSQL_DATENBANK
			);
DatenbankAufDeutsch($db_link);

include '_login.php';



$DienstID=2;
$DienstName ="Anmeldung";


?>

<table id="customers" >
  <tr>
    <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin4.php';"><b>&larrhk;</b></button>  &nbsp; <b><?php echo $DienstName; ?></b></th>
  </tr>
</table>


<?php

echo '<table id="customers" >';


$db_erg = GetSchichtenEinesDienstes($db_link,$DienstID);
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
{
	echo "<tr>";
		echo "<td width=15%>";
		echo "$DienstName<br>";
		echo $zeile['TagVon']."-";
		echo $zeile['ZeitBis']."<br>";
		echo "</td>";

		echo "<td width=30%></td>";

		echo "<td width=10%>";
        	echo $zeile['ZeitVon']."-";
        	echo $zeile['ZeitBis']."<br>";
        	echo "</td>";

        	echo "<td width=30%></td>";
        
		echo "<td width=15%>";
		echo "$DienstName<br>";
        	echo $zeile['TagVon']."-";
        	echo $zeile['ZeitBis']."<br>";
        	echo "</td>";
	echo "</tr>";
	/*
	$db_erg2 = GetDiensteChilds($db_link,$zeile["DienstID"]);
	while ($zeile = mysqli_fetch_array( $db_erg2, MYSQLI_ASSOC))
	{
		echo "<tr><td>";
		echo $zeile["Was"];
        	echo "</td></tr>";
	}
	*/
} 

echo "</table>";



?>
  


<?php

mysqli_free_result( $db_erg );
?>


</body>
</html>
