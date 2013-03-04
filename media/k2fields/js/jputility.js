//$Copyright$

var JPUtility = new Class({
        Implements: [Events, Options],
        
        options: {
                base: ''
        },
        
        initialize: function(options) {
                this.setOptions(options);
        },
        
        toBody: function(method, code, type, isSync, fnc) {
                if (code != '') {
                        var 
                                appendNode = document.id(method == 'tag' ? document : document.body), 
                                el = (method == 'tag' ? appendNode : appendNode.ownerDocument).createElement(type == 'js' ?  'script' : 'link');
                               
                        el.type = type == 'js' ?  "text/javascript" : "text/css";
                        
                        if (type == 'css') el.rel = 'stylesheet';
                        
                        if (method == 'tag') {
                                el[type == 'js' ? 'src' : 'href'] = code;
                                document.id(el).setProperty('async', !isSync);
                                
                                if (fnc) {
                                        el.onreadystatechange = el.onload = function() {
                                                var state = el.readyState;

                                                if (!fnc.done && (!state || /loaded|complete/.test(state))) {
                                                        fnc();
                                                        fnc.done = true;
                                                }
                                        };
                                }
                        } else {
                                el.text = code;
                        }
                        
                        (method == 'tag' ? appendNode.documentElement : appendNode).appendChild(el);
                        
                        return true;
                }
                
                return false;
        },
        
        loaded: {'tag':[], 'request':[]},

        /**
         * method = tag|ajax, default tag
         * types = [css|js,...], can be inferred from src if all srcs end with js or css
         * isSync = default true
         */
        load: function(method, srcs, types, isSync, evt, evtFn, evtOnce) {
                var counter = 0, self = this, src, type, i, n, result;

                if (!method) method = 'tag';
                
                if (typeof srcs == 'string') srcs = [srcs];
                
                if (evtOnce == undefined) evtOnce = true;
                
                n = srcs.length;

                if (isSync == undefined) {
                        isSync = true;
                }
                
                if (typeOf(types) != 'array') {
                        type = types;
                        types = [];
                        
                        for (i = 0; i < n; i++) {
                                types.push(type);
                        }
                }
                
                for (i = 0; i < n; i++) {
                        src = srcs[i];
                        
                        type = types[i];
                        
                        if (!type) {
                                type = src.substr(src.lastIndexOf('.') + 1);
                                
                                if (!['css', 'js'].contains(type)) {
                                        continue;
                                }
                        }
                        
                        if (this.isLoaded(src, type)) {
                                if (evtFn) evtFn();
                                counter++;
                                continue;
                        }
                        
                        if (method == 'tag') {
                                if (result == undefined) {
                                        result = self.toBody(method, src, type, isSync, evtOnce ? (i == 0 ? evtFn : null) : evtFn);
                                } else {
                                        result &= self.toBody(method, src, type, isSync, evtOnce ? (i == 0 ? evtFn : null) : evtFn);
                                }
                                
                                self.loaded['tag'].push(src);
                        } else if (method == 'request') {
                                new Request({
                                        url: src,
                                        method: 'get',
                                        async: !isSync,
                                        onSuccess: function(code) {
                                                if (result == undefined) {
                                                        result = self.toBody(method, code, type);
                                                } else {
                                                        result &= self.toBody(method, code, type);
                                                }

                                                if (evtFn && (evtOnce && i == 0 || !evtOnce)) {
                                                        evtFn();
                                                }

                                                counter++;
                                                
                                                self.loaded['request'].push(this.options.url);
                                        },
                                        onFailure: function() {
                                                result = false;
                                        }
                                }).send();                                
                        }
                }
                
                if (isSync) {
                        return result;
                } else {
                        return false;
                }
        },        
        
        isLoaded: function(src, type, method) {
                if (!method) method = 'tag';
                
                if (method == 'request') return this.loaded[method].contains(src);
                
                var scripts = document.id(document).getElement('head').getChildren(type == 'css' ? 'link' : 'script')

                for (var i = 0; i < scripts.length; i++) {
                        if (scripts[i].getProperty(type == 'css' ? 'href' : 'src') == src && this.loaded[method].contains(src)) {
                                return true;
                        }
                }

                return false;
        },
        
        debug: true,

        dbg: function(val, varVal, varCond) {
                if (!this.debug) return;

                EMPTY: '_EMPTY_';
                NOTEMPTY: '_NOTEMPTY_';

                if (varCond) {
                        if (varCond == this.dbg.EMPTY && varVal) {
                                return;
                        } else if (varCond == this.dbg.NOTEMPTY && (!varVal && varVal !== false)) {
                                return;
                        } else {
                                var m = varCond.match(/^re\:(.+)/);
                                if (m) {
                                        m = new RegExp(m[1].escapeRegExp());
                                        if (!m.test(varVal)) {
                                                return;
                                        }
                                } else if (varVal != varCond) {
                                        return;
                                }
                        }
                }

                if (window.ie) {
                        alert(val);
                } else {
                        console.log(val);
                }
        },
        
        // Requires the FireUnit FF extension (http://fireunit.org/)
        profile: function(fn) {
                if (typeof fireunit == 'object') {
                       fireunit.profile(fn); 
                }
        },
        
        diff: function(o1, o2) {
                var result;
                if (typeOf(o1) == 'object') {
                        result = {};
                        var key;
                        for (key in o1) if (o1[key] != o2[key]) result[key] = o2[key];
                        for (key in o2) if (o1[key] != o2[key] && !result[key]) result[key] = o1[key];
                } else if (typeOf(o1) == 'array') {
                        result = [];
                        o1.each(function(el) { if (!o2.contains(el)) result.push(el); }.bind(this) );
                        o2.each(function(el) { if (!o1.contains(el) && !result.contains(el)) result.push(el); }.bind(this) );
                }
                return result;
        },
        
        replaceTokens:function(str, to, tokens) {
                to = Array.from(to);
                var i, n = to.length, token, isArray = (typeOf(tokens) == 'array');
                for (i = 0; i < n; i++) {
                        token = isArray ? tokens[i] : tokens;
                        str = str.replace(token, to[i]);
                }
                return str;
        },
                
        makeSafePath: function(path) {
                path = path.replace(/(\.){2,}/ig, '').replace(/[^A-Za-z0-9\.\_\- ]/ig, '').replace(/^\./ig, '');
                return path; 
        },
                
        normalizePath: function(path) { return path.replace(/[/\\\\]+/, '/'); },
        
        loadImageAttrs: [],
        
        loadImage: function(src, props, into, type) {
                return this._loadImage(src, props, into, type, this.loadImageAttrs.length);
        },
        
        _loadImage: function(src, props, into, type, ind) {
                if (!type) type = 'icons';
                
                src = this.normalizePath(src);
                
                if (src.split('/').length == 1) {
                        // only file name provided
                        var tmp = this.options.base + this.options.k2fbase;
                        if (tmp.lastIndexOf('/') != tmp.length - 1) tmp += '/';
                        src = tmp + type + '/' + src;
                } else {
                        if (!/http[s]{0,1}\:\/\//.test(src)) src = this.options.base + src;
                }
                
                var m = src.match(/\.([a-z\|]+)$/i), exts = m[1].split('|');
                
                if (m[1] == 'all') exts = ['jpg', 'gif', 'png'];
                
                src = src.replace(m[0], '.'+exts[0]);
                delete exts[0];
                exts = exts.clean();
                
                if (!props) props = {};
                
                props.exts = exts.join('|');
                
                props.ind = ind;
                
                props.onload = function(img) {
                        var ind = img.get('ind'), into = this.loadImageAttrs[ind];
                        if (into) img.inject(into);
                }.bind(this);

                var ut = this;
                
                props.onerror = function(img) {
                        var exts = img.get('exts'), src;
                        
                        if (exts) {
                                exts = exts.split('|');
                                src = img.get('src').replace(/\.[a-z]+$/i, '.'+exts[0]);
                                delete exts[0];
                                exts = exts.clean().join('|');
                                props.exts = exts;
                                
                                return new Asset.image(src, props);
                        }
                        
                        var ind = img.get('ind'), into = this.loadImageAttrs[ind];

                        img = new Element('span', {text: img.get('title') || img.get('alt')});

                        if (into) img.inject(into);
                }.bind(this);
                
                this.loadImageAttrs[ind] = into;
                
                return new Asset.image(src, props);                
        }
});
/**
 * credit: http://uvumitools.com/fromquerystring.html
 */
var extFns = {
        applyParams: function(o) {
                var url, type = typeOf(this);
                
                switch(type){
                        case 'window':
                        case 'document':
                                url = location.href;
                                break;
                        case 'element':
                                switch(this.get('tag')){
                                case 'a':
                                        url = this.get('href');
                                        break;
                                case 'form':
                                        url = this.get('action');
                                        break;
                                        default:
                                        return false;
                                }
                                break;
                        case 'string':
                                url = this;
                                break;
                        default:
                                return false;
                } 
                
                if (typeOf(o) == 'hash') {
                        o = o.toQueryString();
                        if (o) {
                                if (url.contains('?')) url = url.split('?')[0];
                                url = url + '?' + o;
                        }
                } 
                
                return url;
        },
        fromQueryString : function(fn){
                var url, type = typeOf(this);
                switch(type){
                        case 'window':
                        case 'document':
                                url = location.href;
                                break;
                        case 'element':
                                switch(this.get('tag')){
                                case 'a':
                                        url = this.get('href');
                                        break;
                                case 'form':
                                        url = this.get('action');
                                        break;
                                        default:
                                        return false;
                                }
                                break;
                        case 'string':
                                url = this;
                                break;
                        default:
                                return false;
                }
                var parameters = false;
                if (fn) url = fn(url);
                if(url.contains('?')){
                        if(url.contains('#')){
                                url = url.split('#')[0];
                        }
                        var query = url.split('?')[1], curr;
                        if(query != ""){
                                var parameters = new Hash(), params = query.split('&');
                                params.each(function(param){
                                        param = param.split('=');
                                        curr = parameters.get(param[0]);
                                        if (curr) {
                                                curr = Array.from(curr);
                                                curr.push(param[1]);
                                        } else {
                                                curr = param[1];
                                        }
                                        if (typeOf(curr) == 'object') {
                                                for (var i in curr) {
                                                        curr[i] = curr[i].replace(/\+/g, ' ');
                                                }
                                        } else if (typeOf(curr) == 'array') {
                                                for (var i = 0, n = curr.length; i < n; i++) {
                                                        curr[i] = curr[i].replace(/\+/g, ' ');
                                                }
                                        } else {
                                                curr = curr.replace(/\+/g, ' ');
                                        }
                                        parameters.set(param[0],curr);
                                });
                        }
                }
                return parameters;
        }
};

Native.implement([Element,Document,Window,String], extFns); 
Array.implement({
        max: function() {
                return Math.max.apply(Math, this);
        },
        min: function() {
                return Math.min.apply(Math, this);
        },
	range: function(start, end, step){
		if (!step) step = 1;
		for (var i = start; i <= end; i += step) this.push(i);
		return this;
	}
});

/*
---
script: array-sortby.js
version: 1.3.0
description: Array.sortBy is a prototype function to sort arrays of objects by a given key.
license: MIT-style
download: http://mootools.net/forge/p/array_sortby
source: http://github.com/eneko/Array.sortBy

authors:
- Eneko Alonso: (http://github.com/eneko)
- Fabio M. Costa: (http://github.com/fabiomcosta)

credits:
- Olmo Maldonado (key path as string idea)

provides:
- Array.sortBy

requires:
- core/1.3.0:Array

...
*/

(function(){

	var keyPaths = [];

	var saveKeyPath = function(path) {
		keyPaths.push({
			sign: (path[0] === '+' || path[0] === '-')? parseInt(path.shift()+1) : 1,
			path: path
		});
	};

	var valueOf = function(object, path) {
		var ptr = object;
		path.each(function(key) { ptr = ptr[key] });
		return ptr;
	};

	var comparer = function(a, b) {
		for (var i = 0, l = keyPaths.length; i < l; i++) {
			aVal = valueOf(a, keyPaths[i].path);
			bVal = valueOf(b, keyPaths[i].path);
			if (aVal > bVal) return keyPaths[i].sign;
			if (aVal < bVal) return -keyPaths[i].sign;
		}
		return 0;
	};

	Array.implement('sortBy', function(){
		keyPaths.empty();
		Array.each(arguments, function(argument) {
			switch (typeOf(argument)) {
				case "array": saveKeyPath(argument); break;
				case "string": saveKeyPath(argument.match(/[+-]|[^.]+/g)); break;
			}
		});
		return this.sort(comparer);
	});

})();

String.implement('toNumber', function(numericType) {
        if (numericType == 'float' || numericType == 'double') return this.toFloat();
        else if (numericType == 'int' || numericType == 'integer') return this.toInt();
        var f = this.toFloat(), i = this.toInt();
        if (Math.abs(f - i) > 0) return f;
        else return i;
});
