//$Copyright$

// Getting rid of the dreaded old calendar widget and rendering date picker
var Calendar = {
        theme: '',
        setup: function(params) {
                window.addEvent('load', function(){
                        new Picker.Date(document.id(params.inputField), {
                                pickerClass: this.theme,
                                useFadeInOut: !Browser.ie,
                                format: params.ifFormat.trim()+' %H:%M:%S',
                                timeWheelStep: 5,
                                timePicker: true
                        });
                }.bind(this));
        }
};