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
    try {
        $stmt = mysqli_prepare($db_link, $sql);
        if ($types !== "") {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            $err = "Execute failed: " . mysqli_stmt_error($stmt) . "\nSQL: $sql";
            $err .= debug_sql($sql, $types, $params);
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
    mysqli_stmt_close($stmt);
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
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log("AlleBelegteSchichtenCount Execute failed: " . mysqli_stmt_error($stmt));
        echo "Abfrage konnte nicht ausgeführt werden.";
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    $zeile = mysqli_fetch_array($result, MYSQLI_ASSOC);

    return $zeile['Anzahl'];
}



function AlleSchichtenImZeitbereich($db_link, $Von, $Bis, $HelferLevel = -1)#stmt
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

function GetDiensteChilds($db_link, $DienstID)#stmt2
{
    $sql = "SELECT DienstID, Was, Wo, Info, Leiter FROM Dienst where ElternDienstID=? ORDER BY Was";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "i", $DienstID);
    if (!$stmt) {error_log("Fehler in GetDiensteChilds"); return false;}
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}


function ChangeDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)#stmt2
{
    $sql = "UPDATE Dienst SET Was=?, Wo=?, Info=?, Leiter=?, ElternDienstID=?, HelferLevel=?  where DienstID=?";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "sssiiii",$Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel, $DienstID);
    if (!$stmt) {error_log("Fehler in ChangeDienst"); return false;}
    $result = mysqli_stmt_affected_rows($stmt);
    return $result;
}

function NewDienst($db_link, $DienstID, $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel)#stmt2
{

//    $DienstID 
//    $Was         //Name des Dienstes
//    $Wo          //Ort
//    $Info        //vollstaendige Beschreibung
//    $Leiter      // int HelferID des Leiters
//    $Gruppe      // ??
//    $HelferLevel // int (1,2) Teilnehmer oder Dauerhelfer

    $sql = "INSERT INTO Dienst (Was, Wo, Info, Leiter, ElternDienstID, HelferLevel) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = stmt_prepare_and_execute($db_link, $sql, "sssiii", $Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel);

    $HelferName = $_SESSION["HelferName"] ?? "unbekannt";
    $HelferID   = $_SESSION["HelferID"] ?? 0;

    if (!$stmt) {
        $err = mysqli_error($db_link);
        echo "Fehler NewDienst: $err";
        $full_sql = debug_sql($sql, "sssiii", [$Was, $Wo, $Info, $Leiter, $Gruppe, $HelferLevel]);
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName (ID:$HelferID) konnte Schicht nicht anlegen mit Anfrage $full_sql Grund: $err\n", 3, LOGFILE);
        die();
    } else {
        error_log(date('Y-m-d H:i') . "  NeueSchicht: $HelferName (HelferID:$HelferID) hat Dienst angelegt mit Was: $Was Wo: $Wo Info: $Info Leiter: $Leiter Gruppe: $Gruppe HelferLevel: $HelferLevel\n", 3, LOGFILE);
    }
    $result = mysqli_stmt_affected_rows($stmt);
    return $result;
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
    $sql = "select HelferLevel,HelferLevelBeschreibung from HelferLevel";
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


