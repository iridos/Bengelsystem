<?php

require_once("Wizard.php");

$wizard = new Wizard();

$wizard->addCode('selectdatabase',function($storedvariables){
    $selectdatabase['databasetype'] = $_POST['databasetype'];
    return $selectdatabase;
});

$wizard->addCode('enterlogindata',function($storedvariables){
    $logindata['host'] = $_POST['host'];
    $logindata['user'] = $_POST['user'];
    $logindata['password'] = $_POST['password'];
    $logindata['dbname'] = $_POST['dbname'];
    return $logindata;
});

$wizard->addCode('createdatabase',function($storedvariables){
    $conf_file = fopen("../etc/konfiguration.php", "w");
    fwrite($conf_file, "<?php\n");
    if($storedvariables['selectdatabase']['databasetype'] == 'SQLite'){
        fwrite($conf_file, "define( 'MYSQL_DSN', 'sqlite:". realpath("../etc") . "/helferdb.sql' );\n");
        fwrite($conf_file, "define( 'MYSQL_BENUTZER', '' );\n");
        fwrite($conf_file, "define( 'MYSQL_KENNWORT', '' );\n");
        fwrite($conf_file, "define( 'MYSQL_DATENBANK', '' );\n");
    } elseif ($storedvariables['selectdatabase']['databasetype'] == 'MariaDB'){
        fwrite($conf_file, "define( 'MYSQL_DSN', 'mysql:host=" . $storedvariables['enterlogindata']['host'] . ";dbname=". $storedvariables['enterlogindata']['dbname'] . ";charset=utf8mb4' );\n");
        fwrite($conf_file, "define( 'MYSQL_HOST', '".$storedvariables['enterlogindata']['host']."' );\n");
        fwrite($conf_file, "define( 'MYSQL_BENUTZER', '".$storedvariables['enterlogindata']['user']."' );\n");
        fwrite($conf_file, "define( 'MYSQL_KENNWORT', '".$storedvariables['enterlogindata']['password']."' );\n");
        fwrite($conf_file, "define( 'MYSQL_DATENBANK', '".$storedvariables['enterlogindata']['dbname']."' );\n");
    }
    fwrite($conf_file, "?>");
    // Test configuration:
    require_once("SQL.php");
    try{
        $db = DB::getInstance();
    }
    catch( PDOException $exception ) {
        echo "<p>Fehler beim Verbindungsversuch mit der Datenbank: \"".$exception->getMessage( )."\"</p>";
    }
    if(!is_null($db->pdoErrorCode()) && $db->pdoErrorCode() != '1'){
        echo "<p>Fehler ".$db->pdoErrorCode()." beim Verbindungsversuch mit der Datenbank: \"".$db->pdoErrorInfo()[2]."\"</p>";
        $_POST['step'] = 'createdatabase';
    } else {
        echo "<p>Successfully connected to database!</p>";
    }
});

$wizard->addCode('createdatabasetables',function($storedvariables){
    require_once("SQL.php");
    try{
        $db = DB::getInstance();
    }
    catch( PDOException $exception ) {
        echo "<p>Fehler beim Verbindungsversuch mit der Datenbank: \"".$exception->getMessage( )."\"</p>";
    }
    if(!is_null($db->pdoErrorCode()) && $db->pdoErrorCode() != '1'){
        echo "<p>Fehler ".$db->pdoErrorCode()." beim Verbindungsversuch mit der Datenbank: \"".$db->pdoErrorInfo()[2]."\"</p>";
        $_POST['step'] = 'createdatabase';
    } else {
        echo "<p>Successfully connected to database!</p>";
    }
    $sql = file_get_contents("../etc/helferdb_schema_test.sql");
    $db->prepare(__METHOD__,$sql);
    if(!is_null($db->errorCode(__METHOD__)) && $db->errorCode(__METHOD__) != '1'){
        echo "<pre>";
        var_dump(__METHOD__);
        var_dump($db->errorCode(__METHOD__));
        var_dump($db->errorInfo(__METHOD__));
        echo "</pre>";
        echo "<p>Fehler: \"".$db->errorInfo(__METHOD__)[2]."\"</p>";
        $_POST['step'] = 'createdatabasetables';
    }
    $db->execute(__METHOD__);
    if(!is_null($db->errorCode(__METHOD__)) && $db->errorCode(__METHOD__) != '1'){
        echo "<pre>";
        var_dump(__METHOD__);
        var_dump($db->errorCode(__METHOD__));
        var_dump($db->errorInfo(__METHOD__));
        echo "</pre>";
        echo "<p>Fehler: \"".$db->errorInfo(__METHOD__)[2]."\"</p>";
        $_POST['step'] = 'createdatabasetables';
    }
});

$wizard->renderPHP();

?>

