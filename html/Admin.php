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
    <link rel="stylesheet" href="css/style_common.css">
    <link rel="stylesheet" href="css/style_desktop.css" media=
    "screen and (min-width:781px)">
    <link rel="stylesheet" href="css/style_mobile.css" media=
    "screen and (max-width:780px)">
    <meta name="viewport" content="width=480">
</head>
<body>
    <?php

    DatenbankAufDeutsch($db_link);

$AliasHelferID = 0;

//echo "AliasHelfer=$AliasHelferID <br>";
if(isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
}

//echo "AliasHelfer=$AliasHelferID <br>";

if(isset($_POST["AliasHelfer"])) {
    $AliasHelferID = $_POST["AliasHelfer"];
    //echo "post<br>";
}

if($AliasHelferID != 0) {
    $_SESSION["AliasHelferID"] = $AliasHelferID;
}
//echo "AliasHelfer=$AliasHelferID <br>";

$db_erg = Helferdaten($db_link, $HelferID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}

?>
    <div style="width: 100%;">
        <table class="commontable">
            <tr>
                <th><button name="BackHelferdaten" value="1"
                onclick=
                "window.location.href = 'index.php';"><b>↩</b></button>
                &nbsp; <b>Admin HelferDB</b></th>
            </tr>
            <tr>
                <td></td>
            </tr>
            <tr onclick="window.location.href='AdminDienste.php';">
                <td>
                    <a class="fallbacklink" href=
                    'AdminDienste.php'><img src=
                    "Bilder/PfeilRunter.jpeg" style=
                    "width:30px;height:30px;"> <b>Dienste und
                    Schichten verwalten</b></a>
                </td>
            </tr>
            <tr onclick="window.location.href='CreateHelfer.php';">
                <td><img src="Bilder/More.jpeg" style=
                "width:30px;height:30px;"><b>Seite zur Helfer
                selbst-Registrierung</b></td>
            </tr>
            <tr onclick="window.location.href='EmailZuToken.php';">
                <td><img src="Bilder/PfeilRunter.jpeg" style=
                "width:30px;height:30px;"><b>Helfer per Link
                einladen</b></td>
            </tr>
            <tr onclick=
            "window.location.href='AdminHelferUebersicht.php';">
                <td>
                    <a class="fallbacklink" href=
                    'AdminHelferUebersicht.php'><img src=
                    "Bilder/PfeilRunter.jpeg" style=
                    "width:30px;height:30px;"><b>Helferübersicht</b></a>
                </td>
            </tr>
            <tr>
                <th>
                    <b>Helfer als Admin ändern:</b>
                    <form style="display:inline-block;" method=
                    "post">
                        <select style=
                        "height:33px;width:350px;font-size:20"
                        name="AliasHelfer" id="AliasHelfer"
                        onchange="submit()">
                            <?php
                        $db_erg = HelferListe($db_link);
while($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    if ($AliasHelferID != $zeile['HelferID']) {
        echo "<option value='".$zeile['HelferID']."'>".$zeile['Name']."</optionen>";
    } else {
        echo "<option value='".$zeile['HelferID']."' selected='selected'>".$zeile['Name']."</optionen>";
    }
}


?>
                        </select>
                    </form>
                </th>
            </tr>
            <tr>
                <!--td-->
                <!--<table class="innertable" style="padding:15px"><!-
- inner table for indent-->
            </tr>
            <tr onclick=
            "window.location.href='AdminUserdaten.php';">
                <!--td class="invis"></td-->
                <td><img src="Bilder/dot.png" width="30px" height=
                "2px"><img src="Bilder/PfeilRunter.jpeg" style=
                "width:30px;height:30px;"> <b>Helferdaten
                ändern</b></td>
            </tr>
            <tr onclick=
            "window.location.href='AdminMeineSchichten.php';">
                <td><img src="Bilder/dot.png" width="30px" height=
                "2px"><img src="Bilder/PfeilRunter.jpeg" style=
                "width:30px;height:30px;"> <b>Schichten
                Anzeigen/Löschen</b></td>
            </tr>
            <tr onclick=
            "window.location.href='AdminAlleSchichten.php';">
                <td><img src="Bilder/dot.png" width="30px" height=
                "2px"><img src="Bilder/PfeilRunter.jpeg" style=
                "width:30px;height:30px;"> <b>Schichten
                Hinzufügen</b></td>
            </tr>
            <!--</table></td> </tr>  inner table for indent end-->
            <!--<tr><th>Weiteres</th></tr>-->
            <tr onclick=
            "window.location.href='Kalender-all.html';">
                <td><img src="Bilder/More.jpeg" style=
                "width:30px;height:30px"> <b>Admin
                Kalenderansicht</b></td>
            </tr>
            <tr onclick="window.location.href='Ausdrucke.php';">
                <td><img src="Bilder/More.jpeg" style=
                "width:30px;height:30px;"> <b>Ausdrucke</b></td>
            </tr>
            <tr onclick=
            "window.location.href='TeilnehmerSchichtenAusdruck.php';">
                <td><img src="Bilder/More.jpeg" style=
                "width:30px;height:30px;"> <b>Ausdruck
                Schichten</b></td>
            </tr>
        </table>
        <p><img src="Bilder/Info.jpeg" width="25px" height="25px">
        Dienst: z.B. Badgekontrolle Eingang A. Schicht: ein Dienst
        zu einer bestimmten Zeit zB 9-12 Uhr</p><button class=
        "back" name="BackHelferdaten" value="1" onclick=
        "window.location.href = 'index.php';"><b>↩</b></button>
    </div>
</body>
</html>
