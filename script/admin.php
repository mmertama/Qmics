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
        fatal ("Connection failed:" . mysqli_connect_error());
    }
    if(!$db->query("CREATE DATABASE IF NOT EXISTS `Qmics`;")){
        fatal ("DB failed:" . mysqli_connect_error());
    }
    if(!$db->select_db("Qmics")){
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

?>