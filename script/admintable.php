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

admintable.php 
    Generates the per-comic setting table for admin

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

require_once "utils.php";
require_once "configuration.php";

function addFilter($db){
    echo "<script type=\"text/javascript\" src=\"../javascript/utils.js\">\n";
    echo "</script>\n\n";
    echo "<h2>Filter <small id=\"filtertoggleid\" class=\"click_item\" onclick=\"toggleView('filtertoggleid', 'filters')\">Hide</small></h2>";
    echo "<div id=\"filters\">\n\n";
    
    $comicResult = $db->query("SELECT `sourceurl`, `comicid`  FROM `comics`");
    fatalIfFalse($comicResult, $db->error);
   
    $plist = array();
    $tlist = array();
    $minId = 0xFFFFFFF;
    $maxId = 0;
    while($comicsData = $comicResult->fetch_array(MYSQLI_ASSOC)){
        $plist[] = getHeader($comicsData['sourceurl'], "publisher");
        $tlist[] = getHeader($comicsData['sourceurl'], "title");
       
        if($comicsData['comicid'] < $minId)
            $minId = $comicsData['comicid'];
        if($comicsData['comicid'] > $maxId)
            $maxId = $comicsData['comicid'];
    }
    $comicResult->close();
    
    $plist = array_unique($plist, SORT_STRING );
    $tlist = array_unique($tlist, SORT_STRING );
    sort($plist);
    sort($tlist);
    
    echo "<script type=\"text/javascript\" src=\"../javascript/atable.js\">\n";
    echo "</script>\n";
    echo "<div id=\"f_publisher\">Publisher";
    echo "<input type=\"checkbox\" id=\"f_pub_tick\" onchange=\"actived_change('f_publisher', 'f_pub_tick')\">\n";
    echo "<select name=\"opt_filter\" onchange=\"filter_change('f_publisher')\" multiple>\n"; 
    foreach($plist as $p){
        echo("<option value=\"$p\" selected>$p</option>\n");
    }
    echo "</select></div>\n";
    echo "<div id=\"f_title\">Titles";
    echo "<input  type = \"checkbox\" id=\"f_tit_tick\" onchange=\"actived_change('f_title', 'f_tit_tick')\">\n";
    echo "<select name=\"opt_filter\" onchange=\"filter_change('f_title')\" multiple>\n";
    foreach($tlist as $t){
        echo("<option value=\"$t\">$t</option>\n");
    }
    echo "</select></div>\n";
    
    echo "<div id=\"f_comicname\">Comic name ";
    echo "<input  type = \"checkbox\" id=\"f_com_tick\" onchange=\"actived_change('f_comicname', 'f_com_tick')\">\n";
    echo "<input name=\"text_filter\" type=\"text\" onchange=\"filter_change('f_comicname')\"></div>\n";
    
    echo "<div id=\"f_range\">Id Range ";
    echo "<input type=\"checkbox\" id=\"f_ran_tick\" onchange=\"actived_change('f_range', 'f_ran_tick')\">\n";
    echo "Min <input name=\"min_filter\" type=\"number\"  min=\"$minId\" max=\"maxId\" value=\"$minId\" onchange=\"filter_change('f_range')\">\n";
    echo "Max <input name=\"max_filter\" type=\"number\"  min=\"$minId\" max=\"maxId\" value=\"$maxId\" onchange=\"filter_change('f_range')\">\n";
    echo "</div>\n";
    
    echo "<div id=\"f_comicaccess\">Access ";
    echo "<input  type = \"checkbox\" id=\"f_acc_tick\" onchange=\"actived_change('f_comicaccess', 'f_acc_tick')\"/>\n";
    $accessKeys = array_keys(accessFromBytes(0));
    foreach($accessKeys as $key){
        echo "$key <input name=\"check_filter\" type=\"checkbox\" value=\"$key\"onchange=\"filter_change('f_comicaccess')\"/>";
    }
    echo "</div>\n";
    echo "</div>\n";  
}

function addTableScript($userIndex){
    echo "<script type=\"text/javascript\">\n";
    echo "window.onLoad=setTableUser('user_list');\n";
    echo "var filter = new TableFilter(\"adminTable\");\n";
    echo "window.filter = filter;";
    echo "filter.addFilter(\"Publisher\", \"f_publisher\", \"option\");\n";
    echo "filter.addFilter(\"Title\", \"f_title\", \"option\");\n";
    echo "filter.addFilter(\"comicname\", \"f_comicname\", \"text\");\n";
    echo "filter.addFilter(\"comicid\", \"f_range\", \"range\");\n";
    echo "filter.addFilter(5, \"f_comicaccess\", \"check\");\n\n";
    echo "function actived_change(id, checkId){\n";
    echo "var selected = document.getElementById(checkId).checked;\n";
    echo "window.filter.setActive(id, selected);}\n\n";
    echo "function filter_change(id){\n";
    echo "window.filter.setFilter(id);}\n\n";
    echo "function setTableUser(id){\n";
    echo "var opt = getSelectedOption(id);\n";
    echo "var uid = opt.value;\n";
    echo "if(uid != null){\n";
    
    $accessKeys = array_keys(accessFromBytes(0));
    foreach($accessKeys as $key){
        echo "\tdocument.getElementById('userfield_$key').checked = false\n";
    }
    
    echo "\tdocument.getElementById('user_col').innerHTML = opt.innerHTML\n";
    echo "\tdocument.getElementById('updateTarget').value=uid;\n";
    $requestUrl = "'../script/qmics_getimage.php'";
    echo "\tmakeUintRequest($requestUrl, ['action', 'access', 'user', 'admin', 'id', uid], function(array){\n";
    echo "\t\tfilter.setAccessValues(array);}, function(e){fatal(e);});}}\n\n";
    echo "function fillForm(updateValues){\n";
    echo "\tvar values = window.filter.getAccessValues();\n";
    echo "\tvar bytes = uintArrayToString(values);\n";
    echo "\tdocument.getElementById(updateValues).value = bytes;}\n";
    echo "function changeValues(cid, key){\n";
    echo "var selected = document.getElementById(cid).checked;\n";
    echo "window.filter.setAccessTo(key, selected);}\n";
    echo "</script>\n\n";    
}


function addUserList($db, $preset){
    $userQuery = $db->query("SELECT `username`, `userid` FROM `userdata` ORDER BY `username`");
    fatalIfFalse($userQuery, $db->error);
    echo "<h2>User properties</h2>\n";
    echo "<div> User \n";
    echo "<select id=\"user_list\" onchange=\"setTableUser('user_list')\">\n"; 
    
    while($userData = $userQuery->fetch_array(MYSQLI_ASSOC)){
        $user = $userData['username'];
        $id = $userData['userid'];
        echo "<option value=\"$id\"";
        if(($preset == "" && $user == "admin") || $id == $preset){
            echo " selected=\"selected\"";
        }
        echo ">$user</option>\n";
    }
    echo "</select></div>\n";
    $userQuery->close();
}

function generateAdminTable($db, $preset){
    $cols = array('comicname', /*'coverimage',*/ 'comicid');
    addUserList($db, $preset);
    addFilter($db);    
    
    //get every comic
    $comicResult = $db->query("SELECT `" . join('`,`', $cols) . "`, `sourceurl`  FROM `comics` ORDER BY `sourceurl`");
    fatalIfFalse($comicResult, $db->error);
    
    echo("<table id=\"adminTable\">\n");
    echo("<tr>\n");
    echo("<th>Publisher</th><th>Title</th><th>" . join("</th> <th>", $cols)  . "</th>");
    
    
    $accessKeys = array_keys(accessFromBytes(0));
    
    echo("<th><div id=\"user_col\">User</div>");
    foreach($accessKeys as $key){
        echo("<div class=\"tickbox\">$key: <input type = \"checkbox\" id=\"userfield_"."$key\" onchange =\"changeValues('userfield_"."$key', '$key')\"></div>");
    }
    echo "</th>";
    echo("</tr>\n");
    
     
 
    
    while($comicsData = $comicResult->fetch_array(MYSQLI_ASSOC)){
        echo("<tr>\n");
        echo("<td>" . getHeader($comicsData['sourceurl'], "publisher") . "</td>");
        echo("<td>" . getHeader($comicsData['sourceurl'], "title") . "</td>");
        echo("<td>" . $comicsData['comicname'] . "</td>");
        echo("<td>" . $comicsData['comicid'] . "</td>");
        echo("<td>");
        foreach($accessKeys as $key){
            $keyname = $comicsData['comicid'] . "_" . $key;
            echo("<div class=\"tickbox\">$key: <input type=\"checkbox\" value=\"$key\" id=\"$keyname\"");
            echo ("/></div>");
            }
        echo("</td>");
        echo("</tr>\n");
    }
    echo("</table>");
    $comicResult->close();
    
    echo "<form action=\"qmics_admin_db.php\" onsubmit=\"fillForm('updateValues')\" method=\"post\" enctype='multipart/form-data'>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
    echo "<input type=\"hidden\" name=\"user\" value=\"admin\">\n";
    echo "<input type=\"hidden\" id=\"updateTarget\" name=\"target\" value=\"\">\n";
    echo "<input type='hidden' id='updateValues' name='values' value='' />\n";
    echo "<input type=\"submit\" value=\"Submit\"/>\n";
    echo "</form>";
    addTableScript(count($cols));
    
}

?>