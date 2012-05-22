var paf = {
	procOn : 0,
	history : [],
	historyIndex : 0,
	updateIndicator : function(with_status) {
		var s = document.getElementById('sajaStatus');
		if (s && with_status==1) {
			$('#sajaStatus').css('height',Math.max(document.body.clientHeight,window.innerHeight)+'px');
			s.style.display = saja.procOn ? '' : 'none';
		}
	},
	getReq : function() {
		if (window.XMLHttpRequest)
			try {
				return new XMLHttpRequest();
			} catch (e) {
			}
		else if (window.ActiveXObject)
			try {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				try {
					return new ActiveXObject("Msxml2.XMLHTTP");
				} catch (e) {
				}
			}
	},

	run : function(php, id, act, property, session_id, request_id, use_history, current_section, with_status, js_script) {
		var end_js_script = '';
		if(js_script && js_script!='') {
			var scripts = js_script.split('~');
			//alert(scripts[0]+' :: '+scripts[1]);
			if(scripts[0]){
				saja.RunScript(scripts[0]); 
			}
			if(scripts[1]) {
				end_js_script = scripts[1];
			}
		}
		saja.procOn++;
		var requestString = 'req='
				+ (SAJA_HTTP_KEY ? encodeURIComponent(saja.rc4(SAJA_HTTP_KEY,
						php)) : php) + '<!SAJA!>' + session_id + '<!SAJA!>'
				+ (request_id || '');

		// '&cursection='+current_section e introdus de Horia pentru a trimite
		// automat sectiunea in request
		requestString += '&cursection=' + current_section + '&rnd='
				+ Math.random();
		if (use_history) {
			saja.addHistory(function() {
				saja.procOn++;
				saja.SendRequest(id, act, property, requestString, with_status, end_js_script);
			});
		}
		saja.SendRequest(id, act, property, requestString, with_status, end_js_script);
	},
	
	RunScript : function(scriptString) {
		//alert(scriptString);
		if(!scriptString || scriptString=='') return false;
		var script_type = 'eval';
		var script_val = '';
		var script = scriptString.split('|');
		//alert(script[0]+' :: '+script[1]);
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
		//alert(script_type+' :: '+script_val);
		if(!script_val || script_val=='' || !script_type || script_type=='') return false;
		switch (script_type){
			case 'file':
				//alert(script_val);
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
		//alert(js_script);
		var req = saja.getReq();
		saja.updateIndicator(with_status);
		req.open('POST', SAJA_PATH + 'saja.process.php', true);
		req.setRequestHeader('Content-type',
				'application/x-www-form-urlencoded');
		req.send(requestString);
		req.onreadystatechange = function() {
			if (req.readyState == 4 && req.status == 200) {
				actions = req.responseText.split('<saja_split>');
				if (id)
					saja.Put(actions[0], id, act, property)
				if (actions[1])
					eval(actions[1]);
				saja.procOn--;
				saja.updateIndicator(with_status);
				if(js_script && js_script!='') {
					saja.RunScript(js_script); 
				}
			}
		}
	},

	Put : function(content, id, act, property) {
		if (!id)
			return;
		if (property) {
			try {
				var ob = document.getElementById(id);
			} catch (e) {
			}
			if (act == 'p')
				ob[property] = content + ob[property];
			else if (act == 'a')
				ob[property] += content;
			else if (property.split('.')[0] == 'style')
				ob.style[property.split('.')[1]] = content;
			else if (ob)
				ob[property] = content;
		}
		else {
			window[id] = content;
		}
		$('.ErrorMessageModalLayer').css('height',Math.max(document.body.clientHeight,window.innerHeight)+'px');	
	},

	Get : function(id, property) {
		if (!property)
			return saja.phpSerialize(id);
		return saja.phpSerialize(document.getElementById(id)[property]);
	},

	phpSerialize : function(v) {
		var ret = '';
		if (typeof (v) == 'object') {
			var len = v.length;
			if (len) {
				ret = 'a:' + len + ':{';
				for ( var i = 0; i < len; i++) {
					ret += 'i:' + i + ';'
					ret += 's:' + (v[i] + '').length + ':"'
							+ encodeURIComponent(v[i]) + '";'
				}
				ret += '}';
			} else {
				len = 0;
				for ( var i in v)
					len++;
				ret = 'a:' + len + ':{';
				for ( var i in v) {
					ret += 's:' + i.length + ':"' + i + '";'
					ret += 's:' + v[i].length + ':"' + encodeURIComponent(v[i])
							+ '";'
				}
				ret += '}';
			}
		} else {
			ret += 's:' + (v + '').length + ':"' + encodeURIComponent(v) + '";'
		}
		return ret;
	},

	SetStyle : function(ob, styleString) {
		document.getElementById(ob).style.cssText = styleString;
	},

	rc4 : function(pwd, data) {
		var pwd_length = pwd.length;
		var data_length = data.length;
		var key = [];
		var box = [];
		var cipher = '';
		var k;
		for ( var i = 0; i < 256; i++) {
			key[i] = pwd.charCodeAt(i % pwd_length);
			box[i] = i;
		}
		for ( var j = i = 0; i < 256; i++) {
			j = (j + box[i] + key[i]) % 256;
			tmp = box[i];
			box[i] = box[j];
			box[j] = tmp;
		}
		for ( var a = j = i = 0; i < data_length; i++) {
			a = (a + 1) % 256;
			j = (j + box[a]) % 256;
			tmp = box[a];
			box[a] = box[j];
			box[j] = tmp;
			k = box[((box[a] + box[j]) % 256)];
			cipher += String.fromCharCode(data.charCodeAt(i) ^ k);
		}
		return cipher;
	},

	getForm : function(f) {
		var vals = {};
		for ( var i = 0; i < f.length; i++)
			if (f[i].id)
				vals[f[i].id] = f[i].value;
		return vals;
	},

	addHistory : function(value) {
		var historyURL1 = SAJA_PATH + 'saja.php/1/';
		var historyURL2 = SAJA_PATH + 'saja.php/2/';
		saja.historyValue = value;
		saja.lastHistoryURL = saja.lastHistoryURL == historyURL2 ? historyURL1
				: historyURL2;
		saja.sajaHistory.location.href = saja.lastHistoryURL + '#'
				+ (saja.historyIndex - 0 + 1);
	},

	monitorHistory : function() {
		saja.lastHistoryURL = saja.sajaHistory.location.pathname;
		var currentHash = saja.sajaHistory.location.hash;
		var index = currentHash.split('#')[1];
		if (saja.lastHash != currentHash) {
			if (saja.historyValue == null && index < saja.history.length)
				saja.historyIndex = index;
			else {
				if (saja.historyIndex < saja.history.length - 1)
					saja.history.splice(saja.historyIndex - 0 + 1,
							saja.history.length - saja.historyIndex - 1);
				saja.historyIndex = saja.history.length;
				saja.history[saja.historyIndex] = saja.historyValue;
			}
			if (!currentHash && saja.history.length && saja.onBackToStart)
				saja.onBackToStart();
			else if (saja.historyValue != saja.history[index])
				saja.onHistoryChange(index);
			saja.lastHash = currentHash;
			saja.historyValue = null;
		}
		saja.historyInterval = window.setTimeout(saja.monitorHistory, 100);
	}

}