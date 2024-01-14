<?php

function KalenderDatum ($start_date)
{
    $format = 'Y,m,d';
    // the calender counts monts from 0 for whatever reason, 
    // so we have to subtract 1 from the month. Subtracting 1 month is not possible because it can wrap to december
    $KalenderDatum = date_format($start_date, "Y");
    $KalenerMonat = date_format($start_date, "m")-1;
    $KalenderDatum = "$KalenderDatum,$KalenerMonat,".date_format($start_date, "d");
    return ($KalenderDatum );
}
function AusgabeZeitbereichZeile ($start_date,$ZeitBereich,$TageNamenDeutsch) 
{
    // ZeitbereichWerte (array): -1 davor, 0 kein Limit, 1-N Tag N der Con, 1000: nach der Con
    $ZeitBereichWerte = ZEITBEREICHWERTE;
    $ZeitBereichFelder = count($ZeitBereichWerte);
    $ZeitBereichFeldBreite = round(100 / $ZeitBereichFelder); // % width for style
    
    $format = 'Y-m-d';
    
    // iterate over all days plus "before" and "after"
    // Wenn TAG_DAUER=4, dann sind die Werte 1-4 die Tage der Con
    // ZeitBereichWerte = [-1, 0, 1, 2, 3, 4, 1000]
    // -1: davor, 0: alle, 1-4: Tag 1-4, 1000: danach
    foreach ($ZeitBereichWerte as &$EinZeitBereich) {
        if ($EinZeitBereich > 0 && $EinZeitBereich <= TAGE_DAUER) {
            $PlusTage = $EinZeitBereich - 1;
          //TODO: only if locale DE
            $day = $start_date->add(new DateInterval("P{$PlusTage}D"));
            $Wochentag = $TageNamenDeutsch[date_format($day, 'w')];
    
            $Text = "$Wochentag (Tag{$EinZeitBereich})";
            $Von = date_format($day, $format) . " 00:00:00";
            $Bis = date_format($day, $format) . " 23:59:59";
        } elseif ($EinZeitBereich == -1) {
            $Text = 'Davor';
            $Von = "2000-01-01 00:00:00";
            $Bis = date_format($start_date, $format) . " 00:00:00";
        } elseif ($EinZeitBereich == 0) {
            $Text = 'Alle' ;
            $Von = "2000-01-01 00:00:00";
            $Bis = "3000-01-01 00:00:00";
        } elseif ($EinZeitBereich == 1000) {
            $Text = 'Danach';
               $tage_dauer = TAGE_DAUER;
               $day = $start_date->add(new DateInterval("P{$tage_dauer}D"));
            $Von = date_format($day, $format) . " 00:00:00";
            $Bis = "3000-01-01 00:00:00";
        }
      // highlight the selected time range
        if ($EinZeitBereich == $ZeitBereich) {
            $color = 'background-color:#0000FF; ' ;
            $MeinVon = $Von;
            $MeinBis = $Bis;
        } else {
            $color = '';
        }
      // write the field for each day
        echo "<th style='width:{$ZeitBereichFeldBreite}%; $color' ";
        echo "onclick='window.location.href=\"TeilnehmerSchichtenAusdruck.php?ZeitBereich={$EinZeitBereich}\";'>";
        echo "$Text" . "</th>\n";
}
echo "</tr>"; //Zeitbereich tr
return [
   'MeinVon' => $MeinVon,
   'MeinBis' => $MeinBis
       ];
}
?>
