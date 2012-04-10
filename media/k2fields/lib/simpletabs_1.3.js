/*
simpleTabs v1.3 - Modified for K2 to disable cookies on tabs (2010.04.16)

Author: Fotis Evangelou (Komrade Ltd.)
License: GNU/GPL v2.0
Credits:
- Peter-Paul Koch for the "Cookies" functions. More on: http://www.quirksmode.org/js/cookies.html
- Simon Willison for the "addLoadEvent" function. More on: http://simonwillison.net/2004/May/26/addLoadEvent/
Last updated: June 25th, 2009

RELEASE CHANGELOG:
v1.3
- Fixed "recurring divs in content" bug. If your tab contents included div tags, the tabs would break due to a faulty div tag count. Thanks to Sebastian L�scher (www.ddfriends.de) for providing the very simple fix!
- Separated all CSS classes at the top of the script, in case you need to modify them to suit your HTML/CSS structure.
v1.2
- Fixed IE syntax error
v1.1
- Namespaced the entire script

FEATURES TO COME:
- Remember last accessed tab for all tab sets on the same page
- Enable tab selection via URL anchor
- Add a loading indicator for the tab panes

*/

// Main SimpleTabs function
var kmrSimpleTabs = {

	sbContainerClass: "simpleTabs",
	sbNavClass: "simpleTabsNavigation",
	sbContentClass: "simpleTabsContent",
	sbCurrentNavClass: "current",
	sbCurrentTabClass: "currentTab",
	sbIdPrefix: "tabber",	

	init: function(){
		if(!document.getElementsByTagName) return false;
		if(!document.getElementById) return false;
		
		var containerDiv = $$('div.'+kmrSimpleTabs.sbContainerClass);
                
		for(var i=0; i<containerDiv.length; i++){
                        // assign a unique ID for this tab block and then grab it
                        var containerDivId = kmrSimpleTabs.sbIdPrefix+[i];
                        
                        if (containerDiv[i].retrieve("processed")) continue;
                        
                        containerDiv[i].set("id", containerDivId);		

                        // Navigation
                        var ul = containerDiv[i].getElements("ul."+kmrSimpleTabs.sbNavClass);

                        for(var j=0; j<ul.length; j++){
                                var a = ul[j].getElements("a");
                                for(var k=0; k<a.length; k++){
                                        a[k].set("id",containerDivId+"_a_"+k);
                                        a[0].set("class",kmrSimpleTabs.sbCurrentNavClass);
                                        $(a[k]).addEvent('click', function(){
                                                kmrSimpleTabs.setCurrent(this,'simpleTabsCookie');
                                                return false;
                                        });
                                }
                        }

                        // Tab Content
                        var div = containerDiv[i].getElements('div.'+kmrSimpleTabs.sbContentClass);
                        var countDivs = 0;
                        for(var l=0; l<div.length; l++){
                                div[l].set("id",containerDivId+"_div_"+[countDivs]);
                                div[0].set("class", kmrSimpleTabs.sbContentClass+" "+kmrSimpleTabs.sbCurrentTabClass);
                                countDivs++;
                        }
                        
                        containerDiv[i].store("processed", 1);
                        // End navigation and content block handling	
		}
	},
	
	// Function to set the current tab
	setCurrent: function(elm,cookie){
		this.eraseCookie(cookie);
		
		//get container ID
		var thisContainerID = elm.parentNode.parentNode.parentNode.getAttribute("id");
	
		// get current anchor position
		var regExpAnchor = thisContainerID+"_a_";
		var thisLinkPosition = elm.getAttribute("id").replace(regExpAnchor,"");
	
		// change to clicked anchor
		var otherLinks = elm.parentNode.parentNode.getElementsByTagName("a");
		for(var n=0; n<otherLinks.length; n++){
			otherLinks[n].className = "";
		}
		elm.className = kmrSimpleTabs.sbCurrentNavClass;
		
		// change to associated div
		var otherDivs = document.getElementById(thisContainerID).getElementsByTagName("div");
//		var RegExpForContentClass = new RegExp(kmrSimpleTabs.sbContentClass);
//		for(var i=0; i<otherDivs.length; i++){
//			if ( RegExpForContentClass.test(otherDivs[i].className) ) {
//				otherDivs[i].className = kmrSimpleTabs.sbContentClass;
//			}
//		}                 
		// changed by jproven.com, 2011-07-15 allowing several tab groups (including groups contained in tab panels)
                var RegExpForContentClass = new RegExp('^'+thisContainerID+"_div_\\d+$");
		for(var i=0; i<otherDivs.length; i++){
			if ( RegExpForContentClass.test(otherDivs[i].getAttribute("id")) ) {
				otherDivs[i].className = kmrSimpleTabs.sbContentClass;
			}
		}
		document.getElementById(thisContainerID+"_div_"+thisLinkPosition).className = kmrSimpleTabs.sbContentClass+" "+kmrSimpleTabs.sbCurrentTabClass;
		// get Tabs container ID
		var RegExpForPrefix = new RegExp(kmrSimpleTabs.sbIdPrefix);
		var thisContainerPosition = thisContainerID.replace(RegExpForPrefix,"");
		
		// set cookie
		this.createCookie(cookie,'simpleTabsCookie_'+thisContainerPosition+'_'+thisLinkPosition,1);
	},
	
	// Cookies
	createCookie: function(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	},
	
	readCookie: function(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	},
	
	eraseCookie: function(name) {
		this.createCookie(name,"",-1);
	},

	// Loader
	addLoadEvent: function(func) {
		var oldonload = window.onload;
		if (typeof window.onload != 'function') {
			window.onload = func;
		} else {
			window.onload = function() {
				if (oldonload) {
					oldonload();
				}
				func();
			}
		}
	}
	
	// END
};

// Load SimpleTabs
kmrSimpleTabs.addLoadEvent(kmrSimpleTabs.init);
