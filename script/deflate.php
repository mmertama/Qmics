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

deflate.php 
    defalates Rar and Zip packages (and generates zip)

Known issues: 
*This class is a hack and should call rar and zip libraries instead of shell commands,
but platform Im using that was not this simple :-) and this process overhead etc. seems
to be quite ok. However doing these assumptions with the output makes this fragile and
error prone and shall be at least configuration adjustable.
 
*DESCRIPTION*********************************************************************/ 

require_once "configuration.php";
require_once "utils.php";

global $defErr;
$defErr = "";

function compressError(){
    global $defErr;
    return $defErr;
}
function uncompressAll($filename, $outfolder){
    return uncompress($filename, $outfolder, -1);
}

function uncompress($filename, $outfolder, $index){
    global $defErr;
    $commands = array('list'=>"", 'fileselect'=>"", 'exract'=>'');
    $pathInfo = pathinfo($filename);
    if(!isset($pathInfo['extension']))
        continue;
    $ext = strtolower($pathInfo['extension']);
    if($ext == "cbr" || $ext == "rar"){
        $commands['type'] = "rar";
        $commands['list'] = UNRARCMD . " lb  -- \"$filename\"";
        $commands['fileselect'] = "/(.*\.(?:png|jpg))$/i";
        $commands['preextract'] = UNRARCMD . " x -- \"$filename\"";
        $archiveName = $pathInfo['filename'];
        $outfolder .= "/$archiveName";
        $commands['postextract'] = "\"$outfolder\"";
        if(!file_exists($outfolder))
            fatalIfFalse(mkdir($outfolder, 0777, true), "cannot create folder $outfolder");
        return extractTo($filename, $index, $outfolder, $commands);
    }
    elseif($ext == "cbz" || $ext == "zip"){
		$zname =  basename(UNZIPCMD);
    	if($zname == 'unzip'){
        	$commands['type'] = "zip";
        	$commands['list'] = UNZIPCMD . " -l -qq \"$filename\"";
        	$commands['fileselect'] = "/\s*\d+\s+\d+\-\d+\-\d+\s+\d+:\d+\s+(.*\.(?:jpg|png))$/i";
        	$commands['preextract'] = UNZIPCMD . " -qq \"$filename\"";
        	$commands['postextract'] = "-d \"$outfolder\"";
    	} else if($zname = '7z'){
    		$commands['type'] = "zip";
    		$commands['list'] = UNZIPCMD . " l \"$filename\"";
    		$commands['fileselect'] = "/[0-9-]+\s+\d+:\d+:\d+\s+[A.]+\s+\d+\s+\d+\s+(.*\.(?:png|jpg))$/i";
    		$commands['preextract'] = UNZIPCMD . " x  \"$filename\"";
    		$commands['postextract'] = "-o\"$outfolder\" -y";
    		}
        return extractTo($filename, $index, $outfolder, $commands);
    }
    $defErr = "Unknown file:\"$filename\"";
    return FALSE;
}


function extractTo($filename, $index, $outfolder, $commands, $force = FALSE){
    global $defErr;
    $deflateArchive = "";
    $fullname = "";
	$cached = true;
	
    
    if(!file_exists($filename)){
        $defErr = "File not found: $filename";
        return FALSE;
    }
    
    if($index >= 0){
        $readArchive = $commands['list'] . " 2>&1 ";
        $output = array();
        exec($readArchive, $output);
        $jpgs = array();
        foreach($output as $line){
            $pat = $commands['fileselect'];
            if(preg_match($pat, $line, $match)){
                $jpgs[] = $match[1];
            }
        }
        
        if(count($jpgs) == 0 && (archiveType($filename) != $commands['type'])){
            $defErr = "badfiletype";
            return FALSE;
        }
    
        if(count($jpgs) <= $index || count($jpgs) == 0){
            if(replaceWithValidChars($filename, false) != $filename && archiveType($filename) != ''){
                $defErr = "badfilename";
                return FALSE;
            }
            $defErr = "No a valid comic archive \"$filename\": Not found $index from " . count($jpgs) . " files (dump follows):" . join("\n", $output);
            return FALSE;
        }
        sort($jpgs);
        $fullname = "$outfolder/" . $jpgs[$index];
        if($force && file_exists($fullname)){
            if(!unlink($fullname)){
                 $defErr = "Cannot delete $fullname";
                 return FALSE;
            }
        }
                
        if(!file_exists($fullname) && count($outfolder) > 0){
            $jpgname = $jpgs[$index]; 
            fatalIfFalse($jpgname, "unfortunate error at " . $jpgs[$index]);
            $first = " \"$jpgname\" ";
            $deflateArchive = $commands['preextract'] . $first . $commands['postextract'] . " 2>&1 ";
            //echo("<h3>" . $deflateArchive . "</h3>");
            unset($output);
        }
    }
    else{
        $deflateArchive = $commands['preextract'] . " " . $commands['postextract'] . " 2>&1 ";
    }
    
    if(strlen($deflateArchive) > 0){
        $output = array();
        exec($deflateArchive, $output);
        if(count($output) > 0 && preg_match('/caution:\s+filename\s+not\s+matched/', $output[0])){
            $defErr = "invalidfilename";
            return FALSE;
        }
        if(strlen($fullname) > 0 && !file_exists($fullname)){
            if(preg_match('/No\s+such\s+file\s+or\s+directory/', join(' ', $output)) && replaceWithValidChars($fullname, false) != $fullname){
                $defErr = "invalidfilename";
                return FALSE;
            }
            $defErr = "Deflating<br>\"$fullname\"<br>from<br>\"$filename\"<br>failed (dump follows):" . join("<br>\n", $output);
            return FALSE;
        }
        if($index < 0)
            return file_exists($outfolder);
		$cached = false;
    }
     return array('fullname' => $fullname, 'pages' => count($jpgs), 'cached' => $cached);   
}



function compress($infolder, $outfile){
    global $defErr;
    if(file_exists($outfile) && !unlink($outfile)){
        $defErr = "file $outfile exists"; 
        return false;
    }
    $zipArchive = ZIPCMD . " -r -j \"$outfile\" \"$infolder\"";
    $output = array();
    exec($zipArchive, $output);
    if(!file_exists($outfile)){
        $defErr = "compress $infolder to $outfile:" . join("\n", $output);
        return FALSE;
        }
    return TRUE;
}

       /*
function getRarData($name){
    $data = array();
    $data[0] = "broken.jpg";
	$rar = RarArchive::open($name); käytä exec("unrar") ja exec("unzip"), helpompi...
	if ($rar_arch === FALSE){
        $data[1] = -1;
        return $data;
    }
	$entries = $rar->getEntries();
	if ($entries === FALSE){
        $data[1] = -2;
        return $data;
    }
	$entry = $rar->getEntry($entries[0]);
	if ($entry === FALSE){
        $data[1] = -3;
        return $data;
    }
	$file = $entry->extract(COVER_CACHE);
	if ($file === FALSE){
        $data[1] = -3;
        return $data;
    }
    $newname = $name . ".jpg";
    $err = resize(100, 100, $entries[0], $newname);
    delete($entries[0]);
	if($err < 0){
        $data[1] = $err;
        return $data;
    }
    $data[0] = $newname;
    $data[1] = count($entries);
    $rar->close();
	return $data;
}


function getZipData($name){
    $data = array();
    $data[0] = "broken.jpg";
	$zip = new ZipArchive;
	$zip->open($name);
	if ($zip_arch !== TRUE){
        $data[1] = -1;
        return $data;
    }
	$entry = $zip->getNameIndex(0);
	if ($entry === FALSE){
        $data[1] = -3;
        return $data;
    }
	$file = $zip->extractTo(COVER_CACHE, $entry);
	if ($file === FALSE){
        $data[1] = -3;
        return $data;
    }
    $newname = $name . ".jpg";
    $err = resize(100, 100, $entry, $newname);
    delete($entry);
	if(err < 0){
        $data[1] = $err;
        return $data;
  	 }
    $data[0] = $newname;
    $data[1] = $zip->numFiles;
	$zip->close();
	return $data;
}
*/
?>
