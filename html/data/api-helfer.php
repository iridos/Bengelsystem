<?php

require_once "../konfiguration.php";
$options = [];
SESSION_START();
$db = new PDO($dsn, MYSQL_BENUTZER, MYSQL_KENNWORT, $options);
if (!isset($_SESSION["HelferID"])) {
    exit ;
}
$HelferID = $_SESSION["HelferID"];

function read_from_db($db, $requestParams, $HelferID)
{
    $queryParams = [];
    $queryText = "
    select Schicht.SchichtID as id,
           Schicht.Von as start_date,
           Schicht.Bis as end_date,
           concat(Dienst.Was, ' [',Dienst.Wo,'](', SchichtUebersicht.C,'/',SchichtUebersicht.Soll,')') as text, 
           group_concat(Helfer.Name separator '\\n' ) as Name, 
           group_concat(concat(Helfer.Name,'+',Helfer.Handy) separator '\\n' ) as Kontakt, 
           Dienst.Info,
           CASE WHEN (SchichtUebersicht.Soll-SchichtUebersicht.C)<=0 THEN  'darkgreen'  
                WHEN SchichtUebersicht.C>0 THEN '#dd9000' 
                ELSE 'darkred' 
           END   as color,
           CASE WHEN (SchichtUebersicht.Soll-SchichtUebersicht.C)<=0 THEN  'white'  
                WHEN SchichtUebersicht.C>0 THEN 'black' 
                ELSE 'yellow' 
           END   as textColor
	   FROM EinzelSchicht 
           INNER JOIN Helfer  ON EinzelSchicht.HelferID=Helfer.HelferId 
           RIGHT JOIN Schicht ON Schicht.SchichtID=EinzelSchicht.SchichtID 
           INNER JOIN Dienst  ON Dienst.DienstID=Schicht.DienstID 
           INNER JOIN SchichtUebersicht ON Dienst.DienstID=SchichtUebersicht.DienstID AND SchichtUebersicht.SchichtID=Schicht.SchichtID 
           WHERE Helfer.HelferID=$HelferID
           GROUP BY Schicht.SchichtID";

    $query = $db->prepare($queryText);
    $query->execute($queryParams);
    error_log(date('Y-m-d H:i ') . print_r($queryText, true), 3, "/var/log/dropamsee/debug.log");
    $events = $query->fetchAll();
    return $events;
}

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        $result = read_from_db($db, $_GET, $HelferID);
        break;
    case "POST":
        // only if changes will be allowed
        break;
    default:
        throw new Exception("Unexpected Method");
    break;
}
header("Content-Type: application/json");
echo json_encode($result);
