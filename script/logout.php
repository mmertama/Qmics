<!DOCTYPE HTML>
<html>
	<head>
	<meta charset="utf-8">
	<!--meta name="viewport" content="width=device-width,user-scalable=no"-->
        <link rel="stylesheet" type="text/css" href="../style/login.css">
	</head>
    <script type="text/javascript">
        function logout(){
            location.href = "<?php 
   
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

logout.php 
   logout page
   

Known issues: 
* None
 
*DESCRIPTION*********************************************************************/ 

    require_once "configuration.php";
    echo LOGOUT_PAGE;
    ?>";
        }
    </script>
    <body>
        <div class = "login">
            Invalid username or password!
            <input  id="btn" type="button" value="Ok" onclick="logout();">
        </div>
    </body>
    </html>