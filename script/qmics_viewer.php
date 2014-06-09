<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
	<!--meta name="viewport" content="width=device-width,user-scalable=no"-->
    <link rel="stylesheet" type="text/css" href="../style/viewer.css">
    <script type="text/javascript" src="../javascript/utils.js">
    </script>
    </head>
	<body id="main">
    <div id="left"></div>
    <div id="loading">Loading...</div>
    <div id="popup"></div>
     
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

qmics_viewer.php 
   The viewer 
   

Known issues: 
* None
 
*DESCRIPTION*********************************************************************/ 

require_once 'admin.php';
require_once 'getdata.php';
require_once 'utils.php';
require_once "configuration.php";
require_once 'logutils.php';

 if(!session_start()){
     fatal("No session started<BR/>");
 }

$db = openDb();
if(!$db){
	fatal("data base opening failed");
	}
//DB is open
if(!isset($_GET['user']) || strlen($_GET['user']) == 0)
    rejectUser();

$user = $_GET['user'];


$pw = isset($_GET['pass']) ? $_GET['pass'] : "";
$userId = validateUser($db, $user, $pw);

if(!$userId){
	rejectUser($db, $user);
	}

if(!isset($_GET['action']))
    fatal("no action");

if(!isset($_GET['id']) || !isset($_GET['page']))
    fatal("No document\n");

if(!isset($_GET['pagecount']))
    fatal("No pagecount\n");

$userComicId = $_GET['id'];
$userPageId = $_GET['page'];

$pageCount = $_GET['pagecount'];

addLogEvent($db, "$user", 'read', $userComicId, "");

if($_GET['action'] == 'page'){    
    echo "\t\t\t\t\t<img id=\"page\" src=\"qmics_getimage.php?action=page&user=$user&id=$userComicId&page=$userPageId\">\n";
    echo "<script type=\"text/javascript\">\n";
    echo "var currentPage = $userPageId;\n";
    echo "function loadPage(page, pageIndex){\n";
    echo "\tdismissOnScreenMenu();\n";
    echo "\tnewPageIndex = checkPageIndex(pageIndex, $pageCount);\n";
    echo "\tif(newPageIndex == pageIndex){\n";
    echo "\t\tdocument.getElementById(\"loading\").style.display=\"inline\";\n";
    echo "\t\tpage.src=\"qmics_getimage.php?action=page&user=$user&id=$userComicId&page=\" + newPageIndex;\n";
    echo "\t\tupdateOnScreenMenu(newPageIndex);\n";
    echo "\t}\nreturn newPageIndex;\n}\n";
    echo "function newViewer(offset){\n";
    $requestUrl = "'../script/qmics_getimage.php'";
    echo "\tmakeUintRequest($requestUrl, ['action', 'pagequery', 'user', '$user', 'id', '$userComicId', 'offset', offset], function(array){\n";
    echo "\tif(array.length == 2)";
    echo "\twindow.open(\"qmics_viewer.php?action=page&user=$user&id=\" + array[0] + \"&page=0&pagecount=\" + array[1], \"_self\")\n";
    echo "\telse if(offset < 0) \n\t\tviewInfo(\"First comic reached\");\n";
    echo "\telse if(offset > 0) \n\t\tviewInfo(\"Last comic reached\");\n";
    echo "});";
    echo "}\n\n";
    echo "</script>\n";
}
        ?>
    <div id="right"></div>   
    <script type="text/javascript">
        function viewInfo(text){
            var info = document.getElementById("popup");
            info.innerHTML = text;
            info.style.display = "inline";
            window.setTimeout(function(){info.style.display = "none";}, 5000);
        }
        function viewInfoCancel(){
            var info = document.getElementById("popup");
            info.style.display = "none";
        }
        function checkPageIndex(current, count){
            viewInfoCancel();
            if(current >=  count){
                viewInfo("Last page");    
                return current - 1;
            }
            if(current < 0){
                viewInfo("First page");
                return 0;
            }
        return current;
        }
        function addEvent(element, evnt, funct){
            if (element.attachEvent)
                return element.attachEvent('on'+evnt, funct);
            else
                return element.addEventListener(evnt, funct, false);
        }
       
		var page = document.getElementById("page");
		var left = document.getElementById("left");
		var right = document.getElementById("right");
        
        var timeout = null;
        function dismissOnScreenMenu(){
            if(timeout != null)
                window.clearTimeout(timeout); 
            timeout = null;
            osm.style.display = 'none';
        }
        
        function viewOnScreenMenu(){
            if(osm.style.display == 'inline'){
                dismissOnScreenMenu();
                return;
            }
            osm.style.display = 'inline';
            if(timeout != null)
                window.clearTimeout(timeout); 
            timeout = window.setTimeout(function(){
               dismissOnScreenMenu();
            }, 5000);
        }
        
        function updateOnScreenMenu(pageIndex){
            var range = document.getElementById("pageselector");
            range.value = pageIndex;
            var num = document.getElementById("pageselectornum");
            num.innerHTML = pageIndex + 1;
        }
		
        function addListeners(){
            addEvent(left, 'click', function(evt){   
                currentPage = loadPage(page, currentPage - 1);
                }, false);
            
            addEvent(right, 'click', function(evt){
                currentPage = loadPage(page, currentPage + 1);
                }, false);
        
        
            addEvent(page, 'click', function(evt){
                var x = 0;
                var rect = page.getBoundingClientRect();
                if(evt.pageX)
                    x = evt.pageX - rect.left;
                else
                    x = evt.clientX + document.body.scrollLeft + document.documentElement.scrollLeft - rect.left;
			 var marginal = page.width * 0.2;
			 if(x > page.width - marginal){
				currentPage = loadPage(page, currentPage + 1);
				}
			 else if(x < marginal){
				currentPage = loadPage(page, currentPage - 1);
				}
            else{
                viewOnScreenMenu();
            }
            }, false);
             
            var range = document.getElementById("pageselector");
            var num = document.getElementById("pageselectornum");
            addEvent(range, 'change', function(evt){
                var page = parseInt(range.value) + 1;
                num.innerHTML = page;
            }, false); 
            addEvent(range, 'mousedown', function(evt){
                if(timeout != null)
                    window.clearTimeout(timeout); 
            }, false);
            addEvent(num, 'click', function(evt){
                pageNumber = parseInt(range.value);
                if(pageNumber != currentPage)
                    currentPage = loadPage(page, pageNumber);
                dismissOnScreenMenu();
                }, false);
            
            var goprev = document.getElementById("goprev");
            addEvent(goprev, 'click', function(evt){   
                currentPage = loadPage(page, currentPage - 1);
                }, false);
            
            var gonext = document.getElementById("gonext");
            addEvent(gonext, 'click', function(evt){
                currentPage = loadPage(page, currentPage + 1);
                }, false);
            
            var gonextcomic = document.getElementById("gonextcomic");
            addEvent(gonextcomic, 'click', function(evt){
                newViewer(1);
                }, false);
            var goprevcomic = document.getElementById("goprevcomic");
            addEvent(goprevcomic, 'click', function(evt){
                newViewer(-1);
                }, false);
        }
        
        addEvent(page, 'load', function(){
        	var main = document.getElementById("page");
            left.style.height = main.height + 'px';
            right.style.height = main.height + 'px';
            document.getElementById("loading").style.display="none";
         
        }, false);
		</script>
    <div id="onscreenmenu">
        1 <input type="range" id="pageselector" value="0" min="0" max="<?php echo ($pageCount - 1); ?>"/> <?php echo "$pageCount\n"; ?>
        <div id="pageselectornum" class="onscreenBtn">1</div>
        <div id="goprev" class="onscreenBtn">&lt;</div>
        <div id="gonext" class="onscreenBtn">&gt;</div>
        <div id="goprevcomic" class="onscreenBtn">&lt;&lt;</div>
        <div id="gonextcomic" class="onscreenBtn">&gt;&gt;</div>
        <a class="btnLnk" href="<?php echo "qmics_db.php?action=library&user=$user"; ?>#current_position"><div id="goback" class="onscreenBtn">&#8629</div></a>
        <a class="btnLnk" href="<?php echo "qmics_information.php?action=page&user=$user"; ?>"><div id="goinfo" class="onscreenBtn">i</div></a>
    <script type="text/javascript">
        var osm = document.getElementById("onscreenmenu");
        addListeners();
        
    </script>
    </div>        
	</body>
</html>
