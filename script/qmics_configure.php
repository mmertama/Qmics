	
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

qmics_configuration.php 
    Qmics build-in configuration UI. 

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

//ini_set('display_errors', 'On');
ini_set('auto_detect_line_endings', true);
//error_reporting(E_ALL | E_STRICT);  

define('PATTERN','/\s*define\s*\(\s*\'\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\'\s*,\s*([^\)]+)\s*\)\s*;\s*\/\/\s*#([a-zA-Z0-9_]+)#?(.*)/'); //please dont use ')' in your strings :-)
define('PATTERN_SHORT','/\s*define\s*\(\s*\'\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\'\s*,\s*([^\)]+)\s*\)\s*;/'); 
define('TEMPLATE', 'configuration.php.template.txt');

if(isset($_POST['action']) && $_POST['action'] == 'configure'){
	$file = file_get_contents(TEMPLATE);
	if($file == FALSE)
		return FALSE;
	$lines = explode("\n", $file);
	if(file_exists('configuration.php'))
		unlink('configuration.php');
	$file = fopen('configuration.php', 'w');
	foreach($lines as $line){
			if(preg_match(PATTERN, $line, $match)){
				$name = $match[1];
				dieIfError(isset($_POST[$name]), $_POST[$name] . " not found from configuration");
				$tval = $match[2];
				$comment = rtrim($match[4]);
				$value = $_POST[$name];
				if(preg_match('/^"(.*)"$/', $tval, $match)){
					$value = "\"" . $value . "\"";
				}
				if(preg_match('/^\'(.*)\'$/', $tval, $match)){
					$value = "'" . $value . "'";
				}
				fwrite($file, "define('$name', $value);\t\t\t//$comment (Please do not edit)\n");
			}
			else{
				fwrite($file, $line . "\n");
			}
		}
    fclose($file);
	echo ("a new configuration created, please press back");
	echo ("<div id=\"back\"><a href=\"..\qmics.html\">Back</a></div>");		
	}

function dieIfError($err, $string){
if($err == FALSE || $err == NULL){
	echo($string);
	die();
	}
}

function configure($reason, $errors, $oldVal){
	echo("<h3>" . $reason. "</h3><br/>\n");
	if($errors){
		echo("<ul>\n");
		foreach ($errors as $err)
			echo("<li>" . $err . "</li>");
		echo("</ul>\n");
	}
	generateConfigurePage($oldVal);
}

function parseConfigure($pat, $filename){
	$file = file_get_contents($filename);
	if($file == FALSE)
		return FALSE;
	$lines = explode("\n", $file);
	$data = array();
	
	foreach($lines as $line){
			if(preg_match($pat, $line, $match)){
				$name = $match[1];
				$entry = array();
				$entry['value'] = $match[2];
				if(isset($match[3]))
					$entry['type'] = $match[3];
				if(isset($match[4]))
					$entry['comment'] = $match[4];
				if(preg_match('/^"(.*)"$/', $entry['value'], $match)){
					$entry['value'] = $match[1];
				}
				if(preg_match('/^\'(.*)\'$/', $entry['value'], $match)){
					$entry['value'] = $match[1];
				}
				$data[$name] =  $entry;
			}
	}
	return $data;
}

function generateConfigurePage($oldVal){
	$data = parseConfigure(PATTERN, TEMPLATE);
	dieIfError($data, "Cannot open configuration template");
	
	echo("<div class=\"settingform\">");
	echo("<h3>Configure</h3>");
	echo("<div class=\"forminfo\">");
	echo("Qmics system configuration - modify with care!"); 
	echo("</div><br/>\n");
    echo("<form action=\"qmics_configure.php\" method=\"post\">\n");
	echo "<input hidden name = \"action\" value=\"configure\">\n";
	
	
	if($oldVal != NULL){
		$olddata = parseConfigure(PATTERN_SHORT, $oldVal);
		foreach($olddata as $oname => $oval ){
				if(isset($data[$oname])){
					$data[$oname]['value'] =  $oval['value'];
				}
			}
		}		
	
	foreach($data as $name => $value){
		echo($value['comment'] . "<br/>\n");
		echo("<input type=\"text\" name=\"" . $name. "\" value=\"" . $value['value']. "\"/>\n<br/>\n<br/>\n");
		}
	echo("<input type=\"submit\" value=\"configure\" />\n");
	echo("</form>");
	echo("</div>");
}


function validateConfiguration($conf){
	$ok = true;
	$data = parseConfigure(PATTERN, TEMPLATE);
	dieIfError($data, "Cannot open configuration template");
	$confData = parseConfigure(PATTERN_SHORT, $conf);
	$errors = array();
	foreach($data as $name => $value){
		switch($value['type']){
			case 'filepath':
				$folder = $value['value'];
				if(!@file_exists($folder))
            		if(!mkdir($folder, 0777, true)){
            			array_push($errors, "cannot access folder '" . $folder . "' close '" . $name . "'");
            			$ok = false;
            		}
				break;
			case 'integer':
				if($value['value'] != '0' && !intval($value['value'])){
					array_push($errors, "invalid number '" . $value['value'] . "' close '" . $name . "'");
					$ok = false;
				}
				break;
			case 'filenameOrNull':
				$file = $value['value'];
				if(!@file_exists($file)){
					$folder = basename($file);
            		if(!file_exists($folder) && !mkdir($folder, 0777, true)){
            			array_push($errors, "cannot access folder '" . $folder . "' close '" . $name . "'");
            			$ok = false;
            		}
				}
				break;
			case 'filename':
				$file = $value['value'];
				if(!@file_exists($file)){
            			array_push($errors, "cannot access file '" . $file . "' close '" . $name . "'");
            			$ok = false;
            		}
				break;
			case 'executable':
				$file = $value['value'];
				if(!@is_executable($file)){
            			array_push($errors, "cannot access file '" . $file . "' close '" . $name . "'");
            			$ok = false;
            		}
				break;
			case 'URL':
				if(!is_string($value['value'])){
					$errors[] = ("invalid type '" . $value['value'] . "' close '" . $name . "'");
					$ok = false;
				}
				break;
			case 'string':
				if(!is_string($value['value'])){
					$errors[] = ("invalid type '" .  $value['value'] . "' close '" . $name . "'");
					$ok = false;
				}
				break;
			case 'password':
				if(!is_string($value['value'])){
					$errors[] = ("invalid type '" . $value['value'] . "' close '" . $name . "'");
					$ok = false;
				}
				break;
			case 'boolean':
				$t = $value['value'];
				if(strtolower($t) != 'false' && strtolower($t) != 'true'){
					$errors[] = ("invalid type '" . $value . "' close '" . $name . "'");
					$ok = false;
				}
				break;
			default:
				$errors[] = ("Unknown type:'" . $value[type] . "' close '" . $name . "'");
				$ok = FALSE;
			}
		}
	return $errors;
	}



?>
  