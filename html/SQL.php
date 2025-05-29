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
function debug_sql($sql, $types, $params) {
    $i = 0;
    $parts = explode('?', $sql);
    $reconstructed = '';
    foreach ($parts as $part) {
        $reconstructed .= $part;
        if ($i < strlen($types)) {
            $val = $params[$i];
            $type = $types[$i];
            if ($type === 's') {
                $reconstructed .= "'" . addslashes($val) . "'";
            } else {
                $reconstructed .= $val;
            }
            $i++;
        }
    }
    return $reconstructed;
}

function stmt_prepare_and_execute($db_link, $sql, $types = "", ...$params) {
    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        $err = "Prepare failed: " . mysqli_error($db_link) . "\nSQL: $sql";
        echo($err);
        $err .= debug_sql($sql, $types, $params);
        error_log($err);
        return false;
    }

    if ($types !== "") {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = "Execute failed: " . mysqli_stmt_error($stmt) . "\nSQL: $sql";
        echo($err);
        $err .= debug_sql($sql, $types, $params);
        error_log($err);
        return false;
    }

    return $stmt;
}



function CreateHelfer($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferPasswort, $HelferLevel = 1) #stmt
{

    $HelferPasswort = "€" . $HelferPasswort . "ß";
    $PasswortHash = password_hash($HelferPasswort, PASSWORD_DEFAULT);

    // Prepared Statement erstellen
    $sql = "INSERT INTO Helfer (Name, Email, Handy, Status, BildFile, DoReport, Passwort, HelferLevel)
            VALUES (?, ?, ?, 1, '', 0, ?, ?)";
    $stmt = mysqli_prepare($db_link, $sql);

    if (!$stmt) {
        error_log("Fehler beim Vorbereiten des Statements: " . mysqli_error($db_link));
        return false;
    }
    // Parameter binden (ssssi = 4 Strings + 1 Integer)
    mysqli_stmt_bind_param($stmt, "ssssi", $HelferName, $HelferEmail, $HelferHandy, $PasswortHash, $HelferLevel);

    // Query ausführen
    $success = mysqli_stmt_execute($stmt);

    if (!$success) {
        error_log("Fehler beim Einfügen: " . mysqli_stmt_error($stmt));
    }

    $db_erg = $success ? $stmt : false;
    mysqli_stmt_close($stmt);
    error_log(date('Y-m-d H:i') . "  CreateHelfer: $HelferName angelegt mit Email $HelferEmail Handy $HelferHandy \n", 3, LOGFILE);

    return $db_erg;
}


// Testet fuer urllogin, ob Helfer bereits existiert
function HelferIstVorhanden($db_link, $Email)#stmt
{
    $sql = "SELECT COUNT(HelferID) AS Anzahl FROM Helfer WHERE Email = ?";
    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        error_log("Fehler beim Vorbereiten des Statements: " . mysqli_error($db_link));
        return false;
    }
    mysqli_stmt_bind_param($stmt, "s", $Email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $zeile['Anzahl'] ?? 0;
}


//TODO: pruefen, ob Helfer bereits eingeloggt
function HelferLogin($db_link, $HelferEmail, $HelferPasswort, $HelferStatus)#stmt
{
    $sql = "SELECT HelferID, Admin, Name, Passwort, HelferLevel FROM Helfer WHERE Email = ?";
    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        error_log("Fehler beim Vorbereiten des Statements: " . mysqli_error($db_link));
        die('Login ungültige Abfrage');
    }
    mysqli_stmt_bind_param($stmt, "s", $HelferEmail);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Login execute fehlgeschlagen: " . mysqli_stmt_error($stmt));
        die('Login: Fehler beim Ausführen der Abfrage.');
    }
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($zeile = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $HelferPasswort = "€" . $HelferPasswort . "ß";

        if (password_verify($HelferPasswort, $zeile['Passwort'])) {
            $_SESSION["HelferID"] = $zeile['HelferID'];
            $_SESSION["HelferName"] = $zeile['Name'];
            $_SESSION["HelferEmail"] = $HelferEmail;
            $_SESSION["AdminStatus"] = $zeile['Admin'];
            if( $_SESSION["AdminStatus"] == 1) {
                $_SESSION["AdminID"] = $zeile['HelferID'];
            }
            $_SESSION["HelferLevel"] = $zeile['HelferLevel'];

            mysqli_stmt_close($stmt);
            return 1;
        } else {
            echo "Falsches Passwort<br>";
            mysqli_stmt_close($stmt);
            return 0;
        }
    }

    mysqli_stmt_close($stmt);
    return 0;
}


// Liste der Helfer fuer Admin-Seite
//TODO: HelferLevel

function HelferListe($db_link)#stmt
{
    $sql = "SELECT HelferID, Name FROM Helfer";
    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        echo "Helferliste ungültige Abfrage";
        die('Ungültige Abfrage: ' . mysqli_error($db_link));
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result;
}


function Helferdaten($db_link, $HelferID)#stmt
{

    $sql = "SELECT * FROM Helfer Where HelferID = ?";
    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        echo "Helferdaten: Fehler beim Vorbereiten des Statements<br>\n";
        die('Prepare failed: ' . mysqli_error($db_link));
    }
    mysqli_stmt_bind_param($stmt, "i", $HelferID);
    if (!mysqli_stmt_execute($stmt)) {
        echo "Helferdaten: Fehler bei der Ausführung<br>\n";
        die('Execution failed: ' . mysqli_stmt_error($stmt));
    }
    $db_erg = mysqli_stmt_get_result($stmt);
    return $db_erg;
}



function HelferdatenAendern($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID, $HelferLevel = -1, $HelferIsAdmin = -1, $AdminID = 0)#stmt
{
    $result = false;
    if ($HelferLevel == -1) { $HelferLevel = $_SESSION["HelferLevel"]; }
    if ($HelferNewPasswort == "") {
        if ($HelferIsAdmin == -1) {
            $sql = "UPDATE Helfer SET Name = ?, Email = ?, Handy = ?, HelferLevel = ? WHERE HelferId = ?";
            $stmt = mysqli_prepare($db_link, $sql);
            if (!$stmt) {
                die("Prepare failed: " . mysqli_error($db_link));
            }
            mysqli_stmt_bind_param($stmt, "ssssi", $HelferName, $HelferEmail, $HelferHandy, $HelferLevel, $HelferID);
        } else {
            $sql = "UPDATE Helfer SET Name = ?, Email = ?, Handy = ?, Admin = ?, HelferLevel = ? WHERE HelferId = ?";
            $stmt = mysqli_prepare($db_link, $sql);
            if (!$stmt) {
                die("Prepare failed: " . mysqli_error($db_link));
            }
            mysqli_stmt_bind_param($stmt, "ssssii", $HelferName, $HelferEmail, $HelferHandy, $HelferIsAdmin, $HelferLevel, $HelferID);
        }

        if (!mysqli_stmt_execute($stmt)) {
            die("Execute failed: " . mysqli_stmt_error($stmt));
        }

        $result = true;
        echo "<li>Helferdaten geändert</li>";

        $log_prefix = ($AdminID != 0) ? "(Admin $AdminID) " : "";
        error_log(date('Y-m-d H:i') . " {$log_prefix}Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Admin: $HelferIsAdmin\n", 3, LOGFILE);

        mysqli_stmt_close($stmt);
    } else {
        $HelferNewPasswort = "€" . $HelferNewPasswort . "ß";
        $PasswortHash = password_hash($HelferNewPasswort, PASSWORD_DEFAULT);

        if ($HelferIsAdmin == -1) {
            $sql = "UPDATE Helfer SET Name = ?, Email = ?, Handy = ?, HelferLevel = ?, Passwort = ? WHERE HelferId = ?";
            $stmt = mysqli_prepare($db_link, $sql);
            if (!$stmt) {
                die("Prepare failed: " . mysqli_error($db_link));
            }
            mysqli_stmt_bind_param($stmt, "sssssi", $HelferName, $HelferEmail, $HelferHandy, $HelferLevel, $PasswortHash, $HelferID);
        } else {
            $sql = "UPDATE Helfer SET Name = ?, Email = ?, Handy = ?, HelferLevel = ?, Passwort = ?, Admin = ? WHERE HelferId = ?";
            $stmt = mysqli_prepare($db_link, $sql);
            if (!$stmt) { die("Prepare failed: " . mysqli_error($db_link)); }
            mysqli_stmt_bind_param($stmt, "ssssssi", $HelferName, $HelferEmail, $HelferHandy, $HelferLevel, $PasswortHash, $HelferIsAdmin, $HelferID);
        }

        if (!mysqli_stmt_execute($stmt)) { die("HelferdatenAendern failed: " . mysqli_stmt_error($stmt)); }

        $result = true;
        echo "<li>Passwort geändert</li>";
        if ($AdminID != 0) {
                  error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Passwort: neu gesetzt\n", 3, LOGFILE);
        } else {
                  error_log(date('Y-m-d H:i') . "Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Passwort: neu gesetzt\n", 3, LOGFILE);
        }
        $log_prefix = ($AdminID != 0) ? "(Admin $AdminID) " : "";
        error_log(date('Y-m-d H:i') . " {$log_prefix}Helferdaten update: Name: $HelferName (HelferID:$HelferID) Email: $HelferEmail Handy: $HelferHandy HelferLevel: $HelferLevel Passwort: neu gesetzt\n", 3, LOGFILE);

        mysqli_stmt_close($stmt);
    }
    $_SESSION["HelferName"] = $HelferName;
    $_SESSION["HelferEmail"] = $HelferEmail;

    return $result;
}

function AlleSchichten($db_link, $Sort, $HelferLevel = 1)#stmt
{
    $sql =  "SELECT SchichtID,Was,DATE_FORMAT(Von,'%a %H:%i') AS Ab,";
    $sql .= "DATE_FORMAT(Bis,'%a %H:%i') AS Bis,C AS Ist,DATE_FORMAT(Von,'%W %d %M') AS Tag,Soll ";
    $sql .= "FROM Dienst,SchichtUebersicht WHERE Dienst.DienstID=SchichtUebersicht.DienstID AND Dienst.Helferlevel=? ";
    $sql .= ($Sort == '1')
        ? " ORDER BY Von"
        : " ORDER BY Was,Von";

    $stmt = mysqli_prepare($db_link,$sql);
    if (!$stmt) { die("Alleschichten prepare failed " .  mysqli_error($db_link)); }

    mysqli_stmt_bind_param($stmt, "i", $HelferLevel);
    if(!mysqli_stmt_execute($stmt)){die ( "AlleSchichten fehlgeschlagen. sort: $Sort  err: " . mysqli_stmt_error($stmt));}

    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

function AlleSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)#stmt
{
    $sql = "SELECT SUM(Soll) AS Anzahl FROM SchichtUebersicht
            JOIN Dienst ON SchichtUebersicht.DienstID = Dienst.DienstID WHERE 1=1";
    $params = [];
    $types = "";

    if ($HelferLevel != -1) {
        $sql .= " AND HelferLevel = ?";
        $params[] = $HelferLevel;
        $types .= "i";
    }

    if ($DienstID != -1) {
        $sql .= " AND Dienst.DienstID = ?";
        $params[] = $DienstID;
        $types .= "i";
    }

    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        error_log("AlleSchichtenCount prepare failed: " . mysqli_error($db_link));
        echo "Fehler bei Datenbankabfrage.<br>";
        return false;
    }

    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("AlleSchichtenCount execute failed: " . mysqli_stmt_error($stmt));
        echo "Fehler bei Ausführung der Abfrage.<br>";
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);

    return $zeile['Anzahl'];
}


function AlleBelegteSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)#stmt
{
    $sql = "SELECT Count(HelferID) AS Anzahl
            FROM EinzelSchicht, Schicht, Dienst
            WHERE EinzelSchicht.SchichtID=Schicht.SchichtID
            AND Schicht.DienstID=Dienst.DienstID ";
//    $sql = "SELECT COUNT(HelferID) AS Anzahl
//            FROM EinzelSchicht
//            JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
//            JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
//            WHERE 1=1";

    $params = [];
    $types = '';

    if ($HelferLevel != -1) {
        $sql .= " AND HelferLevel = ?";
        $params[] = $HelferLevel;
        $types .= 'i';
    }

    if ($DienstID != -1) {
        $sql .= " AND Dienst.DienstID = ?";
        $params[] = $DienstID;
        $types .= 'i';
    }

    $stmt = mysqli_prepare($db_link, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($db_link));
        echo "Abfrage konnte nicht vorbereitet werden.";
        return false;
    }

    if (!empty($params)) {
        //error_log("debug: binding params  $types ...$params");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("AlleBelegteSchichtenCount Execute failed: " . mysqli_stmt_error($stmt));
        echo "Abfrage konnte nicht ausgeführt werden.";
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);

    return $zeile['Anzahl'];
}



function AlleSchichtenImZeitbereich($db_link, $Von, $Bis, $HelferLevel = 1)#stmt
{
    //debug only error_log("AlleSchichtenImZeitbereich Abfrage:  $Von, $Bis, $HelferLevel");
    // SchichtID, Was, Ab, Bis, Ist, Tag, Soll - Ist und Soll sind die HelferStunden
    # bei -1 nicht in Suche einschliessen
    $sql_helferlevel = ($HelferLevel == -1)
        ? ""
        : "and Dienst.HelferLevel = ?";

    $sql =  "SELECT SchichtID,Was,
                DATE_FORMAT(Von,'%a %H:%i') AS Ab,
                DATE_FORMAT(Bis,'%a %H:%i') AS Bis,
                C AS Ist,
                DATE_FORMAT(Von,'%W %d %M') As Tag,
                Soll,
                Dienst.DienstID
             FROM Dienst,SchichtUebersicht
             WHERE Von >= ? and Von < ? and Dienst.DienstID=SchichtUebersicht.DienstID $sql_helferlevel
             ORDER BY Was,Von";
    $stmt = mysqli_prepare($db_link, $sql);
    if(!$stmt) { 
        error_log("AlleSchichtenImZeitbereich sql " . $sql);
        error_log("AlleSchichtenImZeitbereich prepare failed " . mysqli_error($db_link)); 
        die(mysqli_error($db_link)); 
    }

    if ($HelferLevel == -1)
    {
        mysqli_stmt_bind_param($stmt, "ss", $Von, $Bis);
    } else {
         mysqli_stmt_bind_param($stmt, "ssi", $Von, $Bis, $HelferLevel);
    }
    if(!mysqli_stmt_execute($stmt)) { 
        $err = "AlleSchichtenImZeitbereich query failed: " . mysqli_stmt_error($stmt);
        echo $err;
        error_log($err);
        die($err);
        }

    $result = mysqli_stmt_get_result($stmt);
    return $result ?: null;
}


function AlleSchichtenEinesHelfers($db_link, $HelferID)#stmt
{

    $sql =  "SELECT EinzelSchicht.SchichtID ,EinzelSchichtID,Was,
                    DATE_FORMAT(Von,'%a %H:%i') AS Ab,
                    DATE_FORMAT(Bis,'%a %H:%i') AS Bis
             FROM   EinzelSchicht,Schicht,Dienst
             WHERE  EinzelSchicht.SchichtID=Schicht.SchichtID
                AND Schicht.DienstID = Dienst.DienstID
                AND HelferID=?
             ORDER BY Von";
    $stmt = mysqli_prepare($db_link,$sql);
    if(!$stmt) { die("AlleSchichtenEinesHelfers prepare failed " . mysqli_error($db_link));}
    mysqli_stmt_bind_param($stmt, "i", $HelferID);
    if(!mysqli_stmt_execute($stmt)){
        die("AlleSchichtenEinesHelfers execute failed HelferId $HelferID" . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

function HelferLoeschen($db_link, $HelferID, $AdminID)#stmt
{
    $result = Helferdaten($db_link, $HelferID);
    $HelferName = "(unbekannt)";
    while ($zeile = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $HelferName = $zeile['Name'];
    }

    $result = AlleSchichtenEinesHelfers($db_link, $HelferID);
    $AnzahlHelferschichten = mysqli_num_rows($result);
    if ($AnzahlHelferschichten > 0) {
        echo "Helfer $HelferName hat noch $AnzahlHelferschichten Schichten. Bitte erst die Schichten löschen<br>";
        return -1;
    }

    $stmt = mysqli_prepare($db_link, "DELETE FROM Helfer WHERE HelferID = ?");
    if (!$stmt) {
        echo "Helfer $HelferName konnte nicht gelöscht werden<br>";
        die("HelferLoeschen prepare failed: " . mysqli_error($db_link));
    }

    mysqli_stmt_bind_param($stmt, "i", $HelferID);
    if (!mysqli_stmt_execute($stmt)) {
        echo "Helfer $HelferName konnte nicht gelöscht werden<br>";
        die("HelferLoeschen execute failed: " . mysqli_stmt_error($stmt));
    }

    echo "Helfer $HelferName (HelferID:$HelferID) wurde erfolgreich gelöscht<br>";
    error_log(date('Y-m-d H:i') . "(Admin $AdminID) Helfer gelöscht: Name: $HelferName (HelferID:$HelferID)\n", 3, LOGFILE);

    return 1;
}

function SchichtIdArrayEinesHelfers($db_link, $HelferID)#stmt
{

    // Array, um Zeilen mit von mir belegten Schichten in der Schichtuebersicht einfaerben zu koennenn
    $stmt = mysqli_prepare($db_link, "SELECT SchichtID FROM EinzelSchicht WHERE HelferID = ?");
    if (!$stmt) {
        $err = "Fehler in SchichtIdArrayEinesHelfers " . mysqli_error($db_link);
        error_log($err);
        echo $err;
        die($err);
    }
    mysqli_stmt_bind_param($stmt, "i", $HelferID);
        if (!mysqli_stmt_execute($stmt)) {
        echo "HelferID $HelferID konnte nicht gefunden werden<br>";
        $err = "SchichtIdArrayEinesHelfers execute failed: " . mysqli_stmt_error($stmt);
        error_log($err);
        echo $err;
        die("SchichtIdArrayEinesHelfers execute failed: " . mysqli_stmt_error($stmt));
    }
    $db_erg = mysqli_stmt_get_result($stmt);
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
        //error_log(date('Y-m-d H:i') . "  HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegben.\n", 0);
        //error_log(date('Y-m-d H:i') . "sql query: XXX $sql XXX sql query end");
        //error_log(date('Y-m-d H:i') .  print_r(mysqli_fetch_assoc($db_erg),true));
        # wir lassen mehrfachauswahl des selben Dienstes zu, deshalb hier die Daten und Logging auskommentiert, denn das wird zum ok-Fall
        $row = mysqli_fetch_assoc($db_erg);
        $Von = $row["Von"];
        $Bis = $row["Bis"];
        $Was = $row["Was"];
        $HelferName = $row["Name"];
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
    $SchichtID = mysqli_real_escape_string($db_link, $SchichtID);


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


#   $sql = "select  Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer 
#            FROM Dienst,Schicht,Helfer 
#            where Dienst.DienstID=Schicht.DienstID 
#            AND Helfer.HelferID=Dienst.Leiter And SchichtID=" . $InfoSchichtID;
    $sql = "SELECT Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer
            FROM Dienst
            JOIN Schicht ON Dienst.DienstID = Schicht.DienstID
            LEFT JOIN Helfer ON Helfer.HelferID = Dienst.Leiter
            WHERE SchichtID = $InfoSchichtID";


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
    $HelferName = $_SESSION["HelferName"];
    $HelferID   = $_SESSION["HelferID"];
    if (! $db_erg) {
        echo "Fehler New Dienst";
        $err =  mysqli_error($db_link);
        echo "$err";
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName (ID:$HelferID)   konnte Schicht nicht angelegt mit Anfrage $sql   Grund: $err  \n", 3, LOGFILE);
        die();
    } else {
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName(HelferID:$HelferID)  hat Dienst angelegt mit Was: $Was Wo: $Wo Info: $Info Leiter: $Leiter Gruppe $Gruppe, HelferLevel $HelferLevel  \n", 3, LOGFILE);
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
