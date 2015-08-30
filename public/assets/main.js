(function (document, window, console) {
    "use strict";
    Array.prototype.filter.call(
        document.getElementsByClassName('vote'),
        function(elm) {
            elm.addEventListener('click', function() {
                var classes = ' ' + this.className + ' ';
                if (classes.indexOf(' voted ') > -1) {
                    return;
                }
                var url = this.getAttribute('data-url'),
                    token = this.getAttribute('data-token'),
                    vote = this.getAttribute('data-vote'),
                    r = new XMLHttpRequest();
                r.onload = function() {
                    console.log(this.responseText);
                };
                r.open('post', url, true);
                r.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                r.send('vote=' + vote + '&csrf_token=' + token);
                this.className += ' voted';
                console.log( url + ':' + vote );
            });
        }
    );
})(document, window, console);