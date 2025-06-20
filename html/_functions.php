<?php

require_once 'konfiguration.php';

function PageHeader ($pagename, $eventname = EVENTNAME){
    $header = <<<HEADER
    <!doctype html>
    <html>
    <head>
      <title>$pagename $eventname </title>
      <link rel="stylesheet" href="css/style_common.css"/>
      <link rel="stylesheet" href="css/style_desktop.css" media="screen and (min-width:781px)"/>
      <link rel="stylesheet" href="css/style_mobile.css" media="screen and (max-width:780px)"/>
      <meta name="viewport" content="width=480" />
    </head>
    <body>
HEADER; //<?vim this bracket is just here for vim syntax highlighting
    return $header;
}
function TableHeader ($pagename, $backlink, $eventname = EVENTNAME){
    $tablehead = <<<TABLEHEAD
    <div style="width: 100%;">
    <table class="commontable">
        <tr>
        <th>
        <a href='$backlink'>
        <button name="BackHelferdaten">
        <b>&larrhk;</b>
        </button> &nbsp;
        </a>
       <b>$pagename $eventname</b>
       </th>
       </tr>
    </table>
TABLEHEAD; // <?vim
    return $tablehead;
}


