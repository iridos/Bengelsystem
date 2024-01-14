<?php
require_once("config.php");
$db = new PDO($dsn, $username, $password, $options);

function read($db, $requestParams){
    $queryParams = [];
    $queryText = " SELECT d.Was as name, SUM(TIMESTAMPDIFF(HOUR, s.Von, s.Bis)) AS value
FROM Dienst d
JOIN Schicht s ON d.DienstID = s.DienstID
JOIN EinzelSchicht es ON s.SchichtID = es.SchichtID
JOIN Helfer h ON es.HelferID = h.HelferId
GROUP BY d.DienstID;
";
//  WHERE d.Was = 'Aufbau'
    // handle dynamic loading
    if (isset($requestParams["from"]) && isset($requestParams["to"])) {
         //error_log("timespan given. from: ".$requestParams["from"]." to: ".$requestParams["to"]);
         $queryText .= " WHERE `Schicht.Bis`>=? AND `Schicht.Von` < ?;";
         $queryParams = [filter_var($requestParams["from"],FILTER_SANITIZE_NUMBER_FLOAT), filter_var($requestParams["to"],FILTER_SANITIZE_NUMBER_FLOAT)];      
    }  
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
        // only if changes will be allowed
    break;
    default: 
        throw new Exception("Unexpected Method"); 
    break;
}
header("Content-Type: application/json");
echo json_encode($result);
?>
