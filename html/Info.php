<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();

?>
<!doctype html>
<html>
<head>
  <title><?php echo EVENTNAME ?> Home</title>
  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
<meta http-equiv="Refresh" content="0; url=<?php echo INFORMATIONS_URL;?>" />
<meta name="viewport" content="width=480" />
</head>
<body>
<!-- hier Infos zur eigenen Con oder Helferdiensten allgemein angeben -->
</body>
</html>
