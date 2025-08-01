/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * highlight the noStar of stars in the object
 */
function starsShow(object, noStars){
    var i = 1;
    while(i <= noStars){
        var img = document.getElementById("star_"+object+"_"+i);
        img.src = "/img/star_full.gif";
        i++;
    }
}
/**
 *hides all hihglighted stars in the object
 */
function starsHide(object){
    var i = 1;
    var noStars = 5;
    while(i <= noStars){
        var img = document.getElementById("star_"+object+"_"+i);
        img.src = "/img/star.gif";
        i++;
    }
}