/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    $('#btn-add-email').click(function () {
        addEmail();
        return false;
    });
    $('#btn-send-pdf').click(function () {
        sendPdfEmails();
        return false;
    });
    changeSplaceno();
});

/**
 * Posle pozadavek na server o odeslani faktur na zaskrtle emaily
 */
function sendPdfEmails() {
    var idFaktury = getParameterByName('id_faktury');
    var btnSendPdf = $("#btn-send-pdf");
    $("#email-status").html("");

    $.post("ts_faktura.php?page=ajax&cislo_faktury=" + cislo_faktury+"&id_faktury=" + idFaktury, $("#form-send-pdf").serialize())
        .success(function (json) {
            json = JSON.parse(json);
            viewStatus(json);
        })
        .always(function () {
            btnSendPdf.removeClass("disabled");
        });
}


//pokud je pøidána nìjaká platba, pøiøadí k faktuøe checkbox "uhrazeno" = checked
function changeSplaceno(){
    var uhrazena_castka = 0;    
    var i = 1;
    while(document.getElementsByName("check_platby_"+i)[0]!=null && i <= 50){
        if(document.getElementsByName("check_platby_"+i)[0].checked==true){
            var castka_platby = document.getElementsByName("castka_platby_"+i)[0].value;
            if(document.getElementsByName("typ_platby_"+i)[0].value == "prijmovy"){
                uhrazena_castka = Number(uhrazena_castka) + Number(castka_platby);
            }else{
                uhrazena_castka = Number(uhrazena_castka) - Number(castka_platby);
            }            
        }
        i++;
    }
    if(document.getElementsByName("nova_platba_castka")[0]!=null){
        if(document.getElementsByName("nova_platba_castka")[0].value > 0 ){
            var castka_platby = document.getElementsByName("nova_platba_castka")[0].value;
            if(document.getElementById("typ_dokladu_prijmovy").checked == true){
                uhrazena_castka = Number(uhrazena_castka) + Number(castka_platby);
            }else{
                uhrazena_castka = Number(uhrazena_castka) - Number(castka_platby);
            }  
        }
    }
    //zjistim zda se jedna o dobropis nebo klasickou fakturu
    var select = document.getElementById("typ_faktury");
    var typFaktury = select.options[select.selectedIndex].value;  
    //u dobropisu pocitame zaporne castky
    if(typFaktury == "dobropis"){
        uhrazena_castka = -uhrazena_castka;
    }
    var castkaFaktury = document.getElementById("celkova_castka").textContent;
    castkaFaktury = Number(castkaFaktury.replace(/^\s+|\s+$/gm,'')); //zbabvime se mezer, trim nepodporuji vsechny browsery...
    
    if(uhrazena_castka <=0){
        //neuhrazeno       
        document.getElementsByName("zaplaceno")[0].value = 0;
        document.getElementById("zaplacenoText").innerHTML = "<b style=\"color:red\">Nezaplaceno</b>";
    }else if( uhrazena_castka <  castkaFaktury){
        //castecne_uhrazeno
        document.getElementsByName("zaplaceno")[0].value = 1;
        document.getElementById("zaplacenoText").innerHTML = "<b style=\"color:orange\">Èásteènì splaceno</b>";
    }else if( uhrazena_castka == castkaFaktury){
        //uhrazeno
        document.getElementsByName("zaplaceno")[0].value = 2;
        document.getElementById("zaplacenoText").innerHTML = "<b style=\"color:green\">Splaceno</b>";
    }else if( uhrazena_castka >  castkaFaktury){
        //preplatek
        document.getElementsByName("zaplaceno")[0].value = 3;
        document.getElementById("zaplacenoText").innerHTML = "<b style=\"color:blue\">Pøeplaceno</b>";
    }

}