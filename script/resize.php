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

resize.php 
   Changes size of given image (jpeg or png), output image is always jpg.

Known issues: 
* None
 
*DESCRIPTION*********************************************************************/ 

require_once "configuration.php";
global $resizeError;


function resizeError(){
    global $resizeError;
    return $resizeError;
}

function resize($filename, $width, $height){
    $pathInfo = pathinfo($filename);
    $temp = $pathInfo['dirname'] . "temp_file";
    $sz = resizeCopy($filename, $temp, $width, $height);
    if($sz == FALSE)
        return FALSE;
    if($temp){
        unlink($filename);
        rename($temp, $filename);
        }
    return $sz;
}

function resizeCopy($filename, &$newname, $width, $height) {
	global $resizeError;
    if(!file_exists($filename)){
        $resizeError = "Cannot find image:\"$filename\"";
        return FALSE;
    }
    
    $info = getimagesize($filename); 
	if($info == FALSE){
        $resizeError = "Cannot open image:\"$filename\"";
        return FALSE;
    }
    
    $ratio1 = $width / $info[0];
    $ratio2 = $height / $info[1];
    $ratio = min($ratio1, $ratio2);
    if($ratio >= 1.0){
        $newname = FALSE;
        return array('width' => $info[0], 'height' => $info[1]);
    }
    
    $image = NULL;
	if($info[2] == IMAGETYPE_JPEG){
        $image = imagecreatefromjpeg($filename);
        }
    elseif($info[2] == IMAGETYPE_PNG){
        $image = imagecreatefrompng($filename);
        }
    else{
        $resizeError = "Cannot resize image:\"$filename\" (dump follows):" . implode("|", $info);
        return FALSE;
    }
    
    $width = floor($ratio * $info[0]);  
    $height = floor($ratio * $info[1]);  
	$newImage = imagecreatetruecolor($width, $height); 
	imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
	imagejpeg($newImage, $newname, 70);
	return array('width' => $width, 'height' => $height);
}

?>