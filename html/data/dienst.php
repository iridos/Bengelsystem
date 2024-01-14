<?php
require_once("config.php");
$db = new PDO($dsn, $username, $password, $options);

function read($db, $requestParams){
    $queryParams = [];
    $queryText = "select Schicht.SchichtID as id,Schicht.Von as start_date,Schicht.Bis as end_date,group_concat(Helfer.Name separator '\\n' ) as text from EinzelSchicht INNER JOIN Helfer ON EinzelSchicht.HelferID=Helfer.HelferId INNER JOIN Schicht ON Schicht.SchichtID=EinzelSchicht.SchichtID INNER JOIN Dienst ON Dienst.DienstID=Schicht.DienstID and Dienst.Wo = 'Anmeldung' GROUP BY Schicht.SchichtID;";

    // handle dynamic loading
    if (isset($requestParams["from"]) && isset($requestParams["to"])) {         
       error_log("timespan given. from: ".$requestParams["from"]." to: ".$requestParams["to"]);
       $queryText .= " WHERE `Schicht.Bis`>=? AND `Schicht.Von` < ?;";          $queryParams = [$requestParams["from"], $requestParams["to"]];      }  
    $query = $db->prepare($queryText);
    $query->execute($queryParams);
    $events = $query->fetchAll();
    return $events;
}

switch ($_SERVER["REQUEST_METHOD"]) {
    case "GET":
        $result = read($db, $_GET);
        break;
    case "POST":
        // we'll implement this later
    break;
    default: 
        throw new Exception("Unexpected Method"); 
    break;
}
header("Content-Type: application/json");
echo json_encode($result);

