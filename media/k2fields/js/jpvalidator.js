//$Copyright$

if (window['Formular']) {
        var JPValidator = new Class({
                Extends: Formular,
                watchedFields: [],
                watchField: function(field){
                        field = document.id(field);
                        if (this.options.evaluateFieldsOnBlur)
                                field.addEvent('blur', this.validationMonitor.pass([field, false], this));
                        if (this.options.evaluateFieldsOnChange)
                                field.addEvent('change', this.validationMonitor.pass([field, true], this));
                        this.watchedFields.push(field);
                },
                getFields: function() {
                        return this.watchedFields;
                },
                validateFields: function(fields) {
                        return this.parent(fields || this.getFields());
                }
        });
}