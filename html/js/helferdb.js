function showPassword(id) {
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
function collapse_table_rows(){
 $(document).ready(function() {
  $('tr:not(.header)').hide();

  $('tr.header').click(function() {
    $(this).find('span').text(function(_, value) {
      return value == '-' ? '+' : '-'
    });
    
    $(this).nextUntil('tr.header').slideToggle(100, function() {});
  });
 });
}

function expand_all_table_rows(){

 $('tr:not(.header)').hide(); // make all collapsed so that slideToggle doesnt close opened ones
 $('tr:not(.header)').slideToggle(100, function() {});
}
