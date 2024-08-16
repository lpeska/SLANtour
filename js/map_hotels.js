const average = array => array.reduce((a, b) => a + b) / array.length;
function addMarkerTours(item, mymap) {
    var marker2 = L.marker([item["lat"],item["long"]]).addTo(mymap);
    marker2.bindPopup(item["title"]);
}
function showToursOnMap(){
    var arrLength = Object.keys(tourLocations).length;
    if(arrLength >0){
        var lats = [];
        var longs = [];
        for (var id in tourLocations) {
            lats.push(tourLocations[id].lat);
            longs.push(tourLocations[id].long);
        }
        var meanLat = average(lats);
        var meanLong = average(longs);
        var zoom = 7; 
        if(arrLength >1) {
        
            let stdX = Math.max(...lats)  - Math.min(...lats) ;
            let stdY = Math.max(...longs)  - Math.min(...longs) ;
            let std = Math.max(stdX, stdY)  ;

            if(std > 0.0){
                let deg = Math.floor(360/std);
                zoom = Math.floor( Math.log2(deg) );           
            
                if ( zoom < 1){
                    zoom = 1;
                }else if(zoom > 18){
                    zoom = 18;
                }
            }
        }
         
         

        var mymap = L.map("map").setView([meanLat,meanLong], zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mymap);                  
        
        for (var id in tourLocations) {
            addMarkerTours(tourLocations[id], mymap);
        }
        // tourLocations.forEach(function(item){addMarkerTours(item, mymap)});
    }
    
}

$('#collapseMap').on('shown.bs.collapse', function(e){

    showToursOnMap();
    
});