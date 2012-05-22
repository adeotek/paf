var PAF = {
	procOn : 0,
	history : [],
	historyIndex : 0,
	updateIndicator : function(with_status) {
		var s = document.getElementById('pafStatus');
		if (s && with_status==1) {
			s.style.display = PAF.procOn ? '' : 'none';
		}//if (s && with_status==1)
	},//updateIndicator : function(with_status)
	getReq : function() {
		if (window.XMLHttpRequest) {
			try {
				return new XMLHttpRequest();
			} catch (e) {
			}//try
		} else if (window.ActiveXObject) {
			try {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				try {
					return new ActiveXObject("Msxml2.XMLHTTP");
				} catch (e) {
				}//try
			}//try
		}//if (window.XMLHttpRequest)
	},//getReq : function()
	run : function(php, id, act, property, session_id, request_id, use_history, with_status, js_script) {
		var end_js_script = '';
		if(js_script && js_script!='') {
			var scripts = js_script.split('~');
			if(scripts[0]) {
				PAF.RunScript(scripts[0]); 
			}//if(scripts[0])
			if(scripts[1]) {
				end_js_script = scripts[1];
			}//if(scripts[1])
		}//if(js_script && js_script!='')
		PAF.procOn++;
		var requestString = 'req=' + (PAF_HTTPK ? encodeURIComponent(PAF.rc4(PAF_HTTPK,php)) : php) + '^!PAF!^' + session_id + '^!PAF!^' + (request_id || '') + '&rnd=' + Math.random();
		if (use_history) {
			PAF.addHistory(function() {
				PAF.procOn++;
				PAF.SendRequest(id, act, property, requestString, with_status, end_js_script);
			});//PAF.addHistory(function()
		}//if (use_history)
		PAF.SendRequest(id, act, property, requestString, with_status, end_js_script);
	},//run : function(php, id, act, property, session_id, request_id, use_history, with_status, js_script)
	RunScript : function(scriptString) {
		if(!scriptString || scriptString=='') return false;
		var script_type = 'eval';
		var script_val = '';
		var script = scriptString.split('|');
		if(script[0] && script[1]) {
			if(script[0]!='') {
				script_type = script[0];
			}//if(script[0] && script[0]!='')
			script_val = script[1];
		}else{
			if(script[0]) {
				script_val = script[0];
			}//if(script[1])
		}//if(script[0] && script[1])
		if(!script_val || script_val=='' || !script_type || script_type=='') return false;
		switch (script_type){
			case 'file':
				$.getScript(script_val);
				break;
			case 'eval':
				eval(script_val);
				break;
			default:
				return false;
		}//switch (script_type)
	},//RunScript : function(scriptString)
	SendRequest : function(id, act, property, requestString, with_status, js_script) {
		var req = PAF.getReq();
		PAF.updateIndicator(with_status);
		req.open('POST', PAF_PATH + 'paf.php', true);
		req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		req.send(requestString);
		req.onreadystatechange = function() {
			if (req.readyState==4 && req.status==200) {
				actions = req.responseText.split('^paf!split^');
				if (id) {
					PAF.Put(actions[0], id, act, property)
				}//if (id)
				if (actions[1]) {
					eval(actions[1]);
				}//if (actions[1])
				PAF.procOn--;
				PAF.updateIndicator(with_status);
				if(js_script && js_script!='') {
					PAF.RunScript(js_script); 
				}//if(js_script && js_script!='')
			}//if (req.readyState==4 && req.status==200)
		}//req.onreadystatechange = function()
	},//SendRequest : function(id, act, property, requestString, with_status, js_script)
	Put : function(content, id, act, property) {
		if (!id) return;
		if (property) {
			try {
				var ob = document.getElementById(id);
			} catch (e) {
			}//try
			if (act == 'p') {
				ob[property] = content + ob[property];
			} else if (act == 'a') {
				ob[property] += content;
			} else if (property.split('.')[0] == 'style') {
				ob.style[property.split('.')[1]] = content;
			} else if (ob) {
				ob[property] = content;
			}//if (act=='p')
		} else {
			window[id] = content;
		}//if (property)
	},//Put : function(content, id, act, property)
	Get : function(id, property) {
		if (!property) {
			return PAF.phpSerialize(id);
		}//if (!property)
		return PAF.phpSerialize(document.getElementById(id)[property]);
	},//Get : function(id, property)
	phpSerialize : function(v) {
		var ret = '';
		if (typeof (v) == 'object') {
			var len = v.length;
			if (len) {
				ret = 'a:' + len + ':{';
				for ( var i = 0; i < len; i++) {
					ret += 'i:' + i + ';'
					ret += 's:' + (v[i] + '').length + ':"' + encodeURIComponent(v[i]) + '";'
				}//for ( var i = 0; i < len; i++)
				ret += '}';
			} else {
				len = 0;
				for ( var i in v) {
					len++;
				}//for ( var i in v)
				ret = 'a:' + len + ':{';
				for ( var i in v) {
					ret += 's:' + i.length + ':"' + i + '";'
					ret += 's:' + v[i].length + ':"' + encodeURIComponent(v[i]) + '";'
				}//for ( var i in v)
				ret += '}';
			}//if (len)
		} else {
			ret += 's:' + (v + '').length + ':"' + encodeURIComponent(v) + '";'
		}//if (typeof (v) == 'object')
		return ret;
	},//phpSerialize : function(v)
	SetStyle : function(ob, styleString) {
		document.getElementById(ob).style.cssText = styleString;
	},//SetStyle : function(ob, styleString)
	rc4 : function(pwd, data) {
		var pwd_length = pwd.length;
		var data_length = data.length;
		var key = [];
		var box = [];
		var cipher = '';
		var k;
		for (var i = 0; i < 256; i++) {
			key[i] = pwd.charCodeAt(i % pwd_length);
			box[i] = i;
		}//for (var i = 0; i < 256; i++)
		for (var j = i = 0; i < 256; i++) {
			j = (j + box[i] + key[i]) % 256;
			tmp = box[i];
			box[i] = box[j];
			box[j] = tmp;
		}//for (var j = i = 0; i < 256; i++)
		for (var a = j = i = 0; i < data_length; i++) {
			a = (a + 1) % 256;
			j = (j + box[a]) % 256;
			tmp = box[a];
			box[a] = box[j];
			box[j] = tmp;
			k = box[((box[a] + box[j]) % 256)];
			cipher += String.fromCharCode(data.charCodeAt(i) ^ k);
		}//for (var a = j = i = 0; i < data_length; i++)
		return cipher;
	},//rc4 : function(pwd, data)
	getForm : function(f) {
		var vals = {};
		for (var i = 0; i < f.length; i++){
			if (f[i].id) {
				vals[f[i].id] = f[i].value;
			}//if (f[i].id)
		}//for (var i = 0; i < f.length; i++)
		return vals;
	},//getForm : function(f)
	addHistory : function(value) {
		var historyURL1 = PAF_PATH + 'PAF.php/1/';
		var historyURL2 = PAF_PATH + 'PAF.php/2/';
		PAF.historyValue = value;
		PAF.lastHistoryURL = PAF.lastHistoryURL == historyURL2 ? historyURL1 : historyURL2;
		PAF.pafHistory.location.href = PAF.lastHistoryURL + '#' + (PAF.historyIndex - 0 + 1);
	},//addHistory : function(value)
	monitorHistory : function() {
		PAF.lastHistoryURL = PAF.pafHistory.location.pathname;
		var currentHash = PAF.pafHistory.location.hash;
		var index = currentHash.split('#')[1];
		if (PAF.lastHash != currentHash) {
			if (PAF.historyValue == null && index < PAF.history.length) {
				PAF.historyIndex = index;
			} else {
				if (PAF.historyIndex < PAF.history.length - 1) {
					PAF.history.splice(PAF.historyIndex - 0 + 1, PAF.history.length - PAF.historyIndex - 1);
				}//if (PAF.historyIndex < PAF.history.length - 1)
				PAF.historyIndex = PAF.history.length;
				PAF.history[PAF.historyIndex] = PAF.historyValue;
			}//if (PAF.historyValue == null && index < PAF.history.length)
			if (!currentHash && PAF.history.length && PAF.onBackToStart) {
				PAF.onBackToStart();
			} else if (PAF.historyValue != PAF.history[index]) {
				PAF.onHistoryChange(index);
			}//if (!currentHash && PAF.history.length && PAF.onBackToStart)
			PAF.lastHash = currentHash;
			PAF.historyValue = null;
		}//if (PAF.lastHash != currentHash)
		PAF.historyInterval = window.setTimeout(PAF.monitorHistory, 100);
	}//monitorHistory : function()
}//var PAF