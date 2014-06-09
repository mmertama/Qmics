<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
	<!--meta name="viewport" content="width=device-width,user-scalable=no"-->
    <link rel="stylesheet" type="text/css" href="../style/settings.css">
	</head>
<body>


<?php

/*LICENSE*************************************************************************

    Qmics comics reader
    
    Copyright (C) 2014 Markus Mertama

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
  
*LICENSE*************************************************************************/
/*DESCRIPTION********************************************************************

qmics_information.php 
   The information viewer 
   

Known issues: 
* Quite empty and dull
 
*DESCRIPTION*********************************************************************/ 

require_once 'admin.php';
require_once 'getdata.php';
require_once 'utils.php';
require_once "configuration.php";


 if(!session_start()){
     fatal("No session started<BR/>");
 }

$db = openDb();
if(!$db){
	fatal("data base opening failed");
	}
//DB is open
if(!isset($_GET['user']) || strlen($_GET['user']) == 0)
    rejectUser($db, "");

$user = $_GET['user'];



$pw = isset($_GET['pass']) ? $_GET['pass'] : "";



$userId = validateUser($db, $user, $pw);



if(!$userId){
	rejectUser($db, $user);
	}

if(!isset($_GET['action']))
    fatal("no action");


echo "<h2>Hi $user!</h2>";



?>

<div><img src="../images/instructions.png"/>    
</div>

<?php    
$current = FALSE;

if($_GET['action'] == "page")
    $current = getUserPage($db, $userId);

if($current){
    $uniqComicId = $current['uniqcomicid'];
    $comicQuery = $db->query("SELECT `pagecount` FROM `comics` WHERE `comicid` = '$uniqComicId'");
    fatalIfFalse($comicQuery, $db->error);
    $data = $comicQuery->fetch_array(MYSQLI_ASSOC);

    $userComicId = $current['currentcomicid'];
    $userPageId = $current['currentcomicpage'];
    $pageCount = $data['pagecount'];


    echo "<div id=\"goback\">\n";
    echo "<a href=\"qmics_viewer.php?action=page&user=$user&id=$userComicId&page=$userPageId&pagecount=$pageCount\">Back</a>\n";
    echo "</div>\n";
}
    ?>
</body>
</html>