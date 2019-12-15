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

admin.php 
* implements user authentication and initialization. It is mandatory that these functions are called correctly. When server requests a page its access has to be authenticated. If it is already authenticated, there must be a session where hash is stored. 

Basically the sequence is 

open session 
openDb
validateUser
call rejectUser if false
otherwise continue script

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

require_once "utils.php";
require_once "configuration.php";
require_once "logutils.php";

function rejectUser($db, $user){
	debug_log("invalid login: $user");
    addLogEvent($db, "$user", 'login-fail', 0, $_SERVER["REMOTE_ADDR"]);
    $db->close();
    session_destroy();
    header("Location:logout.php");
    die();
}

function validateUser($db, $user, $pass){
    $userId = FALSE;
    $selectUser = $db->query("SELECT `pass`, `userid` FROM `userdata` WHERE `username`='$user'");
    fatalIfFalse($selectUser, $db->error);
    $data = $selectUser->fetch_array(MYSQLI_ASSOC);
    if(count($data) > 0){   
        if((count($data) == 0 || count($data['pass']) == 0) && $user === 'admin'){
            $r = count($data); 
            $db->query("DROP TABLE `userdata`");
            $db->close();
            fatal($r . " DB integrity error...new needed");
        }
        if(strlen($pass) > 0){
            if(password_verify($pass, $data['pass'])){
                $userId = $data['userid'];
                $_SESSION['pass'] = $data['pass'];
            }
        }
        else{
            if(isset($_SESSION['pass'])){
                if($_SESSION['pass'] === $data['pass']){
                    $userId = $data['userid'];
              }
            }
        }
    }
     
    if($userId != FALSE && strlen($pass) > 0)
        addLogEvent($db, "$user", 'login', $userId, $_SERVER["REMOTE_ADDR"]);
    $selectUser->close();
    return $userId;
}

function createNewUser($db, $user, $password){
    
	$exitTest = $db->query("SELECT `userid` FROM `userdata` WHERE `username`='$user'");
	fatalIfFalse($exitTest, $db->error);
    
    $result = $exitTest->fetch_array(MYSQL_ASSOC);
    
    if($result != FALSE || count($result) > 0){ 
		echo("user already exists<BR>");
        return FALSE;
		} 
    
	$exitTest->close();
    
    if(strlen($password) < MIN_PASSWORDLEN){
        warn("Invalid password, password not changed");
        return FALSE;
    }
	
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $q1 = "INSERT INTO userdata (`username`,`pass`) VALUES ('$user', '$hash')";
    $res = $db->query($q1);
    fatalIfFalse($res, "Cannot add user " . $db->error);
    echo "user added</br>";
	return TRUE;
}




/*
function ensureAdminExists(){
    $usersSelect = $db->query("SELECT `username` FROM `userdata`");
	if($usersSelect == FALSE){
        if(!(isset($_GET['pass'])))
            fatal("Error 1002");
        if(isset($_GET['action']) && $_GET['action'] == 'new_admin'){
            createUserTable($db);
            addUser($db, 'admin', $_GET['pass']);
            echo "admin added\n";
            createUserLogTable($db);
        }else
            fatal("Bad error:" . $db->error);
	}
    else
	   $usersSelect->close();}
*/

function openDb(){
    $db = mysqli_connect(DB_ADDRESS, DB_USER, DB_PASSWD);
	if (mysqli_connect_errno()) {
        warn ("Connection failed:" . mysqli_connect_error());
		$db = FALSE;
    }
    if($db && !$db->query("CREATE DATABASE IF NOT EXISTS `Qmics`;")){
        fatal ("DB failed:" . mysqli_connect_error());
    }
    if($db && !$db->select_db("Qmics")){
        fatal ("DB failed:" . $db->error);
    } 
    return $db;
}

function createComicUserTable($db){
    $res = $db->query("SELECT `usercomicid`, `uniqcomicid`, `userid` `access` FROM `comicsuser`");
    if($res != FALSE){
        $res->close();
        return;
    }
    $db->query("DROP TABLE IF EXISTS `comicsuser`");
    $res = $db->query("
    CREATE TABLE `comicsuser`(
    `usercomicid` INT NOT NULL,
    `uniqcomicid` INT NOT NULL,
    `userid` INT NOT NULL,
    `access` INT NOT NULL,
    `PID` INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(`PID`)
    )");
  	fatalIfFalse($res, "Table \'comicsuser\' creation failed:" . $db->error);
    echo "user linking table created<br/>\n";
}

function createComicDb($db){
    $res = $db->query("SELECT `comicname`, `pagecount`, `coverimage`,`width`,`height`,`comicid`,`sourceurl` FROM `comics`");
    if($res != FALSE){
        $res->close();
        return;
    }
    $db->query("DROP TABLE IF EXISTS `comics`");
    $res = $db->query("
    CREATE TABLE `comics`(
    `comicname` CHAR(255) NOT NULL ,
    `pagecount` INT NOT NULL,
    `coverimage` TEXT  NOT NULL,
    `width` INT NOT NULL,
    `height` INT  NOT NULL,
    `comicid` INT NOT NULL AUTO_INCREMENT,
    `sourceurl` TEXT NOT NULL,
    PRIMARY KEY(`comicid`)
    )");
    fatalIfFalse($res, "Table \'comics\' creation failed:" . $db->error);
    echo "comics table created<br/>\n";
}


function getCacheSize($db){
	$res = $db->query("SELECT `cachesize` FROM `comicsDB`");
	fatalIfFalse($res, "Cache size failed:" . $db->error);
	$data = $res->fetch_array(MYSQLI_ASSOC);
    $size = $data['cachesize'];
	$res->close();
	return $size < 0 ? $size : "Unknown";
}


function getMaxCacheSize($db){
	$res = $db->query("SELECT `maxcachesize` FROM `comicsDB`");
	fatalIfFalse($res, "Maxcache size failed:" . $db->error);
	$data = $res->fetch_array(MYSQLI_ASSOC);
	$size = $data['maxcachesize'];
	$res->close();
	return $size;
}

function setCacheMaxSize($db, $maxSz){
	$query = "UPDATE `comicsDB` SET `maxcachesize` = '$maxSz'";
	$res = query($query);
	fatalIfFalse("Cache size failed:" . $db->error);
	$currentSize = getCacheSize($db);
	if($currentSize > $maxSz){
		$sz = setFolderSize(CURRENT_CACHE, $maxSz * 0.9);
		echo "Cache size set to $sz<br/>\n";
		$res = $db->query("UPDATE `comicsDB` SET `cachesize` = '$sz'");
	}
	fatalIfFalse($res, "Cache size set failed:" . $db->error);
    echo "Cache size max set to $maxSz<br/>\n";
}

function ensureComicsDB($db, $mb, $maxSz){
	$res = $db->query("SELECT `maxcachesize` FROM `comicsDB`");
	if($res == FALSE){
		$db->query("DROP TABLE IF EXISTS `comicsDB`");
		$res = $db->query("
			CREATE TABLE `comicsDB`(
			`cachesize` BIGINT NOT NULL,
			`maxcachesize` BIGINT NOT NULL
			)");
		fatalIfFalse($res, "Table \'comicsDB\' creation failed:" . $db->error);
		$query = "INSERT INTO `comicsDB` (`cachesize`, `maxcachesize`) VALUES ('$mb', '$maxSz')";
		$res = $db->query($query);
		fatalIfFalse($res, "comicDB update error ". $db->error);
	}
	else{
	$res->close();
	}
}

function calcCacheSize($db){
	$size = calcFolderSize(CURRENT_CACHE);
	$query = "UPDATE `comicsDB` SET `cachesize` = '$size'";
	$res = $db->query($query);
	fatalIfFalse($res, $db->error);	
	echo "comics cache size is $size bytes <br/>\n";
	}

?>