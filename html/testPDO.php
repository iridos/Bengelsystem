<?php
session_start();
require_once 'SQL_old.php';
require_once 'SQL.php';

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

function TestAlleSchichten(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichten($dbl, $Sort, $HelferLevel = 1);
    $erg_new = AlleSchichten($Sort, $HelferLevel = 1);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichten returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichten returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichten ok";
}

function TestAlleSchichtenCount(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenCount($dbl, $HelferLevel = 1);
    $erg_new = AlleSchichtenCount($HelferLevel = 1);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenCount returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenCount returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenCount ok";
}

function TestAlleBelegteSchichtenCount(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleBelegteSchichtenCount($dbl, $HelferLevel = 1);
    $erg_new = AlleBelegteSchichtenCount($HelferLevel = 1);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleBelegteSchichtenCount returns".var_export($erg_old, true)."\n";
        echo "New AlleBelegteSchichtenCount returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleBelegteSchichtenCount ok";
}

function TestAlleSchichtenImZeitbereich(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenImZeitbereich($dbl, $Von, $Bis, $HelferLevel = 1);
    $erg_new = AlleSchichtenImZeitbereich($Von, $Bis, $HelferLevel = 1);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenImZeitbereich returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenImZeitbereich returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenImZeitbereich ok";
}

function TestAlleSchichtenEinesHelfers(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenEinesHelfers($dbl, $HelferID);
    $erg_new = AlleSchichtenEinesHelfers($HelferID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenEinesHelfers ok";
}

function TestHelferLoeschen(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferLoeschen($dbl, $HelferID, $AdminID);
    $erg_new = HelferLoeschen($HelferID, $AdminID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferLoeschen returns".var_export($erg_old, true)."\n";
        echo "New HelferLoeschen returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferLoeschen ok";
}

function TestSchichtIdArrayEinesHelfers(){
    $dbl = old\ConnectDB();
    $erg_old = old\SchichtIdArrayEinesHelfers($dbl, $HelferID);
    $erg_new = SchichtIdArrayEinesHelfers($HelferID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old SchichtIdArrayEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New SchichtIdArrayEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "SchichtIdArrayEinesHelfers ok";
}

function TestAlleSchichtenEinesHelfersVonJetzt(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleSchichtenEinesHelfersVonJetzt($dbl, $HelferID);
    $erg_new = AlleSchichtenEinesHelfersVonJetzt($HelferID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleSchichtenEinesHelfersVonJetzt returns".var_export($erg_old, true)."\n";
        echo "New AlleSchichtenEinesHelfersVonJetzt returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleSchichtenEinesHelfersVonJetzt ok";
}

function TestSchichtenSummeEinesHelfers(){
    $dbl = old\ConnectDB();
    $erg_old = old\SchichtenSummeEinesHelfers($dbl, $HelferID);
    $erg_new = SchichtenSummeEinesHelfers($HelferID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old SchichtenSummeEinesHelfers returns".var_export($erg_old, true)."\n";
        echo "New SchichtenSummeEinesHelfers returns '".var_export($erg_new, true)."'\n";
    }
    else echo "SchichtenSummeEinesHelfers ok";
}

function TestLogSchichtEingabe(){
    $dbl = old\ConnectDB();
    $erg_old = old\LogSchichtEingabe($dbl, $HelferID, $SchichtId, $EinzelSchichtId, $Aktion, $AdminID = 0);
    $erg_new = LogSchichtEingabe($HelferID, $SchichtId, $EinzelSchichtId, $Aktion, $AdminID = 0);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old LogSchichtEingabe returns".var_export($erg_old, true)."\n";
        echo "New LogSchichtEingabe returns '".var_export($erg_new, true)."'\n";
    }
    else echo "LogSchichtEingabe ok";
}

function TestHelferSchichtZuweisen(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferSchichtZuweisen($dbl, $HelferID, $SchichtId, $AdminID = 0);
    $erg_new = HelferSchichtZuweisen($HelferID, $SchichtId, $AdminID = 0);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferSchichtZuweisen returns".var_export($erg_old, true)."\n";
        echo "New HelferSchichtZuweisen returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferSchichtZuweisen ok";
}

function TestHelferVonSchichtLoeschen(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferVonSchichtLoeschen($dbl, $HelferID, $EinzelSchichtID, $AdminID = 0);
    $erg_new = HelferVonSchichtLoeschen($HelferID, $EinzelSchichtID, $AdminID = 0);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferVonSchichtLoeschen returns".var_export($erg_old, true)."\n";
        echo "New HelferVonSchichtLoeschen returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferVonSchichtLoeschen ok";
}

function TestHelferVonSchichtLoeschen_SchichtID(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferVonSchichtLoeschen_SchichtID($dbl, $HelferID, $SchichtID, $AdminID = 0);
    $erg_new = HelferVonSchichtLoeschen_SchichtID($HelferID, $SchichtID, $AdminID = 0);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferVonSchichtLoeschen_SchichtID returns".var_export($erg_old, true)."\n";
        echo "New HelferVonSchichtLoeschen_SchichtID returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferVonSchichtLoeschen_SchichtID ok";
}

function TestDetailSchicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\DetailSchicht($dbl, $InfoSchichtID);
    $erg_new = DetailSchicht($InfoSchichtID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DetailSchicht returns".var_export($erg_old, true)."\n";
        echo "New DetailSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DetailSchicht ok";
}

function TestBeteiligteHelfer(){
    $dbl = old\ConnectDB();
    $erg_old = old\BeteiligteHelfer($dbl, $InfoSchichtID);
    $erg_new = BeteiligteHelfer($InfoSchichtID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old BeteiligteHelfer returns".var_export($erg_old, true)."\n";
        echo "New BeteiligteHelfer returns '".var_export($erg_new, true)."'\n";
    }
    else echo "BeteiligteHelfer ok";
}

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

function TestGetDiensteChilds(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetDiensteChilds($dbl, $DienstID);
    $erg_new = GetDiensteChilds($DienstID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetDiensteChilds returns".var_export($erg_old, true)."\n";
        echo "New GetDiensteChilds returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetDiensteChilds ok";
}

function TestChangeDienst(){
    $dbl = old\ConnectDB();
    $erg_old = old\ChangeDienst($dbl, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);
    $erg_new = ChangeDienst($DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old ChangeDienst returns".var_export($erg_old, true)."\n";
        echo "New ChangeDienst returns '".var_export($erg_new, true)."'\n";
    }
    else echo "ChangeDienst ok";
}

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

function TestGetDiensteForDay(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetDiensteForDay($dbl, $helferlevel, $datestring);
    $erg_new = GetDiensteForDay($helferlevel, $datestring);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetDiensteForDay returns".var_export($erg_old, true)."\n";
        echo "New GetDiensteForDay returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetDiensteForDay ok";
}

function TestGetSchichtenForDienstForDay(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetSchichtenForDienstForDay($dbl, $DienstID, $datestring);
    $erg_new = GetSchichtenForDienstForDay($DienstID, $datestring);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetSchichtenForDienstForDay returns".var_export($erg_old, true)."\n";
        echo "New GetSchichtenForDienstForDay returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetSchichtenForDienstForDay ok";
}

function TestGetSchichtenEinesDienstes(){
    $dbl = old\ConnectDB();
    $erg_old = old\GetSchichtenEinesDienstes($dbl, $DienstID);
    $erg_new = GetSchichtenEinesDienstes($DienstID);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old GetSchichtenEinesDienstes returns".var_export($erg_old, true)."\n";
        echo "New GetSchichtenEinesDienstes returns '".var_export($erg_new, true)."'\n";
    }
    else echo "GetSchichtenEinesDienstes ok";
}

function TestChangeSchicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\ChangeSchicht($dbl, $SchichtID, $Von, $Bis, $Soll, $Dauer);
    $erg_new = ChangeSchicht($SchichtID, $Von, $Bis, $Soll, $Dauer);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old ChangeSchicht returns".var_export($erg_old, true)."\n";
        echo "New ChangeSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "ChangeSchicht ok";
}

function TestNewSchicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\NewSchicht($dbl, $DienstID, $Von, $Bis, $Soll, $Dauer);
    $erg_new = NewSchicht($DienstID, $Von, $Bis, $Soll, $Dauer);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old NewSchicht returns".var_export($erg_old, true)."\n";
        echo "New NewSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "NewSchicht ok";
}

function TestDeleteSchicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\DeleteSchicht($dbl, $SchichtID, $Rekursiv);
    $erg_new = DeleteSchicht($SchichtID, $Rekursiv);
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DeleteSchicht returns".var_export($erg_old, true)."\n";
        echo "New DeleteSchicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DeleteSchicht ok";
}

function TestAlleHelferSchichtenUebersicht(){
    $dbl = old\ConnectDB();
    $erg_old = old\AlleHelferSchichtenUebersicht($dbl);
    $erg_new = AlleHelferSchichtenUebersicht();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old AlleHelferSchichtenUebersicht returns".var_export($erg_old, true)."\n";
        echo "New AlleHelferSchichtenUebersicht returns '".var_export($erg_new, true)."'\n";
    }
    else echo "AlleHelferSchichtenUebersicht ok";
}

function TestDatenbankAufDeutsch(){
    $dbl = old\ConnectDB();
    $erg_old = old\DatenbankAufDeutsch($dbl);
    $erg_new = DatenbankAufDeutsch();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DatenbankAufDeutsch returns".var_export($erg_old, true)."\n";
        echo "New DatenbankAufDeutsch returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DatenbankAufDeutsch ok";
}

function TestLastInsertId(){
    $dbl = old\ConnectDB();
    $erg_old = old\LastInsertId($dbl);
    $erg_new = LastInsertId();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old LastInsertId returns".var_export($erg_old, true)."\n";
        echo "New LastInsertId returns '".var_export($erg_new, true)."'\n";
    }
    else echo "LastInsertId ok";
}

function TestHelferLevel(){
    $dbl = old\ConnectDB();
    $erg_old = old\HelferLevel($dbl);
    $erg_new = HelferLevel();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old HelferLevel returns".var_export($erg_old, true)."\n";
        echo "New HelferLevel returns '".var_export($erg_new, true)."'\n";
    }
    else echo "HelferLevel ok";
}

//function HelferLevel($db_link){
function TestDebugAusgabeDbErgebnis(){
    $dbl = old\ConnectDB();
    $erg_old = old\DebugAusgabeDbErgebnis($dbl);
    $erg_new = DebugAusgabeDbErgebnis();
    if((gettype($erg_old) != gettype($erg_new)) || ($erg_old != $erg_new)){
        echo "Old DebugAusgabeDbErgebnis returns".var_export($erg_old, true)."\n";
        echo "New DebugAusgabeDbErgebnis returns '".var_export($erg_new, true)."'\n";
    }
    else echo "DebugAusgabeDbErgebnis ok";
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
?>
