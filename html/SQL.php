<?php

require_once 'konfiguration.php';

function ConnectDB()
{
    $db_link = mysqli_connect(
        MYSQL_HOST,
        MYSQL_BENUTZER,
        MYSQL_KENNWORT,
        MYSQL_DATENBANK
    );
    DatenbankAufDeutsch($db_link);
    return $db_link;
}

function CreateHelfer($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel = 1)
{
    $HelferName = mysqli_real_escape_string($db_link, $HelferName);
    $HelferEmail = mysqli_real_escape_string($db_link, $HelferEmail);
    $HelferHandy = mysqli_real_escape_string($db_link, $HelferHandy);
    // level: Teilnehmer/Dauerhelfer/(Teamleiter)
    $HelferLevel = mysqli_real_escape_string($db_link, $HelferLevel);

    $HelferPasswort = "€" . $HelferPasswort . "ß";
    $PasswortHash = password_hash($HelferPasswort, PASSWORD_DEFAULT);


    // Neuen Helfer anlegen
    $sql = "INSERT INTO Helfer(Name,Email,Handy,Status,BildFile,DoReport,Passwort,HelferLevel) VALUES ('$HelferName','$HelferEmail','$HelferHandy',1,'',0,'$PasswortHash','$HelferLevel')";
    $db_erg = mysqli_query($db_link, $sql);
    error_log(date('Y-m-d H:i') . "  CreateHelfer: $HelferName angelegt mit Email $HelferEmail Handy $HelferHandy \n", 3, LOGFILE);
    return $db_erg;
}

// testet fuer urllogin, ob Helfer bereits existiert
function HelferIstVorhanden($db_link, $Email)
{
    $Email = mysqli_real_escape_string($db_link, $Email);
    $sql = "SELECT count(HelferID) as Anzahl FROM Helfer Where Email = '" . $Email . "'";
    $db_erg = mysqli_query($db_link, $sql);
    $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    return $zeile['Anzahl'];
}

//TODO: pruefen, ob Helfer bereits eingeloggt
function HelferLogin($db_link, $HelferEmail, $HelferPasswort, $HelferStatus)
{
        $HelferEmail = mysqli_real_escape_string($db_link, $HelferEmail);
        $HelferStatus = mysqli_real_escape_string($db_link, $HelferStatus);

    //echo "Test<br>";
    // Helfer Suchen
    $sql = "Select HelferID,Admin,Name,Passwort,HelferLevel From Helfer Where Email='" . $HelferEmail . "'";
    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Login ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferPasswort = "€" . $HelferPasswort . "ß";
        //echo "<b>".$HelferPasswort."</b><br>";
        //echo "<b>".$zeile['Passwort']."</b><br>";
        if (password_verify($HelferPasswort, $zeile['Passwort'])) {
            $_SESSION["HelferID"] = $zeile['HelferID'];
            $_SESSION["HelferName"] = $zeile['Name'];
            $_SESSION["HelferEmail"] = $HelferEmail;
            // wird gerade immer gesetzt, kann also in dual admin/helfer Seiten fuer alle verwendet werden
            $_SESSION["AdminID"] = $zeile['HelferID'];
            $_SESSION["AdminStatus"] = $zeile['Admin'];
            $_SESSION["HelferLevel"] = $zeile['HelferLevel'];
            return 1;
        } else {
            echo "Falsches Passwort<br>";
                return 0;
        }
    }
}

// Liste der Helfer fuer Admin-Seite
//TODO: HelferLevel
function HelferListe($db_link)
{

    $sql = "SELECT HelferID,Name FROM Helfer";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Helferliste ungueltige Abfrage";
        die('Unueltige Abfrage: ' . mysqli_error($db_link));
    }

    return $db_erg;
}


function Helferdaten($db_link, $HelferID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);

    $sql = "SELECT * FROM Helfer Where HelferID =" . $HelferID;
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Helferdaten ungueltige Abfrage<br>\n";
        echo "sql:$sql<br>\n";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    return $db_erg;
}



function HelferdatenAendern($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID, $HelferLevel, $HelferIsAdmin = -1, $AdminID = 0)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    $HelferName = mysqli_real_escape_string($db_link, $HelferName);
    $HelferEmail = mysqli_real_escape_string($db_link, $HelferEmail);
    $HelferHandy = mysqli_real_escape_string($db_link, $HelferHandy);
    $HelferLevel = mysqli_real_escape_string($db_link, $HelferLevel);

    if ($HelferNewPasswort == "") {
        //$sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy' ".($HelferIsAdmin!=-1)?',Admin='$HelferIsAdmin.':'." Where HelferId=".$HelferID;
        if ($HelferIsAdmin == -1) {
            $sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy',HelferLevel='$HelferLevel' Where HelferId=" . $HelferID;
        } else {
            $sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy',Admin=$HelferIsAdmin,HelferLevel='$HelferLevel' Where HelferId=" . $HelferID;
        }
        //echo $sql;
        $db_erg = mysqli_query($db_link, $sql);
        echo "<li>Helferdaten geändert</li>";
        if ($AdminID != 0) {
                  error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Admin: $HelferIsAdmin\n", 3, LOGFILE);
        } else {
                  error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Admin: $HelferIsAdmin\n", 3, LOGFILE);
        }
    } else {
        $HelferNewPasswort = "€" . $HelferNewPasswort . "ß";
        $PasswortHash = password_hash($HelferNewPasswort, PASSWORD_DEFAULT);
        if ($HelferIsAdmin == -1) {
            $sql = "UPDATE Helfer SET Name='" . $HelferName . "',Email='" . $HelferEmail . "',Handy='" . $HelferHandy . "',HelferLevel='$HelferLevel',Passwort='" . $PasswortHash . "' Where HelferId=" . $HelferID;
        } else {
            $sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy',HelferLevel='$HelferLevel',Passwort='$PasswortHash',Admin=$HelferIsAdmin Where HelferId=" . $HelferID;
        }
          //echo $sql;
        $db_erg = mysqli_query($db_link, $sql);
        echo "<li>Passwort geändert</li>";
        if ($AdminID != 0) {
                  error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Passwort: neu gesetzt\n", 3, LOGFILE);
        } else {
                  error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Passwort: neu gesetzt\n", 3, LOGFILE);
        }
    }

    if (! $db_erg) {
        echo "HelferdatenAendern ungueltiges Statement";
        echo $sql;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}




function AlleSchichten($db_link, $Sort, $HelferLevel = 1)
{

    $Sort = mysqli_real_escape_string($db_link, $Sort);

    if ($Sort == '1') {
        $sql = "select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=$HelferLevel order by Von";
    } else {
        $sql = "select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=$HelferLevel order by Was,Von";
    }

    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichten ungueltige Abfrage";
        echo $Sort;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}

function AlleSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)
{
    $nurDienst = "";
    if ($DienstID != -1) {
        $nurDienst = " and Dienst.DienstID = $DienstID";
    }
    $nurHelferLevel = "";
    if ($HelferLevel != -1) {
        $nurHelferLevel = " and HelferLevel = $HelferLevel ";
    }

    $sql = "select Sum(Soll) as Anzahl, HelferLevel  from SchichtUebersicht,Dienst Where SchichtUebersicht.DienstID=Dienst.DienstID $nurHelferLevel $nurDienst";


    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichtenCount ungueltige Abfrage";
        echo $Sort;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    return $zeile['Anzahl'];
}


function AlleBelegteSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)
{
    $nurDienst = "";
    if ($DienstID != -1) {
        $nurDienst = " and Dienst.DienstID = $DienstID";
    }
    $nurHelferLevel = "";
    if ($HelferLevel != -1) {
        $nurHelferLevel = " and HelferLevel = $HelferLevel ";
    }


    $sql = "select Count(HelferID) As Anzahl from EinzelSchicht,Schicht,Dienst Where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID=Dienst.DienstID $nurHelferLevel $nurDienst";

    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichtenCount ungueltige Abfrage";
        echo $Sort;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    return $zeile['Anzahl'];
}


function AlleSchichtenImZeitbereich($db_link, $Von, $Bis, $HelferLevel = 1)
{
    //debug only error_log("AlleSchichtenImZeitbereich Abfrage:  $Von, $Bis, $HelferLevel");
    // SchichtID, Was, Ab, Bis, Ist, Tag, Soll - Ist und Soll sind die HelferStunden
    $Von = mysqli_real_escape_string($db_link, $Von);
    $Bis = mysqli_real_escape_string($db_link, $Bis);
    $HelferLevel = mysqli_real_escape_string($db_link, $HelferLevel);
    $sql_helferlevel = "and Dienst.HelferLevel=$HelferLevel";
    if ($HelferLevel == -1) {
        $sql_helferlevel = "";
    }

    $sql = "select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll, Dienst.DienstID from Dienst,SchichtUebersicht where Von >= '" . $Von . "' and Von <'" . $Bis . "' and Dienst.DienstID=SchichtUebersicht.DienstID $sql_helferlevel order by Was,Von";
    // debug only error_log("AlleSchichtenImZeitbereich sql " . $sql);
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichtenImZeitbereich ungueltige Abfrage<br>";
        echo $sql;
        die('<br>Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}


function AlleSchichtenEinesHelfers($db_link, $HelferID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);

    $sql = "select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=" . $HelferID . " order by Von";

    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichtenEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}

function HelferLoeschen($db_link, $HelferID, $AdminID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);


    $db_erg = Helferdaten($db_link, $HelferID);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferName = $zeile['Name'];
        //echo "HelferName=$HelferName<br>";
    }

    $db_erg = AlleSchichtenEinesHelfers($db_link, $HelferID);

    $AnzahlHelferschichten = mysqli_num_rows($db_erg);
    if ($AnzahlHelferschichten == 0) {
        $sql = "Delete from Helfer where HelferID='$HelferID'";
        $db_erg = mysqli_query($db_link, $sql);
        if (! $db_erg) {
            echo "Helfer $HelferName konnte nicht gelöscht werden<br>";
            echo "$sql <br>";
            return -2;
        } else {
            echo "Helfer $HelferName (HelferID:$HelferID) wurde erfolgreich geloescht<br>";
            error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helfer loeschen: Name: $HelferName (HelferID:$HelferID)\n", 3, LOGFILE);
            return 1;
        }
    } else {
        echo "Helfer $HelferName hat noch $AnzahlHelferschichten Schichten. Bitte erst die Schichten löschen<br>";
        return -1;
    }
}

function SchichtIdArrayEinesHelfers($db_link, $HelferID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);

    // Array, um Zeilen mit von mir belegten Schichten in der Schichtuebersicht einfaerben zu koennenn
    $sql = "SELECT SchichtID FROM EinzelSchicht WHERE HelferID = $HelferID";
    //print_r($sql);
    $db_erg = mysqli_query($db_link, $sql);
    $schichtIDs = array();
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_NUM)) {
        $schichtIDs[] = $zeile[0];
    }
    return($schichtIDs);
}

function AlleSchichtenEinesHelfersVonJetzt($db_link, $HelferID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    // TODO: fix GETDATE() array to string conversion
    $sql = "select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=" . $HelferID . " and Bis>'" . date("Y-m-d H:i:s") . "' order by Von";


    //$sql = "select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." and Bis>'2023-05-20' order by Von";

    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "AlleSchichtenEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}

function SchichtenSummeEinesHelfers($db_link, $HelferID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);

    //$sql = "select count Schicht.Dauer as Anzahl  FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." order by Von";
    $sql = "select count(*) as Anzahl, SUM(TIME_TO_SEC(Schicht.Dauer)) as Dauer FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=" . $HelferID;
    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "SchichtenSummeEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        echo $sql;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }


    return $db_erg;
}

function LogSchichtEingabe($db_link, $HelferID, $SchichtId, $EinzelSchichtId, $Aktion, $AdminID = 0)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    $SchichtId = mysqli_real_escape_string($db_link, $SchichtId);
    $EinzelSchichtId = mysqli_real_escape_string($db_link, $EinzelSchichtId);
    $Aktion = mysqli_real_escape_string($db_link, $Aktion);
    $AdminID = mysqli_real_escape_string($db_link, $AdminID);

        $sql = "SELECT Schicht.Von, Schicht.Bis, Dienst.Was, Helfer.Name
                FROM EinzelSchicht 
                JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID 
                JOIN Dienst ON Schicht.DienstID = Dienst.DienstID 
                JOIN Helfer ON EinzelSchicht.HelferID = Helfer.HelferID
                WHERE EinzelSchicht.HelferID = $HelferID
                AND ( Schicht.SchichtID = $SchichtId OR EinzelSchicht.EinzelSchichtID = $EinzelSchichtId)
                ";
        //error_log(date('Y-m-d H:i') . "  " . $sql ."\n",3,LOGFILE);
    $db_erg = mysqli_query($db_link, $sql);

    if (mysqli_num_rows($db_erg) > 1) {
        echo "HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegeben\n <br>";
        // Fehler geht ins normale Error-Management, nicht ins Logfile
        error_log(date('Y-m-d H:i') . "  HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegben.\n", 0);
        error_log(date('Y-m-d H:i') . "sql query: XXX $sql XXX sql query end");
        error_log(date('Y-m-d H:i') . mysqli_fetch_assoc($db_erg));
    } elseif (mysqli_num_rows($db_erg) == 1) {
        $row = mysqli_fetch_assoc($db_erg);
        $Von = $row["Von"];
        $Bis = $row["Bis"];
        $Was = $row["Was"];
        $HelferName = $row["Name"];
    } else {
        echo "Es wurde keine Zeile zurueckgegeben.";
    }

    if ($AdminID == 0) {
          error_log(date('Y-m-d H:i') . "  HelferSchicht: $HelferName (HelferID:$HelferID) hat Dienst $Was von $Von bis $Bis $Aktion.\n", 3, LOGFILE);
    } else {
          error_log(date('Y-m-d H:i') . "  HelferSchicht: Admin:$AdminID hat  von $HelferName (HelferID:$HelferID) den Dienst $Was von $Von bis $Bis $Aktion.\n", 3, LOGFILE);
    }
}

function HelferSchichtZuweisen($db_link, $HelferID, $SchichtId, $AdminID = 0)
{
    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    $SchichtId = mysqli_real_escape_string($db_link, $SchichtId);

        // Abfrage, ob bereits eine Einzelschicht in der selben Schicht vom Helfer existiert
        $sql = "SELECT EinzelSchichtID from EinzelSchicht WHERE SchichtID='$SchichtId' and HelferID='$HelferID'";

        $db_erg = mysqli_query($db_link, $sql);

    if (mysqli_num_rows($db_erg) > 0) {
         echo "HelferSchichtZuweisen: Schicht existiert bereits!";
         // abgeschaltet, damit mehrfacheintraege fuer Familien moeglich sind
         //return false;
    }

    // Helfer Schicht zuweisen
    $sql = 'INSERT INTO EinzelSchicht(SchichtID,HelferID) VALUES (\''
          . $SchichtId . '\',\''
          .  $HelferID . '\')';
    //echo '<script> console.log("Schicht zuweiweisen: '.$sql.'")</script>';
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "HelferSchichtZuweisen ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
        LogSchichtEingabe($db_link, $HelferID, $SchichtId, -1, "eingetragen", $AdminID);

    return $db_erg;
}

function HelferVonSchichtLoeschen($db_link, $HelferID, $EinzelSchichtID, $AdminID = 0)
{
    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    $SchichtId = mysqli_real_escape_string($db_link, $SchichtId);


    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, -1, $EinzelSchichtID, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $sql = "Delete From EinzelSchicht Where EinzelSchichtID =" . $EinzelSchichtID;
    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);


    return $db_erg;
}

function HelferVonSchichtLoeschen_SchichtID($db_link, $HelferID, $SchichtID, $AdminID = 0)
{
    $HelferID = mysqli_real_escape_string($db_link, $HelferID);
    $SchichtId = mysqli_real_escape_string($db_link, $SchichtId);


    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, $SchichtID, -1, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $sql = "Delete From EinzelSchicht Where SchichtID = $SchichtID and HelferID = $HelferID limit 1;";
    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);

    return $db_erg;
}





function DetailSchicht($db_link, $InfoSchichtID)
{
    $InfoSchichtID = mysqli_real_escape_string($db_link, $InfoSchichtID);


    $sql = "select  Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer FROM Dienst,Schicht,Helfer where Dienst.DienstID=Schicht.DienstID AND Helfer.HelferID=Dienst.Leiter And SchichtID=" . $InfoSchichtID;

    //echo $sql;
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "Details ungueltige Abfrage  ";
        echo $InfoSchichtID;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    return $zeile;
}


function BeteiligteHelfer($db_link, $InfoSchichtID)
{
    $InfoSchichtID = mysqli_real_escape_string($db_link, $InfoSchichtID);

    $sql = "select  Helfer.HelferID,Name,Handy FROM EinzelSchicht,Helfer where EinzelSchicht.HelferID=Helfer.HelferID And SchichtID=" . $InfoSchichtID;

    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "Details ungueltige Abfrage  ";
        echo $InfoSchichtID;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    return $db_erg;
}

function GetDienste($db_link)
{
    $sql = "SELECT DienstID, Was, Wo, Info, Leiter, ElternDienstID, HelferLevel FROM Dienst order By Was";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}

function GetDiensteChilds($db_link, $DienstID)
{
    $DienstID = mysqli_real_escape_string($db_link, $DienstID);

    $sql = "SELECT DienstID, Was, Wo, Info, Leiter FROM Dienst where ElternDienstID='" . $DienstID . "' order by Was";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}


function ChangeDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    $DienstID = mysqli_real_escape_string($db_link, $DienstID);
    $Was = mysqli_real_escape_string($db_link, $Was);
    $Wo = mysqli_real_escape_string($db_link, $Wo);
    $Info = mysqli_real_escape_string($db_link, $Info);
    $Leiter = mysqli_real_escape_string($db_link, $Leiter);
    $Gruppe = mysqli_real_escape_string($db_link, $Gruppe);
    $HelferLevel = mysqli_real_escape_string($db_link, $HelferLevel);     // int (1,2) Teilnehmer oder Dauerhelfer

    $sql = "UPDATE Dienst SET Was='{$Was}', Wo='{$Wo}', Info='{$Info}', Leiter={$Leiter}, ElternDienstID={$Gruppe} where DienstID={$DienstID}";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Fehler Change Dienst";
        echo $sql;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
}

function NewDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{

    $DienstID = mysqli_real_escape_string($db_link, $DienstID);
    $Was = mysqli_real_escape_string($db_link, $Was);           //Name des Dienstes
    $Wo = mysqli_real_escape_string($db_link, $Wo);             //Ort
    $Info = mysqli_real_escape_string($db_link, $Info);         //vollstaendige Beschreibung
    $Leiter = mysqli_real_escape_string($db_link, $Leiter);     // int HelferID des Leiters
    $Gruppe = mysqli_real_escape_string($db_link, $Gruppe);     // ??
    $HelferLevel = mysqli_real_escape_string($db_link, $HelferLevel);     // int (1,2) Teilnehmer oder Dauerhelfer


    $sql = "INSERT INTO Dienst (Was, Wo, Info, Leiter, ElternDienstID, HelferLevel) values ('$Was','$Wo','$Info',$Leiter,$Gruppe,$HelferLevel)";

    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Fehler New Dienst";
        //        echo $sql;
                $err =  mysqli_error($db_link);
        die('Ungueltige Abfrage: ' . $err);
                error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName   konnte Schicht nicht angelegt mit Anfrage $sql   Grund: $err  \n", 3, LOGFILE);
    } else {
            error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName(ID:HelferID)  hat Dienst angelegt mit Was: $WAS Wo: $Wo Info: $Info Leiter: $Leiter Gruppe $Gruppe, HelferLevel $HelferLevel  \n", 3, LOGFILE);
    }
}

function DeleteDienst($db_link, $DienstID, $Rekursiv)
{
    $DienstID = mysqli_real_escape_string($db_link, $DienstID);


    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Schichten eingetragen sind
        $sql = "SELECT SchichtID FROM Schicht where DienstID=" . $DienstID;
        $db_erg = mysqli_query($db_link, $sql);
        if (! $db_erg) {
            echo "Fehler DeleteDienst";
            die('Ungueltige Abfrage: ' . mysqli_error($db_link));
        }

        if (mysqli_num_rows($db_erg) == 0) {
            // Eintrag löschen
            $sql = "DELETE FROM Dienst where DienstID=" . $DienstID;

            echo $sql;
            $db_erg = mysqli_query($db_link, $sql);
            if (! $db_erg) {
                echo "Fehler DeleteDienst";
                die('Ungueltige Abfrage: ' . mysqli_error($db_link));
            }
            return true;
        } else {
            return false;
        }
    }
}


function GetDiensteForDay($db_link, $helferlevel, $datestring)
{
    $unixtime = strtotime($datestring);
    $date1 = date('Y-m-d', $unixtime + 24 * 60 * 60);
    $date2 = date('Y-m-d', $unixtime);
    $sql = "SELECT DienstId, Was, Wo, Info FROM Dienst INNER JOIN Schicht USING (DienstID) WHERE HelferLevel=" . $helferlevel . " GROUP BY DienstId HAVING MIN(Von)<'" . $date1 . "' AND MAX(Bis)>'" . $date2 . "' ORDER BY MIN(Von) ASC;";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}

function GetSchichtenForDienstForDay($db_link, $DienstID, $datestring)
{
    $unixtime = strtotime($datestring);
    $date1 = date('Y-m-d', $unixtime + 24 * 60 * 60);
    $date2 = date('Y-m-d', $unixtime);
    $sql = "select Von, Bis, Soll, Name, Handy from Schicht left join EinzelSchicht using (SchichtId) left join Helfer using (HelferId) where DienstId=" . $DienstID . " and Von<'" . $date1 . "' and Bis>'" . $date2 . "' order by Von;";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}


function GetSchichtenEinesDienstes($db_link, $DienstID)
{
    $DienstID = mysqli_real_escape_string($db_link, $DienstID);


    //$sql = "SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon FROM Schicht where DienstID=".$DienstID;
    $sql = "SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon, DATE_FORMAT(Von,'%H:%i') AS ZeitVon, DATE_FORMAT(Bis,'%H:%i') AS ZeitBis, DATE_FORMAT(Dauer,'%H:%i') AS Dauer FROM Schicht where DienstID=" . $DienstID;
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "GetSchichtenEinesDienstes ungueltige Abfrage";
        echo $sql;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}

function ChangeSchicht($db_link, $SchichtID, $Von, $Bis, $Soll, $Dauer)
{
    $SchichtID = mysqli_real_escape_string($db_link, $SchichtID);
    $Von = mysqli_real_escape_string($db_link, $Von);
    $Bis = mysqli_real_escape_string($db_link, $Bis);
    $Soll = mysqli_real_escape_string($db_link, $Soll);


    $sql = "UPDATE Schicht SET Von='" . $Von . "', Bis='" . $Bis . "', Soll='" . $Soll . "', Dauer='" . $Dauer . "' where SchichtID=" . $SchichtID;

    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Fehler ChangeSchicht";
        echo $sql;
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
}

function NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll, $Dauer, $HelferName)
{

    $DienstID = mysqli_real_escape_string($db_link, $DienstID);
    $Von = mysqli_real_escape_string($db_link, $Von);
    $Bis = mysqli_real_escape_string($db_link, $Bis);
    $Soll = mysqli_real_escape_string($db_link, $Soll);

    /*
    if(validateDate($Von))
    {
        echo "Keine Schicht erstellt Fehler in Von";
        return;
    }
    if(validateDate($Bis, DateTime::ATOM))
    {
        echo "Keine Schicht erstellt Fehler in Bis";
        return Null;
    }
    */
    //$sql = "INSERT INTO Schicht (DienstID, Von, Bis, Soll, Dauer) values ('" . $DienstID . "','" . $Von . "','" . $Bis . "'," . $Soll . ",'" . $Dauer .  "')";
    $sql = "INSERT INTO Schicht (DienstID, Von, Bis, Soll, Dauer) values ('" . $DienstID . "','" . $Von . "','" . $Bis . "'," . $Soll . ",'" . $Dauer .  "')";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Keine Schicht erstellt";
        echo $sql;
                error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName   konnte Schicht nicht angelegt mit $sql  \n", 3, LOGFILE);
                $err = mysqli_error($db_link);
        die('Ungueltige Abfrage: ' . $err);
    } else {
        //TODO: DienstID aufloesen
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName  hat Schicht angelegt mit DienstID $DienstID, Von $Von Bis $Bis Soll $Soll  \n", 3, LOGFILE);
    }
}

function DeleteSchicht($db_link, $SchichtID, $Rekursiv)
{
    $SchichtID = mysqli_real_escape_string($db_link, $SchichtID);

    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Helfer auf der Schicht eingetragen sind
        $sql = "SELECT Name FROM EinzelSchicht,Helfer where SchichtID=" . $SchichtID . " and Helfer.HelferID=EinzelSchicht.HelferID";
        $db_erg = mysqli_query($db_link, $sql);
        if (! $db_erg) {
            echo "Fehler Change Dienst";
            die('Ungueltige Abfrage: ' . mysqli_error($db_link));
        }

        if (mysqli_num_rows($db_erg) == 0) {
            // Eintrag löschen
            $sql = "DELETE FROM Schicht where SchichtID=" . $SchichtID;

            echo $sql;
            $db_erg = mysqli_query($db_link, $sql);
            if (! $db_erg) {
                echo "Fehler Change Dienst";
                die('Ungueltige Abfrage: ' . mysqli_error($db_link));
            }
            return true;
        } else {
            return false;
        }
    }
}


function AlleHelferSchichtenUebersicht($db_link, $HelferLevel)
{
    $sql = "
SELECT 
    Helfer.HelferID AS AliasHelferID, -- Alias für HelferID
    Helfer.HelferLevel,
    Name,
    Email,
    Handy,
    Was,
    COALESCE(SUM(Dauer)/10000, 0) AS Dauer
FROM 
    Helfer
LEFT JOIN 
    EinzelSchicht ON Helfer.HelferID = EinzelSchicht.HelferID
LEFT JOIN 
    Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
LEFT JOIN 
    Dienst ON Schicht.DienstID = Dienst.DienstID
WHERE Helfer.HelferLevel = $HelferLevel
GROUP BY 
    Helfer.HelferID, 
    Was";

    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "AlleHelferSchichtenUebersicht ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}


function DatenbankAufDeutsch($db_link)
{
    $sql = "SET lc_time_names = 'de_DE'";
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "ungueltiges umstellen auf Deutsch";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
}

function LastInsertId($db_link)
{
    $sql = "SELECT LAST_INSERT_ID()";
    $db_erg = mysqli_query($db_link, $sql);

    if (! $db_erg) {
        echo "ungueltige Last InsertID";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    $zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC);
    return $zeile['LAST_INSERT_ID()'];
}

function HelferLevel($db_link)
{
    $sql = "select HelferLevel,HelferLevelBeschreibung from HelferLevel";
    $db_erg = mysqli_query($db_link, $sql);
    if (! $db_erg) {
        echo "Konnte HelferLevel nicht abfragen";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $db_erg;
}

function alleHelferLevel($db_link)
{
    $alleHelferLevel = array();
    $db_erg = HelferLevel($db_link);
    while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
        $HelferLevel = $zeile['HelferLevel'];
        $HelferLevelBeschreibung = $zeile['HelferLevelBeschreibung'];
        $alleHelferLevel[$HelferLevel] = $HelferLevelBeschreibung;
    };
    return $alleHelferLevel;
}



// TODO: als Array zurueckgeben (CreateHelfer anpassen)
// TODO:
//function HelferLevel($db_link){
//    $sql = "SELECT HelferLevel, HelferLevelBeschreibung FROM HelferLevel";
//    $db_erg = mysqli_query($db_link, $sql);
//    if (!$db_erg) {
//        echo "Konnte HelferLevel nicht abfragen";
//        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
//    }
//
//    $results = array();
//    while ($row = mysqli_fetch_assoc($db_erg)) {
//        $results[] = $row;
//    }
//
//    mysqli_free_result($db_erg); // Freigabe des Ergebnisobjekts
//
//    return $results;
//}


function DebugAusgabeDbErgebnis($db_erg)
{
    // Ausgabe auf Browser Console

    // if(mysqli_num_rows($db_erg) > 0) {
    // $fields = mysqli_fetch_fields($db_erg);
    // $field_names = [];
    // foreach($fields as $field) {
    // $field_names[] = $field->name;
    // }
    // $rows = array();
    // while($row = mysqli_fetch_row($db_erg)) {
    // $rows[] = $row;
    // }
    //
    // $js_code = "console.log('Query results:');";
    // $js_code .= "console.log('" . implode('\t', $field_names) . "');";
    // foreach($rows as $row) {
    // $js_code .= "console.log('" . implode('\t', $row) . "');";
    // }
    // echo "<script>" . $js_code . "</script>";
    // } else {
    // echo "Keine Ergebnisse gefunden.";
    // }
    //


    // direkte ausgabe in Seite
    echo "<table>";
    if (mysqli_num_rows($db_erg) > 0) {
        // Tabellenkopf ausgeben
        echo "<tr>";
        $fields = mysqli_fetch_fields($db_erg);
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";

        // Tabelleninhalt ausgeben
        while ($row = mysqli_fetch_row($db_erg)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>Keine Ergebnisse gefunden.</td></tr>";
    }
    echo "</table>";
}
