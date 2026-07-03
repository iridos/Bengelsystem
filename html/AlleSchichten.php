<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';

AlleSchichtenCheckPOST($db_link, $HelferID, $AdminStatus, $AdminID);

$pagename = "Alle Schichten / Schichten hinzufügen";
$backlink = "index.php";
echo PageHeader($pagename);
echo TableHeader($pagename, $backlink);

// Reset AliasHelferID (normale Seite kennt kein Alias)
$_SESSION['AliasHelferID']   = $HelferID;
$_SESSION['AliasHelferName'] = $HelferName;

ZeigeDiensteUndSchichten($db_link, $HelferID, [
    'meine_schichten_link' => 'MeineSchichten.php',
    'HelferLevel'          => $HelferLevel,
]);
?>
</body>
</html>
