<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
require '_functions.php';
$db_link = ConnectDB();
$pagename  = "HelferLevel verwalten";  // name of this page
$backlink  = "Admin.php";         // back button in table header from table header
$header = PageHeader($pagename);
$tablehead = TableHeader($pagename,$backlink);
require '_login.php';

if ($AdminStatus != 1) {
    //Seite nur fuer Admins. Weiter zu index.php und exit, wenn kein Admin
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}
$AliasHelferID = 0;

if (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
}

$HelferLevelInfo = AlleHelferLevelAlles($db_link);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST["AliasHelferID"])) {
        $AliasHelferID = $_POST["AliasHelferID"];
    }
    if (isset($_POST['save'])){
        $level = intval($_POST['save']);
        $beschreibung = $_POST['beschreibung'][$level] ?? $HelferLevelInfo[$level]['HelferLevelBeschreibung'] ;
        $linkcode = $_POST['linkcode'][$level] ?? $HelferLevelInfo[$level]['linkcode'];
        HelferLevelUpdate($db_link, $level, $beschreibung, $linkcode);
    }

    if (isset($_POST['create'])) {
    $beschreibung_neu = trim($_POST['beschreibung_neu'] ?? '');
    $linkcode_neu = trim($_POST['linkcode_neu'] ?? '');
        if ($beschreibung_neu !== '' && $linkcode_neu !== '') {
            HelferLevelInsert($db_link, $beschreibung_neu, $linkcode_neu);
        }
    }
    if (isset($_POST['delete'])) {
        $level = intval($_POST['delete']);
        HelferLevelDelete($db_link, $level);
    }

    // POST from _login.php after login
    //echo var_dump($_POST);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


if ($AliasHelferID != 0) {
    $_SESSION["AliasHelferID"] = $AliasHelferID;
}
$db_erg = Helferdaten($db_link, $HelferID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferIsAdmin = $zeile['Admin'];
}
echo $header; // muss nach redirect-headern fuer POST ausgegeben werden
echo $tablehead; // variablen aus _login.php
?>
<p>
<img src="Bilder/Info.jpeg" width="25px" height="25px">
<b>HelferLevel</b> definieren die Rollen der Accounts und helfen Aufgaben abzugrenzen.
Jeder Account ist genau einem Level zugeordnet.
Hier können Levels bearbeitet, erstellt oder unbenutzte Levels gelöscht werden. Nutzung durch Accounts und Dienste in den Spalten davor.
</p><p>
Der Button ganz rechts verlinkt eine Accounterstellungsseit pro Level (Link auf Erstellungsseite enthält jeweiligen "linkcode").<br>
Alternativ zur Accounterstellungsseite kann ein 
 <a href="EmailZuToken.php"
   title="Account mit diesem Level anlegen"
   class="buttonlike"
   style="text-decoration: none; padding: 15px 12px 9px 12px; background-color: #eee; border: 2px solid #777; border-radius: 3px; display: inline-block; ">
   <span style="white-space: nowrap">✉️ </span></a> persönlicher Einladungslink per Email verschickt werden</b>, der automatisch einen Account "auf Klick" anlegt.
</a>
</p>

<form action="AdminHelferLevel.php" method="post">
<table class="commontable">
    <tr>
        <th>Level</th>
        <th>Beschreibung</th>
        <th>Linkcode</th>
        <th>Accounts</th>
        <th>Dienste</th>
        <th>Aktion</th>
    </tr>
<?php
foreach ($HelferLevelInfo as $level => $info) {
    $beschreibung = htmlspecialchars($info['HelferLevelBeschreibung']);
    $linkcode = htmlspecialchars($info['linkcode']);
    $accounts = AnzahlAccountsMitHelferLevel($db_link, $level);
    $dienste  = AnzahlDiensteMitHelferLevel($db_link, $level);

if ($accounts == 0 && $dienste == 0) {
    $loeschButton = "<button type=\"submit\" name=\"delete\" value=\"$level\" title=\"Eintrag löschen\" style=\"color:red;\">❌</button>";
} else {
    $verwendung = [];
    if ($accounts > 0) $verwendung[] = "$accounts Account(s)";
    if ($dienste > 0)  $verwendung[] = "$dienste Dienst(e)";
    $verwendungsText = implode(" und ", $verwendung);
    $escapedTitle = htmlspecialchars($verwendungsText, ENT_QUOTES);

    $loeschButton = <<<EOL
    <button
        title="$escapedTitle nutzen dieses Level"
        onclick="alert('Dieser HelferLevel ist in Verwendung durch $escapedTitle und kann nicht gelöscht werden.')"
        style="opacity: 0.5; cursor: not-allowed;"
    >❌</button>
EOL;
}
    echo <<<EOL
    <tr>
        <td width="5%">$level</td>
        <td width="25%"><input type="text" name="beschreibung[$level]" value="$beschreibung" size="40"></td>
        <td width="25%"><input type="text" name="linkcode[$level]" value="$linkcode" size="40"></td>
        <td width="5%" style="text-align:center">$accounts</td>
        <td width="5%" style="text-align:center">$dienste</td>
        <td width="15%">
            <button type="submit" name="save" value="$level" title="Ändern">💾</button>
            $loeschButton

         <a href="CreateHelfer.php?linkcode=$linkcode"
           title="Account mit diesem Level anlegen"
           class="buttonlike"
           style="text-decoration: none; padding: 15px 6px 9px 6px; background-color: #eee; border: 1px solid #777; border-radius: 3px; display: inline-block; ">
           <span style="white-space: nowrap"> 🧑➕ </span>
        </a>
        </td>
    </tr>
EOL;
}
?>
    <tr>
        <td>neu</td>
        <td><input type="text" name="beschreibung_neu" placeholder="Neue Beschreibung" size="40"></td>
        <td><input type="text" name="linkcode_neu" placeholder="Neuer Linkcode" size="40"></td>
        <td>-</td>
        <td>-</td>
        <td><button type="submit" name="create" value="1" title="Neu anlegen">➕</button></td>
    </tr>
</table>
</form>

<a href="<?php echo $backlink; ?>"><button class=back name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';">
  <b>&larrhk;</b>
</button>
</body>
</html>
