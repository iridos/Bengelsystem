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
    $conf_file = fopen("../etc/konfiguration.php", "w");
    fwrite($conf_file, "<?php\n");
    fwrite($conf_file, "define( 'MYSQL_HOST', '".$logindata['host']."' );\n");
    fwrite($conf_file, "define( 'MYSQL_BENUTZER', '".$logindata['user']."' );\n");
    fwrite($conf_file, "define( 'MYSQL_KENNWORT', '".$logindata['password']."' );\n");
    fwrite($conf_file, "define( 'MYSQL_DATENBANK', '".$logindata['dbname']."' );\n");
    fwrite($conf_file, "?>");
    return $logindata;
});

$wizard->renderPHP();

?>

