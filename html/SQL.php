<?php

require_once 'konfiguration.php';

class DB {
    private static $instance = null;
    private static $pdo = null;
    private static $stmts = array();

    private function __construct()
    {
        self::$pdo = new PDO(
                MYSQL_DSN,
                MYSQL_BENUTZER,
                MYSQL_KENNWORT,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING)
                );
    }

    public static function getInstance()
    {
        if(self::$instance == null){
            self::$instance = new DB();
            // Set database to german (FIXME should be configurable)
            self::prepare(__METHOD__,"SET lc_time_names = 'de_DE'");
            self::execute(__METHOD__);
        }
        return self::$instance;
    }

    public function prepare($method, $sql, $variant = '')
    {
        return self::$stmts[$method . "::" . $variant] = self::$pdo->prepare($sql);
    }

    public function execute($method, $values = array(), $variant = '')
    {
        return self::$stmts[$method . "::" . $variant]->execute($values);
    }

    public function fetch($method, $variant = '', int $mode = PDO::FETCH_BOTH, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0)
    {
        return self::$stmts[$method . "::" . $variant]->fetch($mode,$cursorOrientation,$cursorOffset);
    }
    public function executeScript($method, $filename, $variant = '')
    {
        $sqlFromFile = file_get_contents($filename);
        $sqlStatements = explode(";", $sqlFromFile);
        $statementcounter = 0;
        foreach($sqlStatements as $sqlStatement){
            if(self::prepare($method, $sqlStatement.";", $variant) == false){
                return array();
            };
            $retval[$statementcounter] = self::execute($method,array(),$variant);
            if(!is_null(self::pdoErrorCode()) && self::pdoErrorCode() != '00000'){
                return $retval;
            }
            $statementcounter++;
        }
        return $retval;
    }
    public function fetchAll($method, $variant = '')
    {
        return self::$stmts[$method . "::" . $variant]->fetchAll(PDO::FETCH_ASSOC);
    }
    public function pdoErrorCode()
    {
        return self::$pdo->errorCode();
    }
    public function pdoErrorInfo()
    {
        return self::$pdo->errorInfo();
    }
    public function errorCode($method, $variant = '')
    {
        return self::$stmts[$method . "::" . $variant]->errorCode();
    }

    public function errorInfo($method, $variant = '')
    {
        return self::$stmts[$method . "::" . $variant]->errorInfo();
    }

    public function onErrorDie($method, $variant = '')
    {
        if (!is_null(self::errorCode($method, $variant)) && self::errorCode($method, $variant) != '00000') {
            echo $method . "::" . $variant . " ungueltige Abfrage<br>\n";
            echo "sql:" . $stmts[$method . "::" . $variant]->queryString . "<br>\n";
            die('Ungueltige Abfrage: ' . self::errorInfo($method, $variant)[2]);
        }
    }
}

// ok
function CreateHelfer($HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel = 1)
{
    // Neuen Helfer anlegen
    $HelferPasswort = "€" . $HelferPasswort . "ß";
    $PasswortHash = password_hash($HelferPasswort, PASSWORD_DEFAULT);

    $db = DB::getInstance();
    $db->prepare(__METHOD__,"INSERT INTO Helfer(Name,Email,Handy,Status,BildFile,DoReport,Passwort,HelferLevel)".
        " VALUES (:name,:email,:handy,:status,:bildfile,:doreport,:passwort,:helferlevel)");
    $db_erg = $db->execute(__METHOD__,[
        "name" => $HelferName,
        "email" => $HelferEmail,
        "handy" => $HelferHandy,
        "status" => 1,
        "bildfile" => '',
        "doreport" => 0,
        "passwort" => $PasswortHash,
        "helferlevel" => $HelferLevel
    ]);

    error_log(date('Y-m-d H:i') . "  CreateHelfer: $HelferName angelegt mit Email $HelferEmail Handy $HelferHandy \n", 3, LOGFILE);
    return $db_erg;
}

// ok
// testet fuer urllogin, ob Helfer bereits existiert
function HelferIstVorhanden($Email)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT count(HelferID) AS Anzahl FROM Helfer WHERE Email = :email");
    $db->execute(__METHOD__,["email" => $Email]);
    // TODO Test, that this still works
    $zeile = $db->fetchAll(__METHOD__);
    return $zeile[0]['Anzahl'];
}

// ok
//TODO: pruefen, ob Helfer bereits eingeloggt
function HelferLogin($HelferEmail, $HelferPasswort, $HelferStatus)
{
    //echo "Test<br>";
    // Helfer Suchen
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT HelferID,Admin,Name,Passwort,HelferLevel FROM Helfer WHERE Email=:email");
    $db_erg = $db->execute(__METHOD__,["email" => $HelferEmail]);
    if (!is_null($db->errorCode(__METHOD__)) && $db->errorCode(__METHOD__) != '00000') {
        echo "Login ungueltige Abfrage";
        die('Ungueltige Abfrage: ' . $db->errorInfo(__METHOD__)[2]);
    }
    while ($zeile = $db->fetchAll(__METHOD__)) {
        $HelferPasswort = "€" . $HelferPasswort . "ß";
        //echo "<b>".$HelferPasswort."</b><br>";
        //echo "<b>".$zeile['Passwort']."</b><br>";
        if (password_verify($HelferPasswort, $zeile[0]['Passwort'])) {
            $_SESSION["HelferID"] = $zeile[0]['HelferID'];
            $_SESSION["HelferName"] = $zeile[0]['Name'];
            //TODO: das sollte nur gesetzt werden, wenn der Helfer Admin ist
            $_SESSION["AdminID"] = $zeile[0]['HelferID'];
            $_SESSION["AdminStatus"] = $zeile[0]['Admin'];
            $_SESSION["HelferLevel"] = $zeile[0]['HelferLevel'];
            return 1;
        } else {
            echo "Falsches Passwort<br>";
            return 0;
        }
    }
}

// ok
// Liste der Helfer fuer Admin-Seite
//TODO: HelferLevel
function HelferListe()
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT HelferID,Name FROM Helfer");
    $db_erg = $db->execute(__METHOD__);
    $db->onErrorDie(__METHOD__);
    $helfer = $db->fetchAll(__METHOD__);
    return $helfer;
}

// ok
function Helferdaten($HelferID)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT * FROM Helfer Where HelferID = :helferid");
    $db_erg = $db->execute(__METHOD__,["helferid" => $HelferID]);
    $db->onErrorDie(__METHOD__);
    $helferdaten = $db->fetchAll(__METHOD__);
    return $helferdaten;
}


// ok
function HelferdatenAendern($HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID, $HelferIsAdmin = -1, $AdminID = 0)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy Where HelferId=:id",'password_empty');
    $db->prepare(__METHOD__,"UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Admin=:admin Where HelferId=:id",'password_empty_admin');
    $db->prepare(__METHOD__,"UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Passwort=:passwort Where HelferId=:id",'password_given');
    $db->prepare(__METHOD__,"UPDATE Helfer SET Name=:name,Email=:email,Handy=:handy,Passwort=:passwort,Admin=:admin Where HelferId=:id",'password_given_admin');

    if ($HelferNewPasswort == "") {
        //$sql = "UPDATE Helfer SET Name='$HelferName',Email='$HelferEmail',Handy='$HelferHandy' ".($HelferIsAdmin!=-1)?',Admin='$HelferIsAdmin.':'." Where HelferId=".$HelferID;
        if ($HelferIsAdmin == -1) {
            $db_erg = $db->execute(__METHOD__,[
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "id" => $HelferID
            ],'password_empty');
            $db->onErrorDie(__METHOD__,'password_empty');
        } else {
            $db_erg = $db->execute(__METHOD__,[
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "admin" => $HelferIsAdmin,
                "id" => $HelferID
            ],'password_empty_admin'); 
            $db->onErrorDie(__METHOD__,'password_empty_admin');
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
            $db_erg = $db->execute(__METHOD__,[
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "passwort" => $PasswortHash,
                "id" => $HelferID
            ],'password_given');
            $db->onErrorDie(__METHOD__, 'password_given');
        } else {
            $db_erg = $db->execute(__METHOD__,[
                "name" => $HelferName,
                "email" => $HelferEmail,
                "handy" => $HelferHandy,
                "passwort" => $PasswortHash,
                "admin" => $HelferIsAdmin,
                "id" => $HelferID
            ],'password_given_admin');
            $db->onErrorDie(__METHOD__, 'password_given_admin');
        }
          //echo $sql;
        echo "<li>Passwort geändert</li>";
        if ($AdminID != 0) {
                  error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Passwort: neu gesetzt\n", 3, LOGFILE);
        } else {
                  error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy Passwort: neu gesetzt\n", 3, LOGFILE);
        }
    }

    return $db_erg;
}

function AlleSchichten($Sort, $HelferLevel = 1)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=:helferlevel order by Von",'sort_by_von');
    $db->prepare(__METHOD__,"select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.Helferlevel=:helferlevel order by Was,Von",'sort_by_was_von');

    if ($Sort == '1') {
        $db_erg = $db->execute(__METHOD__,["helferlevel" => $HelferLevel],'sort_by_von');
        $db->onErrorDie(__METHOD__,'sort_by_von');
    } else {
        $db_erg = $db->execute(__METHOD__,["helferlevel" => $HelferLevel],'sort_by_was_von');
        $db->onErrorDie(__METHOD__,'sort_by_was_von');
    }

    return $db_erg;
}

function AlleSchichtenCount($HelferLevel = 1)
{

    //$sql = "select SUM(Soll) As Anzahl from SchichtUebersicht where HelferLevel=$HelferLevel";
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select Sum(Soll) as Anzahl, HelferLevel  from SchichtUebersicht,Dienst Where SchichtUebersicht.DienstID=Dienst.DienstID and HelferLevel=:helferlevel");
    $db_erg = $db->execute(__METHOD__,["helferlevel" => $HelferLevel]);
    $db->onErrorDie(__METHOD__);
    $zeile = $db->fetchAll(__METHOD__);
    return $zeile['Anzahl'];
}


function AlleBelegteSchichtenCount($db_link, $HelferLevel = 1)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select Count(HelferID) As Anzahl from EinzelSchicht,Schicht,Dienst Where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID=Dienst.DienstID and HelferLevel=:helferlevel");
    $db_erg = $db->execute(__METHOD__,["helferlevel" => $Helferlevel]);
    $db->onErrorDie(__METHOD__);
    $zeile = $db->fetchAll(__METHOD__);
    return $zeile['Anzahl'];
}


function AlleSchichtenImZeitbereich($Von, $Bis, $HelferLevel = 1)
{
    // SchichtID, Was, Ab, Bis, Ist, Tag, Soll - Ist und Soll sind die HelferStunden

    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Von >= :von and Von < :bis and Dienst.DienstID=SchichtUebersicht.DienstID order by Was,Von",'helferlevel_not_set');
    $db->prepare(__METHOD__,"select SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') As Tag, Soll  from Dienst,SchichtUebersicht where Von >= :von and Von < :bis and Dienst.DienstID=SchichtUebersicht.DienstID and Dienst.HelferLevel=:helferlevel order by Was,Von",'helferlevel_set');

    if ($HelferLevel == -1) {
        $db_erg = $db->execute(__METHOD__,[
            "von" => $Von,
            "bis" => $Bis
        ],'helferlevel_not_set');
        $db->onErrorDie(__METHOD__,'helferlevel_not_set');
    }
    else {
        $db_erg = $db->execute(__METHOD__,[
            "von" => $Von,
            "bis" => $Bis,
            "helferlevel" => $HelferLevel
        ],'helferlevel_set');
        $db->onErrorDie(__METHOD__,'helferlevel_set');
    }

    return $db_erg;
}


function AlleSchichtenEinesHelfers($HelferID)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:helferid order by Von");
    $db_erg = $db->execute(__METHOD__,["helferid" => $HelferID]);
    $db->onErrorDie(__METHOD__);
    return $db_erg;
}

// FIXME
function HelferLoeschen($db_link, $HelferID, $AdminID)
{

    $HelferID = mysqli_real_escape_string($db_link, $HelferID);

    static $stmt = false;
    static $stmt_prepared = false;
    if(!$stmt_prepared) {
        $stmt = $pdo->prepare("Delete from Helfer where HelferID='$HelferID'");
        $stmt_prepared = true;
    }

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

function SchichtIdArrayEinesHelfers($HelferID)
{
    // Array, um Zeilen mit von mir belegten Schichten in der Schichtuebersicht einfaerben zu koennenn
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT SchichtID FROM EinzelSchicht WHERE HelferID = :id");
    $db_erg = $db->execute(__METHOD__,["id" => $HelferID]);

    $schichtIDs = array();
    while ($zeile = $db->fetch(__METHOD__)) {
        $schichtIDs[] = $zeile[0];
    }
    return($schichtIDs);
}

function AlleSchichtenEinesHelfersVonJetzt($HelferID)
{
    // TODO: fix GETDATE() array to string conversion
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:id and Bis>:bis order by Von");

    //$sql = "select EinzelSchicht.SchichtID ,EinzelSchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,DATE_FORMAT(Bis,'%a %H:%i') AS Bis FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." and Bis>'2023-05-20' order by Von";

    $db_erg = $db->execute(__METHOD__,[
        "id" => $HelferID,
        "bis" => GETDATE()
    ]);
    $db->onErrorDie(__METHOD__);
    return $db_erg;
}

function SchichtenSummeEinesHelfers($db_link, $HelferID)
{

    //$sql = "select count Schicht.Dauer as Anzahl  FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=".$HelferID." order by Von";
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select count(*) as Anzahl, SUM(TIME_TO_SEC(Schicht.Dauer)) as Dauer FROM  EinzelSchicht,Schicht,Dienst where EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID = Dienst.DienstID and HelferID=:helferid");
    //echo $sql;
    $db_erg = $db->execute(__METHOD__,["helferid" => $HelferID]);
    $db->onErrorDie(__METHOD__);
    return $db_erg;
}


// FIXME
function LogSchichtEingabe($db_link, $HelferID, $SchichtId, $EinzelSchichtId, $Aktion, $AdminID = 0)
{
    $db = DB::getInstance();

    $db->prepare(__METHOD__,"SELECT Schicht.Von, Schicht.Bis, Dienst.Was, Helfer.Name
        FROM EinzelSchicht 
        JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID 
        JOIN Dienst ON Schicht.DienstID = Dienst.DienstID 
        JOIN Helfer ON EinzelSchicht.HelferID = Helfer.HelferID
        WHERE EinzelSchicht.HelferID = $HelferID
        AND ( Schicht.SchichtID = $SchichtId OR EinzelSchicht.EinzelSchichtID = $EinzelSchichtId)
        ");
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

function HelferSchichtZuweisen($HelferID, $SchichtId, $AdminID = 0)
{
    // Abfrage, ob bereits eine Einzelschicht in der selben Schicht vom Helfer existiert
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT EinzelSchichtID from EinzelSchicht WHERE SchichtID=:schichtid and HelferID=:helferid",'einzelschicht_exists');
    $db->prepare(__METHOD__,"INSERT INTO EinzelSchicht(SchichtID,HelferID) VALUES (:schichtid,:helferid)",'new_einzelschicht');

    $db_erg = $db->execute(__METHOD__,[
        "schichtid" => $SchichtId,
        "helferid" => $HelferID
    ],'einzelschicht_exists');

    if($db->fetch(__METHOD__,'einzelschicht_exists')){
         echo "HelferSchichtZuweisen: Schicht existiert bereits!";
         return false;
    }

    // Helfer Schicht zuweisen
    //echo '<script> console.log("Schicht zuweiweisen: '.$sql.'")</script>';
    $db_erg = $db->execute(__METHOD__,[
        "schichtid" => $SchichtId,
        "helferid" => $HelferID
    ],'new_einzelschicht');

    $db->onErrorDie(__METHOD__,'new_einzelschicht');

    LogSchichtEingabe($db_link, $HelferID, $SchichtId, -1, "eingetragen", $AdminID);

    return $db_erg;
}

function HelferVonSchichtLoeschen($HelferID, $EinzelSchichtID, $AdminID = 0)
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, -1, $EinzelSchichtID, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"Delete From EinzelSchicht Where EinzelSchichtID = :id");

    //echo $sql;
    $db_erg = $db->execute(__METHOD__,["id" => $EinzelSchichtID]);

    return $db_erg;
}

function HelferVonSchichtLoeschen_SchichtID($HelferID, $SchichtID, $AdminID = 0)
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, $SchichtID, -1, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"Delete From EinzelSchicht Where SchichtID = :schichtid and HelferID = :helferid limit 1;");
    //echo $sql;
    $db_erg = $db->execute(__METHOD__,[
        "schichtid" => $SchichtID,
        "helferid" => $HelferID
    ]);

    return $db_erg;
}

function DetailSchicht($InfoSchichtID)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select  Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer FROM Dienst,Schicht,Helfer where Dienst.DienstID=Schicht.DienstID AND Helfer.HelferID=Dienst.Leiter And SchichtID=:id");

    //echo $sql;
    $db_erg = $db->execute(__METHOD__,["id" => $InfoSchichtID]);

    $db->onErrorDie(__METHOD__);

    $zeile = $db->fetchAll(__METHOD__);
    return $zeile;
}

function BeteiligteHelfer($InfoSchichtID)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select  Helfer.HelferID,Name,Handy FROM EinzelSchicht,Helfer where EinzelSchicht.HelferID=Helfer.HelferID And SchichtID=:id");
    $db_erg = $db->execute(__METHOD__,["id" => $InfoSchichtID]);
    $db->onErrorDie(__METHOD__);
    return $db_erg;
}

// ok
function GetDienste()
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT DienstID, Was, Wo, Info, Leiter, ElternDienstID, HelferLevel FROM Dienst order By Was");
    $db_erg = $db->execute(__METHOD__);
    $db->onErrorDie(__METHOD__);
    $dienste = $db->fetchAll(__METHOD__);
    return $dienste;
}

// ok
function GetDiensteChilds($DienstID)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT DienstID, Was, Wo, Info, Leiter FROM Dienst where ElternDienstID=:id order by Was");
    $db_erg = $db->execute(__METHOD__,["id" => $DienstID]);
    $db->onErrorDie(__METHOD__);
    $dienste = $db->fetchAll(__METHOD__);
    return $dienste;
}

// ok
function ChangeDienst($DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"UPDATE Dienst SET Was=:was, Wo=:wo, Info=:info, Leiter=:leiter, ElternDienstID=:elterndienstid where DienstID=:dienstid");

    $db_erg = $db->execute(__METHOD__,[
        "was" => $Was,
        "wo" => $Wo,
        "info" => $Info,
        "leiter" => $Leiter,
        "elterndienstid" => $Gruppe,
        "dienstid" => $DienstID
    ]);

    $db->onErrorDie(__METHOD__);
}

// ok
function NewDienst($Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"INSERT INTO Dienst (Was, Wo, Info, Leiter, ElternDienstID, HelferLevel) values (:was,:wo,:info,:leiter,:elterndienstid,:helferlevel)");
    $db_erg = $db->execute(__METHOD__,[
        "was" => $Was,
        "wo" => $Wo,
        "info" => $Info,
        "leiter" => $Leiter,
        "elterndienstid" => $Gruppe,
        "helferlevel" => $HelferLevel
    ]);

    if  (!is_null($db->errorCode(__METHOD__)) && $db->errorCode(__METHOD__) != '00000'){
        $err =  $db->errorInfo(__METHOD__)[2];
        error_log(date('Y-m-d H:i') . "  NeueSchicht: Schicht konnte nicht angelegt werden  Grund: $err  \n", 3, LOGFILE);
        die('Ungueltige Abfrage: ' . $err);
    } else {
        error_log(date('Y-m-d H:i') . "  NeueSchicht: Dienst wurde angelegt mit Was: $Was Wo: $Wo Info: $Info Leiter: $Leiter Gruppe $Gruppe, HelferLevel $HelferLevel  \n", 3, LOGFILE);
    }
}

// ok
function DeleteDienst($DienstID, $Rekursiv)
{
    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Schichten eingetragen sind
        $db = DB::getInstance();
        $db->prepare(__METHOD__,"SELECT SchichtID FROM Schicht where DienstID=:id",'check_schicht');
        $db->prepare(__METHOD__,"DELETE FROM Dienst where DienstID=:id",'delete_dienst');
        
        $db_erg = $db->execute(__METHOD__,['id' => $DienstID],'check_schicht');

        $db->onErrorDie(__METHOD__,'check_schicht');

        if (!$db->fetch(__METHOD__,'check_schicht')){
            // Eintrag löschen
            $db_erg = $db->execute(__METHOD__,['id' => $DienstID],'delete_dienst');
            $db->onErrorDie(__METHOD__,'delete_dienst');
            return true;
        } else {
            return false;
        }
    }
}

// ok
function GetSchichtenForDienstForDay($DienstID, $datestring)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select Von, Bis, Soll, Name, Handy from Schicht left join EinzelSchicht using (SchichtId) left join Helfer using (HelferId) where DienstId=:id and Von<:von and Bis>:bis order by Von;");
    $unixtime = strtotime($datestring);
    $db_erg = $db->execute(__METHOD__,[
        'id' => $DienstID,
        'von' => date('Y-m-d', $unixtime + 24 * 60 * 60),
        'bis' => date('Y-m-d', $unixtime)
    ]);
    $db->onErrorDie(__METHOD__);
    $schichten = $db->fetchAll(__METHOD__);
    return $schichten;
}


// ok
function GetSchichtenEinesDienstes($DienstID)
{
    //$sql = "SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon FROM Schicht where DienstID=".$DienstID;
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i') AS TagVon, DATE_FORMAT(Von,'%H:%i') AS ZeitVon, DATE_FORMAT(Bis,'%H:%i') AS ZeitBis, DATE_FORMAT(Dauer,'%H:%i') AS Dauer FROM Schicht where DienstID=:id");
    $db_erg = $db->execute(__METHOD__,['id' => $DienstID]);
    $db->onErrorDie(__METHOD__);
    $schichten = $db->fetchAll(__METHOD__);
    return $schichten;
}

// ok
function ChangeSchicht($SchichtID, $Von, $Bis, $Soll, $Dauer)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"UPDATE Schicht SET Von=:von, Bis=:bis, Soll=:soll, Dauer=:dauer where SchichtID=:id");

    $db_erg = $db->execute(__METHOD__,[
        'von' => $Von,
        'bis' => $Bis,
        'soll' => $Soll,
        'id' => $SchichtID,
        'dauer' => $Dauer
    ]);

    $db->onErrorDie(__METHOD__);
}

// ok
function NewSchicht($DienstID, $Von, $Bis, $Soll, $Dauer)
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
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"INSERT INTO Schicht (DienstID, Von, Bis, Soll, Dauer) values (:id,:von,:bis,:soll,:dauer)");

    $db_erg = $db->execute(__METHOD__,[
        'id' => $DienstID,
        'von' => $Von,
        'bis' => $Bis,
        'soll' => $Soll,
        'dauer' => $Dauer
    ]);

    if (!is_null($db->errorCode(__METHOD__)) && $db->errorCode(__METHOD__) != '00000') {
        echo "Keine Schicht erstellt";
        //echo $sql;
        error_log(date('Y-m-d H:i') . "  NeueSchicht: Schicht konnte nicht angelegt werden mit $sql  \n", 3, LOGFILE);
        $err = $db->errorInfo(__METHOD__)[2];
        die('Ungueltige Abfrage: ' . $err);
    } else {
        //TODO: DienstID aufloesen
        error_log(date('Y-m-d H:i') . "  NeueSchicht: Schicht wurde angelegt mit DienstID $DienstID, Von $Von Bis $Bis Soll $Soll  \n", 3, LOGFILE);
    }
}

function DeleteSchicht($SchichtID, $Rekursiv)
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SELECT Name FROM EinzelSchicht,Helfer where SchichtID=:id and Helfer.HelferID=EinzelSchicht.HelferID",'check_einzelschicht');
    $db->prepare(__METHOD__,"DELETE FROM Schicht where SchichtID=:id",'delete_einzelschicht');

    if ($Rekursiv) {
        return false;
    } else {
        // Pruefen ob noch Helfer auf der Schicht eingetragen sind
        $db_erg = $db->execute(__METHOD__,["id" => $SchichtID ],'check_einzelschicht');

        $db->onErrorDie(__METHOD__,'check_einzelschicht');

        if (!$db->fetch(__METHOD__,'check_einzelschicht')) {
            // Eintrag löschen
            $db_erg = $db->execute(__METHOD__,["id" => $SchichtID ],'delete_einzelschicht');
            $db->onErrorDie(__METHOD__,'delete_einzelschicht');
            return true;
        } else {
            return false;
        }
    }
}


function AlleHelferSchichtenUebersicht()
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select Helfer.HelferID as AliasHelferID,Name,Email,Handy,Was,SUM(Dauer)/10000 as Dauer from Helfer,EinzelSchicht INNER JOIN Schicht INNER JOIN Dienst where Helfer.HelferID=EinzelSchicht.HelferID and EinzelSchicht.SchichtID=Schicht.SchichtID and Schicht.DienstID=Dienst.DienstID group by Helfer.HelferID,Was");
    $db_erg = $db->execute(__METHOD__);
    $db->onErrorDie(__METHOD__);
    return $db_erg;
}


function DatenbankAufDeutsch()
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"SET lc_time_names = 'de_DE'");
    $db_erg = $db->execute(__METHOD__);
    $db->onErrorDie(__METHOD__);
}


function HelferLevel()
{
    $db = DB::getInstance();
    $db->prepare(__METHOD__,"select HelferLevel,HelferLevelBeschreibung from HelferLevel");
    $db_erg = $stmt->execute(__METHOD__);
    $db->onErrorDie(__METHOD__);
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

//FIXME
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
