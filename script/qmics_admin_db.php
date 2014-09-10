<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
	<!--meta name="viewport" content="width=device-width,user-scalable=no"-->
    <link rel="stylesheet" type="text/css" href="../style/settings.css">
	</head>
<body>
<h2>Settings</h2>
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

qmics_admin_db.php 
   The settings page, both admin and normal user views
   
Known issues: 
* None
 
*DESCRIPTION*********************************************************************/ 

require_once "admin.php";
require_once "utils.php";
require_once "configuration.php";
require_once 'deflate.php';
require_once 'resize.php';
require_once 'fixzip.php';
require_once 'admintable.php';
require_once 'logutils.php';

 if(!session_start()){
     fatal("No session started<BR/>");
 }

$db = openDb();
if(!$db){
	fatal("data base opening failed");
	}


$user = "";
if(isset($_POST['user']))
    $user = $_POST['user'];
elseif(isset($_GET['user']))
    $user = $_GET['user'];
else{
    rejectUser($db, $user);
}

$pw = isset($_POST['pass']) ? $_POST['pass'] : "";
$userId = validateUser($db, $user, $pw);

$adminId = validateUser($db, "admin", $pw);

if(!$userId){
	rejectUser($db, $user);
	$db->close();
	}

if(isset($_GET['action']) && $_GET['action'] == 'logout'){
	session_destroy();
	echo "<script type=\"text/javascript\">location.href = '" . LOGOUT_PAGE . "';</script>";
	exit();
	}


if($adminId){
    if(isset($_POST['action']) && $_POST['action'] == 'update'){
        doAdminUpdate($db, $_POST['target'], $_POST['values']);
        }
    elseif(isset($_POST['action']) && $_POST['action'] == 'adduser'){
        addUser($db, $_POST['nuser'], $_POST['npass']);
        }
    elseif(isset($_POST['action']) && $_POST['action'] == 'modifyuser'){
        modifyUser($db, $_POST['nuser'], $_POST['npass']);
        }
    elseif(isset($_POST['action']) && $_POST['action'] == 'deleteuser'){
        modifyUser($db, $_POST['nuser']);
        }
    elseif(isset($_POST['action']) && $_POST['action'] == 'gencomicsdb'){
        regenComics($db);
    }
    elseif(isset($_POST['action']) && $_POST['action'] == 'clearcaches'){
        clearCaches($db);
    }
    elseif(isset($_POST['action']) && $_POST['action'] == 'recoverdata'){
        $data = preg_split('/\|/', $_POST['recoveries']);
        recover($data);
    }
    else{
        $preset = isset($_GET['target']) ? $_GET['target'] : "";
        generateAdminSettingPage($db, $preset);
        }
}else{
    if(isset($_POST['action']) && $_POST['action'] == 'modifyuser'){
        modifyUser($db, $user, $_POST['npass']);
        }
    elseif(isset($_POST['action']) && $_POST['action'] == 'deleteuser'){
        modifyUser($db, $user);
        }
    else{
        generateUserSettingPage($db, $user);
        }
}


echo "<div id=\"back\"><a href=\"";
if((isset($_POST['action']) && $_POST['action'] != 'view') || 
   (isset($_GET['action']) && $_GET['action'] != 'view')){
    echo "qmics_admin_db.php?action=view&user=$user";
    if(isset($_POST['target'])){
        echo "&target=" . $_POST['target'];
    }
    }
else{
    echo "qmics_db.php?action=library&user=$user";
	}
echo "\">Back</a></div>";


function positionOf($uid, $array){
    for($ii = 0; $ii < count($array); $ii += 2){
        if($array[$ii] == $uid)
            return $ii;
    }
    return -1;
}
   
function addUser($db, $user, $password){
	if(createNewUser($db, $user, $password))
		createLinks($db);
	}   
   
function doAdminUpdate($db, $userId, $values){
    $uu = unpack("C*", base64_decode($values));
    $update = array();
    for($ii = 1; $ii <= count($uu); $ii += 4){
        $update[] = ($uu[$ii] << 24) | ($uu[$ii + 1] << 16) | ($uu[$ii + 2] << 8) | $uu[$ii + 3];
    }
    
    
  
    
    $cus = "SELECT `access`, `uniqcomicid` FROM `comicsuser` WHERE `userid` = '$userId'";
    $comicUserResult = $db->query($cus);
    fatalIfFalse($comicUserResult, $db->error);
    $changes = array();
    $cc = 0;
    
    while($userData = $comicUserResult->fetch_array(MYSQLI_ASSOC)){
        $access = accessFromBytes($userData['access']);
        $comicId = $userData['uniqcomicid'];
        
        $pos = positionOf($comicId, $update);
        if($pos < 0){
            warn("Comic ($comicId) not found");
            continue;
        }
        $newAccess = accessFromBytes($update[$pos + 1]);
        $keys = array_keys($access);
        foreach($keys as $key){ 
            $access[$key] = $newAccess[$key];
                
        }
        $accessBytes = accessToBytes($access);
        if($accessBytes != $userData['access']){
            $change = array('uniqcomicid'=>$userData['uniqcomicid'],
                            'userid' => $userId,
                            'access' => $accessBytes);
            $changes[] =$change;  
        }    
    }
    foreach($changes as $change){
        $q = "UPDATE `comicsuser` SET `access`='". $change['access'] ."' WHERE `userid` ='" . $change['userid'] . "' AND `uniqcomicid` = '" . $change['uniqcomicid'] . "'";
        $res = $db->query($q);
        fatalIfFalse($res, $db->error);
    }
    echo(count($changes) . " changes updated.<br/>\n");
    $comicUserResult->close();
}

function generateUserSettingPage($db, $user){
    $password = array("Password", "npass");
    $hidden = array($user, "user");
    addForm($user, "Change password", $password, "modifyuser", $hidden);
    addForm($user, "Delete account", NULL, "deleteuser", $hidden);
}

function generateAdminSettingPage($db, $preset){
    generateAdminTable($db, $preset);
    $hidden = array("admin", "user");
    $addUser = array("Username", "nuser", "Password", "npass");
    $deleteUser = array("Username", "nuser");
    addForm("admin", "Add user", $addUser, "adduser", $hidden);
    addForm("admin", "Change password", $addUser, "modifyuser", $hidden);
    addForm("admin", "Delete user", $deleteUser, "deleteuser", $hidden);
    addForm("admin", "Generate Comics DB", NULL, "gencomicsdb", $hidden);
    addForm("admin", "Clear Caches", NULL, "clearcaches", $hidden);
}

function addForm($user, $title, $fields, $action, $hidden){
    echo "<div class=\"settingform\">\n";
    echo "<h3>$title</h3>\n";
    echo "<form action=\"qmics_admin_db.php\" method=\"post\">\n";
    echo "<input hidden name = \"action\" value=\"$action\">\n";
    echo("<input type=\"hidden\" name=\"user\" value=\"$user\">\n");
    if(!is_null($hidden)){
        for($i = 0; $i < count($hidden); $i +=2){
            echo "<input type=\"hidden\" name=\"" . $hidden[$i + 1] . "\" value=\"" . $hidden[$i] . "\" />\n";
        }
    }
    if(!is_null($fields)){
        for($i = 0; $i < count($fields); $i +=2){
            echo $fields[$i] . ":";
            if($fields[$i + 1] != null)
                echo "<input type=\"text\" name=\"" . $fields[$i + 1] . "\" />\n";  
        }
    }
    echo "<input type=\"submit\" value=\"$title\" />\n";
    echo "</form>\n</div>\n";
}



function clearCaches($db){
    erase(COVER_CACHE);
    erase(CURRENT_CACHE);
    regenComics($db);
    }



function modifyUser($db, $user, $password = -1){
	$exitTest = $db->query("SELECT `userid` FROM `userdata` WHERE `username`='$user'");
	fatalIfFalse($exitTest,  $db->error);
	$result = $exitTest->fetch_array(MYSQL_ASSOC);
    if(count($result) <= 0){ 
		echo ("user not found");
        return FALSE;
		} 
    $id = $result['userid'];
	$exitTest->close();
    
	if($password == -1){
        if($user == 'admin'){
            echo("Please dont delete admin");
            return FALSE;
        }
        $res = $db->query("DELETE FROM `userdata` WHERE `userid`=$id");
        fatalIfFalse($res, "user not deleted ". $db->error);
        
        $res = $db->query("DELETE FROM `comicsuser` WHERE `userid`=$id");
        fatalIfFalse($res, "user not cleaned ". $db->error);
        
        echo "user deleted</br>";
    }
	else{
        if(strlen($password) < MIN_PASSWORDLEN){
            warn("Invalid password, password not changed");
            return;
            }
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$res = $db->query("UPDATE `userdata` SET `pass`='$hash' WHERE `userid`=$id");
        fatalIfFalse($res, "user not updated ". $db->error);
        echo "user password changed</br>";
    }
    return TRUE;
}

function regenComics($db){
    echo("<input type=\"hidden\" name=\"action\" value=\"view\">");
    createComicDb($db);
    $recoveries = generateComicDb($db);
    if(count($recoveries) > 0){
        addLastLine($recoveries);
    }
    else{
        createComicUserTable($db);         
        createLinks($db);
    }
}

function DeleteOrphanRecords($db){
    $count = 0;
    $comicsQuery = $db->query("SELECT `sourceurl`,`comicid` FROM `comics`");
    fatalIfFalse($comicsQuery, $db->error);
    while($data = $comicsQuery->fetch_array(MYSQLI_ASSOC)){
        $sourceUrl = longPath($data['sourceurl']);
        if(!file_exists($sourceUrl)){
            $id = $data['comicid'];
            $e = $db->query("DELETE FROM `comics` WHERE `comicid`='$id'");
            warnIfFalse($e, $db->error);
            $e = $db->query("DELETE FROM `comicsuser` WHERE `uniqcomicid`='$id'");
            warnIfFalse($e, $db->error);
            $count++;
        }
    }
    $comicsQuery->close();
    return $count;
}


function generateComicDb($db){
    
    $deleted = DeleteOrphanRecords($db);
    $filenames = array();
   
    $res = getFileNames($filenames, LIBRARY, "/\.(cbr|rar|cbz|zip)$/i");
    fatalIfFalse($res, utilError());
    natcasesort($filenames);

    $additions = 0;
    
    $recoveries = array();

    
    $db->autocommit(FALSE);

    foreach($filenames as $filename){
        
        $sourceUrl = $db->real_escape_string(shortPath($filename));
        $comicsQuery = $db->query("SELECT `comicid` FROM `comics` WHERE `sourceurl` = '$sourceUrl'");
        if($comicsQuery != false && $comicsQuery->num_rows > 0){
            $comicsQuery->close();
            continue;
        }
        $additions++;
        
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['basename'];
        $archivepath = replaceWithValidChars($pathInfo['filename'], true);
        
        $data = uncompress($filename, COVER_CACHE . "/" . $archivepath , 0);
        
        if(!$data){
            if(compressError() == "invalidfilename"){
                $recoveries[] = "invalidfilename";
                $recoveries[] = $filename;
                $r = array();
                $r[] =  "invalidfilename";
                $r[] = $filename;
                $hidden = toRecoveryData($r);
                $info = array("Broken file: \"$filename\"", null);
                addForm("admin", "Fix it", $info, "recoverdata", $hidden);
                continue;
            }
            $newname = "";
            $info = "";
            $type = archiveType($filename);
            if(compressError() == "badfiletype"){
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $newname = substr($filename, 0, -strlen($ext)) . $type;
                $info = array("File: \"$filename\" is $type", null);
            }
            if(compressError() == "badfilename"){
                $newname = replaceWithValidChars($filename, false);
                $info = array("File: \"$filename\" has invalid characters", null);
            }
            if(compressError() == "badfiletype" || compressError() == "badfilename"){
                
                if($type != ''){
                    $recoveries[] = "renamefile";
                    $recoveries[] = $filename;
                    $recoveries[] = $newname;
                    $r = array();
                    $r[] = "renamefile";
                    $r[] = $filename;
                    $r[] = $newname;
                    $hidden = toRecoveryData($r);
                    addForm("admin", "Fix it", $info, "recoverdata", $hidden);
                    continue;
                }
            }
            warnIfFalse($data, "compress error:" . compressError(), function() use ($recoveries, $db){
                if(WARNINGS_FATAL){
                    addLastLine($recoveries);
                    $db->rollback();
                }
            });
            continue;
        }
 
       
        
        $pages = $data['pages'];
        $coverimage = $data['fullname'];
        
        $size = resize($coverimage, COVER_WIDTH, COVER_HEIGHT);
        $size = warnIfFalse($size, "resize error:" . resizeError(), function() use ($recoveries, $db){
            if(WARNINGS_FATAL){
                    addLastLine($recoveries);
                    $db->rollback();
                }
            });
         if(!$size)
            continue;
        
        
        $name = $db->real_escape_string($name);
        $coverimage = $db->real_escape_string(shortPath($coverimage));
        
        //echo "'$name', '$filename','$coverimage'<br>\n";
        
        
        $width = $size['width'];
        $height = $size['height'];
  		$q = "INSERT INTO `comics` (`comicname`, `pagecount`, `sourceurl`, `coverimage`, `width`, `height`) VALUES ('$name','$pages','$sourceUrl','$coverimage', '$width', '$height')
        ";
        $res = $db->query($q);
        fatalIfFalse($res, $db->error, function() use ($db) {
                $db->rollback();});
        }
    $db->commit();
    $db->autocommit(TRUE);
    
    echo("comics database regenerated - $additions titles added, $deleted deleted. ".count($filenames)." titles.<br/>\n");
    return $recoveries;
}


function toRecoveryData($array){
    $recoveryData = join("|", $array);
    $hidden = array("admin", "user", $recoveryData, "recoveries");
    return $hidden;
}

function addLastLine($recoveries){
    $hidden = toRecoveryData($recoveries);
    $info = array("Several errors", null);
    addForm("admin", "Fix them all", $info, "recoverdata", $hidden);
    
}

function createLinks($db){
    $db->autocommit(FALSE);
    
    $userIds = array();
    $userDataResult = $db->query("SELECT `userid` from `userdata`");
    fatalIfFalse($userDataResult, $db->error);
    while($userData = $userDataResult->fetch_array(MYSQLI_ASSOC)){
        $userIds[] = $userData['userid'];
    }
    $userDataResult->close();
    
    
    $idsStart = rand();
    if($idsStart < 1000)
        $idsStart += 1000;
    $idsStart &= 0xFFF;

  
    $modified = 0;
    $created = 0;
    
   /* echo "Updating comics";
    ob_flush();
    flush();
    $count = 0;
    addProgress($count);
    $count = 101;
    addProgress($count);
    addProgress($count);
    */
    $userCount = 0;
    $accessFalseAll = accessToBytes(['read' => false, 'download' => false]);
    
    
    $select = $db->prepare("SELECT `PID` FROM `comicsuser` WHERE `userid` = ? AND `uniqcomicid` = ? LIMIT 1");
    fatalIfFalse($select, $db->error);
    $update = $db->prepare("UPDATE `comicsuser` SET `usercomicid`= ? WHERE `PID` = ?");
    fatalIfFalse($update, $db->error);
    $insert = $db->prepare("INSERT INTO `comicsuser` (`userid`, `usercomicid`, `uniqcomicid`, `access`) VALUES (?,?,?,$accessFalseAll)");
    fatalIfFalse($insert, $db->error);
    
    
    foreach($userIds as $userid){
    
        
        $comicsResult = $db->query("SELECT `comicid` from `comics` ORDER BY `sourceurl`");
        fatalIfFalse($comicsResult, $db->error);
        
        $userCount++;
        debug_log("$userCount : $idsStart");
        while($row = $comicsResult->fetch_array(MYSQLI_ASSOC)){
     
            $comicid = $row['comicid'];
            $select->bind_param("ii", $userid, $comicid);
            $selErr = $select->execute();
            fatalIfFalse($selErr, $db->error, function() use ($db) {
                $db->rollback();});
            $res = $select->get_result();
            

            if($res != FALSE && $res->num_rows > 0){
                $data = $res->fetch_array(MYSQLI_ASSOC);
                $pid = $data['PID'];
                $res->close();

                $update->bind_param("ii", $idsStart, /*$userid, $comicid*/ $pid);
                $res = $update->execute();
                $modified++;
            }
            else{
                $insert->bind_param("iii", $userid, $idsStart, $comicid);
                $res = $insert->execute();
                
                $created++;
            }
            $idsStart++;
         
            fatalIfFalse($res, $db->error, function() use ($db) {
                $db->rollback();});
        } 
        debug_log("$userCount : $idsStart");
        $comicsResult->close();
    }
    
    
    $db->commit();
    $db->autocommit(TRUE);
    
debug_log("UPDATE DONE");
    
    echo "$modified records updated, $created records created<br/>\n";
}


function recover($commands){
    $index = 0;
    $count = count($commands);
    $fixCount = 0;
    while($index < $count){
        if($commands[$index] === "invalidfilename"){
            fixfile($commands[$index + 1]);
            $index += 2;
            $fixCount++;
        }
        elseif($commands[$index] === "renamefile"){
            $type = archiveType($commands[$index + 1]); 
            rename($commands[$index + 1], $commands[$index + 2]);
            $index += 3;
            $fixCount++;
        }
        else{
            fatal("Unknown recovery " . $commands[$index] . "($index)");
        }
    }
    htmlEcho ("$fixCount fixes, please update the database.<br/>");
    $hidden = array("admin", "user");
    addForm("admin", "Generate Comics DB", NULL, "gencomicsdb", $hidden);
}
                           

function htmlEcho($str){
    echo preg_replace('/\n/', '</br>', $str);
}
    

function fixfile($filename){
    $folder = pathinfo($filename, PATHINFO_DIRNAME);
    $tempname = "$folder/qmics_tempfile.zip";
    $tempfolder = "$folder/qmics_tempfolder";
    if(file_exists($tempfolder))
        cleanzip($tempfolder, "");
    $out = fixzip($filename, $tempname, $tempfolder, true);
    if(strlen($out) > 0){
        echo "Error when fixing zip: $out<br/>";
        if(file_exists($tempname))
            unlink($tempname);
        } 
    else{
        rename ($filename, "$filename.bak"); 
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $newname = substr($filename, 0, -strlen($ext)) . "zip";
        rename ($tempname, $newname);
        } 
}

    
?>
</body>
</html>