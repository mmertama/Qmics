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

configuration.php.template 
    Qmics general settings, from this file it is generated 'configuration.php' via Qmics configuration functionality when configuration.php is missing, updated or certain error situations - or when admin selects "configure" from "Settings".

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

//PLEASE DO NOT EDIT if this file is "configuration.php", only the "configuration.php.template.txt", if needed!!! 

ini_set('display_errors', 'On');
ini_set('auto_detect_line_endings', true);
error_reporting(E_ALL | E_STRICT);  

define('LIBRARY', "../../private_library");			//Path to library folder. (Please do not edit)
define('COVER_CACHE', "../../private_library/cache/covers");			//Path to cover page cache. (Please do not edit)
define('CURRENT_CACHE', "../../private_library/cache/current");			//Path to content cache. (Please do not edit)
define('ERROR_LOG', "../../myprivate_library/errorlog.txt");			//File to store error log information. (Please do not edit)
define('COVER_WIDTH', 200);			//Width of cover pages in library view. (Please do not edit)
define('COVER_HEIGHT', 200);			//Height of cover pages in library view. (Please do not edit)
define('LOGOUT_PAGE', "../qmics.html");			//Page next after logout. (Please do not edit)
define('MIN_PASSWORDLEN', 5);			//Minimum length of an accepted password. (Please do not edit)
define('ZIPCMD', "/usr/bin/zip");			//Path to 'zip' command. (Please do not edit)
define('UNZIPCMD', "/usr/bin/7z");			//Path to 'unzip' or '7z' command. (Please do not edit)
define('UNRARCMD', "/usr/bin/unrar");			//Path to 'unrar' command. (Please do not edit)
define('DB_ADDRESS', 'localhost');			//Address of MariaDB/MySQL database ('localhost') for a local machine (Please do not edit)
define('DB_USER', 'root');			//Username of MariaDB/MySQL database used (Please do not edit)
define('DB_PASSWD', 'wqeMDwqe256');			//Password of MariaDB/MySQL database used (Please do not edit)
define('WARNINGS_FATAL', false);			//Set 'true' if system warnings aborts execution. (Please do not edit)
define('DEBUG_LOG', "");			//Path if certain information is logged into log file or "" not do logging. (Please do not edit)
define('DEFAULT_CACHE_MAX', 500);			//Set cache size (in GB) (Please do not edit)
define('CACHE_MAX_LIMIT', 1000);			//Set maximum cache size that can be set (in GB) (Please do not edit)
?>

