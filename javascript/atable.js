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

atable.js 
* implements functionality for the admin's 'per comic' setting table - tool admin to adjust comics access rights.  
* It contains filter framework and its few implementations.

Known issues: 
*Set values does not work with Firefox properly - not fixed as onlt admin shall
access the table.
 
*DESCRIPTION*********************************************************************/    
    
function Filter(col){
    this.col = col;
    this.actived = false;
}

Filter.prototype.isActived = function(col){
    return (col == this.col && this.actived);
}

function TextFilter(col){
    Filter.call(this, col);
    this.string = "";
}

TextFilter.prototype.isActived = Filter.prototype.isActived;

function getInputByName(parent, name){
    var inputs = parent.getElementsByTagName("input");
    for(ii = 0; ii < inputs.length; ii++)
        if(inputs[ii].name == name)
            return inputs[ii];
    return undefined;
}

TextFilter.prototype.setFilter = function(id){
    var opt = document.getElementById(id);
    var text = getInputByName(opt, "text_filter");
    if(text == undefined) throw "Field not found from:" + id;
    this.string = text.value;
}

TextFilter.prototype.filter = function(string){
    return string.indexOf(this.string) >= 0;
}

function RangeFilter(col){
    Filter.call(this, col);
    this.min = -1;
    this.max = -1;
}
RangeFilter.prototype.isActived = Filter.prototype.isActived;

RangeFilter.prototype.setFilter = function(id){
    var opt = document.getElementById(id);
    var minf = getInputByName(opt, "min_filter");
    var maxf  = getInputByName(opt, "max_filter");
    var min = parseInt(minf.value);
    var max = parseInt(maxf.value);
    if(isNaN(min) || isNaN(max) || min < 0 || max < 0){
        return;
    }
    if(min > max){
        if(this.min == min){
            maxf.value = min;
            max = min; 
        }
        if(this.max == max){
            minf.value = max;
            min = max;
        }
    }
    this.min = min;
    this.max = max;
}

RangeFilter.prototype.filter = function(string){
    var int = parseInt(string);
    return int >= this.min && int <= this.max;
}

function OptionFilter(col){  
    Filter.call(this, col);
    this.options = new Array();
}
OptionFilter.prototype.isActived = Filter.prototype.isActived;

OptionFilter.prototype.setFilter = function(id){
    var opt = document.getElementById(id);
    var flist = opt.getElementsByTagName("option");
    this.options = [];
    for(var o = 0; o < flist.length; o++){
        if(flist[o].selected)
            this.options.push(flist[o].value);
    }
}

OptionFilter.prototype.filter = function(string){
    for(var i = 0; i < this.options.length; i++){
        if(this.options[i] == string)
            return true;
    }
    return false;
}

function CheckFilter(col){  
    Filter.call(this, col);
    this.checks = {};
}

CheckFilter.prototype.isActived = Filter.prototype.isActived;

CheckFilter.prototype.setFilter = function(id){
    var opt = document.getElementById(id);
    var flist = opt.getElementsByTagName("input");
    this.checks = {};
    for(var o = 0; o < flist.length; o++){
        if(flist[o].id == "" || flist[o].id == null)
            this.checks[flist[o].value] = flist[o].checked;
    }
}

CheckFilter.prototype.filter = function(string){
    var patt = /<\s*input\s+type\s*=\s*['"]checkbox["']\s+value\s*=\s*['"]([a-z0-9_]+)["']\s+id\s*=\s*['"]([a-z0-9_]+)['"]\s*/gi;
    var matches;
    while((matches = patt.exec(string)) != null){
        for(var key in this.checks){
            if(key == matches[1]){
                var checked = document.getElementById(matches[2]).checked;
                if(checked != this.checks[key]){
                    return false;    
                }
            }   
        }
    }
    return true;
}

function TableFilter(tableName){
    this.table = document.getElementById(tableName);
    if(this.table == null) throw "Null pointer";
    this.filters = new Object();
}

TableFilter.prototype.addFilter = function(colId, filterId, type){
    var col = -1;
    if(typeof(colId) == "string")
        col = this.getColIndex(colId);
    else
        col = colId - 1;
    if(col < 0) throw "Column not found:" + colId;
    if(undefined == document.getElementById(filterId)) throw "invalid id:" + filterId;
    if(type == "option")
        this.filters[filterId] = new OptionFilter(col);
    if(type == "text")
        this.filters[filterId] = new TextFilter(col);
    if(type == "range")
        this.filters[filterId] = new RangeFilter(col);
    if(type == "check")
        this.filters[filterId] = new CheckFilter(col);
    if(this.filters[filterId] == null) throw "Bad filter type:" + type;
}


TableFilter.prototype.getColIndex = function (vfield){
    var cells = this.table.getElementsByTagName("th"); 
    for (var i = 0; i < cells.length; i++) {
        if(cells[i].innerHTML == vfield){
            return i;
        }
    }
    return -1;
}

TableFilter.prototype.setActive = function(filterId, bool){
    console.log("activate" + filterId + ":" + bool);
    this.filters[filterId].actived = bool;
    if(bool)
        this.setFilter(filterId);
    else
        this.doFilter();
}

TableFilter.prototype.setFilter = function(filterId){
    this.filters[filterId].setFilter(filterId);
    if(this.filters[filterId].actived)
        this.doFilter();
}

TableFilter.prototype.doFilter = function (){
    for(var r = 1; r < this.table.rows.length; r++){
        var pass = true;
        var table = this;
        var row = this.table.rows[r];
        Object.keys(this.filters).forEach(function(k){
            for(var c = 0; c < row.cells.length && pass; c++ ){
                var col = row.cells[c];
                var f = table.filters[k];
                if(f.isActived(c) && !f.filter(col.innerHTML)){
                    pass = false;
                }    
            }
        });
       
        if(pass){
             row.style.display = "";
        }
        else{
            row.style.display = "none"; 
        }
    }
}

function findParentByType(me, type){
    if(me == undefined) 
        return undefined;
    if(me.tagName.toLocaleLowerCase() == type.toLowerCase())
        return me;
    return findParentByType(me.parentNode, type); 
}

TableFilter.prototype.setAccessTo = function(param, isSet){
    var inputs = this.table.getElementsByTagName("input");
    var patt = /\d+_(.+)/;
    for(var ii = 0; ii < inputs.length; ii++){
        var row = findParentByType(inputs[ii], "tr");
        if(row == undefined) throw "Element not in table";
        if(row.style.display == 'none')
            continue;
        if(inputs[ii].type = "checkbox"){
            var m = patt.exec(inputs[ii].id);
            if(m != null){
                typeVal = m[1];
                if(typeVal == param){
                    inputs[ii].checked = isSet;
                    
                }
            }
        }
    }
}


function accessFromBytes(bytes){
    var array = {
        "read": (bytes & 0x1) != 0,
        "download": (bytes & 0x10) != 0};
    return array;
}

function accessToBytes(array){
    var bytes = 0;
    if("read" in array && array["read"])
        bytes |= 0x1;
    if("download" in array && array["download"])
        bytes |= 0x10;
    return bytes;
}

function positionOf(uid, array){
    for(var o = 0; o < array.length; o += 2){
        if(array[o] == uid){
            return o;
        }
    }
    return undefined;
}



TableFilter.prototype.setAccessValues = function(array){
    var inputs = this.table.getElementsByTagName("input");
    var patt = /(\d+)_(.+)/;
    for(var ii = 0; ii < inputs.length; ii++){
        if(inputs[ii].type = "checkbox"){
            var m = patt.exec(inputs[ii].id);
            if(m != null){
                uidVal = m[1];
                typeVal = m[2];
                var p = positionOf(uidVal, array);
                if(p != undefined){
                    var bytes = array[p + 1];
                    var isSet = accessFromBytes(bytes)[typeVal];
                    inputs[ii].checked = isSet;
                }
            }
        }
    }
    this.doFilter();
}

TableFilter.prototype.getAccessValues = function(){
    var inputs = this.table.getElementsByTagName("input");
    var array = new Array;
    var patt = /(\d+)_(.+)/;
    for(var ii = 0; ii < inputs.length; ii++){
        if(inputs[ii].type = "checkbox"){
            var m = patt.exec(inputs[ii].id);
            if(m != null){
                uidVal = m[1];
                typeVal = m[2];
                var p = positionOf(uidVal, array);
                if(p == undefined){
                    array.push(uidVal);
                    var a = [];
                    a[typeVal] = inputs[ii].checked; 
                    var b = accessToBytes(a);
                    array.push(b);
                }
                else{
                    var bytes = array[p + 1];
                    var b = accessFromBytes(bytes);
                    b[typeVal] = inputs[ii].checked;
                    newBytes = accessToBytes(b);   
                    array[p + 1] = newBytes;
                }
            }
        }
    }
    return array;
}


