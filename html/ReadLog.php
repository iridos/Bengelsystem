<!DOCTYPE html>
<html>
<head>
    <meta name="generator" content=
    "HTML Tidy for HTML5 for Linux version 5.6.0">
    <title>Helfer - Logs</title>
    <link rel="stylesheet" href="css/style_desktop.css" media=
    "screen and (min-width:781px)">
    <link rel="stylesheet" href="css/style_mobile.css" media=
    "screen and (max-width:780px)">
    <!--meta name="viewport" content="width=480" /-->
    <?php
    require_once('konfiguration.php');
    SESSION_START();


    // if we are not logged in, we redirect in the header back to the main page
    if(!isset($_SESSION["HelferID"]) || ! $_SESSION["HelferID"] > 0) {
        echo ' <meta http-equiv="Refresh" content="0; URL=index.php" />
    ';
    }
    // print top of page if we are logged in and not redirecting
    echo " </head> \n <body> \n";
    echo '<button name="BackHelferdaten" value="1"  onclick="window.location.href = \'index.php\';"><b>&larrhk;</b></button><br>'."\n";
    echo '<H1> Helferdaten - Log </H1>';
    echo '<p>Hier werden alle Aktionen, die einen Helfer betreffen aufgelistet</p>';
    echo '<table id="customers">'."\n";

    //check for admin status
    $HelferID = $_SESSION["HelferID"];
    $AdminID = $_SESSION["AdminID"];
    include 'SQL.php';
    $db_link = mysqli_connect(MYSQL_HOST, MYSQL_BENUTZER, MYSQL_KENNWORT, MYSQL_DATENBANK);
    DatenbankAufDeutsch($db_link);

    $db_erg = Helferdaten($db_link, $HelferID);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferName = $zeile['Name'];
        $HelferIsAdmin = $zeile['Admin'];
    }

    // Wir suchen nach HelferID:<zahl> - wenn das Admin Flag gesetzt ist, kann man den Suchstring auf etwas anderes aendern
    $regex = "\(HelferID:$HelferID\)";
    if($HelferIsAdmin) {
        if(isset($_POST['suche']) || isset($_GET['suche'])) {
            $regex = $_POST['suche'].$_GET['suche'];
        }
        echo "<form>";
        echo "Nur Admins: Suchbegriff (Per Default der selbe Suchtext, wie bei nicht-Admin Helfern):<input name='suche' type='text' size=35 value='$regex'><br> \n";
        echo "Der Suchbegriff ist ein <a href='https://de.wikipedia.org/wiki/Regul%C3%A4rer_Ausdruck'>Regul&auml;rer Ausdruck</a><br><br>";
        echo "</form>";
    }

    $regex = "/"."$regex"."/";
    foreach(file(LOGFILE) as $line) {
        #echo "not: $line<br>\n";
        if(preg_match($regex, $line, $matches)) {
            echo "<tr><td> $line</td></tr>";
        }
    }
    ?>
</head>
<body>
</body>
</html>
