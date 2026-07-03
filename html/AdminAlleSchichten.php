<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
require_once '_functions.php';

AlleSchichtenCheckPOST($db_link, $HelferID, $AdminStatus, $AdminID);

$pagename = "Admin Alle Schichten / Schichten hinzufügen";
$backlink = "AdminHelferUebersicht.php";
echo PageHeader($pagename);
echo TableHeader($pagename, $backlink);

// AliasHelferID aus Session — wird von AlleSchichtenCheckPOST gesetzt wenn Admin
// einen anderen Helfer auswählt. Beim ersten Aufruf fällt es auf den eigenen HelferID zurück.
$AliasHelferID = $_SESSION['AliasHelferID'] ?? $HelferID;
// AliasHelferName analog — AlleSchichtenCheckPOST pflegt $_SESSION['AliasHelferName']
$_SESSION['AliasHelferName'] = $_SESSION['AliasHelferName'] ?? $HelferName;

ZeigeDiensteUndSchichten($db_link, $AliasHelferID, [
    'meine_schichten_link' => 'AdminMeineSchichten.php',
    'zeigeHelferAuswahl'   => true,
    'AliasHelferID'        => $AliasHelferID,
    'HelferLevel'          => $HelferLevel,
    'AdminStatus'          => $AdminStatus,
    'AdminID'              => $AdminID,
]);
?>
</body>
</html>
