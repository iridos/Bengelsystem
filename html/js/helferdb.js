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

// Setzt alle Felder einer Zeile/eines Blocks auf den ursprünglichen Wert zurück
function _editFelderZuruecksetzen(container)
{
    $(container).find('input, textarea, select').each(function () {
        if (this.type === 'checkbox') { this.checked = this.defaultChecked; }
        else if (this.tagName === 'SELECT') {
            for (var i = 0; i < this.options.length; i++) { this.options[i].selected = this.options[i].defaultSelected; }
        }
        else { this.value = this.defaultValue; }
    });
}

function toggleEditSchicht(id)
{
    var zeile = $('#S' + id);
    if (zeile.find('td.edit').is(':visible')) { _editFelderZuruecksetzen(zeile.find('td.edit')); }
    zeile.find('.view').toggle();
    zeile.find('.edit').toggle();
}

function toggleEditDienst(id)
{
    var zelle = $('#DD' + id);
    if (zelle.find('div.edit').is(':visible')) { _editFelderZuruecksetzen(zelle.find('div.edit')); }
    zelle.find('.view').toggle();
    zelle.find('.edit').toggle();
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

            // Dienste, die gerade bearbeitet wurden (data-aktiv, siehe _ZeigeDienstHeader):
            // komplett aufklappen und anspringen. Ein evtl. älteres "active" (aus
            // SchichtIdAktiv) weicht, damit die ID nur einmal vorkommt.
            var aktiv = $('table.collapsible tr.header[data-aktiv]');
            if (aktiv.length) {
                $('#active').removeAttr('id');
                aktiv.each(function () {
                    $(this).nextUntil(':not(.collapsible-content)').show();
                    $(this).find('span').first().text('-');
                });
                aktiv.first().attr('id', 'active');
            }
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
