$(document).ready(function() {
    console.log("jQuery is ready!");
});

function showPassword(id)
{
    var x = document.getElementById(id);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}

/**
 * Berechnet "Bis" aus "Von" + Dauer (Stunden- und Minutenfeld), sofern die
 * zugehörige Checkbox "Endzeit von Dauer" angehakt ist. Parameter sind die
 * Element-IDs, damit dieselbe Funktion für das Legacy-Formular und für jede
 * Zeile im Inline-Editiermodus funktioniert.
 * Rechnet mit lokaler Zeit (Date-Konstruktor mit Einzelwerten) -- datetime-local
 * kennt keine Zeitzone, daher keine UTC-Umrechnung und kein "Z"-Gefummel nötig.
 */
function berechneEnde(vonId, stdId, minId, bisId, checkId)
{
    var check = document.getElementById(checkId);
    if (!check || !check.checked) { return; }
    var von = document.getElementById(vonId).value;
    var p = von.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
    if (!p) { return; }
    var std = parseInt(document.getElementById(stdId).value, 10) || 0;
    var min = parseInt(document.getElementById(minId).value, 10) || 0;
    var ende = new Date(+p[1], +p[2] - 1, +p[3], +p[4], +p[5] + std * 60 + min);
    var z = function (n) { return (n < 10 ? '0' : '') + n; };
    document.getElementById(bisId).value = ende.getFullYear() + '-' + z(ende.getMonth() + 1) + '-' + z(ende.getDate())
        + 'T' + z(ende.getHours()) + ':' + z(ende.getMinutes());
}

// Legacy-Formular (AdminDiensteLegacy.php); wird auch per body onload aufgerufen,
// daher auf Seiten ohne die Elemente einfach wirkungslos.
function setEndDate()
{
    berechneEnde('Schicht-Von', 'Schicht-Dauer-H', 'Schicht-Dauer-M', 'Schicht-Bis', 'Schicht-Automatic-Bis');
}

// ---- Inline-Editiermodus (AdminDienste.php, admin_edit) ---------------------
// Die Editierfelder werden erst beim Klick auf den Bleistift gebaut (aus den
// data-Attributen der Zeile). So enthält die Seite keine hunderten versteckten
// Felder, und beim Speichern wird nur die eine Zeile abgeschickt (PHP-Limit
// max_input_vars!).

function _feld(typ, attrs)
{
    return $('<input type="' + typ + '">').attr(attrs)
        .on('keydown', function (ev) { return enterSpeichern(ev, this); });
}

function toggleEditSchicht(id)
{
    var zeile = $('#S' + id);
    var edit = zeile.find('td.edit');
    if (edit.length) { edit.remove(); zeile.find('.view').show(); return; }   // Abbrechen

    var n = 'edit_schicht[' + id + ']';
    var rechne = function () {
        berechneEnde('es_von_' + id, 'es_dh_' + id, 'es_dm_' + id, 'es_bis_' + id, 'es_auto_' + id);
    };
    var td = $('<td class="edit" colspan="5"></td>').on('click', function (e) { e.stopPropagation(); });
    td.append('Von ',
        _feld('datetime-local', {id: 'es_von_' + id, name: n + '[von]', value: zeile.attr('data-von') || ''})
            .css('width', 'auto').on('change', rechne));
    td.append(' Dauer ',
        _feld('number', {id: 'es_dh_' + id, name: n + '[dauer_h]', min: 0, max: 99, value: zeile.attr('data-dh') || 0})
            .css('width', '3em').on('change', rechne), ' h ',
        _feld('number', {id: 'es_dm_' + id, name: n + '[dauer_m]', min: 0, max: 59, value: zeile.attr('data-dm') || 0})
            .css('width', '3em').on('change', rechne), ' m ');
    td.append(' Bis ',
        _feld('datetime-local', {id: 'es_bis_' + id, name: n + '[bis]', value: zeile.attr('data-bis') || ''})
            .css('width', 'auto'));
    td.append(' ', $('<label></label>').append(
        $('<input type="checkbox" checked>').attr('id', 'es_auto_' + id).css('width', 'unset'), ' Ende aus Dauer'));
    td.append(' Soll ',
        _feld('number', {name: n + '[soll]', min: 1, value: zeile.attr('data-soll') || 1}).css('width', '4em'), ' ');
    td.append($('<button type="submit" title="Speichern" data-save>&#128190;</button>').attr({name: 'SpeicherSchicht', value: id}),
        ' ',
        $('<button type="button" title="Abbrechen">&#10006;</button>').on('click', function () { toggleEditSchicht(id); }));
    zeile.find('.view').hide();
    zeile.append(td);
}

function toggleEditDienst(id)
{
    var zelle = $('#DD' + id);
    var edit = zelle.find('div.edit');
    if (edit.length) { edit.remove(); zelle.find('div.view').show(); return; }   // Abbrechen

    var n = 'edit_dienst[' + id + ']';
    var box = $('<div class="edit"></div>');
    var zeile = function (titel, feld) { box.append($('<b></b>').text(titel), '<br>', feld, '<br>'); };

    zeile('Was:', _feld('text', {name: n + '[was]', value: zelle.attr('data-was') || ''}));
    zeile('Ort:', _feld('text', {name: n + '[wo]', value: zelle.attr('data-wo') || ''}));
    zeile('Dienstinfo (Beschreibung):', $('<textarea rows="4"></textarea>').attr('name', n + '[info]').val(zelle.attr('data-info') || ''));
    zeile('Ansprechpartner:', _feld('text', {name: n + '[leiter]', list: 'dl-helfer', value: zelle.attr('data-leiter') || ''}));
    zeile('Elterndienst (leer = Top-Level):',
        _feld('text', {name: n + '[eltern]', list: 'dl-eltern-dienste', placeholder: 'ID oder Name eingeben', value: zelle.attr('data-eltern') || ''}));
    var sel = $('<select></select>').attr('name', n + '[level]');
    $.each(DIENST_EDIT_LEVEL, function (lvl, beschr) { sel.append($('<option></option>').val(lvl).text(beschr)); });
    sel.val(zelle.attr('data-level'));
    zeile('HelferLevel:', sel);
    box.append('<br>',
        $('<button type="submit" title="Speichern" data-save>&#128190;</button>').attr({name: 'SpeicherDienst', value: id}),
        ' ',
        $('<button type="button" title="Abbrechen">&#10006;</button>').on('click', function () { toggleEditDienst(id); }));
    zelle.find('div.view').hide();
    zelle.append(box);
}

// Enter in einem Eingabefeld soll die eigene Zeile speichern -- nicht den
// ersten Submit-Button des (großen) Formulars auslösen.
function enterSpeichern(ev, el)
{
    if (ev.key !== 'Enter') { return true; }
    ev.preventDefault();
    $(el).closest('.edit').find('[data-save]').first().click();
    return false;
}

// Mülleimer: ausgegraut ("gesperrt"), bis beim Dienst "Schichten löschen" angehakt wird
function schichtenLoeschenUmschalten(dienstId, an)
{
    $('tr.header[data-dienst="' + dienstId + '"]').nextUntil('tr.header').find('button.btn-loeschen')
        .toggleClass('gesperrt', !an).css('opacity', an ? '' : '0.4');
}

function loescheSchicht(id, btn)
{
    if ($(btn).hasClass('gesperrt')) {
        alert('Löschen ist gesperrt.\n\nZum Freischalten beim Dienst den Haken "Schichten löschen" setzen.');
        return;
    }
    $('#deleteSchichtID').val(id);
    $('#deleteSchichtForm').submit();
}

//// https://www.w3schools.com/howto/howto_js_collapsible.asp
//var coll = document.getElementsByClassName("collapsible");
//var i;
//
//for (i = 0; i < coll.length; i++) {
//  coll[i].addEventListener("click", function() {
//    this.classList.toggle("active");
//    var content = this.nextElementSibling;
//    if (content.style.display === "block") {
//      content.style.display = "none";
//    } else {
//      content.style.display = "block";
//    }
//  });
//}
//

//// collapse column rows that are not header
function collapse_table_rows()
{
    $(document).ready(
        function () {
            // Nur explizit als "collapsible-content" markierte Zeilen werden versteckt.
            // Alles andere (header, static, zukünftige neue Zeilenarten) bleibt sichtbar,
            // sofern nicht ausdrücklich als einklappbar markiert.
            $('table.collapsible tr.collapsible-content').hide();

            $('table.collapsible tr[target="active"]').show();
            $('table.collapsible tr[target="active"]').prevAll('.header').first().attr('id', 'active');

            // Dienste aufklappen:
            //  data-aktiv      = gerade bearbeitet -> aufklappen und anspringen
            //  data-aufklappen = z.B. wenige Suchtreffer -> nur aufklappen
            var zuAufklappen = $('table.collapsible tr.header[data-aktiv], table.collapsible tr.header[data-aufklappen]');

            // Rückkehr von der Schicht-Detailseite: den Dienst, aus dem man kam, wieder aufklappen
            try {
                var gemerkt = JSON.parse(sessionStorage.getItem('dienstAufgeklappt') || 'null');
                sessionStorage.removeItem('dienstAufgeklappt');
                if (gemerkt && gemerkt.pfad === location.pathname + location.search && (Date.now() - gemerkt.zeit) < 600000) {
                    var gemerkterDienst = $('table.collapsible tr.header[data-dienst="' + gemerkt.dienst + '"]');
                    gemerkterDienst.attr('data-aktiv', '1');
                    zuAufklappen = zuAufklappen.add(gemerkterDienst);
                }
            } catch (e) { /* sessionStorage nicht verfügbar: dann eben eingeklappt */ }

            zuAufklappen.each(function () {
                $(this).nextUntil(':not(.collapsible-content)').show();
                $(this).find('span').first().text('-');
            });
            var anspringen = $('table.collapsible tr.header[data-aktiv]').first();
            if (anspringen.length) {
                $('#active').removeAttr('id');   // ein älteres "active" (SchichtIdAktiv) weicht
                anspringen.attr('id', 'active');
            }

            // Klick auf eine Schicht-Zeile (-> Detailseite): Dienst merken
            $('table.collapsible').on('click', 'tr[onclick*="DetailsSchichten"]', function () {
                var h = $(this).prevAll('tr.header').first();
                try {
                    sessionStorage.setItem('dienstAufgeklappt', JSON.stringify({
                        pfad: location.pathname + location.search, dienst: h.attr('data-dienst'), zeit: Date.now()
                    }));
                } catch (e) { }
            });
            location.href = '#active';

            $('table.collapsible tr.header').click(
                function () {
                    $(this).find('span').text(
                        function (_, value) {
                            return value == '-' ? '+' : '-'
                        }
                    );
                    $(this).nextUntil(':not(.collapsible-content)').slideToggle(100, function () {});
                }
            );
        }
    );
}

function expand_all_table_rows()
{

    $('tr:not(.header)').show();
}
