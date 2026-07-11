<?php

require_once 'konfiguration.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
function debug_sql($sql, $types, $params) {#stmtnoneed
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
    return str_replace(["\n", "\r"], ' ', $reconstructed);
}

function stmt_prepare_and_execute($db_link, $sql, $types = "", ...$params) {#stmt-func
    global $debug;
    try {
        $stmt = mysqli_prepare($db_link, $sql);
        if ($types !== "") {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $err = "Execute failed: " . mysqli_stmt_error($stmt) . "  \n\nSQL: $sql \n";
            $err .= "debug_sql: " . debug_sql($sql, $types, $params);
            error_log($err);
            echo nl2br($err);
            return false;
         }
     } catch (mysqli_sql_exception $e) {
        $err = "MySQLi Error:  " . $e->getMessage() . "\n";
        $err .= debug_sql($sql, $types, $params);
        error_log($err);
        echo nl2br($err); // Optional: für saubere HTML-Darstellung
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

    $stmt = stmt_prepare_and_execute($db_link, $sql, "ssssi", $HelferName, $HelferEmail, $HelferHandy, $PasswortHash, $HelferLevel);
    if (!$stmt) { error_log("Fehler in CreateHelfer, kein stmt"); return null; }
    $result = mysqli_stmt_affected_rows($stmt);
    if ($result !== 1) { error_log("CreateHelfer: Unerwartete Anzahl betroffener Zeilen ($result)"); }
    error_log(date('Y-m-d H:i') . "  CreateHelfer: $HelferName angelegt mit Email $HelferEmail Handy $HelferHandy ($result)\n", 3, LOGFILE);
    return $result ?: null;
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
    $stmt = stmt_prepare_and_execute($db_link, $sql, "s", $HelferEmail);
    if (!$stmt) { error_log("Fehler in HelferLogin, kein stmt"); die('Login ungültige Abfrage');}
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
    mysqli_stmt_close($stmt);
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
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
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
    mysqli_stmt_close($stmt);
    return $result;
}
function AlleSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1, $Rekursiv = false)#stmt
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
        $sql .= $Rekursiv
            ? " AND Dienst.DienstBaumPfad LIKE (SELECT CONCAT(D2.DienstBaumPfad, '%') FROM Dienst D2 WHERE D2.DienstID = ?)"
            : " AND Dienst.DienstID = ?";
        $params[] = $DienstID;
        $types .= "i";
    }
    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return $zeile['Anzahl'] ?? 0;
}
#old
function oldAlleSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)#stmt
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
    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return $zeile['Anzahl'] ?? 0;
}


function AlleBelegteSchichtenCount($db_link, $HelferLevel = -1, $DienstID = -1)#stmt
{
    $sql = "SELECT COUNT(HelferID) AS Anzahl
            FROM EinzelSchicht
            JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
            JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
            WHERE 1=1";

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
    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);

    return $zeile['Anzahl'];
}
function AlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevel = -1, $DienstID = -1, $Rekursiv = false) {
    $sql = "SELECT 
                SUM(LEAST(Soll, Belegt)) AS Besetzt,
                SUM(GREATEST(0, Belegt - Soll)) AS Ueberbelegt
            FROM (
                SELECT 
                    Schicht.SchichtID,
                    COUNT(EinzelSchicht.HelferID) AS Belegt,
                    Schicht.Soll
                FROM Schicht
                LEFT JOIN EinzelSchicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
                JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
                WHERE 1=1";

    $params = [];
    $types = "";

    if ($HelferLevel != -1) {
        $sql .= " AND Dienst.HelferLevel = ?";
        $params[] = $HelferLevel;
        $types .= "i";
    }

    if ($DienstID != -1) {
        $sql .= $Rekursiv
            ? " AND Dienst.DienstBaumPfad LIKE (SELECT CONCAT(D2.DienstBaumPfad, '%') FROM Dienst D2 WHERE D2.DienstID = ?)"
            : " AND Dienst.DienstID = ?";
        $params[] = $DienstID;
        $types .= "i";
    }

    $sql .= " GROUP BY Schicht.SchichtID, Schicht.Soll
            ) AS Belegung";

    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return [
        'besetzt' => (int)($zeile['Besetzt'] ?? 0),
        'ueberbelegt' => (int)($zeile['Ueberbelegt'] ?? 0)
    ];
}

function oldAlleBelegteSchichtenCountMitSurplus($db_link, $HelferLevel = -1, $DienstID = -1) {
    $sql = "SELECT 
                SUM(LEAST(Soll, Belegt)) AS Besetzt,
                SUM(GREATEST(0, Belegt - Soll)) AS Ueberbelegt
            FROM (
                SELECT 
                    Schicht.SchichtID,
                    COUNT(EinzelSchicht.HelferID) AS Belegt,
                    Schicht.Soll
                FROM Schicht
                LEFT JOIN EinzelSchicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
                JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
                WHERE 1=1";

    $params = [];
    $types = "";

    if ($HelferLevel != -1) {
        $sql .= " AND Dienst.HelferLevel = ?";
        $params[] = $HelferLevel;
        $types .= "i";
    }

    if ($DienstID != -1) {
        $sql .= " AND Dienst.DienstID = ?";
        $params[] = $DienstID;
        $types .= "i";
    }

    $sql .= " GROUP BY Schicht.SchichtID, Schicht.Soll
            ) AS Belegung";

    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return [
        'besetzt' => (int)($zeile['Besetzt'] ?? 0),
        'ueberbelegt' => (int)($zeile['Ueberbelegt'] ?? 0)
    ];
}

function AlleSchichtenImZeitbereich($db_link, $Von, $Bis, $HelferLevel = -1, $DienstID = -1)#stmt
{
    $sql_helferlevel = ($HelferLevel == -1) ? "" : "AND Dienst.HelferLevel = ?";
    $sql_dienst      = ($DienstID   == -1) ? "" : "AND Dienst.DienstID = ?";

    $sql =  "SELECT SchichtID,Was,
                DATE_FORMAT(Von,'%a %H:%i') AS Ab,
                DATE_FORMAT(Bis,'%a %H:%i') AS Bis,
                C AS Ist,
                DATE_FORMAT(Von,'%W %d %M') As Tag,
                Soll,
                Dienst.DienstID
             FROM Dienst,SchichtUebersicht
             WHERE Von >= ? and Von < ? and Dienst.DienstID=SchichtUebersicht.DienstID $sql_helferlevel $sql_dienst
             ORDER BY Was,Von";

    $types  = "ss";
    $params = [$Von, $Bis];
    if ($HelferLevel != -1) { $types .= "i"; $params[] = $HelferLevel; }
    if ($DienstID   != -1) { $types .= "i"; $params[] = $DienstID; }

    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, ...$params);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    if (!$result) { error_log("AlleSchichtenImZeitBereich Fehler"); }
    return $result ?: null;
}

function oldAlleSchichtenImZeitbereich($db_link, $Von, $Bis, $HelferLevel = -1)#stmt
{
    //debug only 
    //error_log("AlleSchichtenImZeitbereich Abfrage:  $Von, $Bis, $HelferLevel");
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
    if ($HelferLevel == -1)
    {
        $stmt = stmt_prepare_and_execute($db_link, $sql, "ss", $Von, $Bis);
        $debugmsg = debug_sql($sql, "ss", [$Von, $Bis]);
    } else {
        $stmt = stmt_prepare_and_execute($db_link, $sql, "ssi", $Von, $Bis, $HelferLevel);
        $debugmsg = debug_sql($sql, "ssi", [$Von, $Bis, $HelferLevel]);
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    if(!$result){error_log("AlleSchichtenImZeitBereich Fehler");error_log("AlleSchichtenImZeitbereich $debugmsg");}
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
    mysqli_stmt_close($stmt);
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
    $result = mysqli_stmt_get_result($stmt);
    $schichtIDs = array();
    while ($zeile = mysqli_fetch_array($result, MYSQLI_NUM)) {
        $schichtIDs[] = $zeile[0];
    }
    mysqli_stmt_close($stmt);
    return($schichtIDs);
}

function AlleSchichtenEinesHelfersVonJetzt($db_link, $HelferID) #stmt2
{
    $sql = "SELECT EinzelSchicht.SchichtID, EinzelSchichtID, Was,
                   DATE_FORMAT(Von,'%a %H:%i') AS Ab,
                   DATE_FORMAT(Bis,'%a %H:%i') AS Bis
            FROM EinzelSchicht, Schicht, Dienst
            WHERE EinzelSchicht.SchichtID = Schicht.SchichtID
              AND Schicht.DienstID = Dienst.DienstID
              AND HelferID = ?
              AND Bis > ?
            ORDER BY Von";

    $jetzt = date("Y-m-d H:i:s");
    // is : HelferID -> integer Zeitstempel -> String
    $stmt = stmt_prepare_and_execute($db_link, $sql, "is", $HelferID, $jetzt);
    if (!$stmt) {
        error_log("AlleSchichtenEinesHelfersVonJetzt: Fehler beim Prepare/Execute.");
        return null;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result ?: null;
}

# 
function SchichtenSummeEinesHelfers($db_link, $HelferID) #stmt
{
    $sql = "
        SELECT
            COUNT(*) AS Anzahl,
            SUM(TIME_TO_SEC(Schicht.Dauer)) AS Dauer
        FROM EinzelSchicht
        JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
        JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
        WHERE HelferID = ?
    ";

    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $HelferID);
    if (!$stmt) {
        error_log("Fehler in SchichtenSummeEinesHelfers");
        return false; 
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}



function LogSchichtEingabe($db_link, $HelferID, $SchichtID, $EinzelSchichtID, $Aktion, $AdminID = 0) #stmt2
{

    $sql = "SELECT Schicht.Von, Schicht.Bis, Dienst.Was, Helfer.Name
            FROM EinzelSchicht
            JOIN Schicht ON EinzelSchicht.SchichtID = Schicht.SchichtID
            JOIN Dienst ON Schicht.DienstID = Dienst.DienstID
            JOIN Helfer ON EinzelSchicht.HelferID = Helfer.HelferID
            WHERE EinzelSchicht.HelferID = ?
            AND ( Schicht.SchichtID = ? OR EinzelSchicht.EinzelSchichtID = ?)
            ";
    $types = "iii";
    $stmt = stmt_prepare_and_execute($db_link, $sql, $types, $HelferID,$SchichtID,$EinzelSchichtID);
    if (!$stmt) {error_log("Fehler in LogSchichtEingabe");}
    $result = mysqli_stmt_get_result($stmt);
    $num_rows = mysqli_num_rows($result);

    if ($num_rows> 1) {
        echo "LogSchichtEingabe: Es wurden mehr als eine Zeile zurueckgegeben\n <br>";
        // Fehler geht ins normale Error-Management, nicht ins Logfile
        //error_log(date('Y-m-d H:i') . "  HelferSchichtZuweisen: Es wurden mehr als eine Zeile zurueckgegben.\n", 0);
        //error_log(date('Y-m-d H:i') . "sql query: XXX $sql XXX sql query end");
        //error_log(date('Y-m-d H:i') .  print_r(mysqli_fetch_assoc($result),true));
        # wir lassen mehrfachauswahl des selben Dienstes zu, deshalb hier die Daten und Logging auskommentiert, denn das wird zum ok-Fall
    } elseif ($num_rows == 1) {
        //Regelfall ist $num_rows == 1, keine Ausgabe
    } else {
        echo "Es wurde keine Zeile zurueckgegeben.";
        error_log("LogSchichtEingabe: keine Zeile aus Abfrage erhalten");
        $err = debug_sql($sql, $types, [$HelferID,$SchichtID,$EinzelSchichtID]);
        error_log($err);
        error_log($result);
    }

    $row = mysqli_fetch_assoc($result);
    $Von = $row["Von"] ?? "-";
    $Bis = $row["Bis"] ?? "-";
    $Was = $row["Was"] ?? "-";
    $HelferName = $row["Name"] ?? "-";
    if( $Von === "-" and $Bis === "-" ){
        error_log("Leere Zeiten werden geloggt. Parameter logging-Aufruf (HelferID:$HelferID): $HelferID, $SchichtID, $EinzelSchichtID, $Aktion, $AdminID");
    }

    $logline = date('Y-m-d H:i') . "  HelferSchicht: ";

    if ($AdminID == 0) {
          $logline .= "$HelferName (HelferID:$HelferID) hat Dienst $Was von $Von bis $Bis $Aktion.";
    } else {
          $logline .= "Admin:$AdminID hat  von $HelferName (HelferID:$HelferID) den Dienst $Was von $Von bis $Bis $Aktion.";
    }
    error_log($logline . "\n", 3, LOGFILE);
}

function HelferSchichtZuweisen($db_link, $HelferID, $SchichtID, $AdminID = 0)#stmt2
{
    // Abfrage, ob bereits eine Einzelschicht in der selben Schicht vom Helfer existiert
    $sql = "SELECT EinzelSchichtID from EinzelSchicht WHERE SchichtID=? and HelferID=?";

    $stmt = stmt_prepare_and_execute($db_link, $sql, "ii", $SchichtID, $HelferID);
    if (!$stmt) {error_log("Fehler in HelferSchichtZuweisen Schichtabfrage");}
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
         echo "HelferSchichtZuweisen: Schicht existiert bereits!";
         // abgeschaltet, damit Mehrfacheintraege fuer Familien moeglich sind
         //return false;
    }

    // Helfer Schicht zuweisen
    $sql = "INSERT INTO EinzelSchicht(SchichtID,HelferID) VALUES (?,?)";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "ii", $SchichtID, $HelferID);
    LogSchichtEingabe($db_link, $HelferID, $SchichtID, -1, "eingetragen", $AdminID);

    return true;
}

function HelferVonSchichtLoeschen($db_link, $HelferID, $EinzelSchichtID, $AdminID = 0)#stmt2
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, -1, $EinzelSchichtID, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $sql = "DELETE FROM EinzelSchicht WHERE EinzelSchichtID = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $EinzelSchichtID);
    if (!$stmt) {error_log("Fehler in HelferVonSchichtLoeschen"); return false;}
    $result = mysqli_stmt_affected_rows($stmt);
    return $result;
}

function HelferVonSchichtLoeschen_SchichtID($db_link, $HelferID, $SchichtID, $AdminID = 0)#stmt2
{
    // Log vor Löschen, damit Einzelschicht abgefragt werden kann
    LogSchichtEingabe($db_link, $HelferID, $SchichtID, -1, "entfernt", $AdminID);

    // Lösche Einzelschicht
    $sql = "DELETE FROM EinzelSchicht WHERE SchichtID = ? AND HelferID = ? limit 1;";

    $stmt = stmt_prepare_and_execute($db_link, $sql, "ii", $SchichtID,$HelferID);
    if (!$stmt) {error_log("Fehler in HelferVonSchichtLoeschen_SchichtID"); return false;}
    $result = mysqli_stmt_affected_rows($stmt);
    return $result;
}

function DetailSchicht($db_link, $InfoSchichtID)#stmt2
{
    $sql = "SELECT Was,Wo,Info,Name,Handy,Email,DATE_FORMAT(Dauer,'%H:%i') AS Dauer
            FROM Dienst
            JOIN Schicht ON Dienst.DienstID = Schicht.DienstID
            LEFT JOIN Helfer ON Helfer.HelferID = Dienst.Leiter
            WHERE SchichtID = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $InfoSchichtID);
    if (!$stmt) {error_log("Fehler in DetailSchicht"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {error_log("Kein Resultat in DetailSchicht"); return false;}

    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return $zeile;
}


function BeteiligteHelfer($db_link, $InfoSchichtID)#stmt2
{
    $sql = "SELECT  Helfer.HelferID, Name, Handy
        FROM EinzelSchicht, Helfer
        WHERE EinzelSchicht.HelferID = Helfer.HelferID
        AND SchichtID=?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $InfoSchichtID);
    if (!$stmt) {error_log("Fehler in BeteiligteHelfer"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    // Kein Fehler wenn leeres Resultat
    return $result;
}


function GetDienste($db_link)
{
    $sql = "SELECT DienstID, Was, Wo, Info, Leiter, ElternDienstID, HelferLevel FROM Dienst order By Was";
    $stmt = stmt_prepare_and_execute($db_link, $sql);
    if (!$stmt) {error_log("Fehler in GetDienste"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {error_log("Keine Dienste gefunden"); return false;}
    return $result;
}
function GetDiensteChildren($db_link, $DienstID)#stmt2
{
    // $DienstID NULL anderes Queryformat
    if ($DienstID === null) {
        global $debug;
        $debug=1;
        $ElternDienstQuery = "IS NULL";
        $sql = "SELECT DienstID, Was, Wo, Info, Leiter, HelferLevel FROM Dienst where ElternDienstID $ElternDienstQuery ORDER BY Was";
        $stmt = stmt_prepare_and_execute($db_link, $sql);
    } else {
        $ElternDienstQuery = " = ?";
        $sql = "SELECT DienstID, Was, Wo, Info, Leiter, HelferLevel FROM Dienst where ElternDienstID $ElternDienstQuery ORDER BY Was";
        $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
    }

    if (!$stmt) {error_log("Fehler in GetDiensteChildren"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}


function ChangeDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)
{
    // Gruppe normalisieren: leerer String oder 0 -> NULL (Top-Level)
    $ElternDienstID = (!empty($Gruppe) && (int)$Gruppe > 0) ? (int)$Gruppe : null;

    // 1. Alten Zustand holen
    $sqlAlt = "SELECT ElternDienstID, DienstBaumPfad FROM Dienst WHERE DienstID = ?";
    $stmtAlt = stmt_prepare_and_execute($db_link, $sqlAlt, "i", $DienstID);
    if (!$stmtAlt) {
        error_log("Fehler in ChangeDienst (Alt-Daten lesen): " . mysqli_error($db_link));
        return false;
    }
    $rowAlt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtAlt));
    $alteElternID = $rowAlt['ElternDienstID'];
    $eigenerPfad  = $rowAlt['DienstBaumPfad'] ?? null;

    // Pfad aus DB ungültig oder leer -> neu aufbauen
    if (empty($eigenerPfad) || $eigenerPfad[0] !== '/') {
        $eigenerPfad = _SetzeDienstPfad($db_link, $DienstID, $alteElternID);
        if ($eigenerPfad === false) {
            error_log("ChangeDienst: Pfad für Dienst $DienstID konnte nicht gebaut werden.");
            return false;
        }
    }

    // 2. Zyklus-Prüfung (nur wenn Elterndienst gesetzt)
    if ($ElternDienstID !== null) {
        if ($ElternDienstID === $DienstID) {
            error_log("ChangeDienst: Dienst $DienstID kann nicht sein eigener Elterndienst sein.");
            return false;
        }
        $sqlZyklus = "SELECT COUNT(*) as n FROM Dienst WHERE DienstID = ? AND DienstBaumPfad LIKE ?";
        $stmtZ = stmt_prepare_and_execute($db_link, $sqlZyklus, "is", $ElternDienstID, $eigenerPfad . '%');
        $rowZ = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtZ));
        if (($rowZ['n'] ?? 0) > 0) {
            error_log("ChangeDienst: Zyklus verhindert — Dienst $ElternDienstID ist Nachfahre von $DienstID.");
            return false;
        }
    }

    // 3. Basis-Update aller Felder
    $sql = "UPDATE Dienst SET Was=?, Wo=?, Info=?, Leiter=?, ElternDienstID=?, HelferLevel=? WHERE DienstID=?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "sssiiii", $Was, $Wo, $Info, $Leiter, $ElternDienstID, $HelferLevel, $DienstID);
    if (!$stmt) {
        error_log("Fehler in ChangeDienst (Update): " . mysqli_error($db_link));
        return false;
    }

    // 4. Pfad aktualisieren wenn ElternDienstID sich geändert hat
    if ($alteElternID != $ElternDienstID) {
        $neuerPfad = _SetzeDienstPfad($db_link, $DienstID, $ElternDienstID);
        if ($neuerPfad === false) {
            error_log("Fehler in ChangeDienst (_SetzeDienstPfad): DienstID=$DienstID ElternDienstID=$ElternDienstID");
            return false;
        }
    }

    return true;
}

function RebuildAlleDienstPfade($db_link)
{
    $bericht = ['gefixt' => [], 'ok' => 0];

    // Alle Dienste holen
    $sql = "SELECT DienstID, ElternDienstID, DienstBaumPfad FROM Dienst";
    $stmt = stmt_prepare_and_execute($db_link, $sql);
    if (!$stmt) {
        error_log("RebuildAlleDienstPfade: Fehler beim Lesen aller Dienste");
        return false;
    }
    $result = mysqli_stmt_get_result($stmt);

    // Alle Dienste einmal laden, key DienstID
    $dienste = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['DienstID'];
        $dienste[$id] = [
            'eltern_id'      => $row['ElternDienstID'] !== null ? (int)$row['ElternDienstID'] : null,
            'orig_eltern_id' => $row['ElternDienstID'] !== null ? (int)$row['ElternDienstID'] : null,
            'alter_pfad'     => $row['DienstBaumPfad'],
        ];
    }

    $neuePfade = []; // id => bereits berechneter neuer Pfad (Memoisierung)

    // Klettert von $startID die Elternkette hoch, bis ein bekannter Pfad, ein
    // Top-Level-Dienst, oder ein Zyklus erreicht ist. Baut den Pfad danach von
    // oben (Wurzel) nach unten ($startID) wieder auf.
    $loesePfad = function ($startID) use (&$dienste, &$neuePfade, &$bericht) {
        $kette   = [];  // ids auf dem Weg nach oben, in Reihenfolge
        $inKette = [];  // dieselben ids als Set, für Zyklus-Prüfung
        $aktuelle = $startID;
        $basisPfad = '/';

        while (true) {
            if (isset($neuePfade[$aktuelle])) {
                $basisPfad = $neuePfade[$aktuelle];
                break;
            }
       if (isset($inKette[$aktuelle])) {
           // Zyklus gefunden. Der Teil von $kette ab der ersten Position von
           // $aktuelle ist der eigentliche Zyklus; alles davor ist nur ein
           // Zulauf (zeigt auf den Zyklus, ist aber selbst nicht Teil davon)
           // und bleibt unverändert.
           $zyklusStart = array_search($aktuelle, $kette);
           $zyklusMitglieder = array_slice($kette, $zyklusStart);

           $meldung = "Zyklus in der Eltern-Kette gefunden: Dienst(e) "
                    . implode(', ', $zyklusMitglieder)
                    . " — alle auf Top-Level zurückgesetzt.";
           error_log("RebuildAlleDienstPfade: WARNUNG $meldung");
           $bericht['gefixt'][] = $meldung;

           // Jedes Zyklus-Mitglied wird unabhängig und auf einmal Top-Level -
           // ElternDienstID und Pfad zusammen, damit nichts auseinanderlaufen kann
           foreach ($zyklusMitglieder as $mitglied) {
               $dienste[$mitglied]['eltern_id'] = null;
               $neuePfade[$mitglied] = '/' . $mitglied . '/';
           }
           // Falls $startID nur ein Zulauf zum Zyklus war (nicht Teil davon),
           // bauen wir von hier normal weiter - dafür kürzen wir $kette auf den
           // Zulauf-Teil und nehmen den (jetzt gesetzten) Pfad des ersten
           // Zyklus-Mitglieds als Basis.
           $kette = array_slice($kette, 0, $zyklusStart);
           $basisPfad = $neuePfade[$aktuelle] ?? '/';
           break;
       }

            $kette[] = $aktuelle;
            $inKette[$aktuelle] = true;

            $elternID = $dienste[$aktuelle]['eltern_id'];
            if ($elternID === null || !isset($dienste[$elternID])) {
                if ($elternID !== null) {
                    $meldung = "Dienst $aktuelle: Elterndienst $elternID existiert nicht — auf Top-Level zurückgesetzt.";
                    error_log("RebuildAlleDienstPfade: WARNUNG $meldung");
                    $bericht['gefixt'][] = $meldung;
                    $dienste[$aktuelle]['eltern_id'] = null;
                }
                $basisPfad = '/';
                break;
            }
            $aktuelle = $elternID;
        }

        $pfad = $basisPfad;
        foreach (array_reverse($kette) as $x) {
            $pfad = $pfad . $x . '/';
            $neuePfade[$x] = $pfad;
        }
        return $neuePfade[$startID];
    };

    foreach (array_keys($dienste) as $id) {
        $neuerPfad = $loesePfad($id);
        $eintrag = $dienste[$id]; // aktueller Zustand, evtl. durch Zyklus-Fix verändert

        if ($eintrag['eltern_id'] !== $eintrag['orig_eltern_id']) {
            $sqlFix = "UPDATE Dienst SET ElternDienstID = ? WHERE DienstID = ?";
            stmt_prepare_and_execute($db_link, $sqlFix, "ii", $eintrag['eltern_id'], $id);
        }

        if ($neuerPfad !== $eintrag['alter_pfad']) {
            $sqlUpdate = "UPDATE Dienst SET DienstBaumPfad = ? WHERE DienstID = ?";
            stmt_prepare_and_execute($db_link, $sqlUpdate, "si", $neuerPfad, $id);
            $meldung = "Dienst $id: '{$eintrag['alter_pfad']}' → '$neuerPfad'";
            error_log("RebuildAlleDienstPfade: gefixt $meldung");
            $bericht['gefixt'][] = $meldung;
        } else {
            $bericht['ok']++;
        }
    }

    return $bericht;
}

/**
 * Berechnet und setzt DienstBaumPfad für einen bestehenden Dienst anhand
 * seiner ElternDienstID. Aktualisiert bei einer Pfad-Änderung auch alle
 * Nachfahren (Kinder, Enkel, ...), damit DienstBaumPfad immer konsistent bleibt.
 *
 * Voraussetzung: der Dienst mit $DienstID existiert bereits in der DB
 * (wird also NACH einem INSERT oder zusammen mit einem UPDATE von
 * ElternDienstID aufgerufen, nie vorher).
 *
 * Gibt den neu gesetzten Pfad zurück, oder false bei Fehler.
 */
function _SetzeDienstPfad($db_link, $DienstID, $ElternDienstID)
{
    // 1. Alten Pfad merken (für die Nachfahren-Korrektur unten)
    $sqlAlt = "SELECT DienstBaumPfad FROM Dienst WHERE DienstID = ?";
    $stmtAlt = stmt_prepare_and_execute($db_link, $sqlAlt, "i", $DienstID);
    if (!$stmtAlt) { error_log("Fehler in _SetzeDienstPfad (Alt-Pfad lesen)"); return false; }
    $rowAlt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtAlt));
    $alterPfad = $rowAlt['DienstBaumPfad'] ?? null;

    // 2. Eltern-Pfad ermitteln (leer, wenn Top-Level)
    $elternPfad = '/';
    if ($ElternDienstID !== null && $ElternDienstID !== '' && (int)$ElternDienstID > 0) {
        $sqlEltern = "SELECT DienstBaumPfad FROM Dienst WHERE DienstID = ?";
        $stmtEltern = stmt_prepare_and_execute($db_link, $sqlEltern, "i", $ElternDienstID);
        if (!$stmtEltern) { error_log("Fehler in _SetzeDienstPfad (Eltern-Pfad lesen)"); return false; }
        $rowEltern = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEltern));
        if (!$rowEltern) {
            error_log("_SetzeDienstPfad: Elterndienst $ElternDienstID nicht gefunden (für Dienst $DienstID)");
            return false;
        }
        $elternPfad = $rowEltern['DienstBaumPfad'] ?? '/';
    }

    // 3. Eigenen neuen Pfad setzen
    $neuerPfad = $elternPfad . $DienstID . '/';
    $sqlSelf = "UPDATE Dienst SET DienstBaumPfad = ? WHERE DienstID = ?";
    $stmtSelf = stmt_prepare_and_execute($db_link, $sqlSelf, "si", $neuerPfad, $DienstID);
    if (!$stmtSelf) { error_log("Fehler in _SetzeDienstPfad (Self-Update)"); return false; }

    // 4. Nachfahren korrigieren, falls sich der Pfad-Präfix geändert hat
    //    (Präfix-Ersetzung: alle Dienste, deren Pfad mit dem alten Pfad beginnt)
    //    Nur durchführen, wenn der Pfad gültig mit / beginnt
    if (!empty($alterPfad) && $alterPfad[0] === '/' && $alterPfad !== $neuerPfad) {
        $sqlKinder = "UPDATE Dienst
                      SET DienstBaumPfad = REPLACE(DienstBaumPfad, ?, ?)
                      WHERE DienstBaumPfad LIKE ? AND DienstID != ?";
        $likeAlt = $alterPfad . '%';
        $stmtKinder = stmt_prepare_and_execute($db_link, $sqlKinder, "sssi", $alterPfad, $neuerPfad, $likeAlt, $DienstID);
        if (!$stmtKinder) { error_log("Fehler in _SetzeDienstPfad (Nachfahren-Update)"); return false; }
    }

    return $neuerPfad;
}

function NewDienst($db_link, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)#stmt2
{
//    $Was         //Name des Dienstes
//    $Wo          //Ort
//    $Info        //vollstaendige Beschreibung
//    $Leiter      // int HelferID des Leiters
//    $Gruppe      // ElternDienstID, NULL/0 = Top-Level-Dienst
//    $HelferLevel // int (1,2) Teilnehmer oder Dauerhelfer

    // Gruppe normalisieren: leerer String oder 0 -> NULL (Top-Level)
    $ElternDienstID = (!empty($Gruppe)) ? (int)$Gruppe : null;

    $sql = "INSERT INTO Dienst (Was, Wo, Info, Leiter, ElternDienstID, HelferLevel) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "sssiii", $Was, $Wo, $Info, $Leiter, $ElternDienstID, $HelferLevel);

    $HelferName = $_SESSION["HelferName"] ?? "unbekannt";
    $HelferID   = $_SESSION["HelferID"] ?? 0;

    if (!$stmt) {
        $err = mysqli_error($db_link);
        echo "Fehler NewDienst: $err";
        $full_sql = debug_sql($sql, "sssiii", [$Was, $Wo, $Info, $Leiter, $ElternDienstID, $HelferLevel]);
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName (ID:$HelferID) konnte Schicht nicht anlegen mit Anfrage $full_sql Grund: $err\n", 3, LOGFILE);
        die();
    }

    $newDienstID = mysqli_insert_id($db_link);

    // Pfad setzen (DienstID existiert jetzt, also kann _SetzeDienstPfad sie verwenden)
    $neuerPfad = _SetzeDienstPfad($db_link, $newDienstID, $ElternDienstID);
    if ($neuerPfad === false) {
        error_log(date('Y-m-d H:i') . "  NewDienst: Dienst $newDienstID angelegt, aber DienstBaumPfad konnte nicht gesetzt werden.\n", 3, LOGFILE);
    }

    error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName (HelferID:$HelferID) hat Dienst $newDienstID angelegt mit Was: $Was Wo: $Wo Info: $Info Leiter: $Leiter Gruppe: $ElternDienstID HelferLevel: $HelferLevel\n", 3, LOGFILE);

    return $newDienstID;
}


function DeleteDienst($db_link, $DienstID, $Rekursiv)#stmt2
{
    if ($Rekursiv) {
        //TODO
        return false;
    } else {
        // Pruefen ob noch Schichten eingetragen sind
        $sql = "SELECT SchichtID FROM Schicht where DienstID=?";
        $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
        if (!$stmt) {error_log("Fehler in DeleteDienst select"); return false;}
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 0) {
            // Eintrag löschen
            $sql = "DELETE FROM Dienst where DienstID=?";
            $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
            if (!$stmt) {error_log("Fehler in DeleteDienst delete"); return false;}
            $result = mysqli_stmt_affected_rows($stmt);
            return true;
        } else {
            return false;
        }
    }
}

function GetDiensteForDay($db_link, $HelferLevel, $datestring)#stmt2
{
    // TeilnehmerSchichtenAusdruck2
    $unixtime = strtotime($datestring);
    $date1 = date('Y-m-d', $unixtime + 24 * 60 * 60);
    $date2 = date('Y-m-d', $unixtime);
    $sql = "
    SELECT DienstId, Was, Wo, Info,
           MIN(Von) AS MinVon, MAX(Bis) AS MaxBis
    FROM Dienst
    INNER JOIN Schicht
    USING (DienstID)
    WHERE HelferLevel=?
    GROUP BY DienstID
    HAVING MinVon<?
    AND MaxBis>?
    ORDER BY MIN(Von) ASC";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "iss", $HelferLevel, $date1, $date2);
    if (!$stmt) {error_log("Fehler in GetDiensteForDay select"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

function GetSchichtenForDienstForDay($db_link, $DienstID, $datestring)#stmt2
{
    // TeilnehmerSchichtenAusdruck2
    $unixtime = strtotime($datestring);
    $date1 = date('Y-m-d', $unixtime + 24 * 60 * 60);
    $date2 = date('Y-m-d', $unixtime);
    $sql = "
    SELECT Von, Bis, Soll, Name, Handy
    FROM Schicht
    LEFT JOIN EinzelSchicht
    USING (SchichtID)
    LEFT JOIN Helfer
    USING (HelferID)
    WHERE DienstID=?
    AND Von<?
    AND Bis>?
    ORDER BY Von";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "iss", $DienstID, $date1, $date2);
    if (!$stmt) {error_log("Fehler in GetSchichtenForDiensteForDay select"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}


function GetSchichtenEinesDienstes($db_link, $DienstID)#stmt2
{
    $sql = "
    SELECT SchichtID,Von,Bis,Soll,DATE_FORMAT(Von,'%a %H:%i')
    AS TagVon, DATE_FORMAT(Von,'%H:%i') AS ZeitVon,
       DATE_FORMAT(Bis,'%H:%i') AS ZeitBis, DATE_FORMAT(Dauer,'%H:%i')
       AS Dauer FROM Schicht
    WHERE DienstID=?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
    if (!$stmt) {error_log("Fehler in GetSchichtenEinesDienstes"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

function ChangeSchicht($db_link, $SchichtID, $Von, $Bis, $Soll, $Dauer)#stmt2
{
    $sql = "
        UPDATE Schicht 
        SET Von=?, Bis=?, Soll=?, Dauer=?
        WHERE SchichtID=?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "ssiii", $Von, $Bis, $Soll, $Dauer, $SchichtID);
    if (!$stmt) {error_log("Fehler in ChangeSchicht"); return false;}
    $result = mysqli_stmt_affected_rows($stmt);
    return $result;
}

function NewSchicht($db_link, $DienstID, $Von, $Bis, $Soll, $Dauer, $HelferName) #stmt2
{
    $sql = "INSERT INTO Schicht (DienstID, Von, Bis, Soll, Dauer) values (?,?,?,?,?)";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "issii", $DienstID, $Von, $Bis, $Soll, $Dauer);
    if (!$stmt) {error_log("Fehler in NewSchicht"); return false;}
    $result = mysqli_stmt_affected_rows($stmt);
    if ($result != 1) {
        echo "Keine Schicht erstellt";
        $full_sql = debug_sql($sql, "issii", [ $DienstID, $Von, $Bis, $Soll, $Dauer ]);
        $err = "  NeueSchicht: $HelferName   konnte Schicht nicht angelegt mit $full_sql  \n";
        error_log(date('Y-m-d H:i') . $err , 3, LOGFILE);
        die('Ungueltige Abfrage: ' . $err);
    } else {
        //TODO: DienstID aufloesen
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName  hat Schicht angelegt mit DienstID $DienstID, Von $Von Bis $Bis Soll $Soll  \n", 3, LOGFILE);
    }
}

function DeleteSchicht($db_link, $SchichtID, $Rekursiv)#stmt2
{

    if ($Rekursiv) {
        // TODO rekursives loeschen
        return false;
    } else {
        // Pruefen ob noch Helfer auf der Schicht eingetragen sind
        $sql = "SELECT Name FROM EinzelSchicht JOIN Helfer ON Helfer.HelferID = EinzelSchicht.HelferID WHERE SchichtID = ?";
        $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $SchichtID);
        if (!$stmt) {error_log("Fehler in DeleteSchicht select"); return false;}
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 0) {
            // Eintrag löschen
            $sql = "DELETE FROM Schicht where SchichtID=?";
            $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $SchichtID);
            if (!$stmt) {error_log("Fehler in DeleteSchicht select"); return false;}
            $result = mysqli_stmt_affected_rows($stmt);
            if ($result === 0) {
                $err = "Fehler in DeleteSchicht nichts gelöscht";
                error_log($err);
                echo $err;
                error_log(debug_sql($sql, "i", [$SchichtID]));
                return false;
            }
            return true;
        } else {
            return false; // Es sind noch Helfer eingetragen
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
    WHERE Helfer.HelferLevel = ?
    GROUP BY
        Helfer.HelferID,
        Was";

    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $HelferLevel);
    if (!$stmt) {error_log("Fehler in AlleHelferSchichtenUebersicht select"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}


function DatenbankAufDeutsch($db_link)#stmt_brauchtnicht
{
    $sql = "SET lc_time_names = 'de_DE'";
    $result = mysqli_query($db_link, $sql);

    if (! $result) {
        echo "ungueltiges umstellen auf Deutsch";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
}

function LastInsertId($db_link)
{
    $sql = "SELECT LAST_INSERT_ID()";
    $result = mysqli_query($db_link, $sql);

    if (! $result) {
        echo "ungueltige Last InsertID";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }

    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return $zeile['LAST_INSERT_ID()'];
}

function HelferLevel($db_link)
{
    $sql = "select HelferLevel, HelferLevelBeschreibung, linkcode from HelferLevel order by HelferLevel";
    $result = mysqli_query($db_link, $sql);
    if (! $result) {
        echo "Konnte HelferLevel nicht abfragen";
        die('Ungueltige Abfrage: ' . mysqli_error($db_link));
    }
    return $result;
}

function alleHelferLevel($db_link)
{
    $alleHelferLevel = array();
    $result = HelferLevel($db_link);
    while ($zeile = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $HelferLevel = $zeile['HelferLevel'];
        $HelferLevelBeschreibung = $zeile['HelferLevelBeschreibung'];
        $alleHelferLevel[$HelferLevel] = $HelferLevelBeschreibung;
    };
    return $alleHelferLevel;
}


function HelferLevelAusEinladung($db_link, string $linkcode): array|false {
    $sql = "SELECT HelferLevel, HelferLevelBeschreibung FROM HelferLevel WHERE linkcode = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "s", $linkcode);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || $result->num_rows === 0) {
        return false;
    }

    return $result->fetch_assoc();
}

function AlleHelferLevelAlles($db_link)
{
    $result = HelferLevel($db_link);
    $alle = [];
    while ($zeile = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $level = $zeile['HelferLevel'];
        $alle[$level] = [
            'HelferLevelBeschreibung' => $zeile['HelferLevelBeschreibung'],
            'linkcode' => $zeile['linkcode'],
        ];
    }
    return $alle;
}

function HelferLevelUpdate($db_link, int $level, string $beschreibung, string $linkcode): bool {
    $sql = "UPDATE HelferLevel SET HelferLevelBeschreibung = ?, linkcode = ? WHERE HelferLevel = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "ssi", $beschreibung, $linkcode, $level);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_close($stmt);
    return true;
}

function HelferLevelInsert($db_link, string $beschreibung, string $linkcode): bool {
    $sql = "INSERT INTO HelferLevel (HelferLevelBeschreibung, linkcode) VALUES (?, ?)";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "ss", $beschreibung, $linkcode);
    return $stmt !== false;
}

function HelferLevelDelete($db_link, int $level): bool {
    $sql = "DELETE FROM HelferLevel WHERE HelferLevel = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $level);
    return $stmt !== false;
}

function AnzahlAccountsMitHelferLevel($db_link, int $level): int {
    $sql = "SELECT COUNT(*) AS Anzahl FROM Helfer WHERE HelferLevel = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $level);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        return (int)$row['Anzahl'];
    }
    return 0;
}

function AnzahlDiensteMitHelferLevel($db_link, $level) {
    $sql = "SELECT COUNT(*) FROM Dienst WHERE HelferLevel = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, 'i', $level);
    $stmt->bind_result($anzahl);
    $stmt->fetch();
    return $anzahl;
}

/**
 * Liefert die Kette der Vorfahren-Dienste (inkl. sich selbst) von der Wurzel
 * bis zu $DienstID, als Array [DienstID => Was], in Reihenfolge Wurzel zuerst.
 * Nutzt DienstBaumPfad um die IDs zu extrahieren, macht dann eine einzige
 * Abfrage für alle Namen (kein rekursives Nachladen nötig).
 */
function GetDienstPfadKette($db_link, $DienstID)
{
    if ($DienstID === null || (int)$DienstID <= 0) {
        return []; // Top-Level -- kein Pfad
    }

    $sql = "SELECT DienstBaumPfad FROM Dienst WHERE DienstID = ?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
    if (!$stmt) { return []; }
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $pfad = $row['DienstBaumPfad'] ?? null;
    if (empty($pfad) || $pfad[0] !== '/') {
        error_log("GetDienstPfadKette: ungültiger Pfad für Dienst $DienstID");
        return [];
    }

    // "/5/12/34/" -> [5, 12, 34]
    $ids = array_filter(explode('/', $pfad), fn($x) => $x !== '');
    $ids = array_map('intval', $ids);
    if (empty($ids)) { return []; }

    $platzhalter = implode(',', array_fill(0, count($ids), '?'));
    $sqlNamen = "SELECT DienstID, Was FROM Dienst WHERE DienstID IN ($platzhalter)";
    $stmtNamen = stmt_prepare_and_execute($db_link, $sqlNamen, str_repeat('i', count($ids)), ...$ids);
    if (!$stmtNamen) { return []; }
    $resultNamen = mysqli_stmt_get_result($stmtNamen);

    $namenNachID = [];
    while ($r = mysqli_fetch_assoc($resultNamen)) {
        $namenNachID[(int)$r['DienstID']] = $r['Was'];
    }

    // Reihenfolge aus dem Pfad beibehalten (SQL IN() garantiert keine Reihenfolge)
    $kette = [];
    foreach ($ids as $id) {
        if (isset($namenNachID[$id])) {
            $kette[$id] = $namenNachID[$id];
        }
    }
    return $kette;
}

/**
 * Liefert alle Dienste AUSSER $DienstID selbst und dessen Nachfahren -- als
 * Auswahl-Kandidaten für den Elterndienst. Verhindert Zyklen schon in der
 * Anzeige (zusätzlich zur harten Prüfung in ChangeDienst()).
 * $DienstID = null -> alle Dienste (z.B. beim Anlegen eines komplett neuen
 * Dienstes, wo "sich selbst ausschließen" noch nicht relevant ist).
 */
function GetDiensteAuswahlbar($db_link, $DienstID = null)
{
    if ($DienstID === null || (int)$DienstID <= 0) {
        $stmt = stmt_prepare_and_execute($db_link, "SELECT DienstID, Was FROM Dienst ORDER BY Was");
    } else {
        $sql = "SELECT DienstID, Was FROM Dienst
                WHERE DienstID != ?
                  AND DienstBaumPfad NOT LIKE (SELECT CONCAT(D2.DienstBaumPfad, '%') FROM Dienst D2 WHERE D2.DienstID = ?)
                ORDER BY Was";
        $stmt = stmt_prepare_and_execute($db_link, $sql, "ii", $DienstID, $DienstID);
    }
    if (!$stmt) { error_log("Fehler in GetDiensteAuswahlbar"); return null; }
    return mysqli_stmt_get_result($stmt);
}


// falls man sowohl nach HelferLevel, Beschreibung oder Invite Code filtern will
//function HelferLevelAbfrage($db_link, string $spalte, string $wert): array|false {
//    // Nur bestimmte Spalten zulassen, um SQL-Injection zu verhindern
//    $erlaubteSpalten = ['linkcode', 'HelferLevel', 'HelferLevelBeschreibung'];
//    if (!in_array($spalte, $erlaubteSpalten, true)) {
//        return false;
//    }
//
//    $sql = "SELECT HelferLevel, HelferLevelBeschreibung, linkcode FROM HelferLevel WHERE $spalte = ?";
//    $result = stmt_prepare_and_execute($db_link, $sql, "s", $wert);
//
//    if (!$result || $result->num_rows === 0) {
//        return false;
//    }
//
//    return $result->fetch_assoc();
//}


