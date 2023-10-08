<!DOCTYPE html>
<html>
<head>
  <title> Helferdienste  </title>
  <meta charset="utf-8">
  <!--meta name="viewport" content="width=device-width, initial-scale=1 ,user-scalable=1"-->
  <script src="../scheduler/codebase/dhtmlxscheduler.js"></script>
  <!-- link href="../scheduler/codebase/dhtmlxscheduler_contrast_black.css" rel="stylesheet" type="text/css" charset="utf-8"-->
  <link href="../scheduler/codebase/dhtmlxscheduler.css" rel="stylesheet" type="text/css" charset="utf-8">
  <link rel="stylesheet" href="../scheduler/samples/common/controls_styles.css">
    <style>

        html, body{
            margin:0px;
            padding:0px;
            height:100%;
            overflow:hidden;
        }


    </style> 
    <!-- darkstyle
    style type="text/css" >
            .filters_wrapper {
                    background-color: black;
                    color: white;
                    font: 500 14px Roboto;
                    padding-left: 15px;
                    padding-right: 15px;
            }
            .filters_wrapper span {
                    font-weight: bold;
                    padding-left: 15px;
                    padding-right: 15px;
                    color: rgba(0,0,0,0.7);
            }
            .filters_wrapper label {
                    padding-left: 15px;
                    padding-right: 15px;
            }
    </style-->

</head> 
<body> 
<button name="BackHelferdaten" value="1"  onclick="window.location.href = 'index.php';"><b>&larrhk;</b></button><br>
<div class="filters_wrapper" id="filters_wrapper">
&nbsp;
  Mehrtagesdienste anzeigen: <input id="multidaycheck" class="sch_radio" type="checkbox" checked onchange="toggleMultiday(this)"> 
  Einfärben: <input type="text" id="colorize"> <!--onchange="markEntries(this.value);" onpaste    = "this.onchange();" onsubmit="markEntries(this.value);"-->
  Filtern: <input type="text" id="filterWrap">
  <br/>
  Achtung: Ende Nachtdienste wird falsch angezeigt (immer Mitternacht) - Popup-Fenster zeigt richtige Zeiten
</div>

<div id="scheduler_here" class="dhx_cal_container" style='width:100%; height:100%;'> 
        <div class="dhx_cal_navline"> 
            <div class="dhx_cal_prev_button">&nbsp;</div> 
            <div class="dhx_cal_next_button">&nbsp;</div> 
            <div class="dhx_cal_today_button"></div> 
            <div class="dhx_cal_date"></div> 
            <div class="dhx_cal_tab" name="day_tab"></div> 
            <div class="dhx_cal_tab" name="week_tab"></div> 
            <div class="dhx_cal_tab" name="month_tab"></div>
            <div class="dhx_cal_tab" data-tab="con" style="right:280px;"></div>
            <div class="dhx_cal_tab" data-tab="prep" style="right:280px;" ></div>
 
    </div> 
    <div class="dhx_cal_header"></div> 
    <div class="dhx_cal_data"></div> 
    </div> 
    <script>
//https://docs.dhtmlx.com/scheduler/filtering.html

var filter = document.querySelector("#filterWrap");
filter.addEventListener("input", function(){
  scheduler.setCurrentView();
})  
scheduler.filter_month = scheduler.filter_day = scheduler.filter_week = scheduler.filter_con = scheduler.filter_prep = function(id, event) {
  if(filter.value == ""){
    return true;
  }
  if(event.text.toLowerCase().includes(filter.value.toLowerCase()) ){
    return true; 
  }
  if(event.Name && event.Name.toLowerCase().includes(filter.value.toLowerCase()) ){
    return true; 
  }
  return false;
};


 
    function toggleMultiday(element)
    {
      scheduler.config.multi_day = element.checked ;
      scheduler.render();
      //scheduler.updateView(); // this or render work both - whats the difference
       }
    document.getElementById('colorize').addEventListener( "change" , colorize);
    document.getElementById('colorize').addEventListener( "input" , colorize);
    document.getElementById('colorize').addEventListener( "paste" , colorize);

function colorize (e){ //KS
     var text=e.target.value;
     console.log(text);
     var evs = scheduler.getEvents();
     if(!evs[0].oricolor){
       for (var i=0; i<evs.length; i++){
         evs[i].oricolor=evs[i].color;
         console.log("init "+evs[i].color);
       }
     }
     for (var i=0; i<evs.length; i++){
       if(evs[i].oricolor){evs[i].color=evs[i].oricolor;}

       if(text.length>1 && evs[i].text.includes(text)){ // search description
           evs[i].color="blue";
           console.log("colored event: ",evs[i]," ori: ",evs[i].oricolor);
       }
       if(text.length>2 && evs[i].Name && evs[i].Name.includes(text)){ // search names
          evs[i].color="lightblue";
          console.log("colored event: ",evs[i]," ori: ",evs[i].oricolor);
       }
     }
     scheduler.updateView();
    } 

    scheduler.plugins({
            tooltip: true,
            readonly: true,
            all_timed: true,
            quick_info: true

    });       
    scheduler.config.full_day=false;
    scheduler.config.xml_date="%Y-%m-%d %H:%i"; // deprecated but needed for database format
    scheduler.config.first_hour = 0;            // only show from this hour on
    scheduler.config.last_hour = 24;            // last hour 
    scheduler.setLoadMode("day");               // dynamic loading loads only current day if needed
    scheduler.config.details_on_create=true;    // ???
    scheduler.config.details_on_dblclick=true;  
    scheduler.i18n.setLocale("de");             // german
    scheduler.config.default_date="%l, %d %F";  // %l long day eg Montag, 
    //scheduler.config.readonly = true;         // doesnt show lightbox if true so disabled

    scheduler.locale.labels.con_tab = "4-Tage"  // for custom time ranges
    scheduler.locale.labels.prep_tab = "2-Tage"

    scheduler.attachEvent("onTemplatesReady",function(){  // for custom time ranges
            //Con timeslot
            scheduler.date.con_start = function(date){return date;}; //new Date(202,5,16);}; // calculates start-day of range from current day
            scheduler.templates.con_date = scheduler.templates.week_date;
            scheduler.templates.con_scale_date = scheduler.templates.week_scale_date;
            scheduler.date.add_con=function(date,inc){ return scheduler.date.add(date,inc*4,"day"); }//"next" gives you the next 4 days
            scheduler.date.get_con_end=function(date){ return scheduler.date.add(date,4,"day"); }
            
            //preparation phase 2 days
            function setprep(){scheduler.setCurrentView(new Date(2023,4,16));}
            scheduler.date.prep_start = function(date){return date};
            scheduler.templates.prep_date = scheduler.templates.week_date;
            scheduler.templates.prep_scale_date = scheduler.templates.week_scale_date;
            scheduler.date.add_prep=function(date,inc){ return scheduler.date.add(date,inc*1,"day"); }//"next" gives you the next 2 days
            scheduler.date.get_prep_end=function(date){ return scheduler.date.add(date,2,"day"); }
 
    });

    const dayDate = scheduler.date.date_to_str("%D %d %F %Y");
    scheduler.templates.day_date = function (date) {
       return dayDate(date);
    };

    scheduler.config.all_timed = "short"; // night events arent multi-day - events under 24h are shown
    scheduler.config.lightbox.sections=[	
    	{name:"description", height:130, map_to:"text", type:"textarea" , focus:true},
    	{name:"Dienstbeschreibung", height:90, type:"textarea", map_to:"Info" },
    	{name:"Konakt", height:200, type:"textarea", map_to:"Kontakt" },
        {name:"time", height:72, type:"time", map_to:"auto"}
    ];

    scheduler.templates.tooltip_text = function(start,end,event) {
       return "<b>Helfer:</b> <pre>"+event.Name+"</pre><br/></b> ";
    };

    // actual init
    scheduler.init('scheduler_here', new Date(2023,4,18), "con");
    scheduler.load("data/api-helfer.php");
    //https://docs.dhtmlx.com/scheduler/api__scheduler_createdataprocessor.html
    //var dp = scheduler.createDataProcessor("data/api.php");  // this would be for saving 
    //dp.init(scheduler);
    //dp.setTransactionMode("JSON"); // use to transfer data with JSON

    </script> 
    </body> 
</html>
