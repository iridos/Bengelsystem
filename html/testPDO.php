<?php
session_start();
require_once 'SQL_old.php';
require_once 'SQL.php';

// ok
function TestCreateHelfer(){
    $dbl = old\ConnectDB();
    $erg_old = old\CreateHelfer($dbl, "Max Mustermann", "max@example.com", "+49123-45", "hola234");
    $erg_new = CreateHelfer("Max Mustermann", "max2@example.com", "+49123-45", "hola234");
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old CreateHelfer returns '".var_export($erg_old, true)."'";
        echo "New CreateHelfer returns '".var_export($erg_new, true)."'";
    }
    else echo "CreateHelfer ok\n";
}

// ok
function TestHelferIstVorhanden(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferIstVorhanden($dbl, "max@example.com");
    $erg_new = HelferIstVorhanden("max@example.com");
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferIstVorhanden returns '".var_export($erg_old, true)."'";
        echo "New HelferIstVorhanden returns '".var_export($erg_new, true)."'";
    }
    else echo "HelferIstVorhanden ok\n";
}

// ok
function TestHelferLogin(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferLogin($dbl, "max@example.com", "hola234", 0);
    $erg_old_session = $_SESSION;
    $_SESSION = null;
    $erg_new = HelferLogin("max@example.com", "hola234",  0);
    $erg_new_session = $_SESSION;
    $_SESSION = null;
    $erg_old_wrong = old\HelferLogin($dbl, "max@example.com", "hola235", 0);
    $erg_old_session_wrong = $_SESSION;
    $_SESSION = null;
    $erg_new_wrong = HelferLogin("max@example.com", "hola235",  0);
    $erg_new_session_wrong = $_SESSION;
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferLogin returns '".var_export($erg_old, true)."'";
        echo "New HelferLogin returns '".var_export($erg_new, true)."'";
    }
    else if((gettype($erg_old_session) != gettype($erg_new_session)) || ($erg_old_session != $erg_new_session)){
        echo "Old HelferLogin session returns '".var_export($erg_old_session, true)."'";
        echo "New HelferLogin session returns '".var_export($erg_new_session, true)."'";
    }
    else if((gettype($erg_old_wrong) != gettype($erg_new_wrong)) || ($erg_old_wrong != $erg_new_wrong)){
        echo "Old HelferLogin wrong returns '".var_export($erg_old_wrong, true)."'";
        echo "New HelferLogin wrong returns '".var_export($erg_new_wrong, true)."'";
    }
    else if((gettype($erg_old_session_wrong) != gettype($erg_new_session_wrong)) || ($erg_old_session_wrong != $erg_new_session_wrong)){
        echo "Old HelferLogin session wrong returns '".var_export($erg_old_session_wrong, true)."'";
        echo "New HelferLogin session wrong returns '".var_export($erg_new_session_wrong, true)."'";
    }
    else echo "HelferLogin ok\n";
}

// ok
function TestHelferListe(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferListe($dbl);
    $erg_new = HelferListe();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferListe returns '".var_export($erg_old, true)."'";
        echo "New HelferListe returns '".var_export($erg_new, true)."'";
    }
    else echo "HelferListe ok\n";
}

// ok
function TestHelferdaten(){
    $dbl = old\ConnectDB();
    HelferLogin("max@example.com", "hola234",  0);
    $erg_old = old\Helferdaten($dbl,$_SESSION["HelferID"]);
    $erg_new = Helferdaten($_SESSION["HelferID"]);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old Helferdaten returns '".var_export($erg_old, true)."'";
        echo "New Helferdaten returns '".var_export($erg_new, true)."'";
    }
    else echo "Helferdaten ok\n";
}

// ok
function TestHelferdatenAendern(){
    $dbl = old\ConnectDB();
    HelferLogin("max@example.com", "hola234",  0);
    $cases = array(
        # Case 1 HelferNewPasswort Empty, HelferIsAdmin == -1
        1 => array(
            "HelferName" => "Maxi Mustermann",
            "HelferEmail" => "max3@example.com",
            "HelferHandy" => "+49-531",
            "HelferNewPasswort" => "",
            "HelferID" => $_SESSION["HelferID"],
            "HelferLevel" => 2,
            "HelferIsAdmin" => -1,
            "AdminID" => 0
        ),
        # Case 2 HelferNewPasswort Empty, HelferIsAdmin != -1
        2 => array(
            "HelferName" => "Maxi Mustermann",
            "HelferEmail" => "max3@example.com",
            "HelferHandy" => "+49-531",
            "HelferNewPasswort" => "",
            "HelferID" => $_SESSION["HelferID"],
            "HelferLevel" => 2,
            "HelferIsAdmin" => 1,
            "AdminID" => 0
        ),
        # Case 3 HelferNewPasswort set, HelferIsAdmin == -1
        3 => array(
            "HelferName" => "Maxi Mustermann",
            "HelferEmail" => "max3@example.com",
            "HelferHandy" => "+49-531",
            "HelferNewPasswort" => "hola531",
            "HelferID" => $_SESSION["HelferID"],
            "HelferLevel" => 2,
            "HelferIsAdmin" => -1,
            "AdminID" => 0
        ),
        # Case 4 HelferNewPasswort set, HelferIsAdmin != -1
        4 => array(
            "HelferName" => "Maxi Mustermann",
            "HelferEmail" => "max3@example.com",
            "HelferHandy" => "+49-531",
            "HelferNewPasswort" => "hola531",
            "HelferID" => $_SESSION["HelferID"],
            "HelferLevel" => 2,
            "HelferIsAdmin" => 1,
            "AdminID" => 0
        ),

    );
    foreach($cases as $casenumber => $case){
        ob_start();
        $erg_old = old\HelferdatenAendern($dbl, $case["HelferName"], $case["HelferEmail"], $case["HelferHandy"], $case["HelferNewPasswort"], $case["HelferID"], $case["HelferLevel"], $case["HelferIsAdmin"], $case["AdminID"]);
        $erg_ob_old = ob_get_contents();
        ob_end_clean();
        ob_start();
        $erg_new = HelferdatenAendern($case["HelferName"], $case["HelferEmail"], $case["HelferHandy"], $case["HelferNewPasswort"], $case["HelferID"], $case["HelferLevel"], $case["HelferIsAdmin"], $case["AdminID"]);
        $erg_ob_new = ob_get_contents();
        ob_end_clean();
        if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
            echo "Old HelferdatenAendern case number ".$casenumber." returns '".var_export($erg_old, true)."'\n";
            echo "New HelferdatenAendern case number ".$casenumber." returns '".var_export($erg_new, true)."'\n";
        }
        else if($erg_ob_old != $erg_ob_new){

            echo "Old HelferdatenAendern case number ".$casenumber." ob returns '".$erg_ob_old."'\n";
            echo "New HelferdatenAendern case number ".$casenumber." ob returns '".$erg_ob_new."'\n";
        }
        else echo "HelferdatenAendern case number ".$casenumber." ok\n";
    }
}

// ok
function TestAlleSchichten(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichten($dbl, 1, 2);
    $erg_new = AlleSchichten(1, 2);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichten returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichten returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichten ok\n";
}

// ok
function TestAlleSchichtenCount(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenCount($dbl, 2);
    $erg_new = AlleSchichtenCount(2);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenCount returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenCount returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenCount ok\n";
}

// ok
function TestAlleBelegteSchichtenCount(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleBelegteSchichtenCount($dbl, 2);
    $erg_new = AlleBelegteSchichtenCount(2);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleBelegteSchichtenCount returns".var_export($erg_old, true)."\n";
        echo "New AlleBelegteSchichtenCount returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleBelegteSchichtenCount ok\n";
}

// ok
function TestAlleSchichtenImZeitbereich(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenImZeitbereich($dbl, "2024-02-01", "2024-03-01", 2);
    $erg_new = AlleSchichtenImZeitbereich("2024-02-01", "2024-03-01", 2);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenImZeitbereich returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenImZeitbereich returns '".var_export($erg_new, true)."'\n";
    }
    $erg_old = old\AlleSchichtenImZeitbereich($dbl, "2024-03-01", "2024-04-01", 2);
    $erg_new = AlleSchichtenImZeitbereich("2024-03-01", "2024-04-01", 2);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenImZeitbereich returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenImZeitbereich returns '".var_export($erg_new, true)."'\n";
    }

    else echo "AlleSchichtenImZeitbereich ok\n";
}

// ok
function TestAlleSchichtenEinesHelfers(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $erg_old = old\AlleSchichtenEinesHelfers($dbl,$helfer['HelferID']);
    $erg_new = AlleSchichtenEinesHelfers($helfer['HelferID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenEinesHelfers ok\n";
}

// ok
function TestHelferLoeschen(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer1 = $_SESSION;
    HelferLogin("max3@example.com", "hola531",  0);
    $helfer2 = $_SESSION;
    ob_start();
    $erg_old1 = old\HelferLoeschen($dbl, $helfer1['HelferID'],0);
    $erg_new1 = HelferLoeschen($helfer2['HelferID'],0);
    $schichten = AlleSchichtenEinesHelfers($helfer1['HelferID']);
    foreach($schichten as $schicht)
    {
        HelferVonSchichtLoeschen($helfer1['HelferID'], $schicht['EinzelSchichtID']);
    }
    $schichten = AlleSchichtenEinesHelfers($helfer2['HelferID']);
    foreach($schichten as $schicht)
    {
        HelferVonSchichtLoeschen($helfer2['HelferID'], $schicht['EinzelSchichtID']);
    }
    $erg_old2 = old\HelferLoeschen($dbl, $helfer1['HelferID'],0);
    $erg_new2 = HelferLoeschen($helfer2['HelferID'],0);
    ob_end_clean();
    if((gettype($erg_old1) != gettype($erg_new1)) || ($erg_old1 != $erg_new1)){
        echo "Old HelferLoeschen returns".var_export($erg_old1, true)."\n";
        echo "New HelferLoeschen returns '".var_export($erg_new1, true)."'\n";
    }
    else if((gettype($erg_old2) != gettype($erg_new2)) || ($erg_old2 != $erg_new2)){
        echo "Old HelferLoeschen returns".var_export($erg_old2, true)."\n";
        echo "New HelferLoeschen returns '".var_export($erg_new2, true)."'\n";
    }
    else echo "HelferLoeschen ok\n";
}

// ok
function TestSchichtIdArrayEinesHelfers(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $erg_old = old\SchichtIdArrayEinesHelfers($dbl, $helfer['HelferID']);
    $erg_new = SchichtIdArrayEinesHelfers($helfer['HelferID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old SchichtIdArrayEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New SchichtIdArrayEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "SchichtIdArrayEinesHelfers ok\n";
}

// ok
function TestAlleSchichtenEinesHelfersVonJetzt(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $dienste = GetDienste();
    NewSchicht($dienste[0]["DienstID"], date('Y-m-d H:i',time()+60*60*24), date('Y-m-d H:i',time()+60*60*24+60*60*1.5), 2, "01:30");
    $schichten = GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    HelferSchichtZuweisen($helfer['HelferID'], $schichten[2]['SchichtID'],0);
    $erg_old = old\AlleSchichtenEinesHelfersVonJetzt($dbl, $helfer['HelferID']);
    $erg_new = AlleSchichtenEinesHelfersVonJetzt($helfer['HelferID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenEinesHelfersVonJetzt returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenEinesHelfersVonJetzt returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenEinesHelfersVonJetzt ok\n";
}

// ok
function TestSchichtenSummeEinesHelfers(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $erg_old = old\SchichtenSummeEinesHelfers($dbl, $helfer['HelferID']);
    $erg_new = SchichtenSummeEinesHelfers($helfer['HelferID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old SchichtenSummeEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New SchichtenSummeEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "SchichtenSummeEinesHelfers ok\n";
}

// ok (looked at log file afterwards)
function TestLogSchichtEingabe(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $dienste = GetDienste();
    $schichten = GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    old\LogSchichtEingabe($dbl, $helfer['HelferID'], $schichten[0]['SchichtID'], -1, "test");
    LogSchichtEingabe($helfer['HelferID'], $schichten[0]['SchichtID'], -1, "test");
    echo "LogSchichtEingabe ok\n";
}

// ok
function TestHelferSchichtZuweisen(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer1 = $_SESSION;
    HelferLogin("max3@example.com", "hola531",  0);
    $helfer2 = $_SESSION;
    $dienste = GetDienste();
    $schichten = GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    $erg_old = old\HelferSchichtZuweisen($dbl, $helfer1['HelferID'], $schichten[0]['SchichtID'],0);
    $erg_new = HelferSchichtZuweisen($helfer2['HelferID'], $schichten[0]['SchichtID'],0);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferSchichtZuweisen returns".var_export($erg_old, true)."\n";
        echo "New HelferSchichtZuweisen returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferSchichtZuweisen ok\n";
}

// ok
function TestHelferVonSchichtLoeschen(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer1 = $_SESSION;
    $schichten1 = AlleSchichtenEinesHelfers($helfer1['HelferID']);
    HelferLogin("max3@example.com", "hola531",  0);
    $helfer2 = $_SESSION;
    $schichten2 = AlleSchichtenEinesHelfers($helfer2['HelferID']);
    $erg_old = old\HelferVonSchichtLoeschen($dbl, $helfer1['HelferID'], $schichten1[0]['EinzelSchichtID']);
    $erg_new = HelferVonSchichtLoeschen($helfer2['HelferID'], $schichten2[0]['EinzelSchichtID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferVonSchichtLoeschen returns".var_export($erg_old, true)."\n";
        echo "New HelferVonSchichtLoeschen returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferVonSchichtLoeschen ok\n";
}

// ok
function TestHelferVonSchichtLoeschen_SchichtID(){
    $dbl = old\ConnectDB();
    $schichten = AlleSchichten(1, 2);
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer1 = $_SESSION;
    HelferSchichtZuweisen($helfer1['HelferID'], $schichten[1]['SchichtID'],0);
    HelferLogin("max3@example.com", "hola531",  0);
    $helfer2 = $_SESSION;
    HelferSchichtZuweisen($helfer2['HelferID'], $schichten[1]['SchichtID'],0);
    $erg_old = old\HelferVonSchichtLoeschen_SchichtID($dbl,$helfer1['HelferID'], $schichten[1]['SchichtID']);
    $erg_new = HelferVonSchichtLoeschen_SchichtID($helfer2['HelferID'], $schichten[1]['SchichtID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferVonSchichtLoeschen_SchichtID returns".var_export($erg_old, true)."\n";
        echo "New HelferVonSchichtLoeschen_SchichtID returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferVonSchichtLoeschen_SchichtID ok\n";
}

// ok
function TestDetailSchicht(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $schichten = AlleSchichtenEinesHelfers($helfer['HelferID']);
    $erg_old = old\DetailSchicht($dbl, $schichten[0]['SchichtID']);
    $erg_new = DetailSchicht($schichten[0]['SchichtID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DetailSchicht returns".var_export($erg_old, true)."\n";
        echo "New DetailSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DetailSchicht ok\n";
}

// ok
function TestBeteiligteHelfer(){
    $dbl = old\ConnectDB();
    HelferLogin("max2@example.com", "hola234",  0);
    $helfer = $_SESSION;
    $schichten = AlleSchichtenEinesHelfers($helfer['HelferID']);
    $erg_old = old\BeteiligteHelfer($dbl, $schichten[0]['SchichtID']);
    $erg_new = BeteiligteHelfer($schichten[0]['SchichtID']);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old BeteiligteHelfer returns".var_export($erg_old, true)."\n";
        echo "New BeteiligteHelfer returns '".var_export($erg_new, true)."'\n";
    }
    else echo "BeteiligteHelfer ok\n";
}

// ok
function TestGetDienste(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetDienste($dbl);
    $erg_new = GetDienste();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetDienste returns".var_export($erg_old, true)."\n";
        echo "New GetDienste returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetDienste ok\n";
}

// ok
function TestGetDiensteChilds(){
    $dienste = GetDienste();
    $dbl = old\ConnectDB();
    $erg_old_empty = old\GetDiensteChilds($dbl, $dienste[1]["DienstID"]);
    $erg_new_empty = GetDiensteChilds($dienste[1]["DienstID"]);
    $erg_old_child = old\GetDiensteChilds($dbl, $dienste[0]["DienstID"]);
    $erg_new_child = GetDiensteChilds($dienste[0]["DienstID"]);
    if((gettype($erg_old_empty) != gettype($erg_new_empty)) || ($erg_old_empty != $erg_new_empty)){
        echo "Old GetDiensteChilds empty returns".var_export($erg_old_empty, true)."\n";
        echo "New GetDiensteChilds empty returns '".var_export($erg_new_empty, true)."'\n";
    }
    else if((gettype($erg_old_child) != gettype($erg_new_child)) || ($erg_old_child != $erg_new_child)){
        echo "Old GetDiensteChilds child returns".var_export($erg_old_child, true)."\n";
        echo "New GetDiensteChilds child returns '".var_export($erg_new_child, true)."'\n";
    }
    else echo "GetDiensteChilds ok\n";
}

// ok
function TestChangeDienst(){
    $dienste = GetDienste();
    HelferLogin("max3@example.com", "hola531",  0);
    $dbl = old\ConnectDB();
    $erg_old = old\ChangeDienst($dbl, $dienste[0]["DienstID"], "Frühstück", "Foyer", "SChnibbeln", $_SESSION["HelferID"], 0, $_SESSION["HelferLevel"]);
    $erg_new = ChangeDienst($dienste[1]["DienstID"], "Frühstück", "Foyer", "SChnibbeln", $_SESSION["HelferID"], $dienste[0]["DienstID"], $_SESSION["HelferLevel"]);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old ChangeDienst returns".var_export($erg_old, true)."\n";
        echo "New ChangeDienst returns '".var_export($erg_new, true)."'\n";
    }
    else echo "ChangeDienst ok\n";
}

// ok
function TestNewDienst(){
    $dbl = old\ConnectDB();
    HelferLogin("max3@example.com", "hola531",  0);
    $erg_old = old\NewDienst($dbl, "egal", "Badgekontrolle", "Turnhalle", "Nur Jongleure mit Badge durchlassen", $_SESSION["HelferID"], 0, $_SESSION["HelferLevel"]);
    $erg_new = NewDienst("Badgekontrolle", "Turnhalle", "Nur Jongleure mit Badge durchlassen", $_SESSION["HelferID"], 0, $_SESSION["HelferLevel"]);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old NewDienst returns".var_export($erg_old, true)."\n";
        echo "New NewDienst returns '".var_export($erg_new, true)."'\n";
    }
    else echo "NewDienst ok\n";
}

// ok
function TestDeleteDienst(){
    $dbl = old\ConnectDB();
    $dienste = GetDienste();
    $erg_old = old\DeleteDienst($dbl, $dienste[0]["DienstID"], false);
    $erg_new = DeleteDienst($dienste[1]["DienstID"], false);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DeleteDienst returns".var_export($erg_old, true)."\n";
        echo "New DeleteDienst returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DeleteDienst ok\n";
}

// ok
function TestGetDiensteForDay(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetDiensteForDay($dbl, 2, "2024-02-16");
    $erg_new = GetDiensteForDay(2, "2024-02-16");
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetDiensteForDay returns".var_export($erg_old, true)."\n";
        echo "New GetDiensteForDay returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetDiensteForDay ok\n";
}

// ok
function TestGetSchichtenForDienstForDay(){
    $dienste = GetDienste();
    $dbl = old\ConnectDB();
    $erg_old_good = old\GetSchichtenForDienstForDay($dbl, $dienste[0]["DienstID"], "2024-02-16");
    $erg_new_good = GetSchichtenForDienstForDay($dienste[0]["DienstID"], "2024-02-16");
    $erg_old_bad = old\GetSchichtenForDienstForDay($dbl, $dienste[0]["DienstID"], "2024-02-15");
    $erg_new_bad = GetSchichtenForDienstForDay($dienste[0]["DienstID"], "2024-02-15");
    if((gettype($erg_old_good) != gettype($erg_new_good)) || ($erg_old_good != $erg_new_good)){
        echo "Old GetSchichtenForDienstForDay returns".var_export($erg_old_good, true)."\n";
        echo "New GetSchichtenForDienstForDay returns '".var_export($erg_new_good, true)."'\n";
    }
    else if((gettype($erg_old_bad) != gettype($erg_new_bad)) || ($erg_old_bad != $erg_new_bad)){
        echo "Old GetSchichtenForDienstForDay returns".var_export($erg_old_bad, true)."\n";
        echo "New GetSchichtenForDienstForDay returns '".var_export($erg_new_bad, true)."'\n";
    }
    else echo "GetSchichtenForDienstForDay ok\n";
}

// ok
function TestGetSchichtenEinesDienstes(){
    $dienste = GetDienste();
    $dbl = old\ConnectDB();
    $erg_old_good = old\GetSchichtenEinesDienstes($dbl, $dienste[0]["DienstID"]);
    $erg_new_good = GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    $erg_old_bad = old\GetSchichtenEinesDienstes($dbl, 0);
    $erg_new_bad = GetSchichtenEinesDienstes(0);
    if((gettype($erg_old_good) != gettype($erg_new_good)) || ($erg_old_good != $erg_new_good)){
        echo "Old GetSchichtenEinesDienstes returns".var_export($erg_old_good, true)."\n";
        echo "New GetSchichtenEinesDienstes returns '".var_export($erg_new_good, true)."'\n";
    }
    else if((gettype($erg_old_bad) != gettype($erg_new_bad)) || ($erg_old_bad != $erg_new_bad)){
        echo "Old GetSchichtenEinesDienstes returns".var_export($erg_old_bad, true)."\n";
        echo "New GetSchichtenEinesDienstes returns '".var_export($erg_new_bad, true)."'\n";
    }
    else echo "GetSchichtenEinesDienstes ok\n";
}

// ok
function TestChangeSchicht(){
    $dienste = GetDienste();
    $schichten =  GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    $dbl = old\ConnectDB();
    $erg_old = old\ChangeSchicht($dbl, $schichten[0]["SchichtID"], "2024-02-16T09:00", "2024-02-16T10:30", 1, "01:00");
    $erg_new = ChangeSchicht($schichten[1]["SchichtID"], "2024-02-16T10:30", "2024-02-16T12:00", 1, "01:00");
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old ChangeSchicht returns".var_export($erg_old, true)."\n";
        echo "New ChangeSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "ChangeSchicht ok\n";
}

// ok
function TestNewSchicht(){
    $dienste = GetDienste();
    $dbl = old\ConnectDB();
    $erg_old = old\NewSchicht($dbl, $dienste[0]["DienstID"], "2024-02-15T09:00", "2024-02-15T10:30", 2, "01:30");
    $erg_new = NewSchicht($dienste[0]["DienstID"], "2024-02-15T10:30", "2024-02-15T12:00", 2, "01:30");
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old NewSchicht returns".var_export($erg_old, true)."\n";
        echo "New NewSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "NewSchicht ok\n";
}

// ok
function TestDeleteSchicht(){
    $dbl = old\ConnectDB();
    $dienste = GetDienste();
    NewSchicht($dienste[0]["DienstID"], "2024-02-17T10:30", "2024-02-17T12:00", 2, "01:30");
    NewSchicht($dienste[0]["DienstID"], "2024-02-17T10:30", "2024-02-17T12:00", 2, "01:30");
    $schichten = GetSchichtenEinesDienstes($dienste[0]["DienstID"]);
    $erg_old = old\DeleteSchicht($dbl, $schichten[4]["SchichtID"], false);
    $erg_new = DeleteSchicht($schichten[5]["SchichtID"], false);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DeleteSchicht returns".var_export($erg_old, true)."\n";
        echo "New DeleteSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DeleteSchicht ok\n";
}

// ok
function TestAlleHelferSchichtenUebersicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleHelferSchichtenUebersicht($dbl);
    $erg_new = AlleHelferSchichtenUebersicht();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleHelferSchichtenUebersicht returns".var_export($erg_old, true)."\n";
        echo "New AlleHelferSchichtenUebersicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleHelferSchichtenUebersicht ok\n";
}

// ok
function TestDatenbankAufDeutsch(){
    $dbl = old\ConnectDB();
    old\DatenbankAufDeutsch($dbl);
    DatenbankAufDeutsch();
    echo "DatenbankAufDeutsch ok\n";
}

// ok, checked by hand (will be different for both connections)
function TestLastInsertId(){
    $dbl = old\ConnectDB();
    $dienste = GetDienste();
    Old\NewSchicht($dbl, $dienste[0]["DienstID"], "2024-02-17T10:30", "2024-02-17T12:00", 2, "01:30");
    $erg_old = old\LastInsertId($dbl);
    $erg_new = LastInsertId();
    echo "LastInsertId ok\n";
}

// ok
function TestHelferLevel(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferLevel($dbl);
    $erg_new = HelferLevel();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferLevel returns".var_export($erg_old, true)."\n";
        echo "New HelferLevel returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferLevel ok\n";
}

TestCreateHelfer();
TestHelferIstVorhanden();
TestHelferLogin();
TestHelferListe();
TestHelferdaten();
TestHelferdatenAendern();
TestNewDienst();
TestGetDienste();
TestDeleteDienst();
TestNewDienst();
TestChangeDienst();
TestGetDiensteChilds();
TestNewSchicht();
TestGetSchichtenEinesDienstes();
TestChangeSchicht();
TestGetSchichtenForDienstForDay();
TestAlleSchichten();
TestAlleSchichtenCount();
TestHelferSchichtZuweisen();
TestAlleBelegteSchichtenCount();
TestAlleSchichtenImZeitbereich();
TestAlleSchichtenEinesHelfers();
TestHelferVonSchichtLoeschen();
TestHelferSchichtZuweisen();
TestSchichtIdArrayEinesHelfers();
TestAlleSchichtenEinesHelfersVonJetzt();
TestSchichtenSummeEinesHelfers();
TestLogSchichtEingabe();
TestDetailSchicht();
TestBeteiligteHelfer();
TestGetDiensteForDay();
TestAlleHelferSchichtenUebersicht();
TestDatenbankAufDeutsch();
TestLastInsertId();
TestHelferLevel();
TestHelferVonSchichtLoeschen_SchichtID();
TestDeleteSchicht();
TestHelferLoeschen();
?>
