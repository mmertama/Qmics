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

utils.php 
   General utility and debugging functions.
   

Known issues: 
* The fatal function fails to display output in certain conditions - the fix would be (?) 
either user new window or new page just to show output.
 
*DESCRIPTION*********************************************************************/ 

require_once "configuration.php";
define('FN_FILES', 1);
define('FN_FOLDERS', 2);
define('FN_MATCHALL', "");
define('READ_OK', 0x1);
define('DOWNLOAD_OK', 0x10);


function utilError(){
    global $utilError;
    return $utilError;
}

function hasUsers($db){
    $usersSelect = $db->query("SELECT `username` FROM `userdata`");
    $r = $usersSelect != false;
    $usersSelect->close();
    return $r;
}

function getFileNames(&$files, $dir, $match = FN_MATCHALL, $type = FN_FILES){
    global $utilError;
    if(!file_exists($dir)){
        $utilError = "directory not exists: $dir";
        return FALSE;
    } 
	$handle = opendir($dir);
	if(FALSE == $handle){
        $utilError = "directory open error: $dir";
		return FALSE;
		}
    while(FALSE !== ($filename = readdir($handle))){
        if($filename != "." && $filename != ".."){
            $fullname = $dir."/".$filename;
            if(is_dir($fullname)){
                if(!getFileNames($files, $fullname, $match, $type))
                    return FALSE;
                if(($type & FN_FOLDERS) && ((strlen($match) == 0) || (1 == preg_match($match, $filename)))){
                	$files[] = $fullname;
                }
            }
            else if(($type & FN_FILES) && ((strlen($match) == 0) || (1 == preg_match($match, $filename)))){
                $files[] = $fullname;
            }
        }
    }
  	closedir($handle);
    return TRUE;
}
 
function longPath($string){
    return LIBRARY . "/" . $string;
}

function shortPath($string){
    return substr($string, strlen(LIBRARY) + 1);
}

function replaceWithValidChars($strings, $strict){
    $validChars = '/[^a-z0-9 ._\-\/\)\(\'&#,]+/i';
    $posixvalidChars = '/[^a-z0-9._\-\/]+/i';
    $validator = $strict ? $posixvalidChars : $validChars;
    $out = preg_replace($validator, '_',$strings);
    return $out; 
} 

function erase($folder){
	$files = array();
    $res = getFileNames($files, $folder);
    fatalIfFalse($res, utilError());
    foreach($files as $file)
        warnIfFalse(unlink($file), "unlink $file");
    $folders =  array();
    $res = getFileNames($folders, $folder, FN_MATCHALL, FN_FOLDERS);
    fatalIfFalse($res, utilError());
    foreach($folders as $folder)
        warnIfFalse(rmdir($folder), "rmdir $folder");
}

/*
function getTitle($path, $offset){
    $base = $path;
    while($offset-- > 0)
        $base = dirname($base);
    $name = pathinfo($base, PATHINFO_FILENAME);
    return $name;
}
*/

function getHeader($src, $type){
    $start = 0;
    $off = null;
    if($type == "publisher"){
        $start = 0;
        $off = 1;
    }
    if($type == "title"){
        $start = 1;
        $off = null;
    }
    return makeTitle($src, $start, $off);
}

function makeTitle($path, $offset, $len = null, $glue = " "){
    $p = preg_split("/\//", pathinfo($path, PATHINFO_DIRNAME));
    $p = array_slice($p, $offset, $len);
    return join($glue, $p);
}

/*function relativePath($root, $path){
    return substr($path, strlen($root) + 1);
}*/
 
function accessToBytes($array){
    $var = 0;
    if(isset($array['read']) && $array['read'])
        $var |= READ_OK;
    if(isset($array['download']) && $array['download'])
        $var |= DOWNLOAD_OK;
    return $var;
}

function accessFromBytes($bytes){
    $array = array();
    $array['read'] = ($bytes & READ_OK) != 0;
    $array['download'] = ($bytes & DOWNLOAD_OK) != 0;
    return $array;
}

function isAccess($mixed, $key){
    if(gettype($mixed) == 'array'){
        if(isset($mixed[$key])){
            return $mixed[$key];
        }
        return false;
    }
    return isAccess(accessFromBytes($mixed), $key);
}

function fatalIfFalse($mixed, $str, $cleanup = NULL){
    if($mixed === false || is_null($mixed) || 0 === $mixed){
        if($cleanup != NULL){
            $cleanup();
        }
        fatal($str);
    }
    return $mixed;
}

function warnIfFalse($mixed, $str, $cleanup = NULL){
    if($mixed === false  || is_null($mixed) || 0 === $mixed){
        if($cleanup != NULL){
            $cleanup();
        }
        warn($str);
        }
    return $mixed;
}

function warn($str){
    debug_log("warning: $str");
    $date = new DateTime();
    $timestamp = $date->format('Y/m/d H:i:s');
    
    file_put_contents(ERROR_LOG, "$timestamp $str\n" ,FILE_APPEND);
    flush();
    ob_flush();
    echo("<div style=\"color:black;padding:80px 80px 80px 80px;background-color:red;z-order:10;margin:50px 50px 50px 50px;position:static;\">$str</div>");
    if(WARNINGS_FATAL)
        killme();
    return FALSE;
} 

function killme(){
    sleep(1);
    die();
}

function fatal($str){
    warn($str);
    killme();
    return FALSE;
} 


function debug_log($string){
    if(false != DEBUG_LOG){
        $date = new DateTime();
        $timestamp = $date->format('Y/m/d H:i:s');
        file_put_contents(DEBUG_LOG, "$timestamp $string\n" ,FILE_APPEND);
    }
}

function debug_table($db, $table){
    echo "<br/>\n";
    $res = $db->query("SELECT * FROM ".$table);
    while($data = $res->fetch_array(MYSQL_ASSOC)){
        print_r($data);
        echo "<br/>\n";
    }
    $res->close();
    echo "<br/>\n";
}
?>