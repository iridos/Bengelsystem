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

function setEndDate()
{
    // js Date .toISOString automatically converts to UTC marking the string by "Z" at the end
    // This is not understood by the browser when setting the value of the string
    // UTC is 1h off resulting in a 1h wrong time if the Z is removed
    // so we add the "Z" here, then remove it in the end
    // this works for my local firefox. There should be a check if there already is a Z
    // because maybe browsers also convert to UTC for the internal value
    // but I guess in the worst case we have ZZ at the end and the js autofill fails
    var checkBox = document.getElementById("Schicht-Automatic-Bis");
    if (checkBox.checked == true) {
        var start = new Date(document.getElementById("Schicht-Von").value + 'Z');
        var delta = new Date("0000-01-01T" + document.getElementById("Schicht-Dauer").value);
        var end = new Date(start);
        var endHours = start.getHours() + delta.getHours();
        end.setHours(endHours);
        console.log("Schicht-Von: " + document.getElementById("Schicht-Von").value + 'Z' + " Schicht-Dauer: " + "0000-01-01T" + document.getElementById("Schicht-Dauer").value + "Schicht-Bis: " + end.toISOString().replace(/.000Z/,""));
        end.setMinutes(start.getMinutes() + delta.getMinutes());
        document.getElementById("Schicht-Bis").value = end.toISOString().replace(/.000Z/,"");
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
            // Nur explizit als "collapsible-content" markierte Zeilen werden versteckt.
            // Alles andere (header, static, zukünftige neue Zeilenarten) bleibt sichtbar,
            // sofern nicht ausdrücklich als einklappbar markiert.
            $('table.collapsible tr.collapsible-content').hide();

            $('table.collapsible tr[target="active"]').show();
            $('table.collapsible tr[target="active"]').prevAll('.header').first().attr('id', 'active');
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
