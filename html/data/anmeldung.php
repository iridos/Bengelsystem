<?php
require_once("config.php");
$db = new PDO($dsn, $username, $password, $options);

function read($db, $requestParams){
    $queryParams = [];
    $queryText = "
    select Schicht.SchichtID as id,
           Schicht.Von as start_date,
           Schicht.Bis as end_date,
           concat(Dienst.Was, ' [',Dienst.Wo,'](', SchichtUebersicht.C,'/',SchichtUebersicht.Soll,')') as Name, 
           group_concat(concat(Helfer.Name,'+',Helfer.Handy) separator '\\n' ) as text, 
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
           GROUP BY Schicht.SchichtID;";
  

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
