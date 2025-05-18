<?php

require_once 'konfiguration.php';

function HelferAuswahlButton($db_link, $AliasHelferID)
{
    echo '<b>Helfer w&auml;hlen:<b>';
    echo '<form style="display:inline-block;" method=post>';
    echo '<select style="height:33px;width:350px;" name="AliasHelferID" id="AliasHelferID" onchange="submit()">';
    $db_erg = HelferListe($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        if ($AliasHelferID != $zeile['HelferID']) {
            echo "<option value='" . $zeile['HelferID'] . "'>" . $zeile['Name'] . "</optionen>";
        } else {
            echo "<option value='" . $zeile['HelferID'] . "' selected='selected'>" . $zeile['Name'] . "</optionen>";
        }
    }
    echo '</select></form>';
}

if (isset($_POST['AliasHelferID'])) {
    $AliasHelferID = $_POST['AliasHelferID'];
} elseif (isset($_SESSION["AliasHelferID"])) {
    $AliasHelferID = $_SESSION["AliasHelferID"];
} else {
    HelferAuswahlButton($db_link, $AliasHelferID);
    echo "<p>Erst Helfer auswählen</p>";
    exit;
}                                                                                    
HelferAuswahlButton($db_link, $AliasHelferID);

$_SESSION["AliasHelferID"] = $AliasHelferID;
$AdminID = $_SESSION["AdminID"];

$db_erg = Helferdaten($db_link, $AliasHelferID);
while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $AliasHelferLevel = $zeile['HelferLevel'];                                       
}

