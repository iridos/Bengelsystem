<!doctype html>
<html>
 <head>
  <title>testAlle Helferschichten</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <meta name="viewport" content="width=480" />

 </head>
 <body>
 <button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button>   
 <!--h1> Alle Schichten Ausdruck</h1-->
<div style="width: 100%;">
<?php
SESSION_START();

require_once ('konfiguration.php');
include 'SQL.php';


$db_link = mysqli_connect (
                     MYSQL_HOST, 
                     MYSQL_BENUTZER, 
                     MYSQL_KENNWORT, 
                     MYSQL_DATENBANK
                    );
include '_login.php';

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
    $ZeitBereich = 0;
}



// Helferliste Anzeigen
////////////////////////////////////////////////////////

?>


<!--form method="post" action="AlleSchichtenAusdruck.php#Info"-->
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
      $db_erg = HelferSchichtZuweisen($db_link,$HelferID,$SchichtId);

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
                $db_erg = HelferVonSchichtLoeschen_SchichtID($db_link,$HelferID,$SchichtID);

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
 $db_erg = SchichtenSummeEinesHelfers($db_link,$HelferID);
 $zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC);

/// Schichten Auswahl
////////////////////////////////////////////////////////
if($addschicht=='0')
{
   echo "<p><b>Schichten Hinzufügen geordnet nach</b>";
   echo "<button name='addschicht' value='1'>Tage</button>";
   echo "<button name='addschicht' value='2'>Dienste</button></p>";
}


// fuer Anzahlanzeige in Ueberschrift
$iAlleSchichtenCount = AlleSchichtenCount($db_link);
$iBelegteSchichtenCount = AlleBelegteSchichtenCount($db_link);

    //echo "<p><button name='addschicht' value='0'><b>&larrhk;</b></button></p>";
    echo '<table  class="commontable">';
    echo "<tr class='header'>";
    echo "<th colspan='7'>". "Alle Schichten der Con (" . $iBelegteSchichtenCount."/".$iAlleSchichtenCount. ")</th></tr>";

echo "\n<tr class='header'>\n"; // Zeitbereich tr

// Zeitbereich: -1 davor, 0 kein Limit, 1-N Tag N der Con, 1000: nach der Con
$ZeitBereichWerte = ZEITBEREICHWERTE;
$ZeitBereichFelder = count($ZeitBereichWerte);
$ZeitBereichFeldBreite = round(100/$ZeitBereichFelder); // % width for style

$format='Y-m-d';

// iterate over all days plus "before" and "after"
// Wenn TAG_DAUER=4, dann sind die Werte 1-4 die Tage der Con
// ZeitBereichWerte = [-1, 0, 1, 2, 3, 4, 1000]
// -1: davor, 0: alle, 1-4: Tag 1-4, 1000: danach
foreach($ZeitBereichWerte as &$EinZeitBereich) { 
  if($EinZeitBereich >0 && $EinZeitBereich <= TAGE_DAUER) { 
    $PlusTage=$EinZeitBereich-1;
    //TODO: only if locale DE
    $day = $start_date->add(new DateInterval("P{$PlusTage}D"));
    $Wochentag= $TageNamenDeutsch[date_format($day,'w')];

    $Text = "$Wochentag (Tag{$EinZeitBereich})"; 
    $Von=date_format($day, $format) . " 00:00:00";
    $Bis=date_format($day, $format) . " 23:59:59";
  }
  elseif($EinZeitBereich == -1)   { $Text = 'Davor'; $Von="2000-01-01 00:00:00"; $Bis=date_format($start_date, $format). " 00:00:00";}
  elseif($EinZeitBereich == 0 )   { $Text = 'Alle' ; $Von="2000-01-01 00:00:00"; $Bis="3000-01-01 00:00:00";}
  elseif($EinZeitBereich == 1000) { $Text = 'Danach'; 
     $tage_dauer = TAGE_DAUER;
     $day = $start_date->add(new DateInterval("P{$tage_dauer}D")); $Von=date_format($day, $format). " 00:00:00"; $Bis="3000-01-01 00:00:00";
  }
  // highlight the selected time range
  if($EinZeitBereich == $ZeitBereich) { 
     $color = 'background-color:#0000FF; ' ;
     $MeinVon = $Von;
     $MeinBis = $Bis;
  }
  else { $color = '';}
  //$Text="$Text <br>$MeinVon $MeinBis"; // debug time strings

  // write the field for each day
  echo "<th style='width:{$ZeitBereichFeldBreite}%; $color' onclick='window.location.href=\"AlleSchichtenAusdruck.php?ZeitBereich={$EinZeitBereich}\";'>". "$Text" . "</th>\n";

 }
echo "</tr>"; //Zeitbereich tr

$db_erg = AlleSchichtenImZeitbereich($db_link,$MeinVon,$MeinBis,$HelferLevel); 
//echo "<tr><th class=header> AlleSchichtenImZeitbereich(db_link,$Von,$Bis,$HelferLevel);</th></tr>"; // debug

$OldTag = "";
$OldWas = "";
// um Zeilen mit von mir belegten Schichten hervorzuheben
$MeineDienste = SchichtIdArrayEinesHelfers($db_link,$HelferID);
//print_r($MeineDienste); 

echo "</table>\n";

// Table to print out the shifts for people to enter their name in. If a shift is already taken, the name is printed out.
// The table prints just one day, specified via Zeitbereich. The heading above wrote out the day. 
// the table is sorted by shift type (Was) first and then by time (Ab). 
// "Was" is printed in the middle together with two-letter day and time.
// the first and the 5th (last) column also contain two-letter day and time for people to tear off and take with them.
// the 2nd and 4th column are for the namer. if the shift is already taken, the name is printed in, else the field is empty to write in.
// we iterate over all tasks (Was) and then over Ist and Soll for each task, filling one field for each Ist or Soll and filling in the name in Ist and leave it empty if it is Soll.
echo "<br>next table<br>\n";
echo "<table class='commontable'>\n";
// $db_erg ist aus AlleSchichtenImZeitbereich
// und gibt zurueck  Was, Ab, Bis, Ist, Tag, Soll - Ist und Soll sind die HelferStunden
$OldWas = "";
while ($zeile = mysqli_fetch_array( $db_erg, MYSQLI_ASSOC)) {
  $Tag = $zeile['Tag']; //this should be set above, because we only look at one day
  $Ab  = $zeile['Ab'];
  $Bis = $zeile['Bis'];
  $Ist = $zeile['Ist'];
  $Soll= $zeile['Soll'];
  $Was = $zeile['Was'];
  $TagKurz = substr($Tag,0,2);
  if ( substr($Ab,0,2) == substr($Bis,0,2)) { $Bis = substr($Bis,2); } // if start and end time are on the same day, we only print the end time
  if($Was != $OldWas){
    echo "<tr class='header'>";
    echo "<th colspan=5 style='text-align:center'>$Was ($TagKurz)</th></tr>\n";
  }
  $db_erg_helfer=BeteiligteHelfer($db_link,$zeile['SchichtID']); // get the people who are already signed up for this shift
  // Wir geben zwei Helfer pro Zeile fuer die selbe Schicht aus
  while($Soll > 0){
     $Soll = $Soll - 1;
     $HelferZeile = mysqli_fetch_array( $db_erg_helfer, MYSQLI_ASSOC);
     if(isset($HelferZeile['Name'])) { $Helfername = $HelferZeile['Name']; } else { $Helfername = ''; }
    echo "<tr><td>$Was <br>$Ab - $Bis </td>";
    echo "<td>$Helfername</td>";
    echo "<td>$Was <br>$Ab-$Bis</td>";
    if($Soll >0){ // zweite Spalte nur ausgeben, wenn noch eine Schicht offen ist
        $Soll = $Soll - 1;
        $HelferZeile = mysqli_fetch_array( $db_erg_helfer, MYSQLI_ASSOC); // get the next person
        if(isset($HelferZeile['Name'])) { $Helfername = $HelferZeile['Name']; } else { $Helfername = ''; }
        echo "<td>$Helfername</td>";
        echo "<td>$Was <br>$Ab-$Bis</td><tr>";
    }else{//if there is no more shift to fill in, we fill the rest of the row with empty fields
        echo "<td></td><td></td></tr>"; 
    }
  $OldWas = $Was;
  }
}
echo "</table>\n";


// old table, remove later

mysqli_free_result( $db_erg );


?>

 <!--/form--> 
 </div>

 </body>
</html>
