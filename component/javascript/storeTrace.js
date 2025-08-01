/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var objectOperations = 0;
var limitOperations = 20;

var dpSent = false;
var dp2Sent = false;
var dpSentAgg = false;
var dp2SentAgg = false;
var scrollSent = false;

//new user scanning
var logFile = "";
var startDatetime = "";
var endDatetime = "";
var timeOnPageMiliseconds = 0;
var mouseClicksCount = 0;
var pageViewsCount = 0;
var mouseMovingTime = 0;
var mouseMovingDistance = 0;
var scrollingCount = 0;
var scrollingTime = 0;
var scrollingDistance = 0;
var printPageCount = 0
var selectCount = 0;
var selectedText = "";
var searchedText = "";
var copyCount = 0;
var copyText = "";
var clickOnPurchaseCount = 0;
var purchaseCount = 0;
var forwardingToLinkCount = 0;
var forwardedToLink = "";

//mouse and scroll positions
var mouseLastX = 0;
var mouseLastY = 0;
var scrollLastX = 0;
var scrollLastY = 0;
var enableMouseMoveTime = true;

//info about current page - should be updated onLoad();

var objectsListed = new Array();
//objectsListed["oid"] = "posX, posY, type"

var imagesCount = 0;
var textSizeCount = 0;
var linksCount = 0;
var windowSizeX = 0;
var windowSizeY = 0;
var pageSizeX = 0;
var pageSizeY = 0;

//temporary definitions, should be overide in the HTML file
//var pageID = 0;
//var sessionID = 0;
//var pageType = 0;
var visitID = 0;

function objectOpenedFromRecomendedList(objectID){
    if(objectID!=0){
        storeTestingEvent(userID,objectID, "object_opened_from_list", "1","recomended");
        //storeAggregatedEvent(objectID, "object_opened_from_list", "1");
        //storeImplicitEvent( userID, objectID, "object_opened_from_list", "1");
    }
}

/**
 *compute absolut position of an element
 */
function getElementPosition(id) {
    var ele = document.getElementById(id);
    var top = 0;
    var maxIterations = 30;
    var left = 0;    
    var i = 0;
    if(ele == null){
        return {top: 0, left: 0};
    }
    while(typeof ele != "null" && typeof ele != "undefined" && ele.tagName != "null"   && ele.tagName != "BODY" && i < maxIterations) {
        top += ele.offsetTop;
        left += ele.offsetLeft;
        ele = ele.offsetParent;
        i++;
    }   
    return {top: top, left: left};
}

/**
 * get oid if element is a part of an object
 * object have to have class = object and id=objectID
 */
function getObjectId(element) {
    var oid = null;
    var maxIterations = 30;
    var i = 0;
    while(typeof element != "null" && typeof element != "undefined" && element.tagName != "BODY" && i < maxIterations) {
        if(element.className != undefined){
            if(element.className.match(/(?:^|\s)objekt(?!\S)/) ){
                oid = element.id;
                return oid;
            }
        }
        element = element.parentNode;
        i++;
    }   
    return oid;
}

/*tracing is initialized just after the document is load.
 * this might cause problems as user can exit the page before it is fully loaded
 */
$(document).ready(initTracing);

/**
 *initialize tracing, set objects and window properties
 *e = event onLoad
 */
function initTracing(e){
    imagesCount = document.getElementsByTagName('img').length;
    linksCount = document.getElementsByTagName('a').length;
    windowSizeX = window.innerWidth;
    windowSizeY = window.innerHeight;
    pageSizeX = document.body.clientWidth;
    pageSizeY = document.body.clientHeight;    
    startDatetime = new Date();    
    pageViewsCount ++;
    mouseLastX = e.clientX;
    mouseLastY = e.clientY;
    scrollLastX = window.scrollX;
    scrollLastY = window.scrollY;
    
    var text = document.getElementsByTagName("body")[0].innerHTML;
    if(typeof text != 'undefined'){//text != undefined
      textSizeCount = text.length;  
    }else{
      textSizeCount = -1;  
    }
    
    //textSizeCount
    
    //objectsFilling
    //each object has class=object and id="oid"
    var divs = document.getElementsByClassName('objekt');
    var typ_objektu = 1;//katalog
    for(var i=0; i<divs.length; i++) { 
      //      divs[i].style.display='block'
      var positions = getElementPosition(divs[i].id);      
      if(divs[i].className.indexOf("recomended") !== -1  ){
          typ_objektu = 2;//doporucovaci okenko     
      }else{
          typ_objektu = 1;
      }
      objectsListed[divs[i].id] = positions.top+","+positions.left+","+typ_objektu+"";
    }
  //add event handlers
    
        //mouse clicks
    document.addEventListener('beforeunload', function(e) {
        exitTracing();
    });
    
    //mouse clicks
    document.addEventListener('click', function(e) {
        mouseClicksCount ++;
        var now = new Date();
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        logFile += "\n "+datetime+"; MouseClick";
        //find object
        var object = getObjectId(e.target);
        if(object != 0){
            logFile += "; on oid="+object+";";
            //storeTestingEvent(userID,object, "object_opened_from_list", "1","");
        }
    });
    
    /*vsechny odkazy na objekty musi mit class=object_link*/
    var links = document.getElementsByTagName('a');    
    for(var i = 0; i<links.length; i++){
        if(links[i].className.indexOf("object_link") !== -1 ){                    
            links[i].addEventListener('click', function(e) {
                forwardingToLinkCount ++;
                var now = new Date();
                var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
                logFile += "\n "+datetime+"; ForwardedToObject";
            
                logFile += "; position="+e.clientX+","+e.clientY;     
                //find object
                var object = getObjectId(e.target);
                if(object != null){
                    logFile += "; oid="+object+";";
                    forwardedToLink +="\n oid="+object+";position="+e.clientX+","+e.clientY;
                }           
            });
            links[i].addEventListener('contextmenu', function(e) {
                forwardingToLinkCount ++;
                var now = new Date();
                var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
                logFile += "\n "+datetime+"; ForwardedToObject";
            
                logFile += "; position="+e.clientX+","+e.clientY;     
                //find object
                var object = getObjectId(e.target);
                if(object != null){
                    logFile += "; oid="+object+";";
                    forwardedToLink +="\n oid="+object+";position="+e.clientX+","+e.clientY;
                }           
            });
        }
    }
    
    //print
    document.addEventListener('beforeprint', function(e) {
        printPageCount ++;
        var now = new Date();
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        logFile += "\n "+datetime+"; PrintPage";
    });
    
        
    //copy
    document.addEventListener('copy', function(e) {
        
        var now = new Date();   
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        var selection = null
        if (window.getSelection) {
            selection = window.getSelection();
        } else if (document.selection) {
            selection = document.selection.createRange();
        }
        if(selection != null && selection.toString().length > 0 ){
            logFile += "\n "+datetime+"; Copy text";
            //find object
            var object = getObjectId(e.target);
            if(object != null){
                logFile += "; on oid="+object+";";
            }   
            copyCount ++;
            copyText += ";\n"+selection.toString();
            logFile += " text:"+selection.toString();            
        }
        //add copied text if possible
    });
        
    //cut
    document.addEventListener('cut', function(e) {
        
        var now = new Date();   
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        var selection = null
        if (window.getSelection) {
            selection = window.getSelection();
        } else if (document.selection) {
            selection = document.selection.createRange();
        }
        if(selection != null && selection.toString().length > 0 ){
            logFile += "\n "+datetime+"; Copy text";
            //find object
            var object = getObjectId(e.target);
            if(object != null){
                logFile += "; on oid="+object+";";
            }   
            copyCount ++;
            copyText += ";\n"+selection.toString();
            logFile += " text:"+selection.toString();            
        }
        //add copied text if possible
    });
    
    //selectText (pres on mouse up)
    document.addEventListener('mouseup', function(e) {
        var selection = null
        var objText;
        if (window.getSelection) {
            selection = window.getSelection();
        } else if (document.selection) {
            selection = document.selection.createRange();
        }
        if(selection != null && selection.toString().length > 0){
            selectCount ++;
            var now = new Date();
            var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
            var object = getObjectId(e.target);
            if(object != null){
                objText = " on oid="+object+";";
            }else{
                objText = "";
            }
            selectedText += ";\n"+selection.toString();
            logFile += "\n "+datetime+";"+objText+" Select text:"+selection.toString();            
        }
    });
        
    
    
    //mouse distance
    window.addEventListener('mousemove', function(e){
        if(enableMouseMoveTime == true){
            var actMouseX = e.clientX;
            var actMouseY = e.clientY;
            var distX = Math.abs((actMouseX - mouseLastX));
            var distY = Math.abs((actMouseY - mouseLastY));
            var dist = Math.sqrt((distX*distX) + (distY*distY));
            if(dist > 0 ){
                mouseMovingDistance += dist;
            }            
            mouseLastX = actMouseX;
            mouseLastY = actMouseY;
            var now = new Date();
            var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
            mouseMovingTime += 200;
            enableMouseMoveTime = false;
            if(dist > 50){
            if(logFile.lastIndexOf("\n")>0){
                //nechci neustale se opakujici zapisy s mouse move a scroll
                var lastLog = logFile.substring(logFile.lastIndexOf("\n"));
                if(lastLog.indexOf("MouseMove")!=-1){                    
                    logFile += " + to:"+actMouseX+","+actMouseY; 
                }else{
                    logFile += "\n "+datetime+"; MouseMove, to:"+actMouseX+","+actMouseY;
                }
            }else{
                logFile += "\n "+datetime+"; MouseMove, to:"+actMouseX+","+actMouseY;
            }
            }
            setTimeout(function(){enableMouseMoveTime = true;}, 250);
        }
    });
    
    //scrolling distance
    window.setInterval(function(){
        var actScrollX = window.scrollX;
        var actScrollY = window.scrollY;
        var distX = Math.abs(actScrollX - scrollLastX);
        var distY = Math.abs(actScrollY - scrollLastY);
        var dist = Math.sqrt((distX*distX) + (distY*distY));
        scrollingDistance += dist;
        var now = new Date();
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();
        if(dist > 50){
            scrollingCount++;
            scrollingTime += 200;
            logFile += "\n "+datetime+"; Scroll, to:"+actScrollX+", "+actScrollY;
            scrollLastX = actScrollX;
            scrollLastY = actScrollY;
        }
        
    },250);
    
    sendVisit(1);
    var interval = setInterval(function(){sendVisit(0)}, 30000);

    function stopSendingResults() {
        clearInterval(interval);
    }
    var timeoutSend = setTimeout(function(){stopSendingResults()}, 600000);

}



/**
 *user clicks on Objednat op add to chart button
 */
function clickOnPurchaseButton(){
        clickOnPurchaseCount ++;
        var now = new Date();
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();

        logFile += "\n "+datetime+"; ClickOnPurchaseButton";
        sendVisit();
}

/**
 *user finished purchase
 */
function purchase(){
        purchaseCount ++;
        var now = new Date();
        var datetime = now.getFullYear()+"-"+(now.getMonth()+1)+"-"+now.getDate()+" "+now.getHours()+":"+now.getMinutes()+":"+now.getSeconds();

        logFile += "\n "+datetime+"; ObjectPurchased";
        sendVisit();
}

/**
 *send feedback to the server, keep current numbers (and send them again in 30 seconds
 */
function sendVisit(firstTime = 0){
    endDatetime = new Date();
    storeVisit( userID, objectID, pageID, sessionID, pageType, visitID, firstTime );
    
    //copy from previous version
}

/**
 * called in onBeforeUnload(), tries to sendVisit for the last time
 */
function exitTracing(){
    endDatetime = new Date();
    var logFileEndDate =  endDatetime.getFullYear()+"-"+(endDatetime.getMonth()+1)+"-"+endDatetime.getDate()+" "+endDatetime.getHours()+":"+endDatetime.getMinutes()+":"+endDatetime.getSeconds();      
 
    logFile += "\n "+logFileEndDate+"; End of visit";
    sendVisit();
}



function storeVisit(userID, objectID, pageID, sessionID, pageType, visitID, firstTime ) {
	var paramString = "";
	paramString += "userID=" + userID;
        paramString += "&objectID=" + objectID;
        paramString += "&pageID=" + pageID;
        paramString += "&pageType=" + pageType;
        paramString += "&sessionID=" + sessionID;
        paramString += "&visitID=" + visitID;
        paramString += "&firstTime=" + firstTime;
        
        paramString += "&imagesCount=" + imagesCount;
        paramString += "&textSizeCount=" + textSizeCount;
        paramString += "&linksCount=" + linksCount;
        paramString += "&windowSizeX=" + windowSizeX;
        paramString += "&windowSizeY=" + windowSizeY;
        paramString += "&pageSizeX=" + pageSizeX;
        paramString += "&pageSizeY=" + pageSizeY;
        paramString += "&objectsListed=";
            for (var key in objectsListed) {  
                paramString += key + ":" + objectsListed[key] + ";";  
            }           
            
       paramString += "&startDatetime=" + startDatetime.getFullYear()+"-"+(startDatetime.getMonth()+1)+"-"+startDatetime.getDate()+" "+startDatetime.getHours()+":"+startDatetime.getMinutes()+":"+startDatetime.getSeconds();
       endDatetime = new Date();
       paramString += "&endDatetime=" + endDatetime.getFullYear()+"-"+(endDatetime.getMonth()+1)+"-"+endDatetime.getDate()+" "+endDatetime.getHours()+":"+endDatetime.getMinutes()+":"+endDatetime.getSeconds();      
       timeOnPageMiliseconds = Math.abs(startDatetime - endDatetime);
        
        paramString += "&timeOnPageMiliseconds=" + timeOnPageMiliseconds;
        paramString += "&mouseClicksCount=" + mouseClicksCount;
        paramString += "&pageViewsCount=" + pageViewsCount;
        paramString += "&mouseMovingTime=" + mouseMovingTime;
        paramString += "&mouseMovingDistance=" + mouseMovingDistance;
        paramString += "&scrollingCount=" + scrollingCount;
        paramString += "&scrollingTime=" + scrollingTime;
        paramString += "&scrollingDistance=" + scrollingDistance;
        paramString += "&printPageCount=" + printPageCount;
        paramString += "&selectCount=" + selectCount;
        paramString += "&selectedText=" + selectedText;
        paramString += "&searchedText=" + searchedText;
        paramString += "&copyCount=" + copyCount;
        paramString += "&copyText=" + copyText;
        paramString += "&clickOnPurchaseCount=" + clickOnPurchaseCount;
        paramString += "&purchaseCount=" + purchaseCount;
        paramString += "&forwardingToLinkCount=" + forwardingToLinkCount;
        paramString += "&forwardedToLink=" + forwardedToLink;
        paramString += "&logFile=" + logFile;
        
 startDatetime = endDatetime;        
 timeOnPageMiliseconds = 0;
 mouseClicksCount = 0;
 pageViewsCount = 0;
 mouseMovingTime = 0;
 mouseMovingDistance = 0;
 scrollingCount = 0;
 scrollingTime = 0;
 scrollingDistance = 0;
 printPageCount = 0
 selectCount = 0;
 selectedText = "";
 searchedText = "";
 copyCount = 0;
 copyText = "";
 clickOnPurchaseCount = 0;
 purchaseCount = 0;
 forwardingToLinkCount = 0;
 forwardedToLink = "";
 logFile ="";
//objectsListed["oid"] = "posX, posY, type"


	// send request
	send_xmlhttprequestTrace(readyStateRoutine, 'POST', '/component/public/storeVisit.php',paramString, {"Content-Type" : "application/x-www-form-urlencoded"});
}


function send_xmlhttprequestTrace(obsluha, method, url, content, headers) {
    //alert(url);
	//alert(content);
	var xmlhttp = (window.XMLHttpRequest ? new XMLHttpRequest : (window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : false));
    if (!xmlhttp) {
        return false;
    }
    xmlhttp.open(method, url);
    xmlhttp.onreadystatechange = function() {
        obsluha(xmlhttp);
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
 * @param xmlhttp objekt typu XMLHttpRequest
 * @return bool true v pripade uspechu, jinak nic
 */
function readyStateRoutine(xmlhttp) {
	if (xmlhttp.readyState == 4) {
            //result obsahuje pouze visitID
            var result = xmlhttp.responseText;
            if(result!=""){
                visitID = result;
            }
            
            
            return true;
	}
        return false;
}



/**
* vrati jmeno pouziteho prohlizece - pouziva navigator.userAgent
* @return string jmeno prohlizece
*/
function detectBrowser2() {
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
