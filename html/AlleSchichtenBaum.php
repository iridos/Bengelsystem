<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';

$pagename = "Alle Schichten (Baum)";
$backlink = "index.php";
$backlink = "Admin.php";
$AktuellerDienst = GetAktuellenDienstKontext();
echo PageHeader($pagename);
echo TableHeader($pagename, $backlink, EVENTNAME, "", DienstEbeneUpLinkHtml($db_link, $AktuellerDienst));

$_SESSION['AliasHelferID']   = $HelferID;
$_SESSION['AliasHelferName'] = $HelferName;

ZeigeDienstEbene($db_link, $HelferID, [
    'modus'       => 'SchichtEintragen',
    'HelferLevel' => $HelferLevel,
]);
?>
</body>
</html>
