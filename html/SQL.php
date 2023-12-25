
<?php

require_once 'konfiguration.php';

function ConnectDB()
{
    $datasourcename = "mysql:host=" . MYSQL_HOST . ";dbname=". MYSQL_DATENBANK . ";charset=utf8mb4";
    $pdo = new PDO(
        $datasourcename,
        MYSQL_BENUTZER,
        MYSQL_KENNWORT
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    return $pdo;
}

function CreateHelfer($pdo, $HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel = 1)
{
    // Neuen Helfer anlegen
    $HelferPasswort = "€" . $HelferPasswort . "ß";
    $PasswortHash = password_hash($HelferPasswort, PASSWORD_DEFAULT);

    static $sql = "INSERT INTO Helfer(Name,Email,Handy,Status,BildFile,DoReport,Passwort,HelferLevel)".
        " VALUES (:name,:email,:handy,:status,:bildfile,:doreport,:passwort,:helferlevel)";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $db_erg = $stmt->execute([
        "name" => $HelferName,
        "email" => $HelferEmail,
        "handy" => $HelferHandy,
        "status" => 1,
        "bildfile" => '',
        "doreport" => 0,
        "passwort" => $PasswortHash,
        "helferlevel" => $HelferLevel
    ]);

    $stmt->fetch();
    error_log(date('Y-m-d H:i') . "  CreateHelfer: $HelferName angelegt mit Email $HelferEmail Handy $HelferHandy \n", 3, LOGFILE);
    return $db_erg;
}

// testet fuer urllogin, ob Helfer bereits existiert
function HelferIstVorhanden($pdo, $Email)
{
    static $sql = "SELECT count(HelferID) AS Anzahl FROM Helfer WHERE Email = :email";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $stmt->execute(["email" => $Email]);
    // TODO Test, that this still works
    $zeile = $stmt->fetchAll();
    return $zeile['Anzahl'];
}

//TODO: pruefen, ob Helfer bereits eingeloggt
function HelferLogin($pdo, $HelferEmail, $HelferPasswort, $HelferStatus)
{
    //echo "Test<br>";
    // Helfer Suchen
    static $sql = "SELECT HelferID,Admin,Name,Passwort,HelferLevel FROM Helfer WHERE Email=:email";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $db_erg = $stmt->execute(["email" => $HelferEmail]);
    if ($stmt->errorCode() != 1) {
        echo "Login ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    while ($zeile = $stmt->fetchAll()) {
        $HelferPasswort = "€" . $HelferPasswort . "ß";
        //echo "<b>".$HelferPasswort."</b><br>";
        //echo "<b>".$zeile['Passwort']."</b><br>";
        if (password_verify($HelferPasswort, $zeile['Passwort'])) {
            $_SESSION["HelferID"] = $zeile['HelferID'];
            $_SESSION["HelferName"] = $zeile['Name'];
            //TODO: das sollte nur gesetzt werden, wenn der Helfer Admin ist
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
function HelferListe($pdo)
{

    static $sql = "SELECT HelferID,Name FROM Helfer";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $db_erg = $stmt->execute();
    if ($stmt->errorCode() != 1) {
        echo "Helferliste ungueltige Abfrage";
        die('Unueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    return $db_erg;
}


function Helferdaten($pdo, $HelferID)
{

    static $sql = "SELECT * FROM Helfer Where HelferID = :helferid";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $db_erg = $stmt->execute(["helferid" => $HelferID]);

    if ($stmt->errorCode() != 1) {
        echo "Helferdaten ungueltige Abfrage<br>\n";
        echo "sql:$sql<br>\n";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    return $db_erg;
}



function HelferdatenAendern($pdo, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID, $HelferIsAdmin = -1, $AdminID = 0)
{

    static $stmts_prepared = false;
    static $stmts = array();
    if(!$stmts_prepared) {
        $stmts['password_empty'] = $pdo->prepare("UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy Where HelferId=:id");
        $stmts['password_empty_admin'] = $pdo->prepare("UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Admin=:admin Where HelferId=:id");
        $stmts['password_given'] = $pdo->prepare("UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Passwort=:passwort Where HelferId=:id");
        $stmts['password_given_admin'] = $pdo->prepare("UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Passwort=:passwort,Admin=:admin Where HelferId=:id");
        $stmts_prepared = true;
    }

    if ($HelferNewPasswort == "") {
        //$sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy' ".($HelferIsAdmin!=-1)?',Admin='$HelferIsAdmin.':'." Where HelferId=".$HelferID;
        if ($HelferIsAdmin == -1) {
            $db_erg = $stmts['password_empty']->execute([
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "id" => $HelferID
            ]);
        } else {
            $db_erg = $stmts['password_empty_admin']->execute([
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "admin" => $HelferIsAdmin,
                "id" => $HelferID
            ]); 
        }
        echo "<li>Helferdaten geändert</li>";
        if ($AdminID != 0) {
            error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Admin: $HelferIsAdmin\n", 3, LOGFILE);
        } else {
            error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Admin: $HelferIsAdmin\n", 3, LOGFILE);
        }
    } else {
        $HelferNewPasswort = "€" . $HelferNewPasswort . "ß";
        $PasswortHash = password_hash($HelferNewPasswort, PASSWORD_DEFAULT);
        if ($HelferIsAdmin == -1) {
            $db_erg = $stmts['password_given']->execute([
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "passwort" => $PasswortHash,
                "id" => $HelferID
            ]);
        } else {
            $db_erg = $stmts['password_given_admin']->execute([
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "passwort" => $PasswortHash,
                "admin" => $HelferIsAdmin,
                "id" => $HelferID
            ]);
        }
          //echo $sql;
        echo "<li>Passwort geändert</li>";
        if ($AdminID != 0) {
                  error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Passwort: neu gesetzt\n", 3, LOGFILE);
        } else {
                  error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Passwort: neu gesetzt\n", 3, LOGFILE);
        }
    }

    foreach ($stmts as $stmt) {
        if (!is_null($stmt->errorCode()) && ($stmt->errorCode() != 1) ) {
            echo "HelferdatenAendern ungueltiges Statement";
            echo $stmt->queryString;
            die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
        }
    }

    return $db_erg;
}




function AlleSchichten($pdo, $Sort, $HelferLevel = 1)
{
    static $stmts_prepared = false;
    static $stmts = array();
    if(!$stmts_prepared) {
        $stmts['sort_by_von'] = $pdo->prepare("select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=:helferlevel order by Von");
        $stmts['sort_by_was_von'] = $pdo->prepare("select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=:helferlevel order by Was,Von");
        $stmts_prepared = true;
    }

    if ($Sort == '1') {
        $db_erg = $stmts['sort_by_von']->execute(["helferlevel" => $HelferLevel]);
    } else {
        $db_erg = $stmts['sort_by_was_von']->execute(["helferlevel" => $HelferLevel]);
    }

    foreach ($stmts as $stmt) {
        if (!is_null($stmt->errorCode()) && ($stmt->errorCode() != 1) ) {
            echo "AlleSchichten ungueltige Abfrage";
            echo $Sort;
            die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
        }
    }

    return $db_erg;
}

function AlleSchichtenCount($db_link, $HelferLevel = 1)
{

    //$sql = "select SUM(Soll) As Anzahl from SchichtUebersicht where HelferLevel=$HelferLevel";
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select Sum(Soll) as Anzahl, HelferLevel  from SchichtUebersicht,Dienst Where SchichtUebersicht.DienstID=Dienst.DienstID and HelferLevel=:helferlevel");
    }


    $db_erg = $stmt->execute(["helferlevel" => $HelferLevel]);

    if  ($stmt->errorCode() != 1){
        echo "AlleSchichtenCount ungueltige Abfrage";
        echo $Sort;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    $zeile = $stmt->fetchAll();
    return $zeile['Anzahl'];
}


function AlleBelegteSchichtenCount($db_link, $HelferLevel = 1)
{

    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select Count(HelferID) As Anzahl from EinzelSchicht,Schicht,Dienst Where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID=Dienst.DienstID and HelferLevel=:helferlevel");
    }


    $db_erg = $stmt->execute(["helferlevel" => $Helferlevel]);

    if  ($stmt->errorCode() != 1){
        echo "AlleSchichtenCount ungueltige Abfrage";
        echo $Sort;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    $zeile = $stmt->fetchAll();
    return $zeile['Anzahl'];
}


function AlleSchichtenImZeitbereich($pdo, $Von, $Bis, $HelferLevel = 1)
{
    // SchichtID, Was, Ab, Bis, Ist, Tag, Soll - Ist und Soll sind die HelferStunden

    static $stmts_prepared = false;
    static $stmts = array();
    if(!$stmts_prepared) {
        $stmts['helferlevel_not_set'] = $pdo->prepare("select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Von >= :von and Von < :bis and Dienst.DienstID=SchichtUebersicht.DienstID order by Was,Von");
        $stmts['helferlevel_set'] = $pdo->prepare("select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Von >= :von and Von < :bis and Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.HelferLevel=:helferlevel order by Was,Von");
        $stmts_prepared = true;
    }

    if ($HelferLevel == -1) {
        $db_erg = $stmts['helferlevel_not_set']->execute([
            "von" => $Von,
            "bis" => $Bis
        ]);
    }
    else {
        $db_erg = $stmts['helferlevel_set']->execute([
            "von" => $Von,
            "bis" => $Bis,
            "helferlevel" => $HelferLevel
        ]);
    }

    foreach ($stmts as $stmt) {
        if (!is_null($stmt->errorCode()) && ($stmt->errorCode() != 1) ) {
            echo "AlleSchichtenImZeitbereich ungueltige Abfrage<br>";
            echo $stmt->queryString;
            die('<br>Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
        }
    }

    return $db_erg;
}


function AlleSchichtenEinesHelfers($pdo, $HelferID)
{

    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:helferid order by Von");
    }


    $db_erg = $stmt->execute(["helferid" => $HelferID]);

    if ($stmt->errorCode() != 1){
        echo "AlleSchichtenEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
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
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("SELECT SchichtID FROM EinzelSchicht WHERE HelferID = $HelferID");
    }
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

    // TODO: fix GETDATE() array to string conversion
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:id and Bis>:bis order by Von");
    }

    //$sql = "select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." and Bis>'2023-05-20' order by Von";

    //echo $sql;
    $db_erg = $stmt->execute([
        "id" => $HelferID,
        "bis" => GETDATE()
    ]);

    if($stmt->errorCode() != 1){
        echo "AlleSchichtenEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    return $db_erg;
}

function SchichtenSummeEinesHelfers($db_link, $HelferID)
{

    //$sql = "select count Schicht.Dauer as Anzahl  FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." order by Von";
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select count(*) as Anzahl, SUM(TIME_TO_SEC(Schicht.Dauer)) as Dauer FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:helferid");
    }
    //echo $sql;
    $db_erg = $stmt->execute(["helferid" => $HelferID]);

    if  ($stmt->errorCode() != 1){
        echo "SchichtenSummeEinesHelfers ungueltige Abfrage";
        echo $HelferID;
        echo $stmt->queryString;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
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

    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("SELECT Schicht.Von, Schicht.Bis, Dienst.Was, Helfer.Name
                FROM EinzelSchicht 
                JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID 
                JOIN Dienst ON Schicht.DienstID = Dienst.DienstID 
                JOIN Helfer ON EinzelSchicht.HelferID = Helfer.HelferID
                WHERE EinzelSchicht.HelferID = $HelferID
                AND ( Schicht.SchichtID = $SchichtId OR EinzelSchicht.EinzelSchichtID = $EinzelSchichtId)
                ");
    }
        //error_log(date('Y-m-d H:i') . "  " . $sql ."\n",3,LOGFILE);
    $db_erg = mysqli_query($db_link, $sql);

    if (mysqli_num_rows($db_erg) > 1) {
        echo "HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegeben\n <br>";
        // Fehler geht ins normale Error-Management, nicht ins Logfile
        error_log(date('Y-m-d H:i') . "  HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegben.\n", 0);
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

function HelferSchichtZuweisen($pdo, $HelferID, $SchichtId, $AdminID = 0)
{
    // Abfrage, ob bereits eine Einzelschicht in der selben Schicht vom Helfer existiert
    static $stmts_prepared = false;
    static $stmts = false;
    if(!$stmts_prepared) {
        $stmts['einzelschicht_exists'] = $pdo->prepare("SELECT EinzelSchichtID from EinzelSchicht WHERE SchichtID=:schichtid and HelferID=:helferid");
        $stmts['new_einzelschicht'] = $pdo->prepare("INSERT INTO EinzelSchicht(SchichtID,HelferID) VALUES (:schichtid,:helferid)");
        $stmts_prepared = true;
    }

    $db_erg = $stmts['einzelschicht_exists']->execute([
        "schichtid" => $SchichtId,
        "helferid" => $HelferID
    ]);

    if($stmt['einzelschicht_exists']->fetch()){
         echo "HelferSchichtZuweisen: Schicht existiert bereits!";
         return false;
    }

    // Helfer Schicht zuweisen
    //echo '<script> console.log("Schicht zuweiweisen: '.$sql.'")</script>';
    $db_erg = $stmts['new_einzelschicht']->execute([
        "schichtid" => $SchichtId,
        "helferid" => $HelferID
    ]);

    if  ($stmts['new_einzelschicht']->errorCode() != 1){
        echo "HelferSchichtZuweisen ungueltige Abfrage";
        echo $HelferID;
        die('Ungueltige Abfrage: ' . $stmts['new_einzelschicht']->errorInfo()[2]);
    }
    LogSchichtEingabe($db_link, $HelferID, $SchichtId, -1, "eingetragen", $AdminID);

    return $db_erg;
}

function HelferVonSchichtLoeschen($pdo, $HelferID, $EinzelSchichtID, $AdminID = 0)
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, -1, $EinzelSchichtID, "entfernt", $AdminID);

    // Lösche Einzelschicht
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("Delete From EinzelSchicht Where EinzelSchichtID = :id");
    }

    //echo $sql;
    $db_erg = $stmt->execute(["id" => $EinzelSchichtID]);

    return $db_erg;
}

function HelferVonSchichtLoeschen_SchichtID($pdo, $HelferID, $SchichtID, $AdminID = 0)
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, $SchichtID, -1, "entfernt", $AdminID);

    // Lösche Einzelschicht
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("Delete From EinzelSchicht Where SchichtID = :schichtid and HelferID = :helferid limit 1;");
    }
    //echo $sql;
    $db_erg = $stmt->execute([
        "schichtid" => $SchichtID,
        "helferid" => $HelferID
    ]);

    return $db_erg;
}





function DetailSchicht($pdo, $InfoSchichtID)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select  Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer FROM Dienst,Schicht,Helfer where Dienst.DienstID=Schicht.DienstID AND Helfer.HelferID=Dienst.Leiter And SchichtID=:id");
    }

    //echo $sql;
    $db_erg = $stmt->execute(["id" => $InfoSchichtID]);

    if  ($stmt->errorCode() != 1){
        echo "Details ungueltige Abfrage  ";
        echo $InfoSchichtID;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    $zeile = $stmt->fetchAll();
    return $zeile;
}


function BeteiligteHelfer($pdo, $InfoSchichtID)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select  Helfer.HelferID,Name,Handy FROM EinzelSchicht,Helfer where EinzelSchicht.HelferID=Helfer.HelferID And SchichtID=:id");
    }

    $db_erg = $stmt->execute(["id" => $InfoSchichtID]);

    if  ($stmt->errorCode() != 1){
        echo "Details ungueltige Abfrage  ";
        echo $InfoSchichtID;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }

    return $db_erg;
}

function GetDienste($db_link)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("SELECT DienstID, Was, Wo, Info, Leiter, ElternDienstID, HelferLevel FROM Dienst order By Was");
    }
    $db_erg = mysqli_query($db_link, $sql);
    if  ($stmt->errorCode() != 1){
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    return $db_erg;
}

function GetDiensteChilds($pdo, $DienstID)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("SELECT DienstID, Was, Wo, Info, Leiter FROM Dienst where ElternDienstID=:id order by Was");
    }

    $db_erg = $stmt->execute(["id" => $DienstID]);

    if  ($stmt->errorCode() != 1){
        echo "GetDienste ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    return $db_erg;
}


function ChangeDienst($pdo, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("UPDATE Dienst SET Was=:was, Wo=:wo, Info=:info, Leiter=:leiter, ElternDienstID=:elterndienstid where DienstID=:dienstid");
    }


    $db_erg = $stmt->execute([
        "was" => $Was,
        "wo" => $Wo,
        "info" => $Info,
        "leiter" => $Leiter,
        "elterndienstid" => $Gruppe,
        "dienstid" => $DienstID
    ]);

    if  ($stmt->errorCode() != 1){
        echo "Fehler Change Dienst";
        echo $sql;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
}

function NewDienst($pdo, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("INSERT INTO Dienst (Was, Wo, Info, Leiter, ElternDienstID, HelferLevel) values (:was,:wo,:info,:leiter,:elterndienstid,:helferlevel)");
    }

    $db_erg = $stmt->execute([
        "was" => $Was,
        "wo" => $Wo,
        "info" => $Info,
        "leiter" => $Leiter,
        "elterndienstid" => $Gruppe,
        "helferlevel" => $HelferLevel
    ]);

    $db_erg = mysqli_query($db_link, $sql);
    if  ($stmt->errorCode() != 1){
        echo "Fehler New Dienst";
        //        echo $sql;
        $err =  $stmt->errorInfo()[2];
        die('Ungueltige Abfrage: ' . $err);
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName   konnte Schicht nicht angelegt mit Anfrage $sql   Grund: $err  \n", 3, LOGFILE);
    } else {
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName(ID:HelferID)  hat Dienst angelegt mit Was: $WAS Wo: $Wo Info: $Info Leiter: $Leiter Gruppe $Gruppe, HelferLevel $HelferLevel  \n", 3, LOGFILE);
    }
}

function DeleteDienst($db_link, $DienstID, $Rekursiv)
{
    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Schichten eingetragen sind
        static $stmts_prepared = false;
        static $stmts = array();
        if(!$stmts_prepared) {
            $stmts['check_dienst'] = $pdo->prepare("SELECT SchichtID FROM Schicht where DienstID=:id");
            $stmts['delete_dienst'] = $pdo->prepare("DELETE FROM Dienst where DienstID=:id");
            $stmts_prepared = true;
        }
        
        $db_erg = $stmts['check_dienst']->execute(['id' => $DienstID]);

        if  ($stmts['check_dienst']->errorCode() != 1){
            echo "Fehler DeleteDienst";
            die('Ungueltige Abfrage: ' . $stmts['check_dienst']->errorInfo()[2]);
        }

        if ($stmts['check_dienst']->fetch()) {
            // Eintrag löschen
            $db_erg = $stmts['delete_dienst']->execute(['id' => $DienstID]);
            if  ($stmts['delete_dienst']->errorCode() != 1){
                echo "Fehler DeleteDienst";
                die('Ungueltige Abfrage: ' . $stmts['delete_dienst']->errorInfo()[2]);
            }
            return true;
        } else {
            return false;
        }
    }
}

function GetSchichtenEinesDienstes($db_link, $DienstID)
{

    //$sql = "SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon FROM Schicht where DienstID=".$DienstID;
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon, DATE_FORMAT(Von,'%H:%i') AS ZeitVon, DATE_FORMAT(Bis,'%H:%i') AS ZeitBis FROM Schicht where DienstID=:id");
    }
    $db_erg = $stmt->execute(['id' => $DienstID]);
    if  ($stmt->errorCode() != 1){
        echo "GetSchichtenEinesDienstes ungueltige Abfrage";
        echo $sql;
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    return $db_erg;
}

function ChangeSchicht($db_link, $SchichtID, $Von, $Bis, $Soll)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("UPDATE Schicht SET Von=:von, Bis=:bis, Soll=:soll where SchichtID=:id");
    }

    $db_erg = $stmt->execute([
        'von' => $Von,
        'bis' => $Bis,
        'soll' => $Soll,
        'id' => $SchichtID
    ]);

    if  ($stmt->errorCode() != 1){
        echo "Fehler ChangeSchicht";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
}

function NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll)
{

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
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("INSERT INTO Schicht (DienstID, Von, Bis, Soll) values (:id,:von,:bis,:soll)");
    }

    $db_erg = $stmt->execute([
        'id' => $DienstID,
        'von' => $Von,
        'bis' => $Bis,
        'soll' => $Soll
    ]);

    if  ($stmt->errorCode() != 1){
        echo "Keine Schicht erstellt";
        //echo $sql;
                error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName   konnte Schicht nicht angelegt mit $sql  \n", 3, LOGFILE);
                $err = $stmt->errorInfo()[2];
        die('Ungueltige Abfrage: ' . $err);
    } else {
        //TODO: DienstID aufloesen
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName  hat Schicht angelegt mit DienstID $DienstID, Von $Von Bis $Bis Soll $Soll  \n", 3, LOGFILE);
    }
}

function DeleteSchicht($pdo, $SchichtID, $Rekursiv)
{
    static $stmts_prepared = false;
    static $stmts = false;
    if(!$stmts_prepared) {
        $stmts['check_einzelschicht'] = $pdo->prepare("SELECT Name FROM EinzelSchicht,Helfer where SchichtID=:id and Helfer.HelferID=EinzelSchicht.HelferID");
        $stmts['delete_einzelschicht'] = $pdo->prepare("DELETE FROM Schicht where SchichtID=:id");
        $stmts_prepared = true;
    }

    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Helfer auf der Schicht eingetragen sind
        $db_erg = $stmts['check_einzelschicht']->execute(["id" => $SchichtID ]);

        if  ($stmts['check_einzelschicht']->errorCode() != 1){
            echo "Fehler Change Dienst";
            die('Ungueltige Abfrage: ' . $stmts['check_einzelschicht']->errorInfo()[2]);
        }

        if (!$stmts['check_einzelschicht']->fetch()) {
            // Eintrag löschen
            $db_erg = $stmts['delete_einzelschicht']->execute(["id" => $SchichtID ]);
            if ($stmts['delete_einzelschicht']->errorCode() != 1) {
                echo "Fehler Change Dienst";
                die('Ungueltige Abfrage: ' . $stmts['delete_einzelschicht']->errorInfo()[2]);
            }
            return true;
        } else {
            return false;
        }
    }
}


function AlleHelferSchichtenUebersicht($pdo)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select Helfer.HelferID as AliasHelferID,Name,Email,Handy,Was,SUM(Dauer)/10000 as Dauer from Helfer,EinzelSchicht INNER JOIN Schicht INNER JOIN Dienst where Helfer.HelferID=EinzelSchicht.HelferID and EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID=Dienst.DienstID group by Helfer.HelferID,Was");
    }
    $db_erg = $stmt->execute();

    if  ($stmt->errorCode() != 1){
        echo "AlleHelferSchichtenUebersicht ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    return $db_erg;
}


function DatenbankAufDeutsch($pdo)
{
    static $sql = "SET lc_time_names = 'de_DE'";
    static $stmt = false;
    if(!$stmt) $stmt = $pdo->prepare($sql);
    $db_erg = $stmt->execute();

    if  ($stmt->errorCode() != 1){
        echo "ungueltiges umstellen auf Deutsch";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
}


function HelferLevel($db_link)
{
    static $stmt = false;
    if(!$stmt) {
        $stmt = $pdo->prepare("select HelferLevel,HelferLevelBeschreibung from HelferLevel");
    }
    $db_erg = $stmt->execute();
    if($stmt->errorCode() != 1){
        echo "Konnte HelferLevel nicht abfragen";
        die('Ungueltige Abfrage: ' . $stmt->errorInfo()[2]);
    }
    return $db_erg;
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
