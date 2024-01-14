<?php
$dsn = "mysql:host=localhost;dbname=drop2023";
$username = "drop2023";
$password = "69978eab27b30c999dec1fd5e82cce83";
 
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);
 

define( 'MYSQL_HOST', 'localhost' );
define( 'MYSQL_BENUTZER', 'drop2023' );
define( 'MYSQL_KENNWORT', '69978eab27b30c999dec1fd5e82cce83' );
define( 'MYSQL_DATENBANK', 'drop2023' );

?>
