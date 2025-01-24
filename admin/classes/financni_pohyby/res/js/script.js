$(document).ready(function () {
    var fps = FinancniPohybySerial._getInstance();
    fps.init();
    var fpp = FinancniPohybyPrehled._getInstance();
    fpp.init();
    $("#filter_vynulovat_fp").click(function(){
        var fps = FinancniPohybySerial._getInstance();
        fps.emptySerialFilter();
    });
});
