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

logutils.php 
   Collects user data...for big brother
   

Known issues: 
* None
 
*DESCRIPTION*********************************************************************/ 

require_once "configuration.php";
require_once "utils.php";

function addLogEvent($db, $user, $action, $data, $info){
    $q = "INSERT INTO `userlog` (`user`, `time`, `action`, `data`, `info`) VALUES ('$user',NOW(),'$action','$data', '$info')";
    $res = $db->query($q);
    fatalIfFalse($res, $db->error);
}

function createUserLogTable($db){
    $res = $db->query("SELECT `userid`, `time`, `data`, `action` FROM `userlog`");
    if($res != FALSE){
        $res->close();
        return true;
    }
    $db->query("DROP TABLE IF EXISTS `userlog`");
    $res = $db->query("
    CREATE TABLE `userlog`(
    `user` CHAR(255) NOT NULL,
    `time` TIMESTAMP NOT NULL,
    `action` CHAR(63) NULL,
    `data` INT NOT NULL,
    `info` TEXT,
    `PID` INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(`PID`)
    )");
  	fatalIfFalse($res, "Table \'userlog\' creation failed:" . $db->error);
    
    $users = $db->query("SELECT `username`, `userid` FROM `userdata`");
    if($res == false)
        return;
    while($userData = $users->fetch_array(MYSQLI_ASSOC)){
        addLogEvent($db, "admin", 'create', $userData['userid'], $userData['username']);
    }
    $users->close();
    
    return false;
}

?>