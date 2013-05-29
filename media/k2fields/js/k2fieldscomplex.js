//$Copyright$

var k2fields_type_complex = {
        _complexAggr: [],

        createComplex: function(holder, proxyField, value, condition) {
                var subFields = this.getOpt(proxyField, 'subfields'), subField, proxyProxyField, fields, i, n, j, m, haveDeps = [];

                if (!subFields) return;

                if (value) value = value.split(this.options.valueSeparator);

                var result = [], pos, v, iPos = [], ivs, aggr, elId;

                var fid = this.getOpt(proxyField, 'id');

                for (i = 0, n = subFields.length; i < n; i++) {
                        subField = subFields[i];
                        proxyProxyField = document.id(this.options.pre+subField.id);
                        pos = this.getOpt(proxyProxyField, 'position');
                        v = value ? value[pos] : '';
                        aggr = false;

                        if (!this.isBasic(proxyProxyField)) {
                                var _id = this.optid(proxyProxyField);

                                if (!this.getOpt(proxyProxyField, 'internalValueSeparator')) {
                                        this.setOpt(proxyProxyField, 'internalValueSeparator', this.options.multiValueSeparator);
                                        this._complexAggr.push(_id);
                                        aggr = true;
                                } else {
                                        aggr = this._complexAggr.contains(_id);
                                }
                        }

                        if (v) {
                                if (!iPos[pos]) {
                                        iPos[pos] = 0;
                                        if (ivs = this.getOpt(proxyProxyField, 'internalValueSeparator')) {
                                                if (v.indexOf(ivs) > -1) {
                                                        value[pos] = v.split(ivs);
                                                } else {
                                                        value[pos] = [v];
                                                }
                                                v = value[pos][0];
                                        }
                                } else {
                                        iPos[pos]++;
                                        if (!value[pos][iPos[pos]]) v = '';
                                        else v = value[pos][iPos[pos]];
                                }
                        }

                        this.createFieldConditions(proxyField);

                        if (fields = this.createFieldSub(proxyProxyField, aggr ? value[pos] : v, condition, holder)) {
                                elId = this.getProxyFieldId(proxyProxyField);

                                if (fields.length == 0 && this.createdFields[elId]) fields = this.createdFields[elId];

                                delete this.createdFields[elId];

                                for (j = 0, m = fields.length; j < m; j++)
                                        if (this.initiateFieldDependency(fields[j])) haveDeps.push(fields[j]);

                                result.combine(fields);
                        }
                }

                for (i = 0, n = haveDeps.length; i < n; i++)
                        this.handleFieldDependency(haveDeps[i]);

                return result;
        }
};