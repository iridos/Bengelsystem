<?php

require_once("Wizard.php");

$wizard = new Wizard("../setupWizard.json");

$wizard->addCode('basedata', function ($storedvariables) {
    $basedata['eventname'] = $_POST['eventname'];
    $basedata['startdate'] = $_POST['startdate'];
    $basedata['duration'] = $_POST['duration'];
    $basedata['timezone'] = $_POST['timezone'];
    $basedata['locale'] = $_POST['locale'];
    $basedata['logfile'] = $_POST['logfile'];
    $basedata['infourl'] = $_POST['infourl'];
    $basedata['urlprefix'] = $_POST['urlprefix'];
    $basedata['secretkey'] = $_POST['secretkey'];
    $basedata['secretverification'] = $_POST['secretverification'];
    return $basedata;
});

$wizard->addCode('selectdatabase', function ($storedvariables) {
    $selectdatabase['databasetype'] = $_POST['databasetype'];
    return $selectdatabase;
});

$wizard->addCode('enterlogindata', function ($storedvariables) {
    $logindata['host'] = $_POST['host'];
    $logindata['user'] = $_POST['user'];
    $logindata['password'] = $_POST['password'];
    $logindata['dbname'] = $_POST['dbname'];
    return $logindata;
});

$wizard->addCode('createdatabase', function ($storedvariables) {
    $conf_file = fopen("../bengelsystem_konfiguration.php", "w");
    fwrite($conf_file, "<?php\n");
    if ($storedvariables['selectdatabase']['databasetype'] == 'SQLite') {
        fwrite($conf_file, "define( 'MYSQL_DSN', 'sqlite:" . realpath("..") . "/helferdb.sqlite3' );\n");
        fwrite($conf_file, "define( 'MYSQL_HOST', '' );\n");
        fwrite($conf_file, "define( 'MYSQL_BENUTZER', '' );\n");
        fwrite($conf_file, "define( 'MYSQL_KENNWORT', '' );\n");
        fwrite($conf_file, "define( 'MYSQL_DATENBANK', '' );\n");
        fwrite($conf_file, "define( 'DBTYPE', 'sqlite');\n");
    } elseif ($storedvariables['selectdatabase']['databasetype'] == 'MariaDB') {
        fwrite($conf_file, "define( 'MYSQL_DSN', 'mysql:host=" . $storedvariables['enterlogindata']['host'] . ";dbname=" . $storedvariables['enterlogindata']['dbname'] . ";charset=utf8mb4' );\n");
        fwrite($conf_file, "define( 'MYSQL_HOST', '" . $storedvariables['enterlogindata']['host'] . "' );\n");
        fwrite($conf_file, "define( 'MYSQL_BENUTZER', '" . $storedvariables['enterlogindata']['user'] . "' );\n");
        fwrite($conf_file, "define( 'MYSQL_KENNWORT', '" . $storedvariables['enterlogindata']['password'] . "' );\n");
        fwrite($conf_file, "define( 'MYSQL_DATENBANK', '" . $storedvariables['enterlogindata']['dbname'] . "' );\n");
        fwrite($conf_file, "define( 'DBTYPE', 'mariadb');\n");
    }
    fwrite($conf_file, "define( 'LOGFILE', '" . $storedvariables['basedata']['logfile'] . "' );\n");
    fwrite($conf_file, "define( 'EVENTNAME', '" . $storedvariables['basedata']['eventname'] . "' );\n");
    fwrite($conf_file, "define( 'INFORMATIONS_URL', '" . $storedvariables['basedata']['infourl'] . "' );\n");
    fwrite($conf_file, "define( 'SECRET_KEY', '" . $storedvariables['basedata']['secretkey'] . "' );\n");
    fwrite($conf_file, "define( 'SECRET_VERIFICATION', '" . $storedvariables['basedata']['secretverification'] . "' );\n");
    fwrite($conf_file, "define( 'URLPREFIX', '" . $storedvariables['basedata']['urlprefix'] . "' );\n");
    fwrite($conf_file, "define( 'TAGE_DAUER', '" . $storedvariables['basedata']['duration'] . "' );\n");
    fwrite($conf_file, "date_default_timezone_set('" . $storedvariables['basedata']['timezone'] . "');\n");
    fwrite($conf_file, "setlocale(LC_TIME, \"" . $storedvariables['basedata']['locale'] . "\");\n");
    fwrite($conf_file, "\$start_date = new DateTimeImmutable(\"" . $storedvariables['basedata']['startdate'] . "\");\n");
    fwrite($conf_file, "\$secret_key = SECRET_KEY;\n");
    fwrite($conf_file, "\$secret_verification = SECRET_VERIFICATION;\n");
    fwrite($conf_file, "\$urlprefix = URLPREFIX;\n");
    fwrite($conf_file, "?>");
    // Test configuration:
    require_once("SQL.php");
    try {
        $db = DB::getInstance();
    } catch (PDOException $exception) {
        echo "<p>Fehler beim Verbindungsversuch mit der Datenbank: \"" . $exception->getMessage() . "\"</p>";
    }
    if (!is_null($db->pdoErrorCode()) && $db->pdoErrorCode() != '00000') {
        echo "<p>Fehler " . $db->pdoErrorCode() . " beim Verbindungsversuch mit der Datenbank: \"" . $db->pdoErrorInfo()[2] . "\"</p>";
        $_POST['step'] = 'createdatabase';
    } else {
        echo "<p>Erfolgreich mit der Datenbank verbunden!</p>";
    }
});

$wizard->addCode('createdatabasetables', function ($storedvariables) {
    require_once("SQL.php");
    try {
        $db = DB::getInstance();
    } catch (PDOException $exception) {
        echo "<p>Fehler beim Verbindungsversuch mit der Datenbank: \"" . $exception->getMessage() . "\"</p>";
    }
    if (!is_null($db->pdoErrorCode()) && $db->pdoErrorCode() != '00000') {
        echo "<p>Fehler " . $db->pdoErrorCode() . " beim Verbindungsversuch mit der Datenbank: \"" . $db->pdoErrorInfo()[2] . "\"</p>";
        $_POST['step'] = 'createdatabase';
    } else {
        echo "<p>Erfolgreich mit der Datenbank verbunden!</p>";
    }
    if ($storedvariables['selectdatabase']['databasetype'] == 'SQLite') {
        $dbscript = "../helferdb_structure_sqlite.sql";
    } elseif ($storedvariables['selectdatabase']['databasetype'] == 'MariaDB') {
        $dbscript = "../helferdb_structure_mariadb.sql";
    }
    $statementsReturnvalues = $db->executeScript(__METHOD__, $dbscript);
    if (!is_null($db->pdoErrorCode()) && $db->pdoErrorCode() != '00000') {
        echo "<p>Fehler " . $db->pdoErrorCode() . " bei Statement Nr. " . sizeof($statementsReturnvalues) . " beim Versuch Tabellen anzulegen: \"" . $db->pdoErrorInfo()[2] . "\"</p>";
        $_POST['step'] = 'createdatabase';
    } else {
        echo "<p>Datenbanktabellen wurden erfolgreich angelegt!</p>";
    }
});

$wizard->renderPHP();
