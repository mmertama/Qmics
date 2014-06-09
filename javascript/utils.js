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

utils.js 
* General javascript utility functions 

Known issues: 
*None
 
*DESCRIPTION*********************************************************************/ 

function toggleView(selId, targetId){
            var e = document.getElementById(selId);
            var t = document.getElementById(targetId);
            var selValue = '';
            var tarValue = '';
            var con = e.innerHTML;
            if(con.match(/Hide/i)){
                selValue = "Show";
                tarValue = 'none';
            }
            else if(con.match(/Show/i)){
                selValue = "Hide";
                tarValue = "";
            }
        if(selValue != ''){
            e.innerHTML = selValue;
            t.style.display = tarValue;
            }
        }


function makeUintRequest(query, params, func, errfunc){
    if(params.length > 0)
        query += "?";
    var i = 0; 
    while(true && params.length > 1){
        query += params[i] + "=" + params[i + 1];
        i += 2;
        if(i >= params.length)
            break;
        query += "&";
    }
    
    var req = new XMLHttpRequest();
    req.open('GET', query, true);
    req.responseType = 'arraybuffer';
    req.onload = function(e){
        if(req.readyState == 4){
            if(req.status == 200){
                var buffer = req.response;
                if (buffer) {
                    var v = new DataView(buffer); 
                    var array = new Array();
                    for(var ii = 0; ii < v.byteLength; ii += 4)
                        array.push(v.getUint32(ii, false));
                    func(array);
                }
            }
            else{
                if(errfunc != 'undefined')
                    errfunc(req.statusText);
            }
        }
    }
    req.send();
    }


function uintArrayToString(array){
    var str = new String();
    for(var ii = 0; ii < array.length; ii++){
        str += String.fromCharCode((array[ii] >> 24) & 0xFF);
        str += String.fromCharCode((array[ii] >> 16) & 0xFF);
        str += String.fromCharCode((array[ii] >> 8)  & 0xFF);
        str += String.fromCharCode((array[ii]) & 0xFF);
    }
  return window.btoa(str);
}

function getSelectedOption(id){
    var opt = document.getElementById(id);
    var flist = opt.getElementsByTagName("option");
    for(var o = 0; o < flist.length; o++){
        if(flist[o].selected){
            return  flist[o];
            }
    }
    return null;
}

function fatal(err){
    window.alert(err);
}
