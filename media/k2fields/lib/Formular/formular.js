var Formular;

if(!window.$empty) {
  var $empty = function() { };
}
(function($) {

Formular = new Class({

  Extends : Form.Validator,

  options : {

    onBeforeSubmit : $empty,
    onAfterSubmit : $empty,
    onSuccess : $empty,
    onFailure : $empty,
    onFieldSuccess : $empty,
    onFieldFailure : $empty,
    onFocus : $empty,
    onBlur : $empty,

    theme : 'red',
    tipOffset : {
      x : -10,
      y : 0
    },
    allowClose : true,
    closeErrorsOnDelay : 0,
    animateFields : false,
    validationFailedAnimationClassName : 'formular-validation-failed',
    fieldSelectors : '.required',
    allFieldSelectors : '.text,textarea,select',
    allButtonSelectors : 'input[type="submit"],button,input[type="button"],input[type="reset"]',
    errorClassName : 'formular-inline',
    disableClassName : 'disabled',
    warningPrefix : 'There was an error...',
    submitFormOnSuccess : true,
    disableFieldsOnSuccess : true,
    disableButtonsOnSuccess : true,
    inputEscapeKeyEvent : true,
    repositionBoxesOnWindowResize : true,
    repositionBoxesOnWindowScroll : true,
    serial : true,
    oneErrorAtATime : true,
    scrollToFirstError : true,
    focusOnFirstError : true,
    stopSubmissionRequestOnCancel : true,
    proxyElementStorageKey : 'Formular-element-proxy',
    boxZIndex : 3000
  },

  initialize : function(form,options) {
    this.form = $(form);
    this.form.addClass('formular');
    this.form.addEvent('submit',function(event) {
      if(this.options.submitFormOnSuccess) {
        this.fireEvent('beforeSubmit');
      }
      if(!this.options.submitFormOnSuccess) {
        event.preventDefault();
      }
      else {
        this.hasSubmitted = true;
      }
      this.fireEvent('success');
    }.bind(this));

    options = options || {};
    options.onFormValidate = this.onFormValidate.bind(this);
    options.onElementPass = this.onElementPass.bind(this);
    options.onElementFail = this.onElementFail.bind(this);

    this.scroller = new Fx.Scroll(window);

    this.parent(this.form,options);
    this.setTheme(this.options.theme);
    var fields = this.getAllFields();
    fields.addEvents({

      'focus' : function(event) {
        var input = $(event.target);
        this.activeInput = input;
        this.fireEvent('focus',[input]);
      }.bind(this),

      'blur' : function(event) {
        var input = $(event.target);
        if(this.activeInput && this.activeInput == input) {
          this.activeInput = null;
        }
        this.fireEvent('blur',[input]);
      }.bind(this),

      'keydown' : function(event) {
        if(this.options.inputEscapeKeyEvent) {
          var input = $(event.target);
          var key = event.key;
          if(input && this.activeInput == input && key == 'esc') {
            this.hideError(input);
          }
        }
      }.bind(this)

    });

    if(this.options.repositionBoxesOnWindowResize) {
      window.addEvent('resize',this.repositionBoxes.bind(this));
    }
    if(this.options.repositionBoxesOnWindowScroll) {
      document.addEvent('scroll',this.repositionBoxes.bind(this));
    }
  },

  setTheme : function(theme) {
    for(var i in this.boxes) {
      var box = this.boxes[i];
      if(box) {
        box.removeClass(this.getThemeClassName()).addClass('formular-' + theme);
      }
    }
    this.options.theme = theme;
  },

  getTheme : function() {
    return this.options.theme;
  },

  getThemeClassName : function() {
    return 'formular-' + this.getTheme();
  },

  repositionBoxes : function() {
    var boxes = this.boxes;
    for(var i in boxes) {
      var box = boxes[i];
      if(box) {
        var element = $(box).retrieve('element');
        element = this.getProxyElement(element);
        if(box.getStyle('display','block')) {
          this.positionErrorBox(box,element);
        } 
      }
    }
  },

  getProxyElement : function(element) {
    return element.retrieve(this.options.proxyElementStorageKey) || element;
  },

  getForm : function() {
    return this.form;
  },

  getAllFields : function() {
    return this.getForm().getElements(this.options.allFieldSelectors);
  },

  getButtons : function() {
    return $(this.getForm()).getElements(this.options.allButtonSelectors);
  },

  disableButtons : function() {
    this.getButtons().each(function(button) {
      button.setProperty('disabled',1);
      button.addClass(this.options.disableClassName);
    },this);
  },

  enableButtons : function() {
    this.getButtons().each(function(button) {
      button.setProperty('disabled','');
      button.removeClass(this.options.disableClassName);
    },this);
  },

  disableFields : function() {
    this.getAllFields().each(function(field) {
      field = this.getProxyElement(field);
      field.addClass(this.options.disableClassName);
      field.setProperty('readonly','1');
    },this);
  },

  enableFields : function() {
    this.getAllFields().each(function(field) {
      field = this.getProxyElement(field);
      field.removeClass(this.options.disableClassName);
      field.setProperty('readonly','');
    },this);
  },

  //this method is overriden so that we can have the oneErrorAtATime feature
	validateField: function(field, force) {
    if(this.options.oneErrorAtATime && this.anyErrorBoxesVisible()) {
      var box = this.getFirstVisibleErrorBox();
      if(box) {
        var element = box.retrieve('element');
        if(element != field) {
          return false; 
        }
      }
    }
    return this.parent(field,force);
  },

  setBoxZIndex : function(zIndex) {
    this.options.boxZIndex = zIndex;
    for(var i in this.boxes) {
      var box = this.boxes[i];
      if(box && box.setStyle) {
        box.setStyle('z-index',zIndex);
      }
    }
  },

  getBoxZIndex : function() {
    return this.options.boxZIndex;
  },

  createErrorBox : function(element) {
    var elm = new Element('div',{
      'class':this.options.errorClassName + ' ' + this.getThemeClassName(),
      'styles':{
        'position':'absolute',
        'display':'none',
        'z-index':this.getBoxZIndex()
      }
    });

    var close = '';
    if(this.options.allowClose) {
      close = '<div class="close"></div>';
    }
    var contents = '<table>'+
                   '<tr>'+
                   '<td class="tl x xy"></td>'+
                   '<td class="t y"></td>'+
                   '<td class="tr x xy"></td>'+
                   '</tr>'+
                   '<tr>'+
                   '<td class="l x"></td>'+
                   '<td class="c">'+close+'<div class="txt"></div></td>'+
                   '<td class="r x"></td>'+
                   '</tr>'+
                   '<tr>'+
                   '<td class="bl x xy"></td>'+
                   '<td class="b y"></td>'+
                   '<td class="br x xy"></td>'+
                   '</tr>'+
                   '</table>';
    elm.set('html',contents);
    elm.store('element',element);

    if(this.options.allowClose) {
      elm.getElement('.close').addEvent('click',this.onCloseError.bind(this));
    }

    return elm;
  },

  blur : function() {
    this.getFields()[0].blur();
  },

  onCloseError : function(event) {
    event.stop();
    var box = $(event.target).getParent('.'+this.options.errorClassName);
    if(box) {
      var element = box.retrieve('element');
      this.blur();
      this.hideError(element);
    }
  },

  onCloseErrorDelay : function(element) {
    this.blur();
    this.hideError(element);
  },

  getErrorBox : function(element) {
    if(!this.boxes) {
      this.boxes = {};
    }
    var id = element.id;
    if(!this.boxes[id]) {
      var box = this.createErrorBox(element);
      box.inject(document.body,'inside');

      this.boxes[id] = box;
    }
    return this.boxes[id];
  },

  getFirstVisibleErrorBox : function() {
    for(var i in this.boxes) {
      var box = this.boxes[i];
      if(box && box.getStyle('display') == 'block') {
        return box;
      }
    }
  },

  getErrorBoxMessage : function(box) {
    return box.getElement('.txt').get('html');
  },

  setErrorBoxMessage : function(box,message) {
    message = '<em class="formular-prefix">' + this.options.warningPrefix + '</em>' + message;
    box.getElement('.txt').set('html',message);
  },

  positionErrorBox : function(box,element) {
    if(!element) return;
    var sizes = box.getDimensions();
    var coords = element.getCoordinates();
    var CENTER_TIP_POSITION_X = -40;
    var offsetX = CENTER_TIP_POSITION_X + this.options.tipOffset.x;
    var offsetY = this.options.tipOffset.y;
    box.setStyles({
      'top' : coords.top - sizes.height + offsetY,
      'left' : coords.left + coords.width + offsetX 
    });
  },

  destroyAllBoxes : function() {
    var boxes = this.boxes;
    for(var i in boxes) {
      var box = boxes[i];
      if(box) {
        box.destroy();
      }
    }
  },

  showError : function(element,message) {
    var box = this.getErrorBox(element);
    if(box) {
      element = this.getProxyElement(element);
      var old = this.getErrorBoxMessage(box);
      this.setErrorBoxMessage(box,message);
      this.positionErrorBox(box,element);
      if(box.getStyle('display') != 'block') {
        box.setStyles({
          'opacity':0,
          'display':'block'
        }).tween('opacity',1);

        if(this.options.allowClose && this.options.closeErrorsOnDelay > 0) {
          this.delayErrorClose(element);
        }
      }
      else if(old != message) { 
        box.setOpacity(0.5).fade(1);
      }
    }
  },

  delayErrorClose : function(element) {
    var delay = this.options.closeErrorsOnDelay;
    if(this.closeTimer) {
      clearTimeout(this.closeTimer);
      this.closeTimer = null;
    }
    this.closeTimer = (function() {
      this.onCloseErrorDelay(element);
    }).delay(delay,this);
  },

  hideError : function(element) {
    var box = this.getErrorBox(element);
    if(box) {
      if(box.getStyle('display') == 'block') {
        new Fx.Morph(box).start({
          'opacity':0
        }).chain(function() {
          box.setStyle('display','none');
        });
      }
    }
  },

  anyErrorBoxesVisible : function() {
    for(var i in this.boxes) {
      var box = this.boxes[i];
      if(box && box.getStyle('display') == 'block') {
        return true;
      }
    }
    return false;
  },

  hideAllErrors : function() {
    for(var i in this.boxes) {
      var box = this.boxes[i];
      if(box) {
        var element = box.retrieve('element');
        this.hideError(element);
      }
    }
  },

  onFormValidate : function(pass) {
    if(pass) {
      this.onValidationSuccess();
    }
    else { //there must exist an error
      this.onInvalidFields();
      this.onValidationFailure();
    }
  },

  onInvalidFields : function() {
    if(this.options.focusOnFirstError) {
      this.focusOnFirstVisibleError(); 
    }
    if(this.options.scrollToFirstError) {
      this.scrollToFirstVisibleError();
    }
  },

  scrollToFirstVisibleError : function() {
    var box = this.getFirstVisibleErrorBox();
    if(box) {
      this.scroller.toElement(box);
    }
  },

  focusOnFirstVisibleError : function() {
    var box = this.getFirstVisibleErrorBox();
    if(box) {
      var input = box.retrieve('element');
      input.focus();
    }
  },

  onValidationSuccess : function() {
    if(this.options.disableFieldsOnSuccess) {
      this.disableFields();
    }
    if(this.options.disableButtonsOnSuccess) {
      this.disableButtons();
    }
    this.fireEvent('success');
    this.submitting = !!this.hasSubmitted;
    if(this.submitting) {
      if(this.options.submitFormOnSuccess) {
        this.fireEvent('afterSubmit');
      }
    }
  },

  onValidationFailure : function() {
    this.fireEvent('failure');
  },

  onElementPass : function(element) {
    this.hideError(element);
    var klass = this.options.validationFailedAnimationClassName;
    if(klass && element.hasClass(klass)) {
      element.removeClass(klass);
    }
    this.fireEvent('fieldSuccess',[element]);
  },

  onElementFail : function(element,validators) {
    var val = validators[0];
    if(val) {

      if(this.options.oneErrorAtATime && this.anyErrorBoxesVisible()) {
        var visible = this.getFirstVisibleErrorBox();
        if(visible.retrieve('element') != element) {
          return;
        }
      }

      var validator = this.getValidator(val);
      if(validator) {
        var error = validator.getError(element);
        this.onElementError(element,error);
      }
    }
    this.fireEvent('fieldFailure',[element,validators]);
  },

  onElementError : function(element,message) {
    this.showError(element,message);
    var klass = this.options.validationFailedAnimationClassName;
    if(klass) {
      element = this.getProxyElement(element);
      if(this.options.animateFields) {
        var existingStyles = element.retrieve('existingStyles',element.style);
        var m = element.get('morph');
        m.start('.'+klass).chain(function() {
          element.addClass(klass);
          element.setProperty('style',existingStyles);
        });
      }
      else {
        element.addClass(klass);
      }
    }

  },

  validateFieldset : function(fieldset) {
    var fieldset = $(fieldset) || this.getForm().getElement('[name="'+fieldset+'"]');
    if(fieldset) {
      var inputs = fieldset.getElements(this.options.fieldSelectors);
      return this.validateFields(inputs);
    }
    return false;
  },

  validateFields : function(fields) {
    var fields = $$(fields);
		var fieldResults = $$(fields).map(function(field){
			return this.validateField(field, true);
		}, this);
    var fullyValid = fieldResults.every(function(v) {
      return v;
    });
    if(!fullyValid) {
      this.onInvalidFields();
    }
    return fullyValid;
  },

  isSubmitting : function() {
    return this.submitting;
  },

  submit : function() {
    this.validate();
  },

  resetForm : function() {
    this.getAllFields().setProperty('value','');
    this.enableFields();
    this.enableButtons();
  },

  cancel : function() {
    if(this.isSubmitting() && this.options.stopSubmissionRequestOnCancel) {
      window.ie ? document.execCommand('Stop') : window.stop();
    }
    this.enableFields();
    this.enableButtons();
    this.reset();
    this.hideAllErrors();
    this.submitting = this.hasSubmitted = false;
  },

  destroy : function() {
    this.reset();
    this.destroyAllBoxes();
    this.getForm().store('Formular',null);
    //this.getForm().destroy();
  }

});

})(document.id);
