var CookieManager;

(function() {

    var cookies;

    function parseCookies(cookieStr) {
        var i, l, part,
            result = {},
            parts = cookieStr.split(/\s*;\s*/g);

        for (i = 0, l = parts.length; i < l; i++) {
            part = parts[i].split(/\s*=\s*/);
            result[decodeURIComponent(part[0])] = decodeURIComponent(part[1]);
        }

        return result;
    }

    CookieManager = function() {
        if (cookies === undefined) {
            cookies = parseCookies(document.cookie);
        }
    };

    CookieManager.prototype.getCookie = function(name) {
        if (cookies[name] !== undefined) {
            return cookies[name];
        }
    };

    CookieManager.prototype.setCookie = function(name, value) {
        document.cookie = name + '=' + value;
        cookies[name] = value;
    };

    CookieManager.prototype.deleteCookie = function(name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        delete cookies[name];
    };

}());