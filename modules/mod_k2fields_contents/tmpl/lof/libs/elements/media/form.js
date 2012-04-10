/**
 * $ModDesc
 * 
 * @version	$Id: helper.php $Revision
 * @package	modules
 * @subpackage	$Subpackage.
 * @copyright	Copyright (C) May 2010 LandOfCoder.com <@emai:landofcoder@gmail.com>.All rights reserved.
 */ 
// JavaScript Document
window.addEvent('load', function(){

 var controls=['item_layout'];	
 
 if(  $defined($$('.paramlist tr')) && $$('.paramlist tr').length > 0 ) {  
 	// on off
	
        var trs = $$('.paramlist tr');
	 trs.each( function(tr, index){
		var tmp = tr.getElement('td.paramlist_value .lof-group')
		if( tmp && tmp.get('title') ){
			tr.addClass('group-'+tmp.get('title')).addClass('icon-'+tmp.get('title'));
			for( j=index+1; j < trs.length; j++ ){
				if( $defined(trs[j].getElement('td.paramlist_value .lof-end-group')) ) {
					trs[j].dispose();
					break;
				}
				trs[j].addClass('group-'+tmp.get('title')).addClass('lof-group-tr');
			}
			var title = tmp.get('title');
			tmp.enable= true;
		}
	 });
	 function update( tmp, hide ){
		 	if( hide ){
				tmp.enable = true;
			}
		 	var title = tmp.value;
			if(  tmp.enable==false  && $defined(tmp.enable) ) {
			//	alert( $E('.admintable' ).getElements("*[class=^"+title+"]") );
				$$('.admintable tr.group-'+title ).setStyle('display','');
				tmp.enable=true;

			} else if(title && title !=-1) {
				$$('.admintable tr.group-'+title ).setStyle('display','none');
				tmp.enable=false;
			}
			setTimeout( function(){
				$$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );
			}, 300 );
	 }
	 

	controls.each( function(_group){ 
		$$('#params'+_group).addEvent('change',function(){
			var tmdp = this;
			tmdp.enable = false;
				update( this  );
			var selected = this;
			$$('#params'+_group +' option').each( function(tmp, index){
					if(tmp.value !=selected.value ) {
						update( tmp, true );
					}
			} );
		});
		 $$('#params'+_group+' option').each( function(tmp, index){
				if(!tmp.selected) {
					update( tmp );
				}

		} );
		
	} );
	
	//////////////
	setTimeout( function(){
		$$('.lof-onoff').each( function( item ){
	
			if( $defined($$( "."+item.id.replace("params",'group-') ))  ) {							 
				if( item.checked ){
					//$$( "."+item.id.replace("params",'group-') ).setStyle("display","");
					// $E( 'tr.'+item.id.replace("params",'group-')).setStyle("display",'');
					item.value=1;
				} else {
					if( $$( "."+item.id.replace("params",'group-') ).length > 0 ){
					 	 $$( "."+item.id.replace("params",'group-') ).setStyle("display","none");
						 $$( 'tr.'+item.id.replace("params",'group-'))[0].setStyle("display",'');
						 item.value=0;
					}
				}
			} 
		});
		setTimeout( function(){
				$$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );
		}, 300 );
	}, 200 );
	

	$$('.lof-onoff').addEvent('click', function(item,idx){
	// alert(this.getParent('tr') )
		if( !this.checked ){  //  alert(  );
			this.value=0;
		 	$$( "."+this.id.replace("params",'group-') ).setStyle("display","none");
			$$( 'tr.'+this.id.replace("params",'group-'))[0].setStyle("display",'');
		}else {
			$$( "."+this.id.replace("params",'group-') ).setStyle("display","");
			$$( 'tr.'+this.id.replace("params",'group-'))[0].setStyle("display",'');
			this.value=1;
		}
		setTimeout( function(){ $$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );}, 300 );
	} );
	
} else {
	var controls=['group','enable_caption'];
	controls.each( function(_group){ 
		$$('#params'+_group).addEvent('change',function(){
			 $$('.lof-group').hide();	
			 $$('.lof-'+this.value).show();
			 (function(){
				 var height = ($$('#menu-pane .jpane-slider')[0].getElement('.panelform-legacy').getHeight() );
				 $$('#menu-pane .jpane-slider')[0].setStyle('height', height ) ;
			 }).delay(300);
		});
		 $$('#params'+_group+' option').each(function(item){
			if( item.selected ){
			 $$('.lof-group').hide();	
				(function(){  $$('.lof-'+item.value).show(); }).delay(100);
				 (function(){
				 var height = ($$('#menu-pane .jpane-slider')[0].getElement('.panelform-legacy').getHeight() );
				 $$('#menu-pane .jpane-slider')[0].setStyle('height', height ) ;
				 }).delay(300);
				return ;
			}
		});
	} );
}
} );



$(window).addEvent( 'load', function(){
	// add event addrow
	$$('.it-addrow-block .add').each( function( item, idx ){ 
		item.addEvent('click', function( ){
			var name   = "params["+item.get('id').replace('btna-','')+"][]"
			var newrow = new Element('div', {'class':'row'} );	
			var input  = new Element('input', {'name':name,'type':'text'} );
			var span   = new Element('span',{'class':'remove'});
			var spantext   = new Element('span',{'class':'spantext'}); 
				newrow.adopt( spantext );	
				newrow.adopt( input );	
				newrow.adopt( span );			
			var parent = item.getParent().getParent();	
			parent.adopt( newrow );	
			spantext.innerHTML= parent.getElements('input').length;	
			span.addEvent('click', function(){ 
				if( span.getParent().getElement('input').value ) {
					if( confirm('Are you sure to remove this') ) {
						span.getParent().dispose(); 
					}
				} else {
					span.getParent().dispose(); 
				}
				setTimeout( function(){ $$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );
																		parent.getElements('.spantext').each( function(tm,j){
																			tm.innerHTML=j+1;											   
																		});					
				}, 300 );
			} );				 
			setTimeout( function(){
					$$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );
					parent.getElements('.spantext').each( function(tm,j){tm.innerHTML=j+1;});	
					
			}, 300 );
				    
		} );
	} );
	$$('.it-addrow-block .row').each(function( item ){
		item.getElement('.remove').addEvent('click', function() {
			if( item.getElement('input').value ) {
				if( confirm('Are you sure to remove this') ) {
					item.dispose();
				}
			}else {
				item.dispose();
			}
			setTimeout( function(){ $$('.jpane-slider ')[0].setStyle( 'height', $$('.paramlist')[0].offsetHeight );}, 300 );
		} );
	});
} );

