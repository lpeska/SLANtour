/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *These library functions was created by Jakub Vrana, http://php.vrana.cz
 *Comments and functions were left as they are (in czech)
 **/

/** odeslání XMLHttp požadavku
* Kompletni popis (vcetne prikladu) je na adrese http://php.vrana.cz/ajax.php
* @param function obsluha funkce zajišťující obsluhu při změně stavu požadavku, dostane parametr s XMLHttp objektem
* @param string method GET|POST|...
* @param string url URL požadavku
* @param string [content] tělo zprávy
* @param array [headers] pole předaných hlaviček ve tvaru { 'hlavička': 'obsah' }
* @return bool true v případě úspěchu, false jinak
* @copyright Jakub Vrána, http://php.vrana.cz
*/
function send_xmlhttprequest(obsluha, method, url, content, headers, objectID) {
    //alert(url);
	//alert(content);
	var xmlhttp = (window.XMLHttpRequest ? new XMLHttpRequest : (window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : false));
    if (!xmlhttp) {
        return false;
    }
    xmlhttp.open(method, url);
    xmlhttp.onreadystatechange = function() {
        obsluha(xmlhttp, objectID);
    };

	if (headers) {
        for (var key in headers) {
            xmlhttp.setRequestHeader(key, headers[key]);
        }
    }

    xmlhttp.send(content);
    return true;
}


/**
 * Funkce pro zpetnou vazbu s uzivatelem
 * @param object objekt typu XMLHttpRequest
 * @return bool true v pripade uspechu, jinak nic
 */
function readyStateRoutine(xmlhttp, objectID) {
	if (xmlhttp.readyState == 4) {
		return true;
	}
}



/**
* vrati jmeno pouziteho prohlizece - pouziva navigator.userAgent
* @return string jmeno prohlizece
*/
function detectBrowser() {
	var agt=navigator.userAgent.toLowerCase();

	if (agt.indexOf("opera") != -1) return 'Opera';
	if (agt.indexOf("staroffice") != -1) return 'Star Office';
	if (agt.indexOf("webtv") != -1) return 'WebTV';
	if (agt.indexOf("beonex") != -1) return 'Beonex';
	if (agt.indexOf("chimera") != -1) return 'Chimera';
	if (agt.indexOf("netpositive") != -1) return 'NetPositive';
	if (agt.indexOf("phoenix") != -1) return 'Phoenix';
	if (agt.indexOf("firefox") != -1) return 'Firefox';
	if (agt.indexOf("safari") != -1) return 'Safari';
	if (agt.indexOf("skipstone") != -1) return 'SkipStone';
	if (agt.indexOf("msie") != -1) return 'IE';
	if (agt.indexOf("netscape") != -1) return 'Netscape';
	if (agt.indexOf("mozilla/5.0") != -1) return 'Mozilla';
	if (agt.indexOf('\/') != -1) {
		if (agt.substr(0,agt.indexOf('\/')) != 'mozilla') {
			return navigator.userAgent.substr(0,agt.indexOf('\/'));
		}
		else return 'Netscape';
	}
	else if (agt.indexOf(' ') != -1)
			return navigator.userAgent.substr(0,agt.indexOf(' '));
		else return navigator.userAgent;
}
