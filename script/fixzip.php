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

fixzip.php 
   There are certain assumptions what comes to Zip and Rar packet structure - these
   utilities let admin fix most common problems with invalid filenames.

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

require_once "configuration.php";
require_once "deflate.php";
require_once "utils.php";


function cleanzip($folder, $str){
    $err = "Error: $str ";
    $files = array();
    $e = getFileNames($files, $folder, FN_MATCHALL, FN_FILES);
    fatalIfFalse($e, utilError());
    foreach ($files as $fname){
            if(!unlink($fname)) 
                $err = $err . " - unlink $fname  failed";
        }
    $files = array();
    $e = getFileNames($files, $folder, FN_MATCHALL, FN_FOLDERS);
    fatalIfFalse($e, utilError());
    foreach ($files as $fname){
            if(!rmdir($fname)) 
                $err = $err . " - unlink $fname  failed";
        }
    
    if(!rmdir($folder))
        $err = $err . " - rmdir $folder failed";
    if(file_exists($folder))
        warn("deep uuups");
    return $err; 
}

function fixzip($name, $newname, $folder, $strict){
    if(file_exists($folder)){
        return "$folder exists";
    }
  
    $out = uncompressAll($name, $folder);
    if($out == FALSE){
        return cleanzip($folder,compressError());
    }
    
    
    $array = array();
    $err = getFileNames($array, $folder, FN_MATCHALL, FN_FILES | FN_FOLDERS);
    fatalIfFalse($err, "Cannot read $folder");
    
    foreach($array as $entry){
        $d = pathinfo($entry, PATHINFO_BASENAME);
        $n = replaceWithValidChars($d, $strict);
        echo "$d<br>";
        if($n != $d){
            $entryName = substr($entry, 0, -strlen($d)) . $n;
            //echo "$entry, $newname \n";
            if(!rename($entry, $entryName)){
               return cleanzip($folder,"rename error: $d --> $n");
               }
    
        }   
    }


    $out = compress($folder, $newname);

    if($out == FALSE){
        return cleanzip($folder, compressError());
    }
    
    cleanzip($folder, "");
    return "";
}


function archiveType($filename){
    $handle = fopen($filename, 'r');
    fatalIfFalse($filename, "Cannot open $filename");
    $id = fread($handle, 16);
    fclose($handle);
    if(strncmp($id ,'PK', 2) == 0)
        return "zip";
    if(strncmp($id ,'Rar!', 4) == 0)
        return "rar";
    return '';
}

?>

