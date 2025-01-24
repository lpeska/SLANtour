/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    $('.checkbox_ceny').change(function(){
       changeNadpisy(""); 
    });
    
    $('.checkbox_zajezdy').change(function(){
       if($("#sablona").val()!="2" && $("#sablona").val()!="3"){ 
           uncheckOtherZajezdy($(this));
       }
       changeNadpisy(""); 
       setSlevy(""); 
    });
    
    $("#sablona").change(function(){
        if($("#sablona").val()=="2" || $("#sablona").val()=="3"){
            disableNadpisyZajezdu();
            disableDalsiSluzby();
            checkAllZajezdy();
        }else{
            enableNadpisyZajezdu();
            enableDalsiSluzby();
        }
    });
    
    $('.checkbox_slevy').change(function(){
       setSlevy(""); 
    });
    
    changeNadpisy("setDef");
    setSlevy("");
    if($("#sablona").val()=="2" || $("#sablona").val()=="3"){
        disableNadpisyZajezdu();
        disableDalsiSluzby();
        checkAllZajezdy();
    }  
    
    
});
function uncheckOtherZajezdy(zaj){
    $(".checkbox_zajezdy").prop( "checked", false );    
    zaj.prop( "checked", true );    
}
function disableNadpisyZajezdu(){
    $("#text_datum").prop( "disabled", true );
    $("#styl_datum").prop( "disabled", true );
    $("#text_cena").prop( "disabled", true );
    $("#syl_cena").prop( "disabled", true );
}
function enableNadpisyZajezdu(){
    $("#text_datum").prop( "disabled", false );
    $("#styl_datum").prop( "disabled", false );
    $("#text_cena").prop( "disabled", false );
    $("#syl_cena").prop( "disabled", false );
}

function disableDalsiSluzby(){
    $(".checkbox_dalsi_sluzby").prop( "disabled", true );  
}
function enableDalsiSluzby(){
    $(".checkbox_dalsi_sluzby").prop( "disabled", false );    
}
function checkAllZajezdy(){
    $(".checkbox_zajezdy").prop( "checked", true );    
}



function changeNadpisy(type){
    if(type === "setDef" && typeof(defaultCena)!=="undefined" && typeof(defaultZajezd)!=="undefined" ){
       $("#text_datum").val(defaultZajezd);
       $("#text_cena").val(defaultCena);          
    }   else{
       var id_zajezd = $('.checkbox_zajezdy:checked').filter(':first').val();
       var id_cena = $('.checkbox_ceny:checked').filter(':first').val();
       var datum = dataZajezdu["z_"+id_zajezd];
       var cena = cenyZajezdu["z_"+id_zajezd+"_"+id_cena];

       $("#text_datum").val(datum);
       $("#text_cena").val(cena);        
    }    
}

function setSlevy(type){   
       var id_zajezd = $('.checkbox_zajezdy:checked').filter(':first').val();
       var id_cena = $('.checkbox_ceny:checked').filter(':first').val();
       var id_sleva = $('.checkbox_slevy:checked').filter(':first').val();
       var pouzit_slevu = slevyPouzit["z_"+id_zajezd+"_"+id_sleva];
       if(pouzit_slevu){
           var vyseSlevy = slevyVyse["z_"+id_zajezd+"_"+id_sleva];
           var rawCena = cenyZajezduRaw["z_"+id_zajezd+"_"+id_cena];
           var newCena = rawCena;
           var slevyArr = vyseSlevy.split(" ");
           if(slevyArr[1]=="%"){
               newCena = Math.round(rawCena * (1- slevyArr[0]/100));
           }else{ //slevyArr[1]=="Kè"
               newCena = rawCena - slevyArr[0];
           }
           var textCena = "<span class='prev_cena'> "+rawCena+" Kè </span><br/>"+newCena+"<span class='mena'> Kè</span>";
           var textHeader = "SLEVA až "+vyseSlevy;
           var textPreheader = "FIRST MINUTE";
           
           $("#text_cena").val(textCena);
           $("#styl_cena").val("color:green;");
           $("#styl_nadpis_letaku").val("color:red;");
           $("#text_nadpis_letaku").val(textHeader);
           $("#text_preheader").val(textPreheader);
       }

  //     $("#text_datum").val(datum);
  //     $("#text_cena").val(cena);        
}

/**
 * Zmeni email prave vybraneho objektu v sekci emaily
 * @param radioBtn vybrane radioBtn
 */
function changeObjectEmail(radioBtn) {
    if (radioBtn.is(':checked')) {
        var objektId = radioBtn.val();
        var objednavkaId = getParameterByName('id_objednavka');
        var securityCode = getParameterByName('security_code');
        $.ajax("vouchery_objednavka.php?page=ajax&action=get-obj-email&id_objednavka=" + objednavkaId + "&security_code=" + securityCode + "&id_objekt=" + objektId)
            .done(function (email) {
                var tblEmaily = $('#tbl-emaily');
                tblEmaily.find('tr:contains("objekt") td.email').html(email);
                tblEmaily.find('tr:contains("objekt") td input[type=checkbox]').val(email);
            });
    }
}

/**
 * Zkontroluje pocty osob u sluzeb a pokud je sluzba "neobsazena", zmeni pozadi sluzby
 */
function checkOsobyCnt() {
    var ownerIndex = $('#tbl-sluzby th:contains("poèet")').index() + 1;
    var pocetCol = $('#tbl-sluzby > tbody > tr > td:nth-child(' + ownerIndex + ')');
    var osobyTblBodys = $('#subtbl-osoby > tbody');
    for (var i = 0; i < pocetCol.length; i++) {
        var checkedPersonCnt = $(osobyTblBodys[i]).find('input[type=checkbox]:checked').length
        var pocetValue = $(pocetCol[i]).html();
        var tr = $(osobyTblBodys[i]).parent().parent().parent();
    }
}

/**
 * Posle pozadavek na server o odeslani voucheru na zaskrtle emaily
 */
function sendPdfEmails() {
    var objednavkaId = getParameterByName('id_objednavka');
    var securityCode = getParameterByName('security_code');
    var btnSendPdf = $("#btn-send-pdf");
    $("#email-status").html("");

    $.post("vouchery_objednavka.php?page=ajax&action=send-emails&id_objednavka=" + objednavkaId + "&security_code=" + securityCode, $("#form-send-pdf").serialize())
        .success(function (json) {
            json = JSON.parse(json);
            viewStatus(json);
        })
        .always(function () {
            btnSendPdf.removeClass("disabled");
        });
}