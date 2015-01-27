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

getdata.php 
   Get data from server.
   

Known issues: 
* Im not sure if checkCacheErase, i.e. cache management works a) at all b) feasibly c) robustly
 
*DESCRIPTION*********************************************************************/ 

require_once 'utils.php';
require_once 'deflate.php';
require_once 'configuration.php';
require_once 'resize.php';


function getImage($db, $userid, $id){
    $comicInfo = getComicId($db, $userid, $id);
    
    
    $comicId = $comicInfo['uniqcomicid'];
	$comicResult = $db->query("SELECT `coverimage` FROM `comics` WHERE `comicid` = '$comicId'");
    if($comicResult == FALSE){
		fatal("uups 32:" . $db->error);
		}
	$comicData = $comicResult->fetch_array(MYSQLI_ASSOC);

    $coverimage = longPath($comicData['coverimage']);
    if(!file_exists($coverimage))
        fatal("cover image not found");
    
    header("Content-Type: image/jpeg");
    ob_clean();
    flush(); 
    $bytesRead = readfile($coverimage);
    if(FALSE == $bytesRead){
        fatal("cannot read:" . $coverimage);
    }
   
    $comicResult->close();
}

function checkCacheErase($db, $userId, $comicId){
    $userDataResult = $db->query("SELECT `currentcomicid` FROM `userdata`");
    fatalIfFalse($userDataResult, $db->error);
    
    $inUse = false;
    
    while($currentComicInfo = $userDataResult->fetch_array(MYSQLI_ASSOC)){
        if($currentComicInfo['currentcomicId'] == $comicId){
            $inUse = true;
            break;
        }
    } 
    
    if(!inUse)
        erase(CURRENT_CACHE);
    
    if(!file_exists(CURRENT_CACHE)){
        mkdir(CURRENT_CACHE, 0777, true);
    }
    
    $userDataResult->close();
}



function getComicId($db, $userId, $comicId){
    $comicIdResult = $db->query("SELECT `uniqcomicid`, `access` FROM `comicsuser` WHERE `userid` = '$userId' AND `usercomicid` = '$comicId'");
    fatalIfFalse($comicIdResult, $db->error);
	$comicIds = $comicIdResult->fetch_array(MYSQLI_ASSOC);
 
    
    if(!isset($comicIds['uniqcomicid']) || !isset($comicIds['access'])){
        fatal("bad comic Id");
    }
    $array = array('uniqcomicid' => $comicIds['uniqcomicid'], 'access' => $comicIds['access']);
    $comicIdResult->close();
    return $array;
    }

function updatePages($db, $comicId, $pageIndex){
    $res = $db->query("UPDATE `userdata` SET `currentcomicid` = '$comicId', `currentcomicpage` = '$pageIndex'");
    fatalIfFalse($res, "user not updated ". $db->error);
}



function getPageQuery($db, $userId, $comicId, $offset){ 
    $comicData = pageInfo($db, $userId, $comicId, $offset);
    header('Content-Type: application/octet-stream');
    ob_clean();
    flush(); 
    $data = "";
    if($comicData != null){
        debug_log("at getPageQuery " . $comicData['usercomicid'] .",". $comicData['pagecount']);
        $data = pack("NN", $comicData['usercomicid'], $comicData['pagecount']);
    }else{
         $data = pack("N", 0); 
    } 
    echo $data;
    ob_flush();
}
        

function getAccess($db, $userId){
    debug_log("get access");
    $comicIdQuery = $db->query("SELECT `uniqcomicid`, `access` FROM `comicsuser` WHERE `userid` = '$userId'");
	fatalIfFalse($comicIdQuery, $db->error);
    header('Content-Type: application/octet-stream');
    ob_clean();
    flush(); 
	while($comicIds = $comicIdQuery->fetch_array(MYSQLI_ASSOC)){
        $data = pack("NN", $comicIds['uniqcomicid'], $comicIds['access']);
        echo $data;
      //  debug_log("$userId id:".$comicIds['uniqcomicid']." - a:".$comicIds['access']);
    }
    ob_flush();
    $comicIdQuery->close();
    debug_log("put no more from $userId\n");
}

function pageInfo($db, $userId, $comicId, $offset){
     $geturl = "SELECT `comics`.`sourceurl` FROM `comics` JOIN `comicsuser` ON `comics`.`comicid` = `comicsuser`.`uniqcomicid` WHERE `comicsuser`.`userid` = '$userId' AND `comicsuser`.`access` & ".READ_OK." AND `comicsuser`.`usercomicid` = '$comicId'";
   
    debug_log($geturl);
    
    $urlNameQuery = $db->query($geturl);
    fatalIfFalse($urlNameQuery, $db->error);
    $urlNameData = $urlNameQuery->fetch_array(MYSQLI_ASSOC);
    fatalIfFalse(count($urlNameData), "Invalid query");
    $urlName =  $urlNameData['sourceurl'];
    $urlNameQuery->close();
    
    $limit = $offset;
    $type = "ASC";
    $operator = ">";    
    
    if($offset < 0){
        $limit = -$offset;
        $type = "DESC";
        $operator = "<";    
    }

   $sourceUrl = $db->real_escape_string($urlName);
    
    $query = "SELECT `comics`.`comicid`, `comics`.`sourceurl`, `comics`.`pagecount`, `comicsuser`.`usercomicid` FROM `comics` JOIN `comicsuser` ON `comics`.`comicid` = `comicsuser`.`uniqcomicid` WHERE `comicsuser`.`userid` = '$userId' AND `comicsuser`.`access` & ".READ_OK." AND `comics`.`sourceurl` " . $operator. " '$sourceUrl' ORDER BY `comics`.`sourceurl` " . $type . " LIMIT " . $limit;
    
    
    $comicQuery = $db->query($query);
    fatalIfFalse($comicQuery, $db->error);
    if($comicQuery->num_rows < $limit){
        $limit = $comicQuery->num_rows;
    }
    
    //debug_log($limit . "_" . $comicQuery->num_rows);
    
    if($limit <= 0){
        $comicQuery->close();
        return null;
    }
    $comicQuery->data_seek($limit - 1);
    $comicData = $comicQuery->fetch_array(MYSQLI_ASSOC);
    $array = array ('uniqcomicid' => $comicData['comicid'], 'sourceurl' => $comicData['sourceurl'], 'pagecount' => $comicData['pagecount'], 'usercomicid' => $comicData['usercomicid']);
    $comicQuery->close();
    return $array;
}


function pageFrom($db, $userId, $comicId) {
    $comicIds = getComicId($db, $userId, $comicId);
    if(!IsAccess($comicIds['access'], 'read')){
        fatal("Access Denied");
    }
   
    $uniqComicId = $comicIds['uniqcomicid'];
    $comicResult = $db->query("SELECT `sourceurl`, `pagecount` FROM `comics` WHERE `comicid` = '$uniqComicId'");
    fatalIfFalse($comicResult, "user not updated ". $db->error);
    $comicData = $comicResult->fetch_array(MYSQLI_ASSOC);
    $array = array ('uniqcomicid' => $uniqComicId, 'sourceurl' => $comicData['sourceurl'], 'pagecount' => $comicData['pagecount']);
    $comicResult->close();
    return $array;
}
                    
function getPage($db, $userId, $comicId, $pageIndex, $requestedSize){
    debug_log("get page $userId, $comicId, $pageIndex, $offset, $requestedSize");
	
    checkCacheErase($db, $userId, $comicId);
    
    $comicData = pageFrom($db, $userId, $comicId);        
    
    debug_log($comicdata['uniqcomicid'] . ":" . $comicdata['sourceurl'] . ":" . $comicdata['pagecount'] . " - Requested size: $requestedSize");
    
    if($pageIndex < 0)
        $pageIndex = 0;
    if($pageIndex >= $comicData['pagecount'])
        $pageIndex = $comicData['pagecount'];

    
    $source = longPath($comicData['sourceurl']);
    $pathInfo = pathinfo($source);
    $archivepath = $pathInfo['filename'];

	$targetFolder = CURRENT_CACHE;
	if($requestedSize > 0){
		$targetFolder = CURRENT_CACHE . "/_cache_" . $requestedSize;
		 if(!file_exists($targetFolder)){
			$err = mkdir($targetFolder, 0777, true);
			fatalIfFalse($err, "Failed to create Cache folder");
		}
	}
	
	
    
    $data = uncompress($source, $targetFolder ."/". $archivepath, $pageIndex);
    fatalIfFalse($data, compressError());
    
    $pageImage = $data['fullname'];
	
	debug_log("get image: $pageImage, $requestedSize, " . $data['cached']);	
		
	if($requestedSize > 0 && !$data['cached']){
		$rsz = resize($pageImage, $requestedSize, $requestedSize);
		fatalIfFalse(rsz, "Resize error ($requestedSize) $pageImage: " . resizeError());
	}
	
    header("Content-Type: image/jpeg");
    ob_clean();
    flush(); 
    $bytesRead = readfile($pageImage);
    if(FALSE == $bytesRead){
        fatal("cannot read:" . $pageImage);
    }
    ob_flush();

    updatePages($db, $comicId, $pageIndex);
}

function cacheUpdate($db, $filename){
	$res = $db->query("SELECT `cachesize`, `maxcacachesize` FROM `comicsDB`");
	fatalIfFalse($res, $db->error);
	$sizes = $res->fetch_array(MYSQLI_ASSOC);
	$newSize = filesize($filename) + $sizes['cachesize'];
	$maxSz = $sizes[maxcachesize];
	$res->close();
	if($newSize > $maxSz){
		$sz = setFolderSize(CURRENT_CACHE, $maxSz * 0.9);
		$res = $db->query("UPDATE `comicsDB` SET `cachesize` = '$sz'");
	}
	else{
		$res = $db->query("UPDATE `comicsDB` SET `cachesize` = '$newSize'");
		}
	fatalIfFalse($res, "Cache size set failed:" . $db->error);
}

function getUserPage($db, $userId){
    $userQuery = $db->query("SELECT `currentcomicid`, `currentcomicpage` FROM `userdata` WHERE `userid` = '$userId'");
    fatalIfFalse($userQuery, $db->error);
    $data = $userQuery->fetch_array(MYSQLI_ASSOC);
    if($data == false || $data['currentcomicid'] <= 0){
        $userQuery->close();
        return FALSE;
    }
   
    $id = $data['currentcomicid'];
    $page = $data['currentcomicpage'];
    $userQuery->close();
    
    $comicsIdQuery = $db->query("SELECT `uniqcomicid`, `access` FROM `comicsuser` WHERE `userid` = '$userId' AND `usercomicid` = '$id'");
    fatalIfFalse($comicsIdQuery, $db->error);
  
    $comicIds = $comicsIdQuery->fetch_array(MYSQLI_ASSOC);

  //   echo $comicIds['uniqcomicid'] ."/". $comicIds['access'] ."/".  $data['currentcomicid'] ."/". $userId ."/". //$data['currentcomicpage'] . "<br/>\n";
    
    if(!isAccess($comicIds['access'], 'read')){
        $comicsIdQuery->close();
        return FALSE;
    }
    
    $uniqComicId = $comicIds['uniqcomicid'];
    $comicsIdQuery->close();
    
    return ['currentcomicid' => $id, 'currentcomicpage'=>$page, 'uniqcomicid' => $uniqComicId];
}

function getContent($db, $userid, $id){
	 
    $comicIds = getComicId($db, $userid, $id);
    $comicId = $comicIds['uniqcomicid'];
    
  

    if(!IsAccess($comicIds['access'], 'download')){
        fatal("Access Denied");
    }
    
	$comicResult = $db->query("SELECT `comicname`, `sourceurl` FROM `comics` WHERE `comicid` = '$comicId'");
	
    if($comicResult == FALSE){
		fatal("uups 32:" . $db->error);
		}
	$comicData = $comicResult->fetch_array(MYSQLI_ASSOC);
    
    $filename = longPath($comicData['sourceurl']);
	
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
    header('Cache-Control: private',false); 
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=\''.$comicData['comicname'].'\'');
	header('Expires: 0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filename));
    header('Content-Transfer-Encoding: binary');
    ob_clean();
    flush();
    readfile($filename);
	$comicResult->close();
	}

?>