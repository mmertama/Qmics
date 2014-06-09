<!DOCTYPE html>
<html>
	<head>
		<title>Library</title>
		<style type="text/css" title="currentStyle">
			@import "../style/qmics.css";
            .src {font: small courier;}
		</style>
		<link rel="Shortcut Icon" type="image/png" href="images/qmics.png" />	
        <!--META HTTP-EQUIV="Pragma" CONTENT="no-cache">
        <META HTTP-EQUIV="Expires" CONTENT="-1"-->
	<script type="text/javascript" src="../javascript/utils.js">
    </script>
    <script type="text/javascript">
        function viewInfo(id){
            var info = document.getElementById(id);
            info.style.display = 'inline';
        }
        function hideInfo(id){
            var info = document.getElementById(id);
            info.style.display = 'none';
        }
    </script>    
    </head>
	<body id = "main">
		<h1 class = "main_header">Library</h1>
        
		
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

qmics_db.php 
   The library and entry to service 
   

Known issues: 
* Why sometimes "current" comic is has been disappeared - there seems to be a bug somewhere?
 
*DESCRIPTION*********************************************************************/ 


require_once 'utils.php';
require_once 'admin.php';
require_once 'getdata.php';
require_once 'configuration.php';


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

$action = "";
if(isset($_GET['action']))
    $action = $_GET['action'];

if($user == 'admin'){
	if(!hasUsers($db)){
        if(!(isset($_GET['pass'])))
            fatal("No password");
        createUserTable($db);
        addUser($db, 'admin', $_GET['pass']);
        warn("admin added\n");
        createComicDb($db);
        createComicUserTable($db);
        createUserLogTable($db);
    }
}


//USER DB exists
$pw = isset($_GET['pass']) ? $_GET['pass'] : "";
$userId = validateUser($db, $user, $pw);
if(!$userId){
   rejectUser($db, $user);
   }
//User Valid 



switch($action){
   case 'library':
   generateMenu($user);
   $current = generateContinueReading($db, $user, $userId);
   $index = generatePage($db, $user, $userId, $current);
   generateIndex($index);
   break;
}
    


$db->close();

function generateMenu($user){
    echo "<div id=\"topmenu\">\n";
    echo "<span class=\"topmenu_item\" onclick=\"viewInfo('index')\">Index</span>\n";
    if($user != "Guest"){
    echo "<span class=\"topmenu_item\" id=\"settings\">\n";
    echo "<a href=\"qmics_admin_db.php?action=view&user=$user\">Settings</a>\n";
    echo "</span>\n";     
    }   
    echo "<span class=\"topmenu_item\" id=\"log_out\">\n";
    echo "<a href=\"qmics_admin_db.php?action=logout&user=" . $user . "\">Log out</a>\n";
    echo "</span>\n"; 
    echo "</div>\n";
    echo "<div class=\"clear\"></div>";
}


function generateIndex($index){
    echo "<div id=\"index\">\n";
    echo "<h2 class = \"main_header\">Index <span class=\"click_item\" onclick=\"hideInfo('index')\">Hide</span></h2>\n";
    echo "<div class=\"scrollbox\">\n";
    foreach($index as $item){
        echo "<div class=\"" . $item['type'] . "_index\">";
        echo "<a href=\"#" . $item['name'] . "\">" . $item['title'] . "</a>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
    echo "</div>\n";
}
		
function generatePage($db, $user, $userId, $currentId){
     $getComics = "SELECT
        `comics`.`comicname`,
        `comics`.`comicid`,
        `comics`.`pagecount`,
        `comics`.`coverimage`,
        `comics`.`sourceurl`,
        `comics`.`width`,
        `comics`.`height`,
        `comicsuser`.`usercomicid`,
        `comicsuser`.`access`
    FROM `comics`
    JOIN `comicsuser` ON `comics`.`comicid` = `comicsuser`.`uniqcomicid`
    WHERE `comicsuser`.`userid` = '$userId' AND `comicsuser`.`access` & ".READ_OK . "
    ORDER BY `sourceurl`";
   
    $comicQuery = $db->query($getComics);
    fatalIfFalse($comicQuery, $db->error);
    
    $index = array();
    
    $publisherTitle = "";
    $seriesTitle = "";
    
    echo "<div id=\"body\">";
    
    while($comicData = $comicQuery->fetch_array(MYSQLI_ASSOC)){
    
        $userComicId = $comicData['usercomicid'];
            $titlename = pathinfo($comicData['comicname'], PATHINFO_FILENAME);
           
            $pHeader = getHeader($comicData['sourceurl'], "publisher");
            $tHeader = getHeader($comicData['sourceurl'], "title");
            
            if($tHeader != $seriesTitle && strlen($seriesTitle) > 0){
                echo "\t\t</div>\n";   
            }
            
             if($pHeader != $publisherTitle && strlen($publisherTitle) > 0){
                echo "\t\t</div>\n";   
            }
            
            if($pHeader != $publisherTitle){
                $publisherTitle = $pHeader;
                $pt = replaceWithValidChars($pHeader, true);
                $cliid = $pt . "_cli_pub";
                $pubid = $pt . "_pub";
                $item = array('type' => 'publisher', 'name' => $pubid, 'title' => $publisherTitle);
                $index[] = $item;
                echo "\t\t<a name=\"$pubid\"></a>\n";
                echo "<h2 class=\"publisher\">$publisherTitle  <small id=\"$cliid\" class=\"click_item\" onclick=\"toggleView('$cliid', '$pubid')\">Hide</small></h2><div class=\"pub_head\" id=\"$pubid\">\n";
            }
            
            if($tHeader != $seriesTitle){
                $seriesTitle = $tHeader;
                $st = replaceWithValidChars($tHeader, true);
                $cliid = $st . "_cli_tit";
                $titid = $st . "_tit";
                $item = array('type' => 'title', 'name' => $titid, 'title' => $seriesTitle);
                $index[] = $item;
                echo "\t\t<a name=\"$titid\"></a>\n";
                echo "\t\t<h3 class=\"seriestitle\">$seriesTitle  <small id=\"$cliid\" class=\"click_item\" onclick=\"toggleView('$cliid', '$titid')\">Hide</small></h3><div class=\"tit_head\" id=\"$titid\">\n";
            }
            
            $pageCount = $comicData['pagecount'];
            $coverwidth = $comicData['width'];
            $coverheight = $comicData['height'];
            

        $isDownload = isAccess($comicData['access'], 'download');
        $hilight = $currentId == $comicData['comicid'];     
        echoLibItem($titlename, $user, $userComicId, $isDownload, 0, $pageCount, $coverwidth, $coverheight, $hilight);
            

    }
    
     if(strlen("$seriesTitle") > 0){
         echo "\t\t</div>\n";
     }
     if(strlen("$publisherTitle") > 0){
         echo "\t\t</div>\n";
     }
    $comicQuery->close();
    echo "</div>";
    return $index;
    
}

function echoLibItem($titlename, $user, $userComicId, $isDownload, $page, $pageCount, $coverwidth, $coverheight, $hilight){
    echo("\t\t<div class=\"lib_item\">\n");
    if($hilight){
        echo("\t\t<a name=\"current_position\"></a>\n");
        echo("\t\t\t<figure id=\"current_position\">\n");
    }
    else{
        echo("\t\t\t<figure>\n");
    }
    echo("\t\t\t\t<a href=\"qmics_viewer.php?action=page&user=$user&id=$userComicId&page=$page&pagecount=$pageCount\">\n");
    echo("\t\t\t\t\t<img class=\"bookcover\" src=\"qmics_getimage.php?action=image&user=$user&id=$userComicId\" height=\"$coverheight\" width=\"$coverwidth\">\n");
    echo("\t\t\t\t</a>\n");
    echo("\t\t\t\t\t<figcaption>\n");
    if($isDownload)
        echo("\t\t\t\t\t<a href=\"qmics_getimage.php?action=open&user=$user&id=$userComicId\" class=\"item_download\">$titlename</a>\n" );
    else
        echo("\t\t\t\t\t<div class=\"item_readonly\">$titlename</div>\n");     
    echo("\t\t\t\t\t</figcaption>\n");
    echo("\t\t\t\t\t\t<div class=\"pages\">$pageCount pages.</div>\n");
    echo("\t\t\t</figure>\n");
    echo("\t\t</div>\n\n");
}

function createUserTable($db){
  $db->query("DROP TABLE IF EXISTS `userdata`");
  $res = $db->query("CREATE TABLE `userdata`(
  `username` CHAR(255),
  `pass` CHAR(255),
  `userid` INT NOT NULL AUTO_INCREMENT,
  `currentcomicid` INT NOT NULL,
  `currentcomicpage` INT NOT NULL,
   PRIMARY KEY(`userid`)
  )");
  
  if($res == FALSE){
  	fatal("Table \'userdata\' creation failed:" . $db->error);
  	}
  return TRUE;
}



function generateContinueReading($db, $user, $userId){
    $current = getUserPage($db, $userId);
    
    if(!$current)
        return 0; 
    
    $uniqComicId = $current['uniqcomicid'];
    
    $q = "SELECT `comicname`, `pagecount`, `width`,`height` FROM `comics` WHERE `comicid` = '$uniqComicId'";
    $comicQuery = $db->query($q);
    fatalIfFalse($comicQuery, $db->error);
    $data = $comicQuery->fetch_array(MYSQLI_ASSOC);
    
    $titlename = pathinfo($data['comicname'], PATHINFO_FILENAME);
    echo "<h3> Continue reading \"$titlename\"</h3>\n";
    echoLibItem($titlename, $user, $current['currentcomicid'], false, $current['currentcomicpage'], $data['pagecount'], $data['width'], $data['height'], false);
    $comicQuery->close();
    return $uniqComicId;
    
}



?>
	</body>
</html>
