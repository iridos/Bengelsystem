<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once ('konfiguration.php');
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
<html>
 <head>
  <title>Helfer Drop am See Alle Schichten</title>
  
  
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />
  <script src="js/jquery-3.7.1.min.js" type="text/javascript"></script> 
  <script src="js/helferdb.js" type="text/javascript"></script> 
  <script> collapse_table_rows();
 </script>
 </head>
 <body>
 <button name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button>   
<div style="width: 100%;">
<?php
                  
/// Detailinformation zu ausgewaehlten Schicht Holen
////////////////////////////////////////////////////////
if(isset($_POST['CloseInfo']))
{
	UNSET($InfoMeineSchichtID);
	UNSET($InfoAlleSchichtID);
}
if(isset($_POST['InfoMeineSchichtID']))
{
	$InfoMeineSchichtID = $_POST['InfoMeineSchichtID'];
	UNSET($InfoAlleSchichtID);
	//echo "<b>". $SchichtID . "</b><br>";
	
    $zeile = DetailSchicht($db_link,$InfoMeineSchichtID);

	$Was = $zeile['Was'];
	$Wo = $zeile['Wo'];
    $Dauer = $zeile['Dauer'];
    $Leiter = $zeile['Name'];
	$LeiterHandy =  $zeile['Handy'];
	$LeiterEmail =  $zeile['Email'];
	$Info = $zeile['Info'];

}


if(isset($_GET['InfoAlleSchichtID']))
{
	$InfoAlleSchichtID = $_GET['InfoAlleSchichtID'];
	UNSET($InfoMeineSchichtID);
	//echo "<b>". $SchichtID . "</b><br>";
	
    $zeile = DetailSchicht($db_link,$InfoAlleSchichtID);
	
    $Was = $zeile['Was'];
	$Wo = $zeile['Wo'];
    $Dauer=$zeile['Dauer'];
	$Leiter = $zeile['Name'];
	$LeiterHandy =  $zeile['Handy'];
	$LeiterEmail =  $zeile['Email'];
	$Info = $zeile['Info'];
		
	
	
	// Beteiligte Helfer Holen
    $db_erg = BeteiligteHelfer($db_link,$InfoAlleSchichtID);

	
	$x=0;
	
	while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
	{
		$MitHelferID[$x] = $zeile['HelferID'];
		$MitHelfer[$x] = $zeile['Name'];
		$MitHelferHandy[$x]= $zeile['Handy'];
		$x++;
	}
	
	
}

if(isset($_GET['ZeitBereich']))
{
	$ZeitBereich = $_GET['ZeitBereich'];	
}
else
{
    $ZeitBereich = 1;	    
}

function HelferAuswahlButton($db_link,$AliasHelferID){
echo '<b>Helfer w&auml;hlen:<b> <form style="display:inline-block;" method=post><select style="height:33px;width:350px;" name="AliasHelfer" id="AliasHelfer" onchange="submit()">';
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
echo '</select></form>';
}

if(isset($_POST['AliasHelfer'])) {
   $AliasHelferID=$_POST['AliasHelfer'];
}elseif(isset($_SESSION["AliasHelferID"])){
   $AliasHelferID = $_SESSION["AliasHelferID"];
}else{
   HelferAuswahlButton($db_link,$AliasHelferID);
exit;
}
HelferAuswahlButton($db_link,$AliasHelferID);

$_SESSION["AliasHelferID"]=$AliasHelferID;
$AdminID = $_SESSION["AdminID"];

$db_erg=Helferdaten($db_link,$HelferID);
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
{
	$HelferName=$zeile['Name'];
}

// Helferliste Anzeigen
////////////////////////////////////////////////////////

?>




<form method="post" action="AdminAlleSchichten.php#Info">  
<?php



// Neu Schicht fuer Helfer Eintragen
///////////////////////////////////////////////////////////
if(isset($_POST['plusschicht'])) {
	
	$messages = [];
	$SchichtId = $_POST['plusschicht'];
  
	// Eingaben überprüfen:

	//  if(!preg_match('/^[a-zA-Z]+[a-zA-Z0-9._]+$/', $HelferName)) {
	//    $messages[] = 'Bitte prüfen Sie die eingegebenen Namen';
	//  }


	if(empty($messages)) 
	{
		// Helfer Schicht zuweisen
		$db_erg = HelferSchichtZuweisen($db_link,$AliasHelferID,$SchichtId,$AdminID);

		// Erfolg vermelden und Skript beenden, damit Formular nicht erneut ausgegeben wird
		$HelferName = '';
		$HelferEmail = '';
		$HelferHandy = '';
		//die('<div class="Helfer wurde angelegt.</div>');
	} 
	else 
	{
		// Fehlermeldungen ausgeben:
		echo '<div class="error"><ul>';
		foreach($messages as $message) 
		{
			echo '<li>'.htmlspecialchars($message).'</li>';
		}
    echo '</ul></div>';
	}
   
 
}

if(isset($_POST['minusschicht'])) {
// Mich aus Schicht entfernen
        $messages = [];

        $SchichtID = $_POST['minusschicht'];

        if(empty($messages))
        {
                // Helfer aus Schicht entfernen
                $db_erg = HelferVonSchichtLoeschen_SchichtID($db_link,$AliasHelferID,$SchichtID,$AdminID);

        }
        else
        {
                // Fehlermeldungen ausgeben:
                echo '<div class="error"><ul>';
                foreach($messages as $message)
                {
                        echo '<li>'.htmlspecialchars($message).'</li>';
                }
    echo '</ul></div>';
        }



}

/// Ausgabe auf Deutsch umstellen
/////////////////////////////////////////////////////////////////////////

    DatenbankAufDeutsch($db_link);


// Zusammenfassung Eigener Schichten
 $db_erg = SchichtenSummeEinesHelfers($db_link,$AliasHelferID);
 $zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC);
 
    echo '<table  id="customers"><tr class="header"><th onclick="window.location.href=\'AdminMeineSchichten.php\'">';
    echo " Dienstplan von $HelferName (Zusammenfassung)<br>";
    echo $zeile['Anzahl'];
    echo " Schichten insgesamt ";
    echo $zeile['Dauer']/3600;
    echo " Stunden";
    echo '</th></tr></table><br><br>';
/// Schichten Auswahl
////////////////////////////////////////////////////////
$addschicht =$_SESSION["addschicht"];
$dienstsort =$_SESSION["dienstsort"];



if(isset($_POST['addschicht']) && $_POST['addschicht']=='1')
{
	$addschicht='1';
	$dienstsort='1';
}
if(isset($_POST['addschicht']) && $_POST['addschicht']=='2')
{
	$addschicht='2';
	$dienstsort='2';
}
if(isset($_POST['addschicht']) && $_POST['addschicht']=='0')
{
	$addschicht='0';
}

$_SESSION["addschicht"] =$addschicht;
$_SESSION["dienstsort"] =$dienstsort;

//echo "<br>Detail=".$addschicht."<br>";

if($addschicht=='0')
{
	echo "<p><b>Schichten Hinzufügen geordnet nach</b>";
	echo "<button name='addschicht' value='1'>Tage</button>";
	echo "<button name='addschicht' value='2'>Dienste</button></p>";
}

//echo "InfoAlleSchichtID ".$InfoAlleSchichtID;

if($addschicht!='0')
{
    //$db_erg = AlleSchichten($db_link,$dienstsort);
    //$db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-18 00:00:00","2023-05-19 00:00:00");
    if ($ZeitBereich==1)  // Alle
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2000-05-18 00:00:00","2200-05-19 00:00:00",-1);
    }
    if ($ZeitBereich==2)  // Davor
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2000-05-18 00:00:00","2023-05-18 00:00:00",-1);
    }
    if ($ZeitBereich==3)  // Do
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-18 00:00:00","2023-05-19 00:00:00",-1);
    }
    if ($ZeitBereich==4)  // Fr
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-19 00:00:00","2023-05-20 00:00:00",-1);
    }
    if ($ZeitBereich==5)  // Sa
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-20 00:00:00","2023-05-21 00:00:00",-1);
    }
    if ($ZeitBereich==6)  // So
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-21 00:00:00","2023-05-22 00:00:00",-1);
    }
    if ($ZeitBereich==7)  // Danach
    {
        $db_erg = AlleSchichtenImZeitbereich($db_link,"2023-05-22 00:00:00","2223-05-22 00:00:00",-1);
    }
    // fuer Anzahlanzeige in Ueberschrift
    $iAlleSchichtenCount = AlleSchichtenCount($db_link);
    $iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);


	//echo "<p><button name='addschicht' value='0'><b>&larrhk;</b></button></p>";
	echo '<table  id="customers">';
    echo "<thead>";  
	echo "<tr>";
	echo "</tr><th  colspan='7'>". "Alle Schichten der Con (" . $iBelegteSchichtenCount."/".$iAlleSchichtenCount. ")</th></tr>";

    /*
	if ($dienstsort=='1')
	{
		echo "<th>". "Dienst" . "</th>";
	}
	else
	{
		echo "<th>". "Von" . "</th>";
	}
    */
    if ($ZeitBereich==1) 
    {
        echo "<th style='width:100px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=1\"'>". "Alle" . "</th>";
    }
    else
    {
        echo "<th style='width:100px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=1\"'>". "Alle" . "</th>";
    }
    if ($ZeitBereich==2) 
    {
        echo "<th style='width:100px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=2\"'>". "Davor" . "</th>";
    }
    else
    {
        echo "<th style='width:100px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=2\"'>". "Davor" . "</th>";
    }	
    if ($ZeitBereich==3) 
    {
        echo "<th style='width:50px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=3\"'>". "Do" . "</th>";
    }
    else
    {
        echo "<th style='width:50px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=3\"'>". "Do" . "</th>";
    }	
    if ($ZeitBereich==4) 
    {
        echo "<th style='width:50px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=4\"'>". "Fr" . "</th>";
    }
    else
    {
        echo "<th style='width:50px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=4\"'>". "Fr" . "</th>";
    }	
    if ($ZeitBereich==5) 
    {
        echo "<th style='width:50px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=5\"'>". "Sa" . "</th>";
    }
    else
    {
        echo "<th style='width:50px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=5\"'>". "Sa" . "</th>";
    }	
    if ($ZeitBereich==6) 
    {
        echo "<th style='width:50px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=6\"'>". "So" . "</th>";
    }
    else
    {
        echo "<th style='width:50px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=6\"'>". "So" . "</th>";
    }
    if ($ZeitBereich==7) 
    {
        echo "<th style='width:100px; background-color:#0000FF' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=7\"'>". "Danach" . "</th>";
    }
    else
    {
        echo "<th style='width:100px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=7\"'>". "Danach" . "</th>";
    }		
    //echo "<th style='width:100px' onclick='window.location.href=\"AlleSchichten.php?ZeitBereich=2\"'>". "Davor" . "</th>";
	//echo "<th style='width:50px'>". "Do" . "</th>";
	//echo "<th style='width:50px'>". "Fr" . "</th>";
	//echo "<th style='width:50px'>". "Sa" . "</th>";
    //echo "<th style='width:50px'>". "So" . "</th>";
    //echo "<th style='width:100px'>". "Danach" . "</th>";
    
    echo "</tr>";
    echo "</thead>";  
  
    $OldTag = "";
    $OldWas = "";
    # um Zeilen mit von mir belegten Schichten hervorzuheben
    $MeineDienste = SchichtIdArrayEinesHelfers($db_link,$AliasHelferID);
    //print_r($MeineDienste); 
    
    echo '</table>';
    echo '<table  id="customers">';

    while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC))
	{


		if ($dienstsort=='1')
		{
			$Tag = $zeile['Tag'];
		
			if ($Tag!=$OldTag)
			{
				echo "<tr class='header'><th colspan='5' >";
				echo $Tag;
				echo "</th></tr>";
				$OldTag = $Tag;
			}
		}
		else
		{
			$Was = $zeile['Was'];
		
			if ($Was!=$OldWas)
			{
				echo "<tr class='header'><th  colspan='7' style='width:100%'>";
				echo $Was;
				echo "</th>";
                /*
	            echo "<th style='width:100px'>". "Von" . "</th>";
	            echo "<th style='width:130px'>". "Bis" . "</th>";
	            echo "<th style='width:90px'>". "Ist/Soll" . "</th>";
	            echo "<th style='width:90px'>". "Add" . "</th>";
                */                
                echo "</tr>";
				$OldWas = $Was;
			}
		}		
		$Color="red";
		if ( $zeile['Ist'] > 0 )
		{
			$Color="yellow";
		} 
		if ( $zeile['Ist'] >= $zeile['Soll'] )
		{
			$Color="green";
		} 
		$Von = $zeile['Ab'];
		$Bis = $zeile['Bis'];
		if ( substr($Von,0,2) == substr($Bis,0,2))
		{
			$Bis = substr($Bis,2);	
		} 
		$Von = substr($Von,2);	
		
              // Meine Schichten gruen einfaerben
                if(in_array($zeile['SchichtID'], $MeineDienste)) { 
                     $rowstyle = ' style="background-color:lightgreen" ';
                     $regtext  = 'Meine!';
                   } else {
                     // dummy-style, um SchichtID unsichtbar im Tag anzuzeigen
                     $rowstyle = 'style="dummy:'.$zeile['SchichtID'].'"';
                     $regtext  = '';         
                   }
                    
                echo '<tr '.$rowstyle.'onclick="window.location.href=\'DetailsSchichten.php?InfoAlleSchichtID='.$zeile['SchichtID'].'#Info\';" >';
        
		if ($dienstsort=='1')
		{
			echo "<td>". $zeile['Was'] . "</td>";
		}
		else
		{
			echo "<td>". $zeile['Tag'] . "</td>";
		}
		echo "<td>". $Von . "</td>";
		echo "<td>". $Bis . "</td>";
		echo "<td bgcolor='".$Color."'>". $zeile['Ist'] . "/";
		echo "". $zeile['Soll'] . "</td>";
                # buttons sind in der selben Zelle
		echo "<td width='30px'>" . "<button width='20px' name='plusschicht' value='". $zeile['SchichtID'] ."'>+</button>" ."";
		echo "" . "&nbsp;&nbsp;<button width='120px' name='minusschicht' value='". $zeile['SchichtID'] ."'>&ndash;</button> $regtext" ."</td>";
                //echo "<td>$regtext</td>";
		echo "</tr>\n";

	}
    echo "</table>";


}







mysqli_free_result( $db_erg );


?>
 
 </form> 
 </div>
 
 </body>
</html>
