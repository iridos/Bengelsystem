<?php
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

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
  <script src="js/helferdb.js" type="text/javascript"></script>
  <meta name="viewport" content="width=480" />
 </head>
 <body onload="setEndDate();">
<div style="width: 100%;">
<?php


DatenbankAufDeutsch($db_link);
isset($_SESSION["DienstID"]) && $DienstID = $_SESSION["DienstID"];
$NewDienstID = 0;
isset($_SESSION["SchichtID"]) && $SchichtID = $_SESSION["SchichtID"];


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

if (isset($_POST['HelferID'])) {
    $HelferID = $_POST['HelferID'];
}
if (isset($_POST['ShowHelfer'])) {
    $HelferID = $_POST['HelperSearch'];
}

$_SESSION["HelferID"] = $HelferID;



if (isset($_POST['ChangeDienst'])) {
    $Was = $_POST['Dienst-Was'];
    $Wo = $_POST['Dienst-Wo'];
    $Info = $_POST['Dienst-Info'];
    $Leiter = $_POST['Dienst-Leiter'];
    $Gruppe = $_POST['Dienst-Gruppe'];
       $HelferLevel = $_POST['HelferLevel'];
    ChangeDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);
}

if (isset($_POST['NewDienst'])) {
    $Was = $_POST['Dienst-Was'];
    $Wo = $_POST['Dienst-Wo'];
    $Info = $_POST['Dienst-Info'];
    $Leiter = $_POST['Dienst-Leiter'];
    $Gruppe = $_POST['Dienst-Gruppe'];
       $HelferLevel = $_POST['HelferLevel'];
    NewDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);
    $NewDienstID = LastInsertId($db_link);
}


if (isset($_POST['DeleteDienst'])) {
    if (!DeleteDienst($db_link, $DienstID, false)) {
        echo "Erst Schichten des Dienstes Löschen!";
    }
}


if (isset($_POST['ChangeSchicht'])) {
    $Von = $_POST['Schicht-Von'];
    $Bis = $_POST['Schicht-Bis'];
    $Soll = $_POST['Schicht-Soll'];
    $Dauer = $_POST['Schicht-Dauer'];

    ChangeSchicht($db_link, $SchichtID, $Von, $Bis, $Soll, $Dauer);
}

if (isset($_POST['Schicht-Automatic-Bis'])) {
    $AutomaticBis = 1;
} else {
    $AutomaticBis = 0;
}

if (isset($_POST['Schicht-Anschlussschicht'])) {
    $Anschlussschicht = 1;
} else {
    $Anschlussschicht = 0;
}



if (isset($_POST['NewSchicht'])) {
    $Von = $_POST['Schicht-Von'];
    $Bis = $_POST['Schicht-Bis'];
    $Soll = $_POST['Schicht-Soll'];
    $Dauer = $_POST['Schicht-Dauer'];

    if ($AutomaticBis) {
        $Temp = new DateTime($Von);
      //$Temp2 = DateInterval::createFromDateString('3600 seconds');
        $Temp2 = DateInterval::createFromDateString($Dauer[0] . $Dauer[1] . ' hours ' . $Dauer[3] . $Dauer[4] . ' minutes');

        $Temp = $Temp->add($Temp2);
        $Bis = $Temp->format('Y-m-d H:i:s');
    }
    NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll, $Dauer, $HelferName);
    $SchichtID = LastInsertId($db_link);
    //echo "+".$SchichtID."+";
}


if (isset($_POST['DeleteSchicht'])) {
    if (!DeleteSchicht($db_link, $SchichtID, false)) {
        echo "Erst Helfer aus Schicht austragen<br>";
    }
    $SchichtID = 0;
}



if (isset($_POST['ShowSchicht'])) {
    $SchichtID = $_POST['SchichtSearch'];
}
if (isset($_POST['SchichtSearch']) && !isset($_POST['NewSchicht']) && !isset($_POST['DeleteSchicht'])) {
    $SchichtID = $_POST['SchichtSearch'];
    echo "SchichtSearch<br>";
}

if (isset($_POST['ShowSchichten'])) {
    $DienstID = $_POST['DienstSearch'];
}

if (isset($_POST['DienstSearch'])) {
    $DienstID = $_POST['DienstSearch'];
    $SchichtID = 0;
}


if ($NewDienstID != 0) {
    $DienstID = $NewDienstID;
}

// Dienste Anzeigen
////////////////////////////////////////////////////////

?>
<button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b>&larrhk;</b></button> 
<form method="post">
    <table border="0" class='commontable'>
    <tr><th>  Dienst</th><th><select name="DienstSearch" id="DienstSearch"  onchange="submit()">
<?php


$db_erg = GetDienste($db_link);

$Was = "";
$Wo = "";
$Info = "";
$Leiter = "";
$Gruppe = "";
$HelferLevel = "";

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if (isset ($DienstID) && $zeile['DienstID'] != $DienstID) {
        echo "<option value='" . $zeile['DienstID'] . "'>" . $zeile['Was'] . "</option>";
        $selectedset = true ;
    } else {
        echo "<option value='" . $zeile['DienstID'] . "' selected='selected'>" . $zeile['Was'] . "</option>";

        $Was = $zeile['Was'];
        $Wo = $zeile['Wo'];
        $Info = $zeile['Info'];
        $Leiter = $zeile['Leiter'];
        $Gruppe = $zeile['ElternDienstID'];
        $HelferLevel = $zeile['HelferLevel'];
    }
}
if( ! $selectedset ) { 
echo "<option value='none' selected='selected'>Bitte auswählen</option>";
}

echo "</select>";
echo "</th></tr>";
echo "    </table>";
echo "<p><noscript><button name='ShowSchichten' value='1'>Schichten Anzeigen</button></noscript>";
//echo "<button name='DeleteDienst' value='1'>Dienst löschen</button>";

// Aktueller Dienst und dessen Schichten Anzeigen
////////////////////////////////////////////////////////

if (!isset($DienstID)) {
    echo "<img src='Bilder/Attention_Sign.svg' width='20px'> Bitte erst Dienst Auswählen </body></html>";
    exit;
}
?>




          <table border="0" class="commontable">
            <tr>
              <td style="border: 0px solid black;">Was</td></tr><tr><td style="border: 0px solid black;">
              <input name="Dienst-Was" type="text" value="<?php echo htmlspecialchars($Was ?? '')?>">
              </td>
            </tr>
            <tr>
              <td style="border: 0px solid black;">Wo</td></tr><tr><td style="border: 0px solid black;">
              <input name="Dienst-Wo" type="text " value="<?php echo htmlspecialchars($Wo ?? '')?>">
              </td>
            </tr>
            </tr>
              <td style="border: 0px solid black;">Info</td></tr><tr><td style="border: 0px solid black;">
              <input name="Dienst-Info" type="text" value="<?php echo htmlspecialchars($Info ?? '')?>" >
              </td>
            </tr>
            <tr>
              <td style="border: 0px solid black;">Leiter</td></tr><tr><td style="border: 0px solid black;">
               
               <!--  <input name="Dienst-Leiter" type="text" value="<?php echo htmlspecialchars($Leiter ?? '')?>" > -->
                <?php
                    echo "<select name='Dienst-Leiter'>";
                    $db_erg = HelferListe($db_link);
                while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
                    if ($zeile['HelferID'] != $Leiter) {
                              echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</option>";
                    } else {
                              echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</option>";
                    }
                }
                    echo "</select>";
                ?>
                </td>
              </tr>
              <tr>
                <td style="border: 0px solid black;">Gruppe</td></tr><tr><td style="border: 0px solid black;">
                
                <?php
                    //echo "#####".$Gruppe."#####";
                    echo "<select name='Dienst-Gruppe'>";
                    $db_erg = GetDiensteChilds($db_link, 0);
                while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
                    if ($zeile['DienstID'] != $Gruppe) {
                              echo "<option value='" . $zeile['DienstID'] . "'>" . $zeile['Was'] . "</option>";
                    } else {
                          echo "<option value='" . $zeile['DienstID'] . "' selected='selected'>" . $zeile['Was'] . "</option>";
                    }
                }
                    echo "</select>";
                ?>
                </td>
                </td>
            </tr>
            <tr><td style="border: 0px solid black;">HelferLevel</td></tr>
            <tr><td style="border: 0px solid black;"> 
                <select name="HelferLevel">
                   <option value="1" <?php if ($HelferLevel == 1) {
                        echo "selected";
                                     };?> >Dauerhelfer</option>
                   <option value="2" <?php if ($HelferLevel == 2) {
                        echo "selected";
                                     };?> >Teilnehmer</option>
                   <?php //todo: Name aus HelferLevel-Tabelle erhalten?>
                </select>
             </td></tr>
          </table>
          
          <p>
             <button name="NewDienst" value="1">Dienst anlegen</button>
             <button name="ChangeDienst" value="1">Ändern</button>
             <button name='DeleteDienst' value='1'>Löschen</button>
          </p>
</form>




<form method="post">
    <table border="0" class='commontable'>
    <tr><th>Schicht</th><th><select name="SchichtSearch" id="SchichtSearch" onchange="submit()">
    
    
<?php


$Soll = 1;
$db_erg = GetSchichtenEinesDienstes($db_link, $DienstID);

echo "+" . $SchichtID . "+";

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if ($SchichtID == 0) {
        $SchichtID = $zeile['SchichtID'];
    }
    if ($zeile['SchichtID'] != $SchichtID) {
        echo "<option value='" . $zeile['SchichtID'] . "'>" . $zeile['TagVon'] . "</option>";
    } else {
        echo "<option value='" . $zeile['SchichtID'] . "' selected='selected'>" . $zeile['TagVon'] . "</option>";
        if (isset($_POST['NewSchicht']) && $Anschlussschicht) {
            $Von = $Bis;
        } else {
            $Von = $zeile['Von'];
            $Bis = $zeile['Bis'];
            $Soll = (int)$zeile['Soll'];
            $Dauer = $zeile['Dauer'];
        }
    }
}

echo "</select>";
echo "</th></tr>";
echo " </table>";
echo "<p><noscript><button name='ShowSchicht' value='1'>Schicht Anzeigen</button></noscript>";
//echo "<button name='DeleteSchicht' value='1'>Schicht löschen</button>";

?>

 
        <!--  <table border="0" style="border: 0px solid black;">  -->
        <table border="0" class='commontable'"> 
            <tr>
              <td style="border: 0px solid black;">Von</td></tr><tr><td style="border: 0px solid black;">
              <input id="Schicht-Von" name="Schicht-Von" type="datetime-local" onKeyUp="setEndDate()" value="<?php echo htmlspecialchars($Von ?? '')?>" required>
              </td>
            <tr>
            <tr>
              <td style="border: 0px solid black;">Dauer</td></tr><tr><td style="border: 0px solid black;">
              <input id="Schicht-Dauer" name="Schicht-Dauer" type="time" onChange="setEndDate()" value="<?php echo htmlspecialchars($Dauer ?? '01:00')?>" required>
              </td>
            <tr>
            </tr>
            <tr>
            </tr>
              <td style="border: 0px solid black;">Bis </td></tr><tr><td style="border: 0px solid black;">
              <input id="Schicht-Bis" name="Schicht-Bis" type="datetime-local" value="<?php echo htmlspecialchars($Bis ?? '')?>" required>
              </td>
            <tr>
            </tr>
              <td style="border: 0px solid black;">Anzahl Helfer (Soll)</td></tr><tr><td style="border: 0px solid black;">
              <input name="Schicht-Soll" type="number" min=1 value="<?php echo htmlspecialchars((int)$Soll ?? '')?>"  required>
              </td>
            <tr>
            </tr>

          </table>
               <input  style="width:unset" width=20 id="Schicht-Automatic-Bis" name="Schicht-Automatic-Bis" type="checkbox" onclick="setEndDate()" <?php
                if ($AutomaticBis) {
                    echo "checked";
                }
                ?>  > Endzeit von Dauer<br>

               <input  style="width:unset" width=20 id="Schicht-Anschlussschicht" name="Schicht-Anschlussschicht" type="checkbox" <?php
                if ($Anschlussschicht) {
                    echo "checked";
                }
                ?>   > Anschlussschicht vorbereiten<br>
           <p>
             <button name="NewSchicht" value="1">Schicht anlegen</button>
             <button name="ChangeSchicht" value="1">Ändern</button>
             <button name='DeleteSchicht' value='1'>Löschen</button>
          </p>
 </form>
 
<button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'Admin.php';"><b><b>&larrhk;</b></b></button> 


<?php


mysqli_free_result($db_erg);


$_SESSION["DienstID"] = $DienstID;
$_SESSION["SchichtID"] = $SchichtID;


?>
 

 </div>
 
 </body>
</html>
