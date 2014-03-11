/**
 * PAF (PHP AJAX Framework) ajax requests javascript file.
 *
 * The PAF javascript object used on ajax requests.
 * Copyright (c) 2004 - 2013 Hinter Software
 * License    LICENSE.txt
 *
 * @author     Hinter Software
 * @version    1.2.0
 */
var PAFReq = {
	procOn : 0,
	reqSeparator : ']!PAF![',
	actSeparator : ']!paf!s![',
	oninit_func : 'DestroyCkEditor(window.name)',
	onPAFReqCompleteEvent : true,
	updateProcOn : function(add_val,with_status) {
		PAFReq.procOn += add_val;
		if(PAFReq.procOn<=0) {
			PAFReq.updateIndicator(1,0);
		} else {
			PAFReq.updateIndicator(with_status,1);
		}//if(PAFReq.procOn<=0)
	},//END updateProcOn
	updateIndicator : function(with_status,new_status) {
		if ($('#PAFReqStatus').length>0 && with_status==1) {
			if(new_status==1) {
				$('#PAFReqStatus').css('height',Math.max($(document).outerHeight(),window.innerHeight)+'px');
				$('#PAFReqStatus').css('display','');
			} else {
				$('#PAFReqStatus').css('display','none');
			}//if(new_status==1)
		}//if ($('#PAFReqStatus').length>0 && with_status==1)
	},//END updateIndicator
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
	},//END getReq
	run : function(php,encrypted,id,act,property,session_id,request_id,post_params,with_status,js_script,conf,run_oninit_func,eparam) {
		if(PAFReq.oninit_func && run_oninit_func) { eval(PAFReq.oninit_func); }
		if(conf) {
			var cobj = (encrypted==1 ? JSON.parse(GibberishAES.dec(conf,request_id)) : (typeof(conf)=='object' ? conf : JSON.parse(conf)));
			if(cobj && typeof(cobj)=='object') {
				switch(cobj.type) {
					case 'jqui':
						// ToDo: de implementat jQueryUI dialog box
						break;
					default:
						if(confirm(cobj.txt)!=true) { return; }
						break;
				}//END switch
			}//if(cobj && typeof(cobj)=='object')
		}//if(conf)
		var end_js_script = '';
		if(js_script && js_script!='') {
			var scripts = js_script.split('~');
			if(scripts[0]) {
				PAFReq.runScript(scripts[0]);
			}//f(scripts[0])
			if(scripts[1]) {
				end_js_script = scripts[1];
			}//if(scripts[1])
		}//if(js_script && js_script!='')
		PAFReq.updateProcOn(1,with_status);
		if(encrypted==1) {
			php = GibberishAES.dec(php,request_id);
			if(typeof(eparam)=='object') {
				for(var ep in eparam) { php = php.replace(new RegExp('#'+ep+'#','g'),eparam[ep]); }
			}//if(typeof(eparam)=='object')
			php = eval('\''+php+'\'');
		}//if(encrypted==1)
		var requestString = 'req=' + (PAF_HTTPK ? encodeURIComponent(GibberishAES.enc(php,PAF_HTTPK)) : php)
			+ PAFReq.reqSeparator + session_id + PAFReq.reqSeparator + (request_id || '');
		requestString += post_params;
		PAFReq.sendRequest(id,act,property,requestString,with_status,end_js_script);
	},//END run
	runScript : function(scriptString) {
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
	},//END runScript
	sendRequest : function(id,act,property,requestString,with_status,js_script) {
		var req = PAFReq.getReq();
		req.open('POST',PAF_TARGET,true);
		req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		req.send(requestString);
		req.onreadystatechange = function() {
			if(req.readyState==4 && req.status==200) {
				actions = req.responseText.split(PAFReq.actSeparator);
				if(id) { PAFReq.put(actions[0],id,act,property); }
				if(actions[1]) { eval(actions[1]); }
				if(js_script && js_script!='') { PAFReq.runScript(js_script); }
				PAFReq.updateProcOn(-1,with_status);
				if(PAFReq.onPAFReqCompleteEvent) {
					$.event.trigger({ type: 'onPAFReqComplete' });
				}//if(PAFReq.onPAFReqCompleteEvent)
			}//if(req.readyState==4 && req.status==200)
		};//END req.onreadystatechange
	},//END sendRequest
	put : function(content,id,act,property) {
		if(!id) return;
		if(property) {
			try {
				var ob = document.getElementById(id);
				if(act=='p') {
					ob[property] = content + ob[property];
				} else if(act=='a') {
					ob[property] += content;
				} else if(property.split('.')[0]=='style') {
					ob.style[property.split('.')[1]] = content;
				} else if(ob) {
					ob[property] = content;
				}//if(act=='p')
			} catch (e) {}
		} else {
			window[id] = content;
		}//if(property)
	},//END put
	get : function(id,property) {
		if(!property) { return PAFReq.phpSerialize(PAFReq.escapeString(id)); }
		switch(property) {
			case 'option':
				return PAFReq.phpSerialize(PAFReq.escapeString($('#'+id+' option:selected').text()));
			case 'slider_start':
				return PAFReq.phpSerialize(PAFReq.escapeString($('#'+id).slider('values',0)));
			case 'slider_end':
				return PAFReq.phpSerialize(PAFReq.escapeString($('#'+id).slider('values',1)));
			default:
				return PAFReq.phpSerialize(PAFReq.escapeString(document.getElementById(id)[property]));
		}//END switch
	},//END get
	phpSerialize : function(v) {
		var ret = '';
		if(typeof(v)=='object') {
			var len = v.length;
			if(len) {
				ret = 'a:' + len + ':{';
				for(var i=0; i<len; i++) {
					ret += 'i:' + i + ';';
					ret += 's:' + (v[i] + '').length + ':"' + encodeURIComponent(v[i]) + '";';
				}//for(var i=0; i<len; i++)
				ret += '}';
			} else {
				len = 0;
				for(var i in v) {
					len++;
				}//for(var i in v)
				ret = 'a:' + len + ':{';
				for(var i in v) {
					ret += 's:' + i.length + ':"' + i + '";';
					ret += 's:' + v[i].length + ':"' + encodeURIComponent(v[i]) + '";';
				}//for(var i in v)
				ret += '}';
			}//if(len)
		} else {
			ret += 's:' + (v + '').length + ':"' + encodeURIComponent(v) + '";';
		}//if(typeof(v)=='object')
		return ret;
	},//END phpSerialize
	setStyle : function(ob,styleString) {
		document.getElementById(ob).style.cssText = styleString;
	},//END SetStyle
	getForm : function(f) {
		var vals = {};
		for(var i=0; i<f.length; i++){
			if(f[i].id) {
				vals[f[i].id] = f[i].value;
			}//if(f[i].id)
		}//for(var i=0; i<f.length; i++)
		return vals;
	},//END getForm
	escapeString : function(val) {
		var nval = String(val);
		nval = nval.replace(new RegExp('\\|','g'),'^[!]^');
		nval = nval.replace(new RegExp('~','g'),'^[^]^');
		return nval;
	}//END escapeString
};//END var PAFReq

function pafEscapeElement(elementid) {
	var nval = String(val);
	nval = nval.replace(new RegExp('\\|','g'),'^[!]^');
	nval = nval.replace(new RegExp('~','g'),'^[^]^');
	return nval;
}//END function pafEscapeElement