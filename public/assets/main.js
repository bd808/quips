(function (document, window, console) {
    "use strict";
    var storage = (function(){
      try {
        var s = window.localStorage,
            x = '__test__';
        s.setItem(x, x);
        s.removeItem(x);
        return s;
      } catch(e) {
        return false;
      }
    })();

    Array.prototype.filter.call(
        document.getElementsByClassName('vote'),
        function(elm) {
            var key = elm.getAttribute('data-url'),
                voted = storage ? storage.getItem(key) : false;
            if (voted & elm.getAttribute('data-vote') === voted) {
                elm.className += ' voted';
            }
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
                storage.setItem(url, vote);
            });
            elm.style.display = 'initial';
        }
    );

    Array.prototype.filter.call(
        document.querySelectorAll('section'),
        function(section) {
            var id = section.id,
                header = section.querySelectorAll('h3, h4')[0],
                text = header.innerHTML;
            header.innerHTML = '<span>' + text + '</span>' +
                    '<a href="#' + id + '">#' + id + '</a>';
        }
    );
})(document, window, console);
