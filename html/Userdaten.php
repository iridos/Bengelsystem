<?php
// Login und Admin Status testen. Wenn kein Admin-Status, Weiterleiten auf index.php und beenden
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';

$header= <<< HEADER
<!doctype html>
<html>
 <head>
  <title><?php echo EVENTNAME ?> Persönliche Daten ändern</title>

  <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
  <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>  
  <meta name="viewport" content="width=480" />
 </head>
 <body>
HEADER; //<? vim syntax-highlight-fix


$HelferID = $_SESSION["HelferID"];
$AdminID = $_SESSION["AdminID"];


/// Helferdaten Aendern
////////////////////////////////////////////////////////

$messages = [];
if (isset($_POST['change'])) {

    // Eingaben überprüfen:
    if (strlen($_POST['helfer-newpasswort']) < 8 and $_POST['helfer-newpasswort'] != "") {
        $messages[] = 'Neues Passwort zu kurz';
    }
    $HelferName = $_POST['helfer-name'];
    $HelferEmail = $_POST['helfer-email'];
    $HelferHandy = $_POST['helfer-handy'];
    $HelferNewPasswort  = $_POST['helfer-newpasswort'];
    if (empty($messages)) {
        // Helferdaten Ändern
        HelferdatenAendern($db_link, $HelferName, $HelferEmail, $HelferHandy, $HelferNewPasswort, $HelferID);
        header("Location: " . $_SERVER['PHP_SELF']); // reload as GET
        exit;
    }
} else {
        echo $header;
        // Fehlermeldungen ausgeben:
        echo '<div class="error"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars($message) . '</li>';
        }
        echo '</ul></div>';
}

////////////////////////////////////////////////////////////////
// Helferdate holen
///////////////////////////////////////////////////////////////

$db_erg = Helferdaten($db_link, $HelferID);


while ($zeile = mysqli_fetch_array($db_erg, MYSQLI_ASSOC)) {
    $HelferName = $zeile['Name'];
    $HelferEmail = $zeile['Email'];
    $HelferHandy = $zeile['Handy'];
}

?>

          <table class="commontable">
            <tr>
                <th><button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button> Helferdaten <?php echo EVENTNAME; ?></th>
            </tr>
<form method="post">
            <tr>

              <td>Name</td></tr><tr><td>
              <?php
   if($HelferName == $HelferEmail) { echo "<b style='color:red'> Hier auf Name/Spitzname ändern:"; }
                ?>
              <input name="helfer-name" type="text" value="<?php echo htmlspecialchars($HelferName ?? '')?>" required>
              <?php
   if($HelferName == $HelferEmail) { echo "</b>"; }
                ?>
              </td>
            </tr>
           <tr>
              <td>Handy (freiwillig)</td></tr><tr><td>     
              <input name="helfer-handy" type="tel" value="<?php echo htmlspecialchars($HelferHandy ?? '')?>" >
              </td>
            </tr>
<?php
$HelferEmailHTML    = htmlspecialchars($HelferEmail ?? '');
# if people come from the UrlLink and their name is the email, do not display change-email or passord options
# we still need this in the form
$isHidden="";
if($HelferName == $HelferEmail) {$isHidden="display: none";}

$emailandpass =  <<<emailandpass
        <tr style="$isHidden">
          <td>Loginname (Email). Achtung, danach login nur noch mit neuem Namen möglich!</td></tr><tr style="$isHidden"><td>
          <input name="helfer-email" type="email " value="$HelferEmailHTML" required  style="$isHidden">
          </td>
        </tr>
         <!-- wird vom Code nicht abgefragt <tr style="$isHidden"> 
          <td>Altes Helfer Passwort </td></tr><tr style="$isHidden"><td>
          <input name="helfer-passwort" type="password" value=""  style="$isHidden">
          </td>
        </tr-->
        <tr style="$isHidden">
          <td>Neues Helfer Passwort</td></tr><tr style="$isHidden"><td>
          <input name="helfer-newpasswort" type="text" value=""  style="$isHidden">
          </td>
        </tr>
emailandpass;
    echo $emailandpass;
?>
          </table>

          <p><button name="change" style="width:150px !important" value="1">&Auml;ndern</button></p>
 </form> 
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button>
 </body>
</html>
