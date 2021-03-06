/**
 * PAF (PHP AJAX Framework) ajax requests javascript file.
 *
 * The PAF javascript object used on ajax requests.
 * Copyright (c) 2012 - 2018 AdeoTEK
 * License    LICENSE.md
 *
 * @author     George Benjamin-Schonberger
 * @version    2.3.0
 */

if(AAPP_PHASH && window.name!==AAPP_PHASH) { window.name = AAPP_PHASH; }
$(window).on('load',function() { setCookie('__aapp_pHash_','',1); });
$(window).on('beforeunload',function() { setCookie('__aapp_pHash_',window.name,1); });

var ARequest = {
	procOn : {},
	reqSeparator : ']!r![',
	actSeparator : ']!r!s![',
	pipeChar: '^[!]^',
	tildeChar: '^[^]^',
	serializeMode: 'php',
	escapeStringMode: 'custom',
	onARequestInitEvent : true,
	onARequestCompleteEvent : true,
	timers : [],
	updateProcOn : function(add_val,loader) {
		if(loader) {
			var lkey = 'LOADER-'+String(loader).replaceAll(' ','_').replaceAll("'",'-').replaceAll('"','-');
			if(!ARequest.procOn.hasOwnProperty(lkey)) { ARequest.procOn[lkey] = 0; }
			ARequest.procOn[lkey] += add_val;
			if(ARequest.procOn[lkey]<=0) {
				ARequest.updateIndicator(loader,0);
			} else {
				ARequest.updateIndicator(loader,1);
			}//if(ARequest.procOn[lkey]<=0)
		}//if(loader)
	},//END updateProcOn
	updateIndicator : function(loader,new_status) {
		if(!loader) { return; }
		if(typeof(loader)==='function') {
			loader(new_status);
		} else {
				if(new_status===1) {
				$.event.trigger({ type: 'onARequestLoaderOn', loader: loader });
				} else {
				$.event.trigger({ type: 'onARequestLoaderOff', loader: loader });
				}//if(new_status==1)
		}//if(typeof(loader)=='function')
	},//END updateIndicator
	getRequest : function() {
	    try {
            return new XMLHttpRequest();
        } catch (e) {
            console.log(e);
        }//try
	    // DEPRECATED: removed since IE8
		// if(window.XMLHttpRequest) {
		// 	try {
		// 		return new XMLHttpRequest();
		// 	} catch (e) {
		// 	}//try
		// } else if(window.ActiveXObject) {
		// 	var versions = [
		//         'MSXML2.XmlHttp.5.0',
		//         'MSXML2.XmlHttp.4.0',
		//         'MSXML2.XmlHttp.3.0',
		//         'MSXML2.XmlHttp.2.0',
		//         'Microsoft.XmlHttp'
		//     ];
		//     var xhr;
		//     for(var i=0;i<versions.length;i++) {
		//         try {
		//             xhr = new ActiveXObject(versions[i]);
		//             break;
		//         } catch (e) {
		//         }//END try
		//     }//END for
		//     return xhr;
		// }//if (window.XMLHttpRequest)
	},//END getRequest
	run : function(php,encrypted,id,act,property,session_id,request_id,post_params,loader,async,js_script,conf,jparams,callback,run_oninit_event,eparam,call_type) {
		if(conf) {
			var cobj = false;
			if(encrypted===1) {
				eval('cobj = '+GibberishAES.dec(conf,request_id));
			} else {
				cobj = typeof(conf)==='object' ? conf : { type: 'std', message: conf };
			}//if(encrypted==1)
			if(cobj && typeof(cobj)==='object') {
				switch(cobj.type) {
					case 'jqui':
						ARequest.jquiConfirmDialog(cobj,function() {
							ARequest.runExec(php,encrypted,id,act,property,session_id,request_id,post_params,loader,async,js_script,jparams,callback,run_oninit_event,eparam,call_type);
						});
						return;
					default:
						if(confirm(decodeURIComponent(cobj.message))!==true) { return; }
						break;
				}//END switch
			}//if(cobj && typeof(cobj)=='object')
		}//if(conf)
		ARequest.runExec(php,encrypted,id,act,property,session_id,request_id,post_params,loader,async,js_script,jparams,callback,run_oninit_event,eparam,call_type);
	},//END run
	runExec : function(php,encrypted,id,act,property,session_id,request_id,post_params,loader,async,js_script,jparams,callback,run_oninit_event,eparam,call_type) {
		if(run_oninit_event && ARequest.onARequestInitEvent) {
			$.event.trigger({ type: 'onARequestInit', callType: call_type, target: id, action: act, property: property });
		}//if(run_oninit_event && ARequest.onARequestInitEvent)
		var end_js_script = '';
		if(js_script && js_script!=='') {
			var scripts = js_script.split('~');
			if(scripts[0]) { ARequest.runScript(scripts[0]); }
			if(scripts[1]) { end_js_script = scripts[1]; }
		}//if(js_script && js_script!='')
		ARequest.updateProcOn(1,loader);
		if(encrypted===1) {
			php = GibberishAES.dec(php,request_id);
			if(typeof(eparam)==='object') {
				for(var ep in eparam) { php = php.replace(new RegExp('#'+ep+'#','g'),eparam[ep]); }
			}//if(typeof(eparam)=='object')
			var jparams_str = '';
			if(typeof(jparams)==='object') {
				for(var pn in jparams) { jparams_str += 'var '+pn+' = '+JSON.stringify(jparams[pn])+'; '; }
			}//if(typeof(jparams)=='object')
			php = eval(jparams_str+'\''+php+'\'');
		}//if(encrypted==1)
		if(session_id===decodeURIComponent(session_id)) { session_id = encodeURIComponent(session_id); }
		var requestString = 'req=' + encodeURIComponent((AAPP_UID ? GibberishAES.enc(php,AAPP_UID) : php))
			+ ARequest.reqSeparator + session_id + ARequest.reqSeparator + (request_id || '');
		requestString += '&phash='+window.name+post_params;
		if(encrypted===1 && typeof(callback)==='string') { callback = GibberishAES.dec(callback,request_id); }
		var l_call_type = call_type ? call_type : 'run';
		ARequest.sendRequest(id,act,property,requestString,loader,end_js_script,async,callback,l_call_type);
	},//END runExec
	runFromString : function(data) {
		eval('var objData = '+data);
		var sphp = GibberishAES.dec(objData.php,'xSTR');
		sphp = eval("'"+sphp.replaceAll("\\'","'")+"'");
		var call_type = objData.call_type ? objData.call_type : 'runFromString';
		ARequest.run(sphp,objData.encrypted,objData.id,objData.act,objData.property,objData.session_id,objData.request_id,objData.post_params,objData.loader,objData.async,objData.js_script,objData.conf,objData.jparams,objData.callback,objData.run_oninit_event,objData.eparam,call_type);
	},//END runFromString
	timerRun : function(interval,timer,data) {
		if(data && timer) {
			ARequest.runFromString(GibberishAES.dec(data,timer));
			if(ARequest.timers[timer]) { clearTimeout(ARequest.timers[timer]); }
			ARequest.timers[timer] = setTimeout(function(){ ARequest.timerRun(interval,timer,data); },interval);
		}//if(data && timer)
	},//END timerRun
	runRepeated : function(interval,php,encrypted,id,act,property,session_id,request_id,post_params,loader,async,js_script,conf,jparams,callback,run_oninit_event,eparam) {
		if(interval && interval>0) {
			var ltimer = request_id+(new Date().getTime());
			var run_data = GibberishAES.enc(JSON.stringify({ php: GibberishAES.enc(php,'xSTR'), encrypted: encrypted, id: id, act: act, property: property, session_id: session_id, request_id: request_id, post_params: post_params, loader: loader, async: async, js_script: js_script, conf: conf, jparams: jparams, callback: callback, run_oninit_event: run_oninit_event, eparam: eparam, call_type: 'runRepeated' }),ltimer);
			ARequest.timerRun(interval,ltimer,run_data);
		}//if(interval && interval>0)
	},//END runRepeated
	runScript : function(scriptString) {
		if(!scriptString || scriptString==='') { return false; }
		var script_type = 'eval';
		var script_val = '';
		var script = scriptString.split('|');
		if(script[0] && script[1]) {
			if(script[0]!=='') { script_type = script[0]; }
			script_val = script[1];
		}else{
			if(script[0]) { script_val = script[0]; }
		}//if(script[0] && script[1])
		if(!script_val || script_val==='' || !script_type || script_type==='') { return false; }
		switch(script_type){
			case 'file':
				$.getScript(script_val);
				break;
			case 'eval':
				eval(script_val);
				break;
			default:
				return false;
		}//END switch
	},//END runScript
	sendRequest : function(id,act,property,requestString,loader,js_script,async,callback,call_type) {
		var req = ARequest.getRequest();
		var lasync = typeof(async)!=='undefined' ? ((!(async===0 || async===false || async==='0'))) : true;
		req.open('POST',AAPP_TARGET,lasync);
		req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		req.send(requestString);
		req.onreadystatechange = function() {
			if(req.readyState===4) {
				if(req.status===200) {
				    var actions = req.responseText.split(ARequest.actSeparator);
                    var content = actions[0]+(actions[2] ? actions[2] : '');
					var htmlTarget = req.getResponseHeader('HTMLTargetId');
                    if(typeof(htmlTarget)==='string' && htmlTarget.length>0) {
                        ARequest.put(content,htmlTarget,act,property);
                    } else if(id) {
                        ARequest.put(content,id,act,property);
                    } else {
                        $.event.trigger({ type: 'onARequestDataReceived', responseData: content });
                    }//if(typeof(htmlTarget)==='string' && htmlTarget.length>0)
                    if(actions[1]) {
                    	try {
                    		eval(actions[1]);
                    	} catch (ee) {
							console.log(ee);
							console.log(actions[1]);
                        }//END try
                    }//if(actions[1])
                    if(js_script && js_script!=='') { ARequest.runScript(js_script); }
                    ARequest.updateProcOn(-1,loader);
                    if(ARequest.onARequestCompleteEvent) { $.event.trigger({ type: 'onARequestComplete', callType: call_type }); }
                    if(callback) {
                        if(callback instanceof Function) {
                            callback();
                        } else if(typeof(callback)==='string') {
                            eval(callback);
                        }//if(callback instanceof Function)
                    }//if(callback)
				} else {
					console.log(req);
				}//if(req.status==200)
			}//if(req.readyState==4)
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
	getRadioValueFromObject : function(obj,parent,name) {
		var result = null;
		var radios = null;
		if(typeof(obj)=='object' && obj!=null) {
			if(typeof(name)!='string' || name.length==0) {
				name = (typeof(obj.name)=='string' && obj.name.length>0 ? obj.name : '');
			}//if(typeof(name)!='string' || name.length==0)
			if(name.length>0) {
				if(typeof(parent)!='object' || parent==null) {
					var dparent = obj.dataset.parent;
					if(dparent) {
						parent = document.getElementById(dparent);
					} else {
						parent = document.getElementsByTagName('body')[0];
					}//if(dparent)
				}//if(typeof(parent)!='object')
				if(typeof(parent)=='object' && parent!=null) {
					radios = parent.querySelectorAll('[name='+name+']');
					for(var i=0;i<radios.length;i++) {
						if(radios[i].checked) {
							result = radios[i].value;
							break;
						}//if(radios[i].checked)
					}//END for
				}//if(typeof(parent)=='object' && parent!=null)
			}//if(name.length>0)
		} else if(typeof(parent)=='object' && parent!=null && typeof(name)=='string' && name.length>0) {
			radios = parent.querySelectorAll('[name='+name+']');
			for(var i=0;i<radios.length;i++) {
				if(radios[i].checked) {
					result = radios[i].value;
					break;
				}//if(radios[i].checked)
			}//END for
		}//if(typeof(obj)=='object' && obj!=null)
		return result;
	},//END getRadioValueFromObject
	getFromObject : function(obj,property,attribute) {
		var val = '';
		if(typeof(obj)!=='object' || obj==null || !property) { return val; }
		switch(property) {
			case 'option':
				if(typeof(attribute)==='string' && attribute.length>0) {
					val = obj.options[obj.selectedIndex].getAttribute(attribute);
				} else {
					val = obj.options[obj.selectedIndex].text;
				}//if(typeof(attribute)=='string' && attribute.length>0)
				break;
			case 'radio':
				if(typeof(attribute)==='string' && attribute.length>0) {
					val = ARequest.getRadioValueFromObject(null,obj,attribute);
				} else {
					val = null;
				}//if(typeof(attribute)=='string' && attribute.length>0)
				break;
			case 'visible':
				val = !!(obj.offsetWidth || obj.offsetHeight || obj.getClientRects().length) ? 1 : 0;
				break;
			case 'slider_start':
				val = $(obj).slider('values',0);
				break;
			case 'slider_end':
				val = $(obj).slider('values',1);
				break;
			case 'content':
				val = obj.innerHTML;
				break;
			case 'nvalue':
				val = obj.value;
				var nformat = obj.getAttribute('data-format');
				if(nformat) {
					var farr = nformat.split('|');
					val = val.replaceAll(farr[3],'').replaceAll(farr[2],'');
					if(farr[1]) { val = val.replaceAll(farr[1],'.'); }
				}//if(nformat)
				break;
			case 'dvalue':
				val = obj.value;
				var dformat = obj.getAttribute('data-format');
				if(dformat) {
					var tformat = obj.getAttribute('data-timeformat');
					if(tformat) { dformat += ' ' + tformat; }
					var dt = getDateFromFormat(val,dformat);
					if(dt>0) {
						if(dformat.split(' ').length>1) {
							val = formatDate(new Date(dt),'yyyy-MM-dd HH:mm:ss');
						} else {
							val = formatDate(new Date(dt),'yyyy-MM-dd 00:00:00');
						}//if(dformat.split(' ').length>1)
					} else {
						val = '';
					}//if(dt>0)
				}//if(dformat)
				break;
			case 'fdvalue':
				val = obj.value;
				var dformat = obj.getAttribute('data-format');
				if(dformat) {
					var tformat = obj.getAttribute('data-timeformat');
					if(tformat) { dformat += ' ' + tformat; }
					var dt = getDateFromFormat(val,dformat);
					if(dt>0) {
						var outformat = obj.getAttribute('data-out-format');
						if(outformat) {
							val = formatDate(new Date(dt),outformat);
						} else {
							if(dformat.split(' ').length>1) {
								val = formatDate(new Date(dt),'yyyy-MM-dd HH:mm:ss');
							} else {
								val = formatDate(new Date(dt),'yyyy-MM-dd 00:00:00');
							}//if(dformat.split(' ').length>1)
						}//if(outformat)
					} else {
						val = '';
					}//if(dt>0)
				}//if(dformat)
				break;
			case 'attr':
				if(typeof(attribute)==='string' && attribute.length>0) {
					val = obj.getAttribute(attribute);
				} else {
					val = obj[property];
				}//if(typeof(attribute)=='string' && attribute.length>0)
				break;
			case 'function':
				if(typeof(attribute)==='string' && attribute.length>0) {
				    if(window.hasOwnProperty(attribute)) {
				        val = window[attribute](obj);
				    } else {
				        console.log('PAF Error: Unable to find method [' + attribute + ']!');
				    }//if(window.hasOwnProperty(attribute))
				}//if(typeof(attribute)==='string' && attribute.length>0)
                break;
			default:
				// Removed call "attr--" (replaced by "attr:")
				if(typeof(obj.type)==='string' && obj.type==='radio' && property==='value') {
					val = ARequest.getRadioValueFromObject(obj,null,null);
				} else {
				val = obj[property];
				}//if(typeof(obj.type)=='string' && obj.type=='radio')
				break;
		}//END switch
		if(val && typeof(val)==='string') { val = val.split(ARequest.actSeparator).join(''); }
		return val;
	},//END getFromObject
	getToArray : function(obj,initial) {
		if(typeof(obj)!=='object' || obj==null) { return initial; }
		var aresult;
		var nName = obj.nodeName.toLowerCase();
		var objName = obj.getAttribute('name');
		if(!objName || objName.length<=0) { objName = obj.getAttribute('data-name'); }
		if(objName) {
			var names = objName.replace(/^[\w|\-|_]+/,"$&]").replace(/]$/,"").split("][");
			if(nName==='input' || nName==='select' || nName==='textarea') {
				switch(obj.getAttribute('type')) {
					case 'checkbox':
						if(obj.checked===true) {
							if(obj.value) {
								var val = obj.value;
								val = ARequest.escapeString(val.split(ARequest.actSeparator).join(''));
							} else {
								var val = 0;
							}//if(obj.value)
						} else {
							var val = 0;
						}//if(obj.checked===true)
						break;
					case 'radio':
						var rval = document.querySelector('input[type=radio][name='+objName+']:checked').value;
						if(rval) {
							var val = ARequest.escapeString(rval.split(ARequest.actSeparator).join(''));
						} else {
							var val = 0;
						}//if(rval)
						break;
					default:
						var pafprop = obj.getAttribute('data-paf-prop');
						if(typeof(pafprop)=='string' && pafprop.length>0) {
							var pp_arr = pafprop.split(':');
							if(pp_arr.length>1) {
								var val = ARequest.getFromObject(obj,pp_arr[0],pp_arr[1]);
							} else {
								var val = ARequest.getFromObject(obj,pp_arr[0]);
							}//if(pp_arr.length>1)
						} else {
							var val = ARequest.getFromObject(obj,'value');
						}//if(typeof(pafprop)=='string' && pafprop.length>0)
						val = ARequest.escapeString(val);
						break;
				}//END switch
			} else {
				var val = ARequest.getFromObject(obj,'content');
				val = ARequest.escapeString(val);
			}//if(nName=='input' || nName=='select' || nName=='textarea')
		    if(names.length>0) {
		    	for(var i=names.length-1;i>=0;i--) {
		    		var tmp;
		    		if(names[i]!='') {
			    		tmp = {};
			    		tmp['#k#_'+names[i]] = (i==(names.length-1) ? val : aresult);
			    	} else {
			    		tmp = [ (i==(names.length-1) ? val : aresult) ];
			    	}//if(names[i]!='')
			    	aresult = tmp;
		    	}//END for
		    } else {
		    	aresult = [ val ];
		    }//if(names.length>0)
		}//if(objName)
		if(typeof(initial)!='object' && typeof(initial)!='array') { return aresult; }
	    return arrayMerge(initial,aresult,true);
	},//END getToArray
	getFormContent : function(id) {
		var result = '';
		var frm = document.getElementById(id);
		var rbelements = {};
		if(typeof(frm)=='object') {
			$(frm).find('.postable').each(function() {
				if(this.nodeName.toLowerCase()=='input' && this.getAttribute('type')=='radio') {
					if(!rbelements.hasOwnProperty(this.getAttribute('name'))) {
                        rbelements[this.getAttribute('name')] = 1;
                        result = ARequest.getToArray(this,result);
					}//if(!rbelements.hasOwnProperty(this.getAttribute('name')))
				} else {
					result = ARequest.getToArray(this,result);
				}//if(this.nodeName.toLowerCase()=='input' && this.getAttribute('type')=='radio')
			});
		} else {
			console.log('Invalid form: '+id);
		}//if(frm)
		return result;
	},//END getFormContent
	get : function(id,property,attribute) {
		var result = null;
		if(!property) {
			result = ARequest.escapeString(id);
		} else if(property=='form') {
			result = ARequest.getFormContent(id);
		} else {
			var eObj = document.getElementById(id);
			var val = null;
			if(typeof(eObj)!='object') {
				console.log('Invalid element: '+id);
			} else if(eObj==null) {
				console.log('Null element: '+id);
			} else {
				val = ARequest.getFromObject(document.getElementById(id),property,attribute);
			}//if(typeof(obj)!='object')
			if(typeof(val)!='string') {
				result = val;
			} else {
				result = ARequest.escapeString(val);
			}//if(typeof(val)!='string')
		}//if(!property)
		return ARequest.serialize(result);
	},//END get
	escapeString : function(val) {
		if(ARequest.escapeStringMode!='custom') { return val; }
		var nval = String(val);
		nval = nval.replace(new RegExp('\\|','g'),ARequest.pipeChar);
		nval = nval.replace(new RegExp('~','g'),ARequest.tildeChar);
		return nval;
	},//END escapeString
	serialize: function(val) {
		if(ARequest.serializeMode=='php') { return ARequest.phpSerialize(val); }
		return JSON.stringify(val);
	},//END serialize
	phpSerialize : function(mixed_value) {
		var val,key,okey,
	    ktype = '',
	    vals = '',
	    count = 0,
	    end = '';
	    _utf8Size = function(str) {
      		var size = 0,
	        i = 0,
	        l = str.length,
	        code = '';
			for(i = 0; i < l; i++) {
		    	code = str.charCodeAt(i);
		    	if(code < 0x0080) {
		    		size += 1;
		    	} else if (code < 0x0800) {
		    		size += 2;
		    	} else {
		    		size += 3;
		    	}//if(code < 0x0080)
			}//END for
      		return size;
		};//_utf8Size = function(str)
		_getType = function(inp) {
			var match,key,cons,types,type = typeof inp;
		    if(type === 'object' && !inp) { return 'null'; }
			if(type === 'object') {
      			if(!inp.constructor) { return 'object'; }
      			cons = inp.constructor.toString();
      			match = cons.match(/(\w+)\(/);
      			if(match) { cons = match[1].toLowerCase(); }
      			types = ['boolean', 'number', 'string', 'array'];
      			for(key in types) {
        			if(cons == types[key]) {
          				type = types[key];
          				break;
        			}//if(cons == types[key])
      			}//END for
    		}//if(type === 'object')
    		return type;
		};//_getType = function(inp)
		type = _getType(mixed_value);
		if(type !== 'object' && type !== 'array') { end = ';'; }
		switch(type) {
			case 'function':
				val = '';
				break;
			case 'boolean':
				val = 'b:' + (mixed_value ? '1' : '0');
				break;
    		case 'number':
				val = (Math.round(mixed_value) == mixed_value ? 'i' : 'd') + ':' + mixed_value;
				break;
			case 'string':
				var lval = ARequest.escapeString(mixed_value);
				val = 's:' + _utf8Size(lval) + ':"' + lval + '"';
      			break;
		    case 'array':
		    case 'object':
				val = 'a';
			    for(key in mixed_value) {
        			if(mixed_value.hasOwnProperty(key)) {
          				ktype = _getType(mixed_value[key]);
          				if(ktype === 'function') { continue; }
          				okey = (key.match(/^[0-9]+$/) ? parseInt(key, 10) : key);
          				vals += ARequest.serialize(okey) + ARequest.serialize(mixed_value[key]);
          				count++;
        			}//if(mixed_value.hasOwnProperty(key))
      			}//END for
      			val += ':' + count + ':{' + vals + '}';
      			break;
    		case 'undefined':
      			// Fall-through
    		default:
      			// if the JS object has a property which contains a null value, the string cannot be unserialized by PHP
      			val = 'N';
      			break;
  		}//END switch
  		val += end;
  		return val;
	},//END phpSerialize
	setStyle : function(ob,styleString) {
		document.getElementById(ob).style.cssText = styleString;
	},//END SetStyle
	getForm : function(f) {
		var vals = {};
		for(var i=0; i<f.length; i++){
			if(f[i].id) { vals[f[i].id] = f[i].value; }
		}//for(var i=0; i<f.length; i++)
		return vals;
	},//END getForm
	jquiConfirmDialog : function(options,callback) {
		var cfg = {
			type: 'jqui',
			message: '',
			title: '',
			ok: '',
			cancel: '',
			targetid: ''
		};
		if(options && typeof(options)=='object') { $.extend(cfg,options); }
		if(typeof(cfg.targetid)!='string' || cfg.targetid.length==0) { cfg.targetid = getUid(); }
		if(typeof(cfg.message)!='string' || cfg.message.length==0) { cfg.message = '???'; }
		if(typeof(cfg.title)!='string') { cfg.title = ''; }
		if(typeof(cfg.ok)!='string' || cfg.ok.length==0) { cfg.ok = 'OK'; }
		if(typeof(cfg.cancel)!='string' || cfg.cancel.length==0) { cfg.cancel = 'Cancel'; }
		var lbuttons = {};
		lbuttons[decodeURIComponent(cfg.ok)] = function() { $(this).dialog('destroy'); callback(); };
		lbuttons[decodeURIComponent(cfg.cancel)] = function() { $(this).dialog('destroy'); };
		if(!$('#'+cfg.targetid).length) { $('body').append('<div id="'+cfg.targetid+'" style="display: none;"></div>'); }
		$('#'+cfg.targetid).html(decodeURIComponent(cfg.message));
		var minWidth = $(window).width()>500 ? 500 : ($(window).width() - 20);
		var maxWidth = $(window).width()>600 ? ($(window).width() - 80) : ($(window).width() - 20);
		$('#'+cfg.targetid).dialog({
			title: decodeURIComponent(cfg.title),
			dialogClass: 'ui-alert-dlg',
			minWidth: minWidth,
			maxWidth: maxWidth,
			minHeight: 'auto',
			resizable: false,
			modal: true,
			autoOpen: true,
			show: {effect: 'slide', duration: 300, direction: 'up'},
			hide: {effect: 'slide', duration: 300, direction: 'down'},
			closeOnEscape: true,
			buttons: lbuttons
	    });
	},//END jquiConfirmDialog
	doWork : function(interval,timer,data) {
		postMessage(data);
		ARequest.timers[timer] = setTimeout(function(){ ARequest.doWork(interval,timer,data); },interval);
	},//END function doWork
	onMessage : function(e) {
		var obj = JSON.parse(e.data);
		if(ARequest.timers[obj.timer]) { clearTimeout(ARequest.timers[obj.timer]); }
		ARequest.doWork(obj.interval,obj.timer,obj.data);
	}//END onMessage
};//END var ARequest

RegExp.escape = function(text) { return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&"); };

String.prototype.replaceAll = function(find,replace,noescape) {
    var str = this;
    if(noescape==true || noescape==1) { return str.replace(new RegExp(find,'g'),replace); }
    return str.replace(new RegExp(RegExp.escape(find),'g'),replace);
};//String.prototype.replaceAll = function(find,replace)

function getUid() {
	var d = new Date();
	return (Math.round(Math.random()*1000000)) + '-' + d.getTime() + '-' + d.getMilliseconds();
}//function getUid

function setCookie(name,value,validity) {
	var expdate = undefined;
	if(validity>0) {
		expdate = new Date();
		expdate.setDate(expdate.getDate() + validity);
	}//if(validity>0)
	document.cookie = name+'='+encodeURIComponent(value)+']'+name+'|'+(expdate ? '; expires='+expdate.toGMTString() : '')+'; path=/; domain='+location.host+';';
}//END function setCookie

function getCookie(name) {
	var result = undefined;
    if(document.cookie.length>0 && document.cookie.indexOf('|'+name+'=')!=(-1)) { result = decodeURIComponent(document.cookie.substring(document.cookie.indexOf(name+'=')+name.length+2,document.cookie.indexOf(']'+name+'|'))); }
	return result;
}//END function getCookie

function pafEscapeElement(elementid) {
	var nval = String(val);
	nval = nval.replace(new RegExp('\\|','g'),ARequest.pipeChar);
	nval = nval.replace(new RegExp('~','g'),ARequest.tildeChar);
	return nval;
}//END function pafEscapeElement

function arrayMerge(farray,sarray,recursive) {
	if(typeof(farray)!='object' && typeof(farray)!='array') { return sarray; }
	if(typeof(sarray)!='object' && typeof(sarray)!='array') { return farray; }
	var rec = recursive ? true : false;
	for(var p in sarray) {
		if(p in farray) {
			if(isNaN(parseInt(p))) {
				farray[p] = rec ? arrayMerge(farray[p],sarray[p],true) : sarray[p];
			} else {
				farray[farray.length] = sarray[p];
			}//if(isNaN(parseInt(p)))
		} else {
			farray[p] = sarray[p];
		}//if(p in farray))
	}//END for
	return farray;
}//END function arrayMerge

/*** Date Functions ***/
// ===================================================================
// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// NOTICE: You may use this code for any purpose, commercial or
// private, without any further permission from the author. You may
// remove this notice from your final code if you wish, however it is
// appreciated by the author if at least my web site address is kept.
//
// You may *NOT* re-distribute this code in any way except through its
// use. That means, you can include it in your product, or your web
// site, or any other form where the code is actually being used. You
// may not put the plain javascript up on your site for download or
// include it in your javascript libraries for download.
// If you wish to share this code with others, please just point them
// to the URL instead.
// Please DO NOT link directly to my .js files from your site. Copy
// the files to your server and use them there. Thank you.
// ===================================================================

// HISTORY
// ------------------------------------------------------------------
// May 17, 2003: Fixed bug in parseDate() for dates <1970
// March 11, 2003: Added parseDate() function
// March 11, 2003: Added "NNN" formatting option. Doesn't match up
//                 perfectly with SimpleDateFormat formats, but
//                 backwards-compatability was required.

// ------------------------------------------------------------------
// These functions use the same 'format' strings as the
// java.text.SimpleDateFormat class, with minor exceptions.
// The format string consists of the following abbreviations:
//
// Field        | Full Form          | Short Form
// -------------+--------------------+-----------------------
// Year         | yyyy (4 digits)    | yy (2 digits), y (2 or 4 digits)
// Month        | MMM (name or abbr.)| MM (2 digits), M (1 or 2 digits)
//              | NNN (abbr.)        |
// Day of Month | dd (2 digits)      | d (1 or 2 digits)
// Day of Week  | EE (name)          | E (abbr)
// Hour (1-12)  | hh (2 digits)      | h (1 or 2 digits)
// Hour (0-23)  | HH (2 digits)      | H (1 or 2 digits)
// Hour (0-11)  | KK (2 digits)      | K (1 or 2 digits)
// Hour (1-24)  | kk (2 digits)      | k (1 or 2 digits)
// Minute       | mm (2 digits)      | m (1 or 2 digits)
// Second       | ss (2 digits)      | s (1 or 2 digits)
// AM/PM        | a                  |
//
// NOTE THE DIFFERENCE BETWEEN MM and mm! Month=MM, not mm!
// Examples:
//  "MMM d, y" matches: January 01, 2000
//                      Dec 1, 1900
//                      Nov 20, 00
//  "M/d/yy"   matches: 01/20/00
//                      9/2/00
//  "MMM dd, yyyy hh:mm:ssa" matches: "January 01, 2000 12:30:45AM"
// ------------------------------------------------------------------

var MONTH_NAMES=new Array('January','February','March','April','May','June','July','August','September','October','November','December','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
var DAY_NAMES=new Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sun','Mon','Tue','Wed','Thu','Fri','Sat');
function LZ(x) {return(x<0||x>9?"":"0")+x}

// ------------------------------------------------------------------
// isDate ( date_string, format_string )
// Returns true if date string matches format of format string and
// is a valid date. Else returns false.
// It is recommended that you trim whitespace around the value before
// passing it to this function, as whitespace is NOT ignored!
// ------------------------------------------------------------------
function isDate(val,format) {
	var date=getDateFromFormat(val,format);
	if (date==0) { return false; }
	return true;
}

// -------------------------------------------------------------------
// compareDates(date1,date1format,date2,date2format)
//   Compare two date strings to see which is greater.
//   Returns:
//   1 if date1 is greater than date2
//   0 if date2 is greater than date1 of if they are the same
//  -1 if either of the dates is in an invalid format
// -------------------------------------------------------------------
function compareDates(date1,dateformat1,date2,dateformat2) {
	var d1=getDateFromFormat(date1,dateformat1);
	var d2=getDateFromFormat(date2,dateformat2);
	if (d1==0 || d2==0) {
		return -1;
		}
	else if (d1 > d2) {
		return 1;
		}
	return 0;
}

// ------------------------------------------------------------------
// formatDate (date_object, format)
// Returns a date in the output format specified.
// The format string uses the same abbreviations as in getDateFromFormat()
// ------------------------------------------------------------------
function formatDate(date,format) {
	format=format+"";
	var result="";
	var i_format=0;
	var c="";
	var token="";
	var y=date.getYear()+"";
	var M=date.getMonth()+1;
	var d=date.getDate();
	var E=date.getDay();
	var H=date.getHours();
	var m=date.getMinutes();
	var s=date.getSeconds();
	var yyyy,yy,MMM,MM,dd,hh,h,mm,ss,ampm,HH,H,KK,K,kk,k;
	// Convert real date parts into formatted versions
	var value=new Object();
	if (y.length < 4) {y=""+(y-0+1900);}
	value["y"]=""+y;
	value["yyyy"]=y;
	value["yy"]=y.substring(2,4);
	value["M"]=M;
	value["MM"]=LZ(M);
	value["MMM"]=MONTH_NAMES[M-1];
	value["NNN"]=MONTH_NAMES[M+11];
	value["d"]=d;
	value["dd"]=LZ(d);
	value["E"]=DAY_NAMES[E+7];
	value["EE"]=DAY_NAMES[E];
	value["H"]=H;
	value["HH"]=LZ(H);
	if (H==0){value["h"]=12;}
	else if (H>12){value["h"]=H-12;}
	else {value["h"]=H;}
	value["hh"]=LZ(value["h"]);
	if (H>11){value["K"]=H-12;} else {value["K"]=H;}
	value["k"]=H+1;
	value["KK"]=LZ(value["K"]);
	value["kk"]=LZ(value["k"]);
	if (H > 11) { value["a"]="PM"; }
	else { value["a"]="AM"; }
	value["m"]=m;
	value["mm"]=LZ(m);
	value["s"]=s;
	value["ss"]=LZ(s);
	while (i_format < format.length) {
		c=format.charAt(i_format);
		token="";
		while ((format.charAt(i_format)==c) && (i_format < format.length)) {
			token += format.charAt(i_format++);
			}
		if (value[token] != null) { result=result + value[token]; }
		else { result=result + token; }
		}
	return result;
}

// ------------------------------------------------------------------
// Utility functions for parsing in getDateFromFormat()
// ------------------------------------------------------------------
function _isInteger(val) {
	var digits="1234567890";
	for (var i=0; i < val.length; i++) {
		if (digits.indexOf(val.charAt(i))==-1) { return false; }
		}
	return true;
}
function _getInt(str,i,minlength,maxlength) {
	for (var x=maxlength; x>=minlength; x--) {
		var token=str.substring(i,i+x);
		if (token.length < minlength) { return null; }
		if (_isInteger(token)) { return token; }
		}
	return null;
}

// ------------------------------------------------------------------
// getDateFromFormat( date_string , format_string )
//
// This function takes a date string and a format string. It matches
// If the date string matches the format string, it returns the
// getTime() of the date. If it does not match, it returns 0.
// ------------------------------------------------------------------
function getDateFromFormat(val,format) {
	val=val+"";
	format=format+"";
	var i_val=0;
	var i_format=0;
	var c="";
	var token="";
	var token2="";
	var x,y;
	var now=new Date();
	var year=now.getYear();
	var month=now.getMonth()+1;
	var date=1;
	var hh=now.getHours();
	var mm=now.getMinutes();
	var ss=now.getSeconds();
	var ampm="";

	while (i_format < format.length) {
		// Get next token from format string
		c=format.charAt(i_format);
		token="";
		while ((format.charAt(i_format)==c) && (i_format < format.length)) {
			token += format.charAt(i_format++);
			}
		// Extract contents of value based on format token
		if (token=="yyyy" || token=="yy" || token=="y") {
			if (token=="yyyy") { x=4;y=4; }
			if (token=="yy")   { x=2;y=2; }
			if (token=="y")    { x=2;y=4; }
			year=_getInt(val,i_val,x,y);
			if (year==null) { return 0; }
			i_val += year.length;
			if (year.length==2) {
				if (year > 70) { year=1900+(year-0); }
				else { year=2000+(year-0); }
				}
			}
		else if (token=="MMM"||token=="NNN"){
			month=0;
			for (var i=0; i<MONTH_NAMES.length; i++) {
				var month_name=MONTH_NAMES[i];
				if (val.substring(i_val,i_val+month_name.length).toLowerCase()==month_name.toLowerCase()) {
					if (token=="MMM"||(token=="NNN"&&i>11)) {
						month=i+1;
						if (month>12) { month -= 12; }
						i_val += month_name.length;
						break;
						}
					}
				}
			if ((month < 1)||(month>12)){return 0;}
			}
		else if (token=="EE"||token=="E"){
			for (var i=0; i<DAY_NAMES.length; i++) {
				var day_name=DAY_NAMES[i];
				if (val.substring(i_val,i_val+day_name.length).toLowerCase()==day_name.toLowerCase()) {
					i_val += day_name.length;
					break;
					}
				}
			}
		else if (token=="MM"||token=="M") {
			month=_getInt(val,i_val,token.length,2);
			if(month==null||(month<1)||(month>12)){return 0;}
			i_val+=month.length;}
		else if (token=="dd"||token=="d") {
			date=_getInt(val,i_val,token.length,2);
			if(date==null||(date<1)||(date>31)){return 0;}
			i_val+=date.length;}
		else if (token=="hh"||token=="h") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<1)||(hh>12)){return 0;}
			i_val+=hh.length;}
		else if (token=="HH"||token=="H") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<0)||(hh>23)){return 0;}
			i_val+=hh.length;}
		else if (token=="KK"||token=="K") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<0)||(hh>11)){return 0;}
			i_val+=hh.length;}
		else if (token=="kk"||token=="k") {
			hh=_getInt(val,i_val,token.length,2);
			if(hh==null||(hh<1)||(hh>24)){return 0;}
			i_val+=hh.length;hh--;}
		else if (token=="mm"||token=="m") {
			mm=_getInt(val,i_val,token.length,2);
			if(mm==null||(mm<0)||(mm>59)){return 0;}
			i_val+=mm.length;}
		else if (token=="ss"||token=="s") {
			ss=_getInt(val,i_val,token.length,2);
			if(ss==null||(ss<0)||(ss>59)){return 0;}
			i_val+=ss.length;}
		else if (token=="a") {
			if (val.substring(i_val,i_val+2).toLowerCase()=="am") {ampm="AM";}
			else if (val.substring(i_val,i_val+2).toLowerCase()=="pm") {ampm="PM";}
			else {return 0;}
			i_val+=2;}
		else {
			if (val.substring(i_val,i_val+token.length)!=token) {return 0;}
			else {i_val+=token.length;}
			}
		}
	// If there are any trailing characters left in the value, it doesn't match
	if (i_val != val.length) { return 0; }
	// Is date valid for month?
	if (month==2) {
		// Check for leap year
		if ( ( (year%4==0)&&(year%100 != 0) ) || (year%400==0) ) { // leap year
			if (date > 29){ return 0; }
			}
		else { if (date > 28) { return 0; } }
		}
	if ((month==4)||(month==6)||(month==9)||(month==11)) {
		if (date > 30) { return 0; }
		}
	// Correct hours value
	if (hh<12 && ampm=="PM") { hh=hh-0+12; }
	else if (hh>11 && ampm=="AM") { hh-=12; }
	var newdate=new Date(year,month-1,date,hh,mm,ss);
	return newdate.getTime();
}

// ------------------------------------------------------------------
// parseDate( date_string [, prefer_euro_format] )
//
// This function takes a date string and tries to match it to a
// number of possible date formats to get the value. It will try to
// match against the following international formats, in this order:
// y-M-d   MMM d, y   MMM d,y   y-MMM-d   d-MMM-y  MMM d
// M/d/y   M-d-y      M.d.y     MMM-d     M/d      M-d
// d/M/y   d-M-y      d.M.y     d-MMM     d/M      d-M
// A second argument may be passed to instruct the method to search
// for formats like d/M/y (european format) before M/d/y (American).
// Returns a Date object or null if no patterns match.
// ------------------------------------------------------------------
function parseDate(val) {
	var preferEuro=(arguments.length==2)?arguments[1]:false;
	generalFormats=new Array('y-M-d','MMM d, y','MMM d,y','y-MMM-d','d-MMM-y','MMM d');
	monthFirst=new Array('M/d/y','M-d-y','M.d.y','MMM-d','M/d','M-d');
	dateFirst =new Array('d/M/y','d-M-y','d.M.y','d-MMM','d/M','d-M');
	var checkList=new Array('generalFormats',preferEuro?'dateFirst':'monthFirst',preferEuro?'monthFirst':'dateFirst');
	var d=null;
	for (var i=0; i<checkList.length; i++) {
		var l=window[checkList[i]];
		for (var j=0; j<l.length; j++) {
			d=getDateFromFormat(val,l[j]);
			if (d!=0) { return new Date(d); }
			}
		}
	return null;
}
/*** END Date Functions ***/