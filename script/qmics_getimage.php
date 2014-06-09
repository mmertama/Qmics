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

qmics_getimage.php 
   Accessor to server data 
   

Known issues: 
*This file name is not descriptive...
 
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
    rejectUser();
$user = $_GET['user'];
$userId = validateUser($db, $user, "");
if(!$userId){
    rejectUser($db, $user);
	}

if(!isset($_GET['action']))
    fatal("no action");
 

if(!isset($_GET['id']))
    fatal("no id");

if($_GET['action'] == 'image'){
    getImage($db, $userId, $_GET['id']);
}

if($_GET['action'] == 'open'){
    getContent($db, $userId, $_GET['id']);
}

if($_GET['action'] == 'page'){
    if(!isset($_GET['page']))
        fatal("no page");
    getPage($db, $userId, $_GET['id'], $_GET['page']);
}

if($_GET['action'] == 'pagequery'){
    if(!isset($_GET['offset']))
        fatal("no offset");
    getPageQuery($db, $userId, $_GET['id'], $_GET['offset']);
}

if($_GET['action'] == 'access' && $user == "admin"){
    getAccess($db, $_GET['id']);
}
