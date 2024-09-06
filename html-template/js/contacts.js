(function ($) {

	"use strict";

// Creating map options
var mapOptions = {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    maxZoom: 18,
    id: "mapbox/streets-v11",
    tileSize: 512,
    zoomOffset: -1,
    accessToken: "pk.eyJ1IjoibHBlc2thIiwiYSI6ImNrYmx5dGh4cjA3MHMycW1pdHp4Y2ZheGoifQ.e-0fQLJYoUUxsM0X6Z-gxQ"
};
    
    window.addEventListener("load", function(){
        var slanyX = 50.230446218120264;
        var slanyY = 14.082620927422633;
        var slanyText = 'Cestovní kancelář Slantour';

        var roudniceY = 50.423300;
        var roudniceX = 14.255568;
        var roudniceText = 'Prodejna Slantour - Roudnice nad Labem';  

        
        var mymap = L.map("map_contact").setView([slanyX, slanyY], 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mymap);                  

        var slanyMarker = L.marker([slanyX,slanyY]).addTo(mymap);
        slanyMarker.bindPopup(slanyText);
        var roudniceMarker = L.marker([roudniceY,roudniceX]).addTo(mymap);
        roudniceMarker.bindPopup(roudniceText);
    });


})(window.jQuery); 