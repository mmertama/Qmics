
	
<?php

define('PATTERN','/\s*define\s*\(\s*\'\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\'\s*,\s*([^\)]+)\s*\)\s*;\s*\/\/\s*#([a-zA-Z0-9_]+)#?(.*)/'); //please dont use ')' in your strings :-)
define('PATTERN_SHORT','/\s*define\s*\(\s*\'\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\'\s*,\s*([^\)]+)\s*\)\s*;/'); 

if(isset($_POST['action']) && $_POST['action'] == 'configure'){
	$file = file_get_contents("configuration.php.template");
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

function configure($reason, $oldVal){
	echo("<h3>" . $reason. "</h3><br/>\n");
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
	$data = parseConfigure(PATTERN, "configuration.php.template");
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

?>
  