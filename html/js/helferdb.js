function showPassword(id)
{
    var x = document.getElementById(id);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
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
            // Unter-Zeilen in collapsible Tabellen verbergen
            $('table.collapsible tr:not(.header)').hide();
            // Zeile mit dem target="active" (von PHP nach submit gesetzt) und dazugehoerige Zeilen anzeigen
            $('table.collapsible tr[target="active"]').prevUntil('tr.header').addBack().nextUntil('tr.header').addBack().show();
            // id="active" als Anker auf letztes tr.header vor der target=active Seite setzen und dort hin springen
            // damit der Nutzer nach Abschicken des Posts seine geoeffneten Optionen sieht
            $('table.collapsible tr[target="active"]').prevAll('.header').first().attr('id', 'active');
            location.href = '#active';
            $('table.collapsible tr.header').click(
                function () {
                    $(this).find('span').text(
                        function (_, value) {
                            return value == '-' ? '+' : '-'
                        }
                    );
                    $(this).nextUntil('tr.header').slideToggle(100, function () {});
                }
            );
        }
    );
}

function expand_all_table_rows()
{

    $('tr:not(.header)').show();
}
