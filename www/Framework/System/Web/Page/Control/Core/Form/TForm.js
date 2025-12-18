((t) => {
    t.doPostBack = () => document.querySelector('form').submit();
})(window.T = (window.T || {}));