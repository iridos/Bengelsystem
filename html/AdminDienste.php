<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
SESSION_START();
require_once('konfiguration.php');
include 'SQL.php';
$db_link = ConnectDB();
include '_login.php';

if($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="generator" content=
    "HTML Tidy for HTML5 for Linux version 5.6.0">
    <title>Admin Drop am See</title>
    <link rel="stylesheet" href="css/style_desktop.css" media=
    "screen and (min-width:781px)">
    <link rel="stylesheet" href="css/style_mobile.css" media=
    "screen and (max-width:780px)">
    <meta name="viewport" content="width=480">
</head>
<body>
    <div style="width: 100%;">
        <?php


        DatenbankAufDeutsch($db_link);

$DienstID = $_SESSION["DienstID"];
$SchichtID = $_SESSION["SchichtID"];


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];

if(isset($_POST['HelferID'])) {
    $HelferID = $_POST['HelferID'];
}
if(isset($_POST['ShowHelfer'])) {
    $HelferID = $_POST['HelperSearch'];
}

$_SESSION["HelferID"] = $HelferID;



if(isset($_POST['ChangeDienst'])) {

    $Was = $_POST['Dienst-Was'];
    $Wo = $_POST['Dienst-Wo'];
    $Info = $_POST['Dienst-Info'];
    $Leiter = $_POST['Dienst-Leiter'];
    $Gruppe = $_POST['Dienst-Gruppe'];
    $HelferLevel = $_POST['HelferLevel'];
    ChangeDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);

}

if(isset($_POST['NewDienst'])) {

    $Was = $_POST['Dienst-Was'];
    $Wo = $_POST['Dienst-Wo'];
    $Info = $_POST['Dienst-Info'];
    $Leiter = $_POST['Dienst-Leiter'];
    $Gruppe = $_POST['Dienst-Gruppe'];
    $HelferLevel = $_POST['HelferLevel'];
    NewDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);

}


if(isset($_POST['DeleteDienst'])) {

    if (!DeleteDienst($db_link, $DienstID, false)) {
        echo "Erst Schichten des Dienstes Löschen!";
    }

}


if(isset($_POST['ChangeSchicht'])) {

    $Von = $_POST['Schicht-Von'];
    $Bis = $_POST['Schicht-Bis'];
    $Soll = $_POST['Schicht-Soll'];

    ChangeSchicht($db_link, $SchichtID, $Von, $Bis, $Soll);

}


if(isset($_POST['NewSchicht'])) {

    $Von = $_POST['Schicht-Von'];
    $Bis = $_POST['Schicht-Bis'];
    $Soll = $_POST['Schicht-Soll'];

    NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll);

}


if(isset($_POST['DeleteSchicht'])) {


    if(!DeleteSchicht($db_link, $SchichtID, false)) {
        echo "Erst Helfer aus Schicht austragen<br>";
    }

}



if(isset($_POST['ShowSchicht'])) {
    $SchichtID = $_POST['SchichtSearch'];
}
if(isset($_POST['SchichtSearch'])) {
    $SchichtID = $_POST['SchichtSearch'];
}

if(isset($_POST['ShowSchichten'])) {
    $DienstID = $_POST['DienstSearch'];
}

if(isset($_POST['DienstSearch'])) {
    $DienstID = $_POST['DienstSearch'];
    $SchichtID = 0;
}




// Dienste Anzeigen
////////////////////////////////////////////////////////

?><button class="back" name="BackHelferdaten" value="1"
        onclick=
        "window.location.href = 'Admin.php';"><b>↩</b></button>
        <form method="post">
            <table border="0" id='customers'>
                <tr>
                    <th>Dienst</th>
                    <th><select name="DienstSearch" id=
                    "DienstSearch" onchange="submit()">
                        <?php


                $db_erg = GetDienste($db_link);

$Was = "";
$Wo = "";
$Info = "";
$Leiter = "";
$Gruppe = "";
$HelferLevel = "";

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {

    if ($zeile['DienstID'] != $DienstID) {
        echo "<option value='".$zeile['DienstID']."'>".$zeile['Was']."</option>";

    } else {
        echo "<option value='".$zeile['DienstID']."' selected='selected'>".$zeile['Was']."</option>";
        $Was = $zeile['Was'];
        $Wo = $zeile['Wo'];
        $Info = $zeile['Info'];
        $Leiter = $zeile['Leiter'];
        $Gruppe = $zeile['ElternDienstID'];
        $HelferLevel = $zeile['HelferLevel'];
    }
}

echo "</select>";
echo "</th></tr>";
echo "    </table>";
echo "<p><noscript><button name='ShowSchichten' value='1'>Schichten Anzeigen</button></noscript>";
//echo "<button name='DeleteDienst' value='1'>Dienst löschen</button>";

// Aktueller Dienst und dessen Schichten Anzeigen
////////////////////////////////////////////////////////

?>
                        <!--  <input name="Dienst-Leiter" type="text" value="<?=htmlspecialchars($Leiter ?? '')?>" > -->
                        <?php
echo "<select name='Dienst-Leiter'>";
$db_erg = HelferListe($db_link);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if ($zeile['HelferID'] != $Leiter) {
        echo "<option value='".$zeile['HelferID']."'>".$zeile['Name']."</option>";

    } else {
        echo "<option value='".$zeile['HelferID']."' selected='selected'>".$zeile['Name']."</option>";

    }
}
echo "</select>";
?><?php
//echo "#####".$Gruppe."#####";
echo "<select name='Dienst-Gruppe'>";
$db_erg = GetDiensteChilds($db_link, 0);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {

    if ($zeile['DienstID'] != $Gruppe) {
        echo "<option value='".$zeile['DienstID']."'>".$zeile['Was']."</option>";

    } else {
        echo "<option value='".$zeile['DienstID']."' selected='selected'>".$zeile['Was']."</option>";

    }
}
echo "</select>";
?>
                        <option value=
                        "1" <?php if($HelferLevel == 1) {
                            echo "selected";
                        };?>>
                            Dauerhelfer
                        </option>
                        <option value=
                        "2" <?php if($HelferLevel == 2) {
                            echo "selected";
                        };?>>
                            Teilnehmer
                        </option><?php //todo: Name aus HelferLevel-Tabelle erhalten?>
                    </select></th>
                </tr>
            </table>
            <p><button name="ChangeDienst" value=
            "1">Ändern</button><button name="NewDienst" value=
            "1">Neue</button><button name='DeleteDienst' value=
            '1'>Löschen</button></p>
        </form>
        <form method="post">
            <table border="0" id='customers'>
                <tr>
                    <th>Schicht</th>
                    <th><select name="SchichtSearch" id=
                    "SchichtSearch" onchange="submit()">
                        <?php


                        $Soll = 1;
$db_erg = GetSchichtenEinesDienstes($db_link, $DienstID);

while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if ($SchichtID == 0) {
        $SchichtID = $zeile['SchichtID'];
    }
    if ($zeile['SchichtID'] != $SchichtID) {
        echo "<option value='".$zeile['SchichtID']."'>".$zeile['TagVon']."</option>";
    } else {
        echo "<option value='".$zeile['SchichtID']."' selected='selected'>".$zeile['TagVon']."</option>";
        $Von = $zeile['Von'];
        $Bis = $zeile['Bis'];
        $Soll = (int)$zeile['Soll'];
    }
}

echo "</select>";
echo "</th></tr>";
echo " </table>";
echo "<p><noscript><button name='ShowSchicht' value='1'>Schicht Anzeigen</button></noscript>";
//echo "<button name='DeleteSchicht' value='1'>Schicht löschen</button>";

?>
                        <!--  <table border="0" style="border: 0px solid black;">  -->
                        <?php


mysqli_free_result($db_erg);


$_SESSION["DienstID"] = $DienstID;
$_SESSION["SchichtID"] = $SchichtID;


?>
                    </select></th>
                </tr>
            </table>
        </form>
    </div>
</body>
</html>
