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

configuration.php 
    Qmics general settins for installer

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

ini_set('display_errors', 'On');
ini_set('auto_detect_line_endings', true);
error_reporting(E_ALL | E_STRICT);  

define('LIBRARY', "../../myprivate_library");
define('COVER_CACHE',"../../myprivate_library/cache/covers");
define('CURRENT_CACHE',"../../myprivate_library/cache/current");
define('ERROR_LOG',"../../myprivate_library/errorlog.txt");
define('COVER_WIDTH', 200);
define('COVER_HEIGHT', 200);
define('LOGOUT_PAGE',"../qmics.html");
define('MIN_PASSWORDLEN', 5);
define('ZIPCMD', "/usr/syno/bin/zip");
define('UNZIPCMD', "/usr/syno/bin/unzip");
define('UNRARCMD', "/usr/syno/bin/unrar");
define('DB_ADDRESS', 'localhost');
define('DB_USER', 'qmics');
define('DB_PASSWD', 'qmicspasswd');
define('WARNINGS_FATAL', false);
?>