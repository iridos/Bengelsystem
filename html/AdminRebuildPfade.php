<?php
require_once 'konfiguration.php';
SESSION_START();
require 'SQL.php';
$db_link = ConnectDB();
require '_login.php';
require '_functions.php';

// Nur für Admins
if ($AdminStatus != 1) {
    echo '<!doctype html><head><meta http-equiv="Refresh" content="0; URL=index.php" /></head></html>';
    exit;
}

$bericht = null;
if (isset($_POST['rebuild'])) {
    $bericht = RebuildAlleDienstPfade($db_link);
}
echo PageHeader("Dienst-Pfade neu aufbauen");
?>
<h2>DienstBaumPfad Rebuild</h2>
<p>Berechnet alle DienstBaumPfad-Werte neu aus ElternDienstID. 
   Inkonsistenzen (Zyklen, fehlende Eltern) werden auf Top-Level zurückgesetzt 
   und hier angezeigt.</p>

<?php if ($bericht !== null): ?>
    <h3>Ergebnis</h3>
    <p><?= $bericht['ok'] ?> Dienste waren bereits korrekt.</p>
    <?php if (!empty($bericht['gefixt'])): ?>
        <p><strong>⚠ Folgende Einträge wurden korrigiert:</strong></p>
        <ul>
        <?php foreach ($bericht['gefixt'] as $m): ?>
            <li><?= htmlspecialchars($m) ?></li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>✓ Keine Korrekturen nötig.</p>
    <?php endif; ?>
<?php endif; ?>

<form method="post">
    <button type="submit" name="rebuild">Pfade neu aufbauen</button>
</form>


