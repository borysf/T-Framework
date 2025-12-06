var TValidator = {
    validators: [],
    focusAlreadySet: false,
    formAlreadyBound: false,
    formOnceValidated: false,
    activeGroup: false,

    setActiveGroup: function (group) {
        this.activeGroup = group;
    },

    add: function(controlId, validatorId, validateMethod, message, cssClass, setFocus, displayMessage, validationGroup, displayMode) {
        var ctl = document.getElementById(controlId);

        if (!ctl) {
            return;
        }

        var v = {
            ctl: controlId,
            validator: validatorId,
            validateMethod:	validateMethod,
            message: message,
            css: cssClass,
            setFocus: setFocus,
            displayMessage: displayMessage,
            validationGroup: validationGroup,
            displayMode: displayMode
        };

        this.validators.push(v);

        if (!this.formAlreadyBound) {
            document.getElementsByTagName('form')[0].addEventListener('submit', function(e) {
                this.focusAlreadySet = false;
                this.formOnceValidated = true;

                if (!this.validate()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }

                return true;
            }.bind(this));

            this.formAlreadyBound = true;
        }

        var activeValidator = function() {
            if (this.formOnceValidated) {
                this.runValidator(v);
            };
        }.bind(this);

        switch (ctl.tagName.toLowerCase()) {
            case 'input':
                switch(ctl.type.toLowerCase()) {
                    case 'text':
                    case 'password':
                        ctl.addEventListener('blur', activeValidator);
                        ctl.addEventListener('keyup', activeValidator);
                        ctl.addEventListener('keydown', activeValidator);
                    break;
                    case 'checkbox':
                        ctl.addEventListener('click', activeValidator);
                    break;
                    case 'file':
                        ctl.addEventListener('change', activeValidator);
                    break;
                }
            break;
            case 'textarea':
                ctl.addEventListener('blur', activeValidator);
                ctl.addEventListener('keyup', activeValidator);
                ctl.addEventListener('keydown', activeValidator);
            break;
            case 'select':
                ctl.addEventListener('change', activeValidator);
            break;
        }
    },

    causeValidation: function(evt, ctlId, group) {
        document.getElementById(ctlId).addEventListener(evt, function() {
            this.setActiveGroup(group);
        }.bind(this));
    },

    validate: function() {
        var ret = true;

        if (this.activeGroup === false) return true;

        for (var i = 0; i < this.validators.length; i++) {
            if (this.activeGroup != this.validators[i].validationGroup) continue;
            if (!this.runValidator(this.validators[i])) ret = false;
        }

        this.activeGroup = false;
        return ret;
    },

    runValidator: function(v) {
        var ctl = document.getElementById(v.ctl);

        if (!ctl) {
            return true;
        }

        var vr = true;

        if (v.validateMethod instanceof RegExp) {
            vr = ctl.value.match(v.validateMethod);
        } else if (v.validateMethod instanceof Function) {
            vr = v.validateMethod(ctl);
        }

        if (!vr) {
            if (v.css) ctl.classList.add(v.css);
            if (v.displayMessage) {
                var msgEl = document.getElementById(v.validator);
                msgEl.textContent = v.message;

                if (v.displayMode == 'dynamic') {
                    msgEl.style.display = '';
                } else {
                    msgEl.style.visibility = 'visible';
                }
            }

            if (v.setFocus && !this.focusAlreadySet) {
                ctl.focus();
                this.focusAlreadySet = true;
            }

            return false;
        }

        if (v.css) ctl.classList.remove(v.css);
        if (v.displayMode == 'dynamic') {
            document.getElementById(v.validator).style.display = 'none';
        } else {
            document.getElementById(v.validator).style.visibility = 'hidden';
        }

        return true;
    }
}
