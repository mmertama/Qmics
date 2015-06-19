<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
    <style type="text/css" title="currentStyle">
			@import "../style/qmics.css";
            .src {font: small courier;}
		</style>
		<link rel="Shortcut Icon" type="image/png" href="images/qmics.png" />	
    </head>
	<body id="main">
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

entry.php 
    Entry point to implement PRG pattern 

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

if(!file_exists('configuration.php')){
	require_once('qmics_configure.php');
	configure("No configuration found, please configure", NULL);
	exit();
}

require_once 'utils.php';
require_once 'admin.php';
require_once 'configuration.php';

 if(!session_start()){
     fatal("No session started<BR/>");
 }

$db = openDb();
if(!$db){
	require_once('qmics_configure.php');
	configure("Problems with database, please check settings", NULL);
	exit();
	}
//DB is open
if(!isset($_POST['user']) || strlen($_POST['user']) == 0)
    rejectUser($db, "");

$user = $_POST['user'];


$action = "";
if(isset($_POST['action']))
    $action = $_POST['action'];


if($user == 'admin'){
	if(filemtime('configuration.php') < filemtime('configuration.php.template')){
		require_once('qmics_configure.php');
		configure("Configuration settings updated, please check", "configuration.php");
		exit();
	}
	if(!hasUsers($db)){
        if(!(isset($_POST['pass'])))
            fatal("No password");
        createUserTable($db);
        if(!createNewUser($db, 'admin', $_POST['pass'])){
        	fatal("add admin failed!");
        	}
        warn("admin added\n");
        createComicDb($db);
        createComicUserTable($db);
        createUserLogTable($db);
    }
	ensureComicsDB($db, -1, toBytes(DEFAULT_CACHE_MAX));
}

//USER DB exists
$pw = isset($_POST['pass']) ? $_POST['pass'] : "";
$userId = validateUser($db, $user, $pw);
if(!$userId){
   rejectUser($db, $user);
   }
else{
	$uri = 'location:qmics_db.php?user=' . $user . "&action=" . $action;
	header($uri);
	}
?>
</body>
</html>
