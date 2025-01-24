/**
 * Spusti se po nacteni dokumentu
 */


$(document).ready(function () {
    $('#delete-selected').on('click', function () {
        if(confirm('Opravdu smazat?')){
             deleteSelected();
        }
        
    });
    $('#soldout-selected').on('click', function () {
        if(confirm('Opravdu provést akci?')){
             soldoutSelected();
        }
        
    });
    $('#generate_nove_sluzby').on('click', function () {
        genrateNoveSluzby();
    });
    
    $(".nastavit_vzorec").click(function (event) {
        zobrazit_parametry_vzorce(event);
    });
    
    $("#update_goGlobalHotelName").click(function (event) {
        goglobal_get_hotel_id();
    });
       
    $( ".select_kalkulacni_vzorec" ).each(function( index, element ) {
        zobrazit_parametry_vzorce_short(element);
    });
    
    $(".check_by_class").change(function (event) {
        var className = event.currentTarget.id;
        var checked = event.currentTarget.checked;
        check_all_from_class(className, checked);
    });           

    $(".copy_vzorec_from_cena").change(function () {
        var id_cena = $(this).attr("id");
        id_cena = id_cena.replace("copy_vzorec_from_cena_","");
        var id_cena_array = id_cena.split("_");
        var id_cena_target = id_cena_array[0];
        var index = id_cena_array[1];
                
        var id_cena_source = $(this).val();
        
        save_kv_settings(id_cena_source);
        copy_kv_from_to(id_cena_source, id_cena_target, index);
        $(this).val("");
        
        
    });           
        
    
    $(".filtr input, .filtr select").bind("keydown", function(event) {
      // track enter key
      var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
      if (keycode == 13) { // keycode for enter key
         // force the 'Enter Key' to implicitly click the Update button
         document.getElementById('defaultSubmitButton').click();
         return false;
      } else  {
         return true;
      }
   }); // end of function  
    
}); 

function genrateNoveSluzby(){
    var pocetNovychCen = $("#pocet_novych_cen").val();
    var pocetExistujicichCen = $("#pocet_existujicich_cen").val();
    var id_serial = get("id_serial");
    $.ajax({
            url: '?typ=cena&pozadavek=ajax_dalsi_sluzby&posledni_cena=' + pocetExistujicichCen+'&pocet_novych=' + pocetNovychCen+'&id_serial=' + id_serial,
            type: 'get',

            success: function (res) {
                $("#pocet_existujicich_cen").val( parseInt(pocetNovychCen) + parseInt(pocetExistujicichCen) );
                $("#celkovy_pocet_cen").val( parseInt(pocetNovychCen) + parseInt(pocetExistujicichCen));
                $('#table_nove_sluzby tr:last').after(res);                
                console.log(res);                
            }
        });
}

function save_kv_settings(id_cena){
    //- zjistit poradove cislo ceny
    //- ulozit id_vzorce
    //- zjistit zda je otevrene okno s jeho promennymi
    //- pokud ano, ulozit do promennych; to same s cenovymi mapami
    var i = 1;
    var index = -1;
    while(typeof $("#id_cena_"+i).val() != "undefined" ){
        if($("#id_cena_"+i).val() == id_cena){
            index = i;
            break;
        }
        i++;
    }
    var id_kv = $("#id_kalkulacni_vzorec_"+index).val();
    id_kv_list["cena_"+id_cena] = id_kv;
    
    if($("#nastaveni_vzorce_"+index).text().match("Vzorec") !== null){
        //otevreny promenne            
        var j = 1;
        var promenne = [];
        while(typeof $("#name_variable_"+j+"_cena_"+id_cena).val() != "undefined" ){
            var varName = $("#name_variable_"+j+"_cena_"+id_cena).val();
            var varType = $("#type_variable_"+j+"_cena_"+id_cena).val();
            var price = $("#value_variable_"+j+"_cena_"+id_cena).val();
            var currency = $("#variable_currency_"+j+"_cena_"+id_cena).val();
            var indexKV = 0;
            
            for(l=0; l < kv_promenne_list["vars_"+id_cena+"_"+id_kv].length;l++){
                if(kv_promenne_list["vars_"+id_cena+"_"+id_kv][l][0]==varName){
                   indexKV = l;
                   break;
                }
            }
            
            try {
                var flightFrom = kv_promenne_list["vars_"+id_cena+"_"+id_kv][indexKV][4];
                var flightTo = kv_promenne_list["vars_"+id_cena+"_"+id_kv][indexKV][5] ;
                var directFlight = kv_promenne_list["vars_"+id_cena+"_"+id_kv][indexKV][6] ;
            }catch (e) {
                var flightFrom = "";
                var flightTo = "";
                var directFlight = 0;
            }
            if(varType == "letuska"  && typeof $("#global_checkbox_cenova_mapa_cena_"+id_cena+"_"+varName).val() != "undefined"){
                flightFrom = $("#flight_from_cena_"+id_cena+"_"+varName).val();
                flightFrom = $("#flight_to_cena_"+id_cena+"_"+varName).val();
                if(flightFrom = $("#flight_direct_cena_"+id_cena+"_"+varName)[0].checked){
                   directFlight = 1; 
                }else{
                   directFlight = 0; 
                }
            }
            promenne.push([varName,varType,price,currency,flightFrom,flightTo,directFlight]);
            var cenova_mapa = [];
            if((varType == "timeMap" || varType == "letuska" || varType == "external") && typeof $("#global_checkbox_cenova_mapa_cena_"+id_cena+"_"+varName).val() != "undefined"){
                var k = 1;
                while(typeof $("#cm_termin_od_"+k+"_cena_"+id_cena+"_"+varName).val() != "undefined"  &&
                       ($("#cm_termin_od_"+k+"_cena_"+id_cena+"_"+varName).val() != "" ||
                        $("#cm_termin_do_"+k+"_cena_"+id_cena+"_"+varName).val() != "" ||
                        $("#cm_castka_"+k+"_cena_"+id_cena+"_"+varName).val() != "") ){
                    
                    var termin_od = $("#cm_termin_od_"+k+"_cena_"+id_cena+"_"+varName).val();
                    var termin_do = $("#cm_termin_do_"+k+"_cena_"+id_cena+"_"+varName).val();
                    var castka = $("#cm_castka_"+k+"_cena_"+id_cena+"_"+varName).val();
                    
                    cenova_mapa.push([termin_od,termin_do,castka]);
                    k++;
                }
                cenove_mapy["timemap_cena_"+id_cena+"_"+id_kv+"_"+varName] = cenova_mapa;
                
            }
            j++;
        }
        kv_promenne_list["vars_"+id_cena+"_"+id_kv] = promenne;
    }
    
    
}

function copy_kv_from_to(id_cena_source, id_cena_target, index){
    id_kv_list["cena_"+id_cena_target] = id_kv_list["cena_"+id_cena_source];
    var id_kv = id_kv_list["cena_"+id_cena_target];
    $("#id_kalkulacni_vzorec_"+index).val(id_kv);
 
    kv_promenne_list["vars_"+id_cena_target+"_"+id_kv] = kv_promenne_list["vars_"+id_cena_source+"_"+id_kv] ;
    var promenne = kv_promenne_list["vars_"+id_cena_target+"_"+id_kv];
    
    var timeMapPromenne = [];
    var timeMapTyp = [];
    var flightFrom = [];
    var flightTo = [];
    var directFlight = [];
    for(var i = 0; i < promenne.length; i++){
        if(promenne[i][1]=="timeMap" || promenne[i][1]=="letuska" || promenne[i][1]=="external"){
            var varName = promenne[i][0];
            timeMapPromenne.push(varName);
            timeMapTyp.push(promenne[i][1]);
            flightFrom.push(promenne[i][4]);
            flightTo.push(promenne[i][5]);
            directFlight.push(promenne[i][6]);
            
            cenove_mapy["timemap_cena_"+id_cena_target+"_"+id_kv+"_"+varName] = cenove_mapy["timemap_cena_"+id_cena_source+"_"+id_kv+"_"+varName];
        }
    }
    zpv(index);
    for(var j = 0; j < timeMapPromenne.length; j++){
        show_cenova_mapa("cena_"+id_cena_target, timeMapPromenne[j], id_kv, timeMapTyp[j], flightFrom[j],flightTo[j],directFlight[j]);
    }
    
}

function czechDateToDays(date){
    date = date.replace(" ","");
    var date_array = date.split(".");
    if(date_array[0].length == 1){
       date_array[0] = "0"+date_array[0]; 
    }
    if(date_array[1].length == 1){
       date_array[1] = "0"+date_array[1]; 
    }
    return new Date(date_array[2]+"-"+date_array[1]+"-"+date_array[0]).getTime();
}


function calculate_overlap(t1_od,t1_do,t2_od, t2_do ){
    t1_od = czechDateToDays(t1_od);
    t1_do = czechDateToDays(t1_do);
    t2_od = czechDateToDays(t2_od);
    t2_do = czechDateToDays(t2_do);
    if(t1_do < t2_od || t2_do < t1_od ){
        return 0;
    }else if(t1_od == t1_do){
        return 1;
    }else{
        var overlap = Math.min(t1_do,t2_do) - Math.max(t1_od,t2_od);
        return overlap/(t1_do - t1_od);
    }
    
}

function updatePricesByKV(){
    var termin_od = $("#zajezd_termin_od").val();
    var termin_do = $("#zajezd_termin_do").val();
    //pokud form_type==edit, trochu jinak ceny zobrazime
    
    if (termin_od == "" || termin_do == "") {
        //do nothing
        return;
    }
    
    $(".cena_id").each(function(index, element){
        var form_type = $("#form_type").val(); 
        //pro kazdou cenu zkontroluju, zda pro ni existuje kalkulacni vzorec a nastaveni promennych. Pokud ano, predpocitam cenu
        var id_cena = $(this).val();
        var id_kv = id_kv_list["cena_"+id_cena];
        if( typeof id_kv !== 'undefined' ){
            var vzorec_text = vzorec["vzorec_"+id_kv];
            var kv_vars = kv_promenne_list["vars_"+id_cena+"_"+id_kv];
            if(kv_vars instanceof Array){                
                for(var i = 0; i < kv_vars.length; i++){//for all variables
                    var mena_prepocet = 0;
                    for(var k = 0; k < meny.length; k++){
                        if(meny[k][0] == kv_vars[i][3]){//soucasna mena
                            mena_prepocet = meny[k][2] ;
                            break;
                        }
                    }
                    if(kv_vars[i][1]=="const"){
                        //use const                        
                        var castka = mena_prepocet * kv_vars[i][2];
                        vzorec_text = vzorec_text.replace(kv_vars[i][0],castka);

                    }else if(kv_vars[i][1]=="timeMap"){
                        var cm = cenove_mapy["timemap_cena_"+id_cena+"_"+id_kv+"_"+kv_vars[i][0]];
                        if(cm instanceof Array){ 
                            for(var j = 0; j < cm.length; j++){//for all price map records
                                //TODO: improve calculation, v tuto chvili vubec neresi prekryv terminu - vezme se prvni pouzitelny termin
                                if(calculate_overlap(termin_od,termin_do,cm[j][0],cm[j][1]) > 0 ){
                                    var castka = mena_prepocet * cm[i][2];
                                    vzorec_text = vzorec_text.replace(kv_vars[i][0],castka);
                                    break; 
                                }
                            }
                        }    
                    }
                }
                //evaluate result - only if all variables are instanced
                if(vzorec_text.match(/[a-zA-Z]/) == null){
                    var final_price = eval(vzorec_text);
                    if(form_type == "edit"){
                      $("#castka_kv_"+(index+1)).html(final_price); 
                      $("#some_kv_exists").val("1");
                    }else{
                      $("#castka_"+(index+1)).val(final_price);  
                    }                    
                    
                }
            }
        }
    });  
    if($("#form_type").val() == "edit"){
        if($("#some_kv_exists").val() === "0"){
            //nemam zadny KV - neni duvod zobrazovat cely sloupec
            $(".cena_dle_kv").remove();
        }
    }
}

function check_all_from_class(className, checked){
   $("."+className).prop({checked: checked});
}

function vygenerovane_terminy_delete_row(row_id){
   $("#termin_row_"+row_id).remove();
   vygenerovane_terminy_update_row_numbers();
}

function vygenerovane_terminy_update_row_numbers(){
    $(".termin_row").each(function(index,element){
        var former_id = element.id;
        former_id = former_id.replace("termin_row_","");        
        element.id = "termin_row_"+(index+1);
        
        $(this).find(".delete_row").attr("onClick","javascript:vygenerovane_terminy_delete_row('"+(index+1)+"');") ;  
        
        $(this).find("#termin_od_"+former_id).attr("name","termin_od_"+(index+1));
        $(this).find("#termin_od_"+former_id).attr("id","termin_od_"+(index+1));
        
        
        $(this).find("#termin_do_"+former_id).attr("name","termin_do_"+(index+1)); 
        $(this).find("#termin_do_"+former_id).attr("id","termin_do_"+(index+1));
          

        $(this).find(".cena_cell").each(function(i, el){
            var cena_id = el.id;
            cena_id = cena_id.replace("cena_cell_","");
            $(this).find(".checkbox_cena").attr("id","checkbox_cena_"+cena_id+"_"+(index+1));
            
            $(this).find(".bigNumber").attr("id","cena_"+cena_id+"_"+(index+1));
            $(this).find(".bigNumber").attr("name","cena_"+cena_id+"_"+(index+1));
            
        });
    });   
}

function toggle_check_first_price(){
    if($( "td.cena_cell:first-of-type input.checkbox_cena:checked" ).size() > 0){
         $( "td.cena_cell:first-of-type input.checkbox_cena" ).attr('checked', false);
    }else{
         $( "td.cena_cell:first-of-type input.checkbox_cena" ).attr('checked', true);
    }
}



function refresh_and_round(){
    refresh_checked('.kc_val');
    round_checked("90");
}

function refresh_checked(className){
    //for each checked input
    $(".checkbox_cena:checked").each(function(index, element){
        var currentID = element.id;
        currentID = currentID.replace("checkbox_","");
        
        var newVal = $("#"+currentID).parent().find(className).text();
        $("#"+currentID).val(newVal);
        
    });
}

function round_checked(roundType){
    //for each checked input
    $(".checkbox_cena:checked").each(function(index, element){
        var currentID = element.id;
        currentID = currentID.replace("checkbox_","");
        if($("#"+currentID).val()!==""){
            $("#"+currentID).val( round_by_type($("#"+currentID).val(),roundType) );
        }
    });
}
function round_by_type(value, roundType){
    if(roundType == "10"){
        return Math.ceil(value/10)*10;
    }else if(roundType == "100"){
        return Math.ceil(value/100)*100;
    }else if(roundType == "90"){
        return (Math.ceil(value/100)*100)-10;
    }
}

function calculate_all_pc(cena_id){
    var vzorec_id = id_kv_list[cena_id];
    var var_array = vzorec_promenne["vzorec_"+vzorec_id];
    if (typeof var_array != 'undefined'){
        for (var i = 0; i < var_array.length; i++) {
            if(var_array[i][1]=="timeMap" || var_array[i][1]=="letuska" || var_array[i][1]=="external"){
                var variableName = var_array[i][0];
                $(".row_cenova_mapa_"+cena_id+"_"+variableName).each(function(index,element){
                    var row_id = index+1;
                    calculate_pc(row_id, cena_id, variableName);
                });
            }
        }
    }
}

function calculate_pc(row_id, cena_id, variableName){
    var vzorec_id = id_kv_list[cena_id];
    var vzorec_text = vzorec["vzorec_"+vzorec_id];                       

    var var_array = vzorec_promenne["vzorec_"+vzorec_id];
    if (typeof var_array == 'undefined'){

    }else{


        for (var i = 0; i < var_array.length; i++) {
            var mena = $("#variable_currency_"+(i+1)+"_"+cena_id).val();
            var kurz = 0;
            var curr_price = 0;
            for(var j=0; j < meny.length; j++){
                if(meny[j][0] == mena){
                    kurz = meny[j][2];
                    break;
                }
            }

            if(var_array[i][1]=="const"){
                curr_price = $("#value_variable_"+(i+1)+"_"+cena_id).val();  
                vzorec_text = vzorec_text.replace(var_array[i][0],(curr_price*kurz));
            }else if((var_array[i][1]=="timeMap" || var_array[i][1]=="letuska" || var_array[i][1]=="external") && var_array[i][0]==variableName){
                curr_price = $("#cm_castka_"+row_id+"_"+cena_id+"_"+var_array[i][0]).val();
                if(curr_price !== ""){//nahradime pouze pokud je cena nejak zadana
                    vzorec_text = vzorec_text.replace(variableName,(curr_price*kurz));
                }
            }else{
                //todo: nejak osetrit vypis chyb
                $("#prodejni_cena_"+row_id+"_"+cena_id+"_"+variableName).html("Neznámý typ promìnné!");
            }                                

        }
        //probehla vsechna nahrazeni promennych
        if(vzorec_text.match(/[a-zA-Z]/)===null){
            var result = eval(vzorec_text);
            $("#prodejni_cena_"+row_id+"_"+cena_id+"_"+variableName).html(result);
        }else{
            //todo: osetrit vypis chyb
          //  $("#prodejni_cena_"+row_id+"_"+cena_id+"_"+variableName).html("Nìkterá promìnná není vyplnìná!");
        }

    }            
}

function initialize_callendar(){
    $(".dynamicCalendar-ymd").datepicker('destroy').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: "dd.mm. yy",
        dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Èt', 'Pa', 'So'],
        monthNames: ['Leden', 'Únor', 'Bøezen', 'Duben', 'Kvìten', 'Èerven', 'Èervenec', 'Srpen', 'Záøí', 'Øíjen', 'Listopad', 'Prosinec'],
        monthNamesShort: ['Leden', 'Únor', 'Bøezen', 'Duben', 'Kvìten', 'Èerven', 'Èervenec', 'Srpen', 'Záøí', 'Øíjen', 'Listopad', 'Prosinec'],
        yearRange: 'c-1:c+10',
        firstDay: 1});

    $(".termin_od.dynamicCalendar-ymd").change(function(){
        var date_from = $(this).val();
        if(date_from != "" ){
            
            var input_from_id = $(this).attr("id");
            var input_to_id = input_from_id.replace("termin_od","termin_do");
            var date_to = date_from; //todo: nejaka kalkulace dat - napr. dalsi den + delat zmenu pouze pokud termin do je mene/rovno nez od, pripadne pokud je prazdny
            var original_date_to = $("#"+input_to_id).val();
            if(original_date_to == ""){
                $("#"+input_to_id).val(date_to);
            }else{
                var to = new Date(original_date_to.split(".").reverse().join("-").replace(" ",""));
                var from = new Date(date_from.split(".").reverse().join("-").replace(" ",""));
                if(to.getTime() < from.getTime()){
                    $("#"+input_to_id).val(date_to);
                }                
            }
            
        }
    });
    $(".termin_do.dynamicCalendar-ymd").change(function(){
        var date_to = $(this).val();
        if(date_to != "" ){            
            var input_to_id = $(this).attr("id");
            var input_from_id = input_to_id.replace("termin_do","termin_od");
            var row_id_arr = input_from_id.match(/termin_od_([0-9]+)/);
            var orig_row_id = row_id_arr[0].replace("termin_od_","");            
            var row_id = Number(orig_row_id)+1;
            input_from_id = input_from_id.replace("termin_od_"+orig_row_id,"termin_od_"+row_id);
            
            var next_date_from = $("#"+input_from_id).val()
            if(next_date_from == "" ){ 
                $("#"+input_from_id).val(date_to);
            }            
        }
    });    
    
}

function cenova_mapa_delete_rows(cena_id, variableName){
    $(".checkbox_cenova_mapa_"+cena_id+"_"+variableName+":checked").each(function(index,element){
        var id = element.id;
        id = id.replace("checkbox_cenova_mapa_","");
        cenova_mapa_delete_row(id, true);
    })
    updateRowNumbers(cena_id+"_"+variableName);
}
function cenova_mapa_delete_row(id, multipleRows){
    $("#row_cenova_mapa_"+id).remove();
    if(multipleRows === false){
        var idArray = id.split("_");
        var restrictedID = idArray[0]+"_"+idArray[1]+"_"+idArray[2];
        updateRowNumbers(restrictedID);
    }
}

function updateRowNumbers(row_id){
    $(".row_cenova_mapa_"+row_id).each(function(index,element){
        element.id = "row_cenova_mapa_"+row_id+"_"+(index+1);
        var row_array = row_id.split("_");
        var cena_id = row_array[0]+"_"+row_array[1];
        var variable_name = row_array[2];
        
        $(this).find(".termin_od").attr("id","cm_termin_od_"+(index+1)+"_"+row_id);
        $(this).find(".termin_od").attr("name","cm_termin_od_"+(index+1)+"_"+row_id);
        
        $(this).find(".termin_do").attr("id","cm_termin_do_"+(index+1)+"_"+row_id);
        $(this).find(".termin_do").attr("name","cm_termin_do_"+(index+1)+"_"+row_id);   
        
        $(this).find(".poznamka").attr("id","cm_poznamka_"+(index+1)+"_"+row_id);
        $(this).find(".poznamka").attr("name","cm_poznamka_"+(index+1)+"_"+row_id);   

        $(this).find(".externalID").attr("id","cm_externalID_"+(index+1)+"_"+row_id);
        $(this).find(".externalID").attr("name","cm_externalID_"+(index+1)+"_"+row_id);         
        
        $(this).find(".checkbox_terminy").attr("id","use_dates_"+(index+1)+"_"+row_id);
        $(this).find(".checkbox_terminy").attr("name","use_dates_"+(index+1)+"_"+row_id);  
        
        
                        
        $(this).find(".castka").attr("id","cm_castka_"+(index+1)+"_"+row_id);
        $(this).find(".castka").attr("name","cm_castka_"+(index+1)+"_"+row_id); 
        $(this).find(".castka").attr("onChange","javascript:calculate_pc("+(index+1)+",'"+cena_id+"','"+variable_name+"');") ; 
         
        $(this).find(".label_cm_castka").attr("id","label_cm_castka_"+(index+1)+"_"+row_id); 
         
        $(this).find(".ext_prices").attr("id","ext_prices_"+(index+1)+"_"+row_id); 
         
        $(this).find(".prodejni_cena").attr("id","prodejni_cena_"+(index+1)+"_"+cena_id+"_"+variable_name); 
        
        $(this).find(".checkbox_cenova_mapa_"+row_id).attr("id","checkbox_cenova_mapa_"+row_id+"_"+(index+1));  
        
        $(this).find(".delete_link").attr("onClick","javascript:cenova_mapa_delete_row('"+row_id+"_"+(index+1)+"',false);") ;  
        
        
        $(this).find(".update_link").attr("id","update_cm_"+(index+1)+"_"+row_id);
        $(this).find(".update_link").attr("href","#cm_"+(index+1)+"_"+row_id);
        
        if($(this).find(".update_link").attr("onClick")!= undefined){
            if($(this).find(".update_link").attr("onClick").indexOf("update_letuska") !== -1){        
                $(this).find(".update_link").attr("onClick","javascript:update_letuska("+(index+1)+",'"+cena_id+"','"+variable_name+"');") ; 
                
                $(this).find(".termin_do_shift").attr("id","termin_do_shift_"+(index+1)+"_"+row_id);
                $(this).find(".termin_do_shift").attr("name","termin_do_shift_"+(index+1)+"_"+row_id);
                $(this).find(".name_anchor").attr("name","cm_"+(index+1)+"_"+row_id);
                
            }
            if($(this).find(".update_link").attr("onClick").indexOf("update_goglobal") !== -1){
                $(this).find(".update_link").attr("onClick","javascript:update_goglobal("+(index+1)+",'"+cena_id+"','"+variable_name+"');") ;  
                
                $(this).find(".name_anchor").attr("name","cm_"+(index+1)+"_"+row_id);
            }
            if($(this).find(".update_link").attr("onClick").indexOf("allow_manual_edit") !== -1){
                $(this).find(".update_link").attr("onClick","javascript:allow_manual_edit("+(index+1)+",'"+cena_id+"','"+variable_name+"');") ;  
            }
        }        
        $(this).find(".count").text((index+1));
    });
    
    initialize_callendar();
}
function check_all(event){
    var id = event.currentTarget.id;
    id = id.replace("global_checkbox_cenova_mapa_","");
    $(".checkbox_cenova_mapa_"+id).prop({checked: event.currentTarget.checked});
}
function check_all_dates(event){
    var id = event.currentTarget.id;
    id = id.replace("global_checkbox_terminy_","");
    $(".checkbox_terminy_"+id).prop({checked: event.currentTarget.checked});
}


//wrapper pro akce pri zmene vzorce: predevsim otevre dialog k vyplneni vzorce a zobrazi informace k predvyplnenym promennym
function changed_vzorec(vzorec){
    var poradove_cislo = vzorec.id.replace("id_kalkulacni_vzorec_","");
    var selected_id_vzorce = $("#"+vzorec.id).val();
    var cenaID = $("#id_cena_"+poradove_cislo).val();
    id_kv_list["cena_"+cenaID] = selected_id_vzorce;
    zobrazit_parametry_vzorce_short(vzorec);
    if(selected_id_vzorce!= "NULL" && selected_id_vzorce!= ""){
        zpv(poradove_cislo);
    }    
}

//zobrazi zkraceny vypis skutecne nastavenych parametru vzorce, nebo info o tom, ze chybi - pouzije se u seznamu sluzeb
function zobrazit_parametry_vzorce_short(vzorec){
     var poradove_cislo = vzorec.id.replace("id_kalkulacni_vzorec_","");   
    var id_cena = $("#id_cena_"+poradove_cislo).val();
    var id_vzorce = id_kv_list["cena_"+id_cena];
    var selected_id_vzorce = $("#"+vzorec.id).val();
    if(selected_id_vzorce!= "NULL" && selected_id_vzorce!= ""){
        var selected_id_vzorce = $("#"+vzorec.id).val();
        if(id_vzorce == selected_id_vzorce){
            var vars = kv_promenne_list["vars_"+id_cena+"_"+selected_id_vzorce];
            if (typeof vars == 'undefined'){
                $("#vzorec_params_"+poradove_cislo).html("<b class=\"red\">Promìnné vzorce dosud nebyly vyplnìné!</b>");
                zpv(poradove_cislo);
            }else{
                var outputHTML = "<b class=\"green\">Zadané promìnné:</b> "
                for (var i = 0; i < vars.length; i++) {
                    if(vars[i][1]=="timeMap"){
                       outputHTML += vars[i][0]+" (term. mapa); "; 
                    }else{
                       outputHTML += vars[i][0]+": "+vars[i][2]+"; ";
                    }                    
                }
                $("#vzorec_params_"+poradove_cislo).html(outputHTML);
            }
        }else{
            $("#vzorec_params_"+poradove_cislo).html("<b class=\"red\">Vzorec byl zmìnìn, vyplòte prosím nové promìnné!</b>");
            zpv(poradove_cislo);
        }
        
    }else{
        //vyhodili jsme vzorec, prestanu zobrazovat promenne a hlasku
        $("#vzorec_params_"+poradove_cislo).html("");
        spv(poradove_cislo);
    }
    
            
}

//wrapper k funkci zpv - vstup pomoci onClick eventu
function zobrazit_parametry_vzorce(event){
    var button_id = event.currentTarget.id;
    button_id = button_id.replace("nastavit_vzorec_","");
    zpv(button_id);
}

//skryje moznosti nastaveni promennych u vybraneho vzorce prirazeneho ke sluzbe
function spv(button_id){    
    var container_id = "#nastaveni_vzorce_"+button_id;
    $(container_id).empty();    
}

//zobrazi moznosti nastaveni promennych u vybraneho vzorce prirazeneho ke sluzbe
function zpv(button_id){    
    var container_id = "#nastaveni_vzorce_"+button_id;
    var cena_id = "cena_"+$("#id_cena_"+button_id).val();
    var id_cena = $("#id_cena_"+button_id).val();
    var selector_id = "#id_kalkulacni_vzorec_"+button_id; 
    var id_vzorce = $(selector_id+"  option:selected").val();

    var outputHTML = "<b>Vzorec:</b> "+ vzorec["vzorec_"+id_vzorce]+"<br/><br/><table><tr><th>Promìnná</th><th>Mìna</th><th>Hodnota</th></tr>";
    if(id_vzorce != "NULL"){
        var previous_id_vzorce = id_kv_list[cena_id];
        if(id_vzorce == previous_id_vzorce){
            //jedna se o stejny vzorec jako bylo predchozi nastaveni - vyhledam informace o zadanych promennych
            var promenne = vzorec_promenne["vzorec_"+id_vzorce];
            var nastaveni_promennych = kv_promenne_list["vars_"+id_cena+"_"+id_vzorce];
            for (var i = 0; i < promenne.length; i++) {
                var nazev_promenne = promenne[i][0];
                var typ_promenne = promenne[i][1];
                var hodnota_promenne = promenne[i][2];
                var mena_promenne = default_mena;
                var bez_meny = promenne[i][3];
                var flight_from = "PRG";
                var flight_to = "";
                var flight_direct = 0;
                var data_from_object = 0;
                
                if(nastaveni_promennych instanceof Array){
                    for(var j = 0; j < nastaveni_promennych.length; j++) {
                        if(nastaveni_promennych[j][0] == nazev_promenne){
                            hodnota_promenne = nastaveni_promennych[j][2];
                            mena_promenne = nastaveni_promennych[j][3];
                            flight_from = nastaveni_promennych[j][4];
                            flight_to = nastaveni_promennych[j][5];
                            flight_direct = nastaveni_promennych[j][6];
                            data_from_object = nastaveni_promennych[j][7];
                            break;
                        }
                    }     
                }
                outputHTML += show_form_vzorec_variables([nazev_promenne,typ_promenne,hodnota_promenne,mena_promenne,bez_meny,flight_from,flight_to,flight_direct, data_from_object],i,cena_id,id_vzorce);
            }            
        }else{
            //doslo ke zmene vzorce, zobrazim defaultni parametry            
            var promenne = vzorec_promenne["vzorec_"+id_vzorce];
            for (var i = 0; i < promenne.length; i++) {
                outputHTML += show_form_vzorec_variables(promenne[i],i,cena_id,id_vzorce);
            }
        }    
        
    }
    outputHTML += "</table>"
    $(container_id).html(outputHTML);
    
}

function show_form_vzorec_variables(variable,i,cena_id,id_vzorce){
    var html = "";
    i++;
    if(variable[1]=="const"){
        html ="<tr>\n\
                <td>"+variable[0]+" (konst.)</td>\n\
                <td>\n\
                    "+show_form_mena(i,cena_id,variable[3],variable[4])+"\n\
                </td>\n\
                <td>\n\
                    <input id=\"name_variable_"+i+"_"+cena_id+"\" name=\"name_variable_"+i+"_"+cena_id+"\" value=\""+variable[0]+"\" type=\"hidden\">\n\
                    <input id=\"type_variable_"+i+"_"+cena_id+"\" name=\"type_variable_"+i+"_"+cena_id+"\" value=\""+variable[1]+"\" type=\"hidden\">\n\
                    <input onChange=\"javascript:calculate_all_pc('"+cena_id+"')\" id=\"value_variable_"+i+"_"+cena_id+"\" name=\"value_variable_"+i+"_"+cena_id+"\" value=\""+variable[2]+"\" type=\"text\">\n\
                </td>\n\
              </tr>";
    }else if(variable[1]=="timeMap"){
        html ="<tr>\n\
                <td>"+variable[0]+" (termínová mapa)\n\
                <input id=\"name_variable_"+i+"_"+cena_id+"\" name=\"name_variable_"+i+"_"+cena_id+"\" value=\""+variable[0]+"\" type=\"hidden\">\n\
                <input id=\"type_variable_"+i+"_"+cena_id+"\" name=\"type_variable_"+i+"_"+cena_id+"\" value=\""+variable[1]+"\" type=\"hidden\">\n\
                </td>\n\
                <td>\n\
                    "+show_form_mena(i,cena_id,variable[3],variable[4])+"\n\
                </td>\n\
                <td id=\"cenova_mapa_"+cena_id+"_"+variable[0]+"_"+id_vzorce+"\">\n\
                <a href=\"#\" onclick=\"javascript:show_cenova_mapa('"+cena_id+"','"+variable[0]+"','"+id_vzorce+"', 'timeMap', '"+variable[5]+"', '"+variable[6]+"', '"+variable[7]+"');\" >zobrazit cenovou mapu -&gt;</a> \n\
                </td>\n\
            </tr>";
    }else if(variable[1]=="external"){
        var select_goglobal = "";
        if(objekty_goglobal.length > 0){
            select_goglobal = "<select id=\"select_goglobal_"+i+"_"+cena_id+"\" name=\"select_goglobal_"+i+"_"+cena_id+"\">\n\
                    <option value=\"-1\">---</option>\n";
            for (var j = 0; j < objekty_goglobal.length; j++) {
                var select = "";
                if(variable[8] == objekty_goglobal[j][0]){
                    select = "selected=\"selected\"";
                }
                select_goglobal += "<option value=\""+objekty_goglobal[j][0]+"\" "+select+">"+objekty_goglobal[j][1]+" ("+objekty_goglobal[j][2]+")</option>";              
            }
            select_goglobal += "</select><br/>";
            
        }
        html ="<tr>\n\
                <td>"+variable[0]+" (GoGlobal API)\n\
                <input id=\"name_variable_"+i+"_"+cena_id+"\" name=\"name_variable_"+i+"_"+cena_id+"\" value=\""+variable[0]+"\" type=\"hidden\">\n\
                <input id=\"type_variable_"+i+"_"+cena_id+"\" name=\"type_variable_"+i+"_"+cena_id+"\" value=\""+variable[1]+"\" type=\"hidden\">\n\
                </td>\n\
                <td>\n\
                    "+show_form_mena(i,cena_id,variable[3],variable[4])+"\n\
                </td>\n\
                <td id=\"cenova_mapa_"+cena_id+"_"+variable[0]+"_"+id_vzorce+"\">\n\
                    "+select_goglobal+"\n\
                    <button onclick=\"javascript:show_cenova_mapa('"+cena_id+"','"+variable[0]+"','"+id_vzorce+"', 'external', '"+variable[5]+"', '"+variable[6]+"', '"+variable[7]+"',"+i+");\" type='button'>zobrazit termínovou mapu</button> \n\
                </td>\n\
            </tr>";
    }else if(variable[1]=="letuska"){
        var select_letuska = "";
        if(objekty_letuska.length > 0){
            select_letuska = "<select id=\"select_letuska_"+i+"_"+cena_id+"\" name=\"select_letuska_"+i+"_"+cena_id+"\">\n\
                    <option value=\"-1\">---</option>\n";
            for (var j = 0; j < objekty_letuska.length; j++) {
                var select = "";
                if(variable[8] == objekty_letuska[j][0]){
                    select = "selected=\"selected\"";                    
                }
                select_letuska += "<option value=\""+objekty_letuska[j][0]+"\" "+select+">"+objekty_letuska[j][1]+" ("+objekty_letuska[j][2]+")</option>";              
            }
            select_letuska += "</select><br/>";
            
        }        
        html ="<tr>\n\
                <td>"+variable[0]+" (Letuska.cz API)\n\
                <input id=\"name_variable_"+i+"_"+cena_id+"\" name=\"name_variable_"+i+"_"+cena_id+"\" value=\""+variable[0]+"\" type=\"hidden\">\n\
                <input id=\"type_variable_"+i+"_"+cena_id+"\" name=\"type_variable_"+i+"_"+cena_id+"\" value=\""+variable[1]+"\" type=\"hidden\">\n\
                </td>\n\
                <td>\n\
                    "+show_form_mena(i,cena_id,variable[3],variable[4])+"\n\
                </td>\n\
                <td id=\"cenova_mapa_"+cena_id+"_"+variable[0]+"_"+id_vzorce+"\">\n\
                    "+select_letuska+"\n\
                    <button onclick=\"javascript:show_cenova_mapa('"+cena_id+"','"+variable[0]+"','"+id_vzorce+"', 'letuska', '"+variable[5]+"', '"+variable[6]+"', '"+variable[7]+"',"+i+");\"  type='button'>zobrazit termínovou mapu</button> \n\
                </td>\n\
            </tr>";    
    }
    return html;
}

function show_form_mena(i, cena_id, selected, bez_meny){
    if(bez_meny == 1){
        var output = "Bez mìny <input type=\"hidden\" id=\"variable_currency_"+i+"_"+cena_id+"\" name=\"variable_currency_"+i+"_"+cena_id+"\" value=\""+mena_bez_prepoctu+"\" />";
    }else{
        var output = "<select onChange=\"javascript:calculate_all_pc('"+cena_id+"')\" id=\"variable_currency_"+i+"_"+cena_id+"\" name=\"variable_currency_"+i+"_"+cena_id+"\">";
        var sel="";
        for(var j=0;j < meny.length;j++){
            if(selected==meny[j][0] || (typeof(selected)=="undefined" && meny[j][0]==default_mena)){
                sel="selected=\"selected\"";
            }else{
                sel="";
            }
            output +="<option value=\""+meny[j][0]+"\" "+sel+">"+meny[j][1]+"</option>";
        }
        output += "</select>";
    }
    return output;
}


function show_cenova_mapa_objekt(typ, flight_from, flight_to, flight_direct){
    var cenova_mapa = $("#cenova_mapa");
    var text_update_all = "";
    var offers_column = "";
    var form_before_pricemap = "";
    if(typ == 6){
        text_update_all = "<a href=\"#\" onclick=\"javascript:update_goglobal_all('cena_0','var1', '"+id_objektu+"')\" title=\"Obnovit ceny všech øádkù\">Update vše</a>";
        offers_column = "<th>Nalezené nabídky";
        typ = "external";
    }else if(typ == 5){
        
        text_update_all = "<a href=\"#\" onclick=\"javascript:update_letuska_all('cena_0','var1')\" title=\"Obnovit ceny všech øádkù\">Update vše</a> | <a href=\"#\" onclick=\"javascript:allow_manual_edit_all('cena_0','var1')\" title=\"Povolit manuální editaci všech cen\">Editovat vše</a>";
        offers_column = "<th>Nalezené nabídky";
        typ = "letuska";
    }
    var j = 1;    
    
    var cm_text = "<table>\n\
                        <tr><th><input type=\"checkbox\" class=\"global_checkbox_cenova_mapa\" id=\"global_checkbox_cenova_mapa_cena_0_var1\" title=\"Vybrat všechny\"/> <th>Id<th>Termín od<th>Termín do<th><input type=\"checkbox\" class=\"global_checkbox_terminy\" id=\"global_checkbox_terminy_cena_0_var1\" title=\"Vybrat všechny termíny\" checked=\"checked\"/> Použít termíny <br/>pøi generování zájezdù<th>Èástka<th>Prodejni cena <br/>(pøed zaokrouhlením)"+ offers_column+"<th>"+text_update_all +"\n";
    //todo: nacist jiz existujici data
    
    if (typeof cenove_mapy != 'undefined'){
        var existujici_cm = cenove_mapy["timemap_cena_0_0_var1"];
        for(var k=0;k < existujici_cm.length;k++){
            cm_text += show_cenova_mapa_row((k+1),existujici_cm[k][0], existujici_cm[k][1], existujici_cm[k][2], "cena_0", "var1", 0,typ,existujici_cm[k][3],existujici_cm[k][4],existujici_cm[k][5],existujici_cm[k][6]);
        }
        j=k+1;
    }

    cm_text += show_cenova_mapa_row(j,"","","", "cena_0", "var1", 0, typ,"","",1, 0);
    cm_text += "<tr id=\"cm_last_row_cena_0_var1_0\">\n\
                    <td colspan=\"8\">\n\
                    <button onclick=\"(function(event){show_cenova_mapa_add_row('cena_0','var1','0','"+typ+"');})();\" type='button'>Pøidat øádek</button>\n\
                    <button onclick=\"(function(event){show_cenova_mapa_add_multiple_rows('cena_0','var1','0','"+typ+"');})();\"  type='button'> Pøidat</button>  \n\
                        <input class='two_digit' name='add_count_cena_0_var1' value='5' id='add_count_cena_0_var1'> øádkù a posunout termíny o <input  class='two_digit' name='shift_days_cena_0_var1' value='7' id='shift_days_cena_0_var1'> dní; \n\
                    Zaškrtnuté øádky: <button class=\"red\" onclick=\"javascript:cenova_mapa_delete_rows('cena_0','var1');\"  type='button'>smazat</button>\n\
                </table>";

    cenova_mapa.html(cm_text);    
    initialize_callendar();
    calculate_all_pc(0);
    
    
    
    $(".global_checkbox_terminy").change(function (event) {
        check_all_dates(event);
    }); 
    
    $(".global_checkbox_cenova_mapa").change(function (event) {
        check_all(event);
    });    
}



function show_cenova_mapa(cena_id, variable_name,  vzorec_id, typ, flight_from, flight_to, flight_direct, variable_id){
    var cenova_mapa = $("#cenova_mapa_"+cena_id+"_"+variable_name+"_"+vzorec_id+"");        
    var text_update_all = "";
    var offers_column = "";
    var form_before_pricemap = "";
    if(typ == "external"){
        text_update_all = "<a href=\"#\" onclick=\"javascript:update_goglobal_all('"+cena_id+"','"+variable_name+"','"+id_objektu+"')\" title=\"Obnovit ceny všech øádkù\">Update vše</a>";
        offers_column = "<th>Nalezené nabídky";
        
        if(typeof $("#select_goglobal_"+variable_id+"_"+cena_id+"") !== 'undefined'){
            var dataFromObject = $("#select_goglobal_"+variable_id+"_"+cena_id+"").val();
            existujici_ocm = objekty_cenova_mapa[dataFromObject];
        }    
    
    }else if(typ == "letuska"){
        var check_direct = "";
        if(flight_direct>0){
            check_direct = "checked=\"checked\"";
        }
        form_before_pricemap = "Kód letištì - odlet: <input type=\"text\" value=\""+flight_from+"\" name=\"flight_from_"+cena_id+"_"+variable_name+"\" id=\"flight_from_"+cena_id+"_"+variable_name+"\"/>,\n\
                             kód letištì - pøílet:  <input type=\"text\" value=\""+flight_to+"\" name=\"flight_to_"+cena_id+"_"+variable_name+"\" id=\"flight_to_"+cena_id+"_"+variable_name+"\"/>, \n\
                             pouze pøímé lety: <input type=\"checkbox\" "+check_direct+" value=\"1\" name=\"flight_direct_"+cena_id+"_"+variable_name+"\" id=\"flight_direct_"+cena_id+"_"+variable_name+"\"/>\n\
                             <br/>";
        text_update_all = "<a href=\"#\" onclick=\"javascript:update_letuska_all('"+cena_id+"','"+variable_name+"')\" title=\"Obnovit ceny všech øádkù\">Update vše</a>";
        offers_column = "<th>Nalezené nabídky";
        if(typeof $("#select_letuska_"+variable_id+"_"+cena_id+"") !== 'undefined'){
            var dataFromObject = $("#select_letuska_"+variable_id+"_"+cena_id+"").val();
            existujici_ocm = objekty_cenova_mapa[dataFromObject];
        }
    }
    var j = 1;    
    
    var cm_text = form_before_pricemap+"<table>\n\
                        <tr><th><input type=\"hidden\" name=\"cenova_mapa_opened_"+cena_id+"_"+variable_name+"\" value=\"1\" /> <input type=\"checkbox\" class=\"global_checkbox_cenova_mapa\" id=\"global_checkbox_cenova_mapa_"+cena_id+"_"+variable_name+"\" title=\"Vybrat všechny\"/> <th>Id<th>Termín od<th>Termín do<th><input type=\"checkbox\" class=\"global_checkbox_terminy\" id=\"global_checkbox_terminy_"+cena_id+"_"+variable_name+"\" title=\"Vybrat všechny termíny\" checked=\"checked\"/> Použít termíny <br/>pøi generování zájezdù<th>"+variable_name+"<th>Prodejni cena <br/>(pøed zaokrouhlením)"+ offers_column+"<th>"+text_update_all +"\n";
    //todo: nacist jiz existujici data
    var existujici_cm = cenove_mapy["timemap_"+cena_id+"_"+vzorec_id+"_"+variable_name];
    
    if (typeof existujici_ocm != 'undefined'){
        for(var k=0;k < existujici_ocm.length;k++){
            cm_text += show_cenova_mapa_row((j),existujici_ocm[k][0], existujici_ocm[k][1], existujici_ocm[k][2], cena_id, variable_name,  vzorec_id,typ,existujici_ocm[k][3],existujici_ocm[k][4],existujici_ocm[k][5],existujici_ocm[k][6], "object", dataFromObject);        
            j++;
        }
    }
    
    if (typeof existujici_cm != 'undefined'){
        for(var k=0;k < existujici_cm.length;k++){
            cm_text += show_cenova_mapa_row((j),existujici_cm[k][0], existujici_cm[k][1], existujici_cm[k][2], cena_id, variable_name,  vzorec_id,typ,existujici_cm[k][3],existujici_cm[k][4],existujici_cm[k][5],existujici_cm[k][6]);
            j++;
        }
    }
    if(j==1){
      cm_text += show_cenova_mapa_row(j,"","","",cena_id, variable_name,  vzorec_id,typ,"","",1, 0);
    }
    cm_text += "<tr id=\"cm_last_row_"+cena_id+"_"+variable_name+"_"+vzorec_id+"\">\n\
                    <td colspan=\"8\">\n\
                        <button onclick=\"(function(event){show_cenova_mapa_add_row('"+cena_id+"','"+variable_name+"','"+vzorec_id+"','"+typ+"');})();\" type='button'>Pøidat øádek</button>\n\
                        <button onclick=\"(function(event){show_cenova_mapa_add_multiple_rows('"+cena_id+"','"+variable_name+"','"+vzorec_id+"','"+typ+"');})();\"  type='button'> Pøidat</button>  \n\
                            <input class='two_digit' name='add_count_"+cena_id+"_"+variable_name+"' value='5' id='add_count_"+cena_id+"_"+variable_name+"'> øádkù a posunout termíny o <input  class='two_digit' name='shift_days_"+cena_id+"_"+variable_name+"' value='7' id='shift_days_"+cena_id+"_"+variable_name+"'> dní; \n\
                        Zaškrtnuté øádky: <button class=\"red\" onclick=\"javascript:cenova_mapa_delete_rows('"+cena_id+"','"+variable_name+"');\"  type='button'>smazat</button>\n\
                </table>";
    var prev_html = cenova_mapa.html();
    var retainStr = "";
    if(prev_html.indexOf("<select") != -1){
       var head = prev_html.indexOf("<select");
       var tail = prev_html.indexOf("/select>") +8;
       var lenght = tail - head;
       retainStr = prev_html.substr(head,lenght)+ "<br/>";
    }
    cenova_mapa.html(retainStr +cm_text);         
    initialize_callendar();
    calculate_all_pc(cena_id);
    
    
    
    $(".global_checkbox_terminy").change(function (event) {
        check_all_dates(event);
    }); 
    
    $(".global_checkbox_cenova_mapa").change(function (event) {
        check_all(event);
    });    
}

function show_cenova_mapa_row(id, termin_od, termin_do,  castka, cena_id, variable_name,  vzorec_id, typ, external_id, external_text, useDatesInTourGeneration, terminShift, source, objekt_id){
    var text_castka = "";
    var updateButton = "";
    var ajaxResults = "";
    var terminDoShift = "";
    var objekt_link = "";
    var external_text_tags = external_text.replace(/\[/g,"<").replace(/\]/g,">");
    
    if(source == "object"){
        //pouze zobrazíme existující termíny, nebudou editovatelné ani se nebudou pøímo ukládat
        if(useDatesInTourGeneration === "1"){
            check_use_data = "ANO";
        }else{
            check_use_data = "NE";
        }
        var bgColor = "style=\"background-color:#ffe6b2;\"";
        if(typ == "timeMap"){
             text_castka = ""+castka+"";         
        }else if(typ == "external"){
             text_castka = ""+castka+"";
             objekt_link = "/admin/objekty.php?id_objektu="+objekt_id+"&typ=tok_list&pozadavek=show_goglobal";
        }else if(typ == "letuska"){
             terminDoShift = ""+terminShift+"";
             if(terminDoShift == "0"){
                 terminDoShift = "";
             }else{
                 terminDoShift = "<span title=\"Posun pøíletu\">+"+terminShift+"</span>";
             }
             text_castka = ""+castka+"";
             objekt_link = "/admin/objekty.php?id_objektu="+objekt_id+"&typ=tok_list&pozadavek=show_letuska";
        }        
        var cm_text = "<tr "+bgColor+" id=\"row_cenova_mapa_"+cena_id+"_"+variable_name+"_"+id+"\" class=\"row_cenova_mapa_"+cena_id+"_"+variable_name+"\">\n\
                    <td>\n\
                    <td class=\"count\">"+id+"</td>\n\
                    <td>"+termin_od+"\n\
                    <td>"+termin_do+"\n\
                    <td>"+check_use_data+"\n\
                    <td>"+text_castka+"\n\
                        "+terminDoShift+"\n\
                    <td><td>"+ external_text_tags.replace(/<br *\/?>/g, "") +"\n\
                    <td><a href=\""+objekt_link+"\">Objekt</a>";
        
    }else{


        if(typ == "timeMap"){
             text_castka = "<input type=\"text\" onchange=\"javascript:calculate_pc("+id+",'"+cena_id+"','"+variable_name+"')\" value=\""+castka+"\" class=\"castka\" id=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\"  name=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\" />";         
        }else if(typ == "external"){
             text_castka = "<span class=\"label_cm_castka\" id=\"label_cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\">"+castka+"</span><input type=\"hidden\" onchange=\"javascript:calculate_pc("+id+",'"+cena_id+"','"+variable_name+"')\" value=\""+castka+"\" class=\"castka\" id=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\"  name=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\" />";
             updateButton = " | <a class=\"name_anchor\" name=\"cm_"+id+"_"+cena_id+"_"+variable_name+"\"> </a><a href=\"#cm_"+id+"_"+cena_id+"_"+variable_name+"\" class=\"update_link\" id=\"update_cm_"+id+"_"+cena_id+"_"+variable_name+"\" onclick=\"javascript:update_goglobal("+id+",'"+cena_id+"','"+variable_name+"', '"+id_objektu+"')\"><img height=\"11\" src=\"/admin/img/ico-reload.png\" alt=\"Naèíst externí data\"/></a>";
             ajaxResults = "<td class=\"ext_prices\" id=\"ext_prices_"+id+"_"+cena_id+"_"+variable_name+"\" >"+ external_text;
        }else if(typ == "letuska"){
             terminDoShift = "<input type=\"hidden\" class=\"termin_do_shift\" value=\""+terminShift+"\" id=\"termin_do_shift_"+id+"_"+cena_id+"_"+variable_name+"\" name=\"termin_do_shift_"+id+"_"+cena_id+"_"+variable_name+"\" />";
             text_castka = "<span class=\"label_cm_castka\" id=\"label_cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\">"+castka+"</span><input type=\"hidden\" onchange=\"javascript:calculate_pc("+id+",'"+cena_id+"','"+variable_name+"')\" value=\""+castka+"\" class=\"castka\" id=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\"  name=\"cm_castka_"+id+"_"+cena_id+"_"+variable_name+"\" />";
             updateButton = " | <a class=\"name_anchor\" name=\"cm_"+id+"_"+cena_id+"_"+variable_name+"\"> </a><a href=\"#cm_"+id+"_"+cena_id+"_"+variable_name+"\" class=\"update_link\" id=\"update_cm_"+id+"_"+cena_id+"_"+variable_name+"\" onclick=\"javascript:update_letuska("+id+",'"+cena_id+"','"+variable_name+"')\"><img height=\"11\" src=\"/admin/img/ico-reload.png\" alt=\"Naèíst externí data\"/></a>";
             updateButton += " | <a href=\"#cm_"+id+"_"+cena_id+"_"+variable_name+"\" class=\"manual_edit_link\" id=\"manual_edit_cm_"+id+"_"+cena_id+"_"+variable_name+"\" onclick=\"javascript:allow_manual_edit("+id+",'"+cena_id+"','"+variable_name+"')\">Editovat cenu</a>";
             
             ajaxResults = "<td class=\"ext_prices\" id=\"ext_prices_"+id+"_"+cena_id+"_"+variable_name+"\" >"+ external_text_tags.replace(/<br *\/?>/g, "");
        }

        var check_use_data = "";
        if(useDatesInTourGeneration === "1"){
            check_use_data = "checked=\"checked\"";
        }
        var cm_text = "<tr "+bgColor+" id=\"row_cenova_mapa_"+cena_id+"_"+variable_name+"_"+id+"\" class=\"row_cenova_mapa_"+cena_id+"_"+variable_name+"\">\n\
                    <td><input type=\"checkbox\" class=\"checkbox_cenova_mapa_"+cena_id+"_"+variable_name+"\" id=\"checkbox_cenova_mapa_"+cena_id+"_"+variable_name+"_"+id+"\" title=\"Vybrat záznam\"/>\n\
                    <td class=\"count\">"+id+"</td>\n\
                    <td><input id=\"cm_termin_od_"+id+"_"+cena_id+"_"+variable_name+"\" class=\"dynamicCalendar-ymd termin_od\" type=\"text\" value=\""+termin_od+"\" name=\"cm_termin_od_"+id+"_"+cena_id+"_"+variable_name+"\" />\n\
                    <td><input id=\"cm_termin_do_"+id+"_"+cena_id+"_"+variable_name+"\"  class=\"dynamicCalendar-ymd termin_do\" type=\"text\" value=\""+termin_do+"\" name=\"cm_termin_do_"+id+"_"+cena_id+"_"+variable_name+"\" />\n\
                    <td><input id=\"use_dates_"+id+"_"+cena_id+"_"+variable_name+"\" class=\"checkbox_terminy_"+cena_id+"_"+variable_name+" checkbox_terminy\" type=\"checkbox\" "+check_use_data+" value=\"1\" name=\"use_dates_"+id+"_"+cena_id+"_"+variable_name+"\"  />\n\
                    <td>"+text_castka+"\n\
                        "+terminDoShift+"\n\
                        <input type=\"hidden\" class=\"poznamka\" name=\"cm_poznamka_"+id+"_"+cena_id+"_"+variable_name+"\" id=\"cm_poznamka_"+id+"_"+cena_id+"_"+variable_name+"\" value=\""+external_text+"\" />\n\
                        <input type=\"hidden\" class=\"externalID\" name=\"cm_externalID_"+id+"_"+cena_id+"_"+variable_name+"\" id=\"cm_externalID_"+id+"_"+cena_id+"_"+variable_name+"\" value=\""+external_id+"\" />\n\
                    <td class=\"prodejni_cena\" id=\"prodejni_cena_"+id+"_"+cena_id+"_"+variable_name+"\">\n\
                    "+ajaxResults +"\n\n\
                    <td><a href=\"#\" class=\"delete_link\" onclick=\"javascript:cenova_mapa_delete_row('"+cena_id+"_"+variable_name+"_"+id+"',false);\" title=\"smazat øádek\"><img width=\"10\" src=\"./img/delete-cross.png\" alt=\"smazat øádek\" ></a>\n\
                    "+updateButton;
    }  
    return cm_text;
}

function addDays(originalDate, days){
    cloneDate = new Date(originalDate.valueOf());
    cloneDate.setDate(cloneDate.getDate() + days);
    return cloneDate;
  }

function show_cenova_mapa_add_multiple_rows(cena_id, variable_name,  vzorec_id, typ){
    var countRows = parseInt($("#add_count_"+cena_id+"_"+variable_name).val());
    var daysShift = parseInt($("#shift_days_"+cena_id+"_"+variable_name).val());

    var id_last_row = $("#cm_last_row_"+cena_id+"_"+variable_name+"_"+vzorec_id+"");
    var last_id = id_last_row.prev().children(".count").html();
    
    var last_from = ($("#cm_termin_od_"+(parseInt(last_id))+"_"+cena_id+"_"+variable_name+"").val()).split(".");
    var last_to = ($("#cm_termin_do_"+(parseInt(last_id))+"_"+cena_id+"_"+variable_name+"").val()).split(".");

    var dateFrom = new Date(parseInt(last_from[2]),parseInt(last_from[1])-1,parseInt(last_from[0]));
    var dateTo = new Date(parseInt(last_to[2]),parseInt(last_to[1])-1,parseInt(last_to[0]));

    if(countRows > 0 &&  daysShift > 0){
        //call add row for each occurence; 
        for (let i = 1; i <= countRows; i++) {
            let newFrom = addDays(dateFrom,daysShift*i);
            let newTo = addDays(dateTo,daysShift*i);

            czDateFrom = newFrom.getDate()+"."+ (newFrom.getMonth()+1)+". "+ newFrom.getFullYear();
            czDateTo = newTo.getDate()+"."+ (newTo.getMonth()+1)+". "+ newTo.getFullYear();
            last_id++;
            id_last_row.before(show_cenova_mapa_row(last_id,czDateFrom,czDateTo,"",cena_id, variable_name,  vzorec_id, typ,"",""));            
        }

    }

    initialize_callendar();      
     
}

function show_cenova_mapa_add_row(cena_id, variable_name,  vzorec_id, typ){
    var id_last_row = $("#cm_last_row_"+cena_id+"_"+variable_name+"_"+vzorec_id+"");
    var last_id = id_last_row.prev().children(".count").html();
    last_id++;
    id_last_row.before(show_cenova_mapa_row(last_id,"","","",cena_id, variable_name,  vzorec_id, typ,"",""));
        
    initialize_callendar();   
}

/*pro každý øádek cenové mapy zavolá funkci update_goglobal*/
function update_goglobal_all(cenaId, varName, objekty){
    var i = 1;
    while( $("#row_cenova_mapa_"+cenaId+"_"+varName+"_"+i).length ){
        update_goglobal(i, cenaId, varName, objekty);
        i++;
    }        
}

/*pro každý øádek cenové mapy zavolá funkci update_letuska*/
function update_letuska_all(cenaId, varName){
    var i = 1;
    while( $("#row_cenova_mapa_"+cenaId+"_"+varName+"_"+i).length ){
        update_letuska(i, cenaId, varName);
        i++;
    }        
}

/*pro každý øádek cenové mapy zavolá funkci povolit manuální editaci*/
function allow_manual_edit_all(cenaId, varName){
    var i = 1;
    while( $("#row_cenova_mapa_"+cenaId+"_"+varName+"_"+i).length ){
        allow_manual_edit(i, cenaId, varName);
        i++;
    }        
}


function get(name){
   if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
      return decodeURIComponent(name[1]);
}
var rotation = function (img){
  $(img).rotate({
    angle:0,
    animateTo:360,
    easing: function (x,t,b,c,d){        // t: current time, b: begInnIng value, c: change In value, d: duration
      return c*(t/d)+b;
    },    
    callback: function(){   rotation(img);  }     
  });
}


/*provede AJAX dotaz, ktery odesle pres SOAP API dotaz na volne pokoje daneho hotelID, 
 * prislusne objektove kategorie nebo objektu. vrati JSON, ktery funkce dal parsuje*/
function goglobal_get_hotel_id(){
    var id_serial = get("id_serial");
    var hotelName = $("#goGlobalHotelName").val();
    var city = $("#goGlobalCity").val(); 
    var country = $("#goGlobalCountry").val();   
    var terminOd = $("#goGlobalTerminOd").val();  
    var pocetNoci = $("#goGlobalPocetNoci").val();  
    //if(hotelName !== "" && city !== ""){
        rotation("#update_goGlobalHotelName img");
        $.ajax({
            url: '?typ=cena&pozadavek=ajax_get_goglobal_hotel_id&id_serial=' + id_serial,
            type: 'post',
            data: {
                'klient_ajax': 'true',
                'hotelName': hotelName,
                'city': city,
                'country': country,
                'terminOd': terminOd,
                'pocetNoci': pocetNoci
            },
            success: function (res) {
                $("#update_goGlobalHotelName img").stopRotate();
                $("#update_goGlobalHotelName img").rotate(0);
                 var response = jQuery.parseJSON( res );
                if( typeof response.error === "undefined"){
                    var output = "\n\
                                    <div id=\"hotel_names\"><table>";
                    $.each(response, function(propName, value){     
                        try {
                            output += "<tr><td><b>"+value.name+"</b> <td>ID:"+value.code+"\n";
                        }
                        catch(error) {
                            //do nothing
                        }
                    });                       
                    output +="</table></div>";
                    $("#ext_hotel_ids").html(output);                       
                }else{
                   output = "<b class=\"red\">Chyba:"+response.error+"</b>";
                   console.log(response.error);
                }
                if( typeof response.warning !== "undefined"){
                        output += "<b style=\"color:orange;\">"+response.warning+"</b>\n";
                }
                $("#ext_hotel_ids").html(output); 
            }
        });
    //}
}

function days_diff(dstr1, dstr2){
    let date_1 = new Date(dstr1);
    let date_2 = new Date(dstr2);
    
    const days = (date_1, date_2) =>{
        let difference = date_2.getTime() - date_1.getTime();
        let TotalDays = Math.ceil(difference / (1000 * 3600 * 24));
        return TotalDays;
    }
    return days(date_1, date_2);

}

/*provede AJAX dotaz, ktery odesle pres REST API dotaz na lety k danym datum, a letistim,
 *  vrati JSON, ktery funkce dal parsuje*/
function update_letuska(rowId, cenaId, varName){
    var termin_od = $("#cm_termin_od_"+rowId+"_"+cenaId+"_"+varName+"").val();
    var termin_do = $("#cm_termin_do_"+rowId+"_"+cenaId+"_"+varName+"").val();  
    //TODO: get values
    var from = $("#flight_from_"+cenaId+"_"+varName+"").val();
    var to = $("#flight_to_"+cenaId+"_"+varName+"").val();
    var direct = $("#flight_direct_"+cenaId+"_"+varName+"").is(':checked');
    if(direct === true){
        direct = 1;
    }else{
        direct = 0;
    }

    console.log(from+to+direct+termin_od+termin_do);
    cenaId = cenaId.replace("cena_",""); 
    var id_serial = get("id_serial");
    console.log('serial.php?typ=cena&pozadavek=ajax_get_letuska_ceny&id_cena=' + cenaId+'&id_serial=' + id_serial)
    if(termin_od !== "" && termin_do !== "" && from !== "" && to !== ""){
        rotation("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img");
        $.ajax({
            url: 'serial.php?typ=cena&pozadavek=ajax_get_letuska_ceny&id_cena=' + cenaId+'&id_serial=' + id_serial,
            type: 'post',
            data: {
                'klient_ajax': 'true',
                'termin_od': termin_od,
                'termin_do': termin_do,
                'from_code': from,
                'to_code': to,
                'direct': direct
            },            
            success: function (res) {
                $("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img").stopRotate();
                $("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img").rotate(0);
                 var response = jQuery.parseJSON( res );
                 console.log(response);
                if( typeof response.error === "undefined"){
                    var output = "<a href=\"#\" title=\"Zobrazit nabídky\" class=\"offer_toggle\" id=\"offer_toggle_for_"+rowId+"_"+cenaId+"_"+varName+"\" ><img src=\"/admin/img/toggle.png\" height=\"10\" /></a> \n\
                                    <div id=\"offers_for_"+rowId+"_"+cenaId+"_"+varName+"\"><table>";
                    var offer = "";
                    var lastOfferInfo = "";      
                    var i = 0;              
                    from = from.toUpperCase();
                    to = to.toUpperCase();
                    $.each(response.segments, function(propName, flightDetail){
                        directions = Object.keys(flightDetail.flights);
                        var fromKey = "";
                        var toKey = "";
                        directions.forEach((dname) => {
                            if (dname.startsWith(from)){
                                  fromKey =   dname;
                            }  else {
                                  toKey =   dname;
                            }
                        });
                    
                        
                        var poznamka = "";
                        var termin_do_shift = "<input type=\"hidden\" value=\"0\" class=\"termin_do_shift termin_do_shift_for_"+rowId+"_"+cenaId+"_"+varName+"\" />";
                        var price = flightDetail.price.total;
                        var currency = flightDetail.price.currency;
                        var company = flightDetail.company;
                        var baggage = flightDetail.baggage.allowed;
                        var onward_time_from = flightDetail.flights[fromKey][0].from.time;
                        var onward_time_to = flightDetail.flights[fromKey][flightDetail.flights[fromKey].length-1].to.time;
                        if(flightDetail.flights[fromKey][0].from.date !== flightDetail.flights[fromKey][flightDetail.flights[fromKey].length-1].to.date){
                            var days = days_diff(flightDetail.flights[fromKey][0].from.date,flightDetail.flights[fromKey][flightDetail.flights[fromKey].length-1].to.date)
                            
                            onward_time_to += " <b>(+"+days+")</b>";
                        }
                        var return_time_from = flightDetail.flights[toKey][0].from.time;
                        var return_time_to = flightDetail.flights[toKey][flightDetail.flights[toKey].length-1].to.time;
                        if(flightDetail.flights[toKey][0].from.date !== flightDetail.flights[toKey][flightDetail.flights[toKey].length-1].to.date){
                            var days = days_diff(flightDetail.flights[toKey][0].from.date,flightDetail.flights[toKey][flightDetail.flights[toKey].length-1].to.date)                            
                            return_time_to += " <b>(+"+days+")</b>";

                            termin_do_shift = "<input type=\"hidden\" value=\"1\" class=\"termin_do_shift termin_do_shift_for_"+rowId+"_"+cenaId+"_"+varName+"\" />";
                        }
                        poznamka += "[b]Letecká spoleènost[/b]: "+company+" [br/] [b]Let tam[/b]: "+onward_time_from+" - "+onward_time_to+" [br/] ";
                        var onwardDetail = "<table><tr><th>Let<th>Z<th>Do<th>Datum a èas";
                        var returnDetail = "<table><tr><th>Let<th>Z<th>Do<th>Datum a èas";                        
                        $.each(flightDetail.flights[fromKey], function(pn, flight){
                            var dateTime = flight.from.date+" "+flight.from.time
                            if(flight.from.date === flight.to.date){
                                dateTime += " - "+flight.to.time
                            }else{
                                dateTime += " - "+flight.to.date+" "+flight.to.time
                            }
                            onwardDetail += "<tr><td>"+flight.company+" "+flight.flight_number+"<td>"+flight.from.airport+"<td>"+flight.to.airport+"<td>"+dateTime;
                            poznamka += "- "+flight.company+" "+flight.flight_number+": "+flight.from.airport+" - "+flight.to.airport+", "+dateTime+"[br/]";                            
                        });
                        onwardDetail += "</table>";
                        
                        poznamka += " [b]Let zpìt[/b]: "+return_time_from+" - "+return_time_to+" [br/]";
                        
                        $.each(flightDetail.flights[toKey], function(pn, flight){
                            var dateTime = flight.from.date+" "+flight.from.time
                            if(flight.from.date === flight.to.date){
                                dateTime += " - "+flight.to.time
                            }else{
                                dateTime += " - "+flight.to.date+" "+flight.to.time
                            }
                            returnDetail += "<tr><td>"+flight.company+" "+flight.flight_number+"<td>"+flight.from.airport+"<td>"+flight.to.airport+"<td>"+dateTime;
                             poznamka += "- "+flight.company+" "+flight.flight_number+": "+flight.from.airport+" - "+flight.to.airport+", "+dateTime+"[br/]";                            

                        });
                        returnDetail += "</table>";
                        var radio = "<input style=\"float:left\" type=\"radio\" class=\"radio_offer offer_for_"+rowId+"_"+cenaId+"_"+varName+"\" name=\"radio_offer_"+rowId+"_"+cenaId+"_"+varName+"\" value=\""+price+" "+currency+"\" /><div class=\"poznamka\" style=\"display:none;\">"+poznamka+"</div>";
                        offer = radio+termin_do_shift+" <p class=\"flight_offer\" title=\""+company+", "+price+" "+currency+" (TAM: "+onward_time_from+" - "+onward_time_to+", ZPÌT: "+return_time_from+" - "+return_time_to+", Zavazadla: "+baggage+")\"> (TAM: "+onward_time_from+" - "+onward_time_to+", ZPÌT: "+return_time_from+" - "+return_time_to+", Zavazadla: "+baggage+")</p>";
                        var offerDetail = "<div style=\"display:none\" id=\"offerDetail_"+rowId+"_"+cenaId+"_"+varName+"\" class=\"offerDetail\">\n\
                                <b>Tam:</b><br/> "+onwardDetail+"\n\
                                <b>Zpìt:</b><br/> "+returnDetail+"\n\
                            </div>";
                        
                        //remove near duplicate entries
                        if( ""+price+currency+offer !== lastOfferInfo){
                            i++;
                            if(i == 21){
                            output += "</table>\n\
                                    <script type=\"text/javascript\">$('#showMoreFlights_"+rowId+"_"+cenaId+"_"+varName+"').click(function(){$('#moreFlights_"+rowId+"_"+cenaId+"_"+varName+"').toggleClass( 'hidden' )});</script>   \n\
                                    <span style=\"cursor:pointer\" id=\"showMoreFlights_"+rowId+"_"+cenaId+"_"+varName+"\" class=\"showMoreFlights\">Zobrazit další nabídky</span>\n\
                                    <table class='hidden' id=\"moreFlights_"+rowId+"_"+cenaId+"_"+varName+"\">";
                            }
                            lastOfferInfo = ""+price+currency+offer;
                            output += "<tr><td><b>"+company+"</b> "+price+" "+currency+"<td>\n "+offer+offerDetail+"\n";                            
                        }
                        

                    });                
                    output +="</table></div>";
                    $("#ext_prices_"+rowId+"_cena_"+cenaId+"_"+varName).html(output);   
                    radioOffersBindAction();
                    flightsToggleBindAction();
                }else{
                    $("#ext_prices_"+rowId+"_cena_"+cenaId+"_"+varName).html("<b class=\"red\">Chyba:"+response.error+"</b>");
                    console.log(response.error);
                }

            }
        });
    }
}

/*upraví nìkteré hidden pole na textové tak, aby bylo možno je ruènì editovat*/
function allow_manual_edit(rowId, cenaId, varName){

    $("#label_cm_castka_"+rowId+"_"+cenaId+"_"+varName).html("Cena a posun priletu:");
    
    //<input type="hidden" onchange="javascript:calculate_pc(2,'cena_0','var1')" value="2670" class="castka" id="cm_castka_2_cena_0_var1" name="cm_castka_2_cena_0_var1">
    var cena = $("#cm_castka_"+rowId+"_"+cenaId+"_"+varName);
    cena.get(0).type= 'text';  
    cena.css('width', '40px');  
    var posun = $("#termin_do_shift_"+rowId+"_"+cenaId+"_"+varName)
    posun.get(0).type= 'text'; 
    posun.css('width', '15px');   
    $("#cm_externalID_"+rowId+"_"+cenaId+"_"+varName).val("manual_edit");  


}

function show_hidden_offers(ids){
    $("#"+ids).siblings("div").toggleClass("hidden");
}


/*provede AJAX dotaz, ktery odesle pres SOAP API dotaz na volne pokoje daneho hotelID, 
 * prislusne objektove kategorie nebo objektu. vrati JSON, ktery funkce dal parsuje*/
function update_goglobal(rowId, cenaId, varName, objekty){
    var termin_od = $("#cm_termin_od_"+rowId+"_"+cenaId+"_"+varName+"").val();
    var termin_do = $("#cm_termin_do_"+rowId+"_"+cenaId+"_"+varName+"").val();  
    cenaId = cenaId.replace("cena_",""); 
    if(objekty == "no" || typeof(objekty)==='undefined'){
        var id_serial = get("id_serial");
        var url_link = 'serial.php?typ=cena&pozadavek=ajax_get_goglobal_ceny&id_cena=' + cenaId+'&id_serial=' + id_serial;

    }else{
        var id_objektu = get("id_objektu");
        var url_link = 'objekty.php?typ=objekty&pozadavek=ajax_get_goglobal_ceny&id_objektu=' + id_objektu;
    }
    
    
    if(termin_od !== "" && termin_do !== ""){
        rotation("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img");
        /*TODO: zde je treba rozlisit, zda se jedna o  dotaz z objektu (neexistuje serial, na serverove strane je treba pouzit k ziskani OK jiny dotaz do DB!!!!)*/
        $.ajax({
            url: url_link,
            type: 'post',
            data: {
                'klient_ajax': 'true',
                'termin_od': termin_od,
                'termin_do': termin_do
                /*perhaps add some feature here to identify Object-based query?*/
            },
            success: function (res) {
                $("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img").stopRotate();
                $("#update_cm_"+rowId+"_cena_"+cenaId+"_"+varName+" img").rotate(0);
                 var response = jQuery.parseJSON( res );
                if( typeof response.error === "undefined"){
                    var output = "<a href=\"#\" title=\"Zobrazit nabídky\" class=\"offer_toggle\" id=\"offer_toggle_for_"+rowId+"_"+cenaId+"_"+varName+"\" ><img src=\"/admin/img/toggle.png\" height=\"10\" /></a> \n\
                                    <div id=\"offers_for_"+rowId+"_"+cenaId+"_"+varName+"\"><table>";
                    var parity = ["suda","licha"] ;
                    var i = 0 ;
                    var max_offers = 20;
                    $.each(response, function(propName, value){
                        i++;
                        var j = 0;
                        var offer = "";
                        var offerExcess = "<a href='#"+value.name+"' onclick='javascript:show_hidden_offers(\"more_"+value.code+"_"+rowId+"_"+cenaId+"\")' id='more_"+value.code+"_"+rowId+"_"+cenaId+"'>Zobrazit další</a><div class='hidden'>"
                        $.each(value.offer, function(offerId, offerVal){  
                            j++;                      
                            var radio = "<input type=\"radio\" class=\"radio_offer offer_for_"+rowId+"_"+cenaId+"_"+varName+"\" name=\"radio_offer_"+rowId+"_"+cenaId+"_"+varName+"\" value=\""+offerVal.price+" "+offerVal.currency+"\" />";
                            
                            if(j <= max_offers){
                              offer += radio+" <span title=\""+offerVal.policy+"\">"+offerVal.price+" "+offerVal.currency+" ("+offerVal.room+", "+offerVal.roomBasis+")<br/></span>";
                            }else{
                              offerExcess += radio+" <span title=\""+offerVal.policy+"\">"+offerVal.price+" "+offerVal.currency+" ("+offerVal.room+", "+offerVal.roomBasis+")<br/></span>";
                            }
                            
                        });
                        
                        if(j>max_offers) {
                           offerExcess += "</div>"; 
                           offer += offerExcess;
                        }
                        
                        parita = parity[i%2];
                        output += "<tr><td style='vertical-align:top;' class='"+parita+"'><a name='#"+value.name+"'><b>"+value.name+"</b> ("+value.code+") </a><td class='"+parita+"'>\n "+offer+"\n";

                    });                
                    output +="</table></div>";
                    $("#ext_prices_"+rowId+"_cena_"+cenaId+"_"+varName).html(output);   
                    radioOffersBindAction();
                }else{
                    $("#ext_prices_"+rowId+"_cena_"+cenaId+"_"+varName).html("<b class=\"red\">Chyba:"+response.error+"</b>");
                    
                    var poznamka = "Chyba: "+response.error;
                    var d = new Date();
                    var today = d.getDate() +"."+(d.getMonth()+1 )+"."+d.getFullYear();
                    poznamka += ". Nalezeno: " +today;
            
                    $("#cm_poznamka_"+rowId+"_cena_"+cenaId+"_"+varName).val(poznamka);
                    
                    console.log(response.error);
                }

            }
        });
    }
}


function radioOffersBindAction(){
    $(".radio_offer").unbind( "change" );
    $(".radio_offer").change(function(){
       if($(this).is(':checked')){ 
            var className = "";
            var value = $(this).val();
            var castka = $(this).val().split(" ")[0];
            var mena = $(this).val().split(" ")[1];
            $($(this).attr('class').split(' ')).each(function() { 
                if (this !== '' && this.indexOf("offer_for_") !== -1) {
                    className = this;
                }    
            });              
            className = className.replace("offer_for_","");
            var attrs = className.split("_");
            if($(this).siblings(".termin_do_shift").val()!= ""){
                var date_shift = $(this).siblings( ".termin_do_shift" ).val()
                $("#termin_do_shift_"+attrs[0]+"_cena_"+attrs[1]+"_"+attrs[2]).val(date_shift); 
            }
            
            //vyhledam poradi promenne v seznamu
            var vzorec_id = id_kv_list["cena_"+attrs[1]];                    
            var var_array = vzorec_promenne["vzorec_"+vzorec_id];
            var promenna_id = 0;
            for (var i = 0; i < var_array.length; i++) {
                if(var_array[i][0] === attrs[2]){
                    promenna_id = i+1;
                }
            }
            $("#label_cm_castka_"+attrs[0]+"_cena_"+attrs[1]+"_"+attrs[2]).html(value);
            $("#cm_castka_"+attrs[0]+"_cena_"+attrs[1]+"_"+attrs[2]).val(castka);   
            var external_id = $(this).parent().prev().html().replace(/.*\(|\)/g,"");
            var poznamka = $(this).next().html().replace(/\(|\)/g,"");
            poznamka = $(this).parent().prev().html() + ", " + poznamka;
            poznamka = poznamka.replace(external_id+",","");
            
                       
            var d = new Date();
            var today = d.getDate() +"."+(d.getMonth()+1 )+"."+d.getFullYear();
            poznamka += ". Nalezeno: " +today;
            
            $("#cm_poznamka_"+attrs[0]+"_cena_"+attrs[1]+"_"+attrs[2]).val(poznamka);
            $("#cm_externalID_"+attrs[0]+"_cena_"+attrs[1]+"_"+attrs[2]).val(external_id);            
            $("#variable_currency_"+promenna_id+"_cena_"+attrs[1]+" option").each(function() {
                this.selected = (this.text == mena); 
            });
            
            calculate_pc(attrs[0],"cena_"+attrs[1],attrs[2]);
        }
    });
    
    $(".offer_toggle").unbind( "click" );
    $(".offer_toggle").click(function(){
       var id = $(this).attr("id").replace("offer_toggle_for_","");
       $("#offers_for_"+id).toggle(0);
       return false;
    });    
    $(".offer_toggle img").rotate({bind:{
        mouseenter: function(){
          $(this).rotate({
            angle: 0,
            animateTo:90
            });
          },
        mouseout: function(){
          $(this).rotate({
            angle: 0
            });
          }
        }
    });
       
}



function flightsToggleBindAction(){
    $(".flight_offer").unbind( "click" );
    $(".flight_offer").click(function(){
       $(this).next().toggle();
    });           
}

function copyToShortPriceName(priceSubID) {
    //pokud jsme zaskrtli zkraceny vypis a zaroven je prazdne pole kratkeho nazvu, zkopiruje se tam puvodni nazev
    if (document.getElementsByName("zkraceny_vypis_" + priceSubID)[0].value == 1) {
        if (document.getElementsByName("kratky_nazev_" + priceSubID)[0].value == "") {
            document.getElementsByName("kratky_nazev_" + priceSubID)[0].value = document.getElementsByName("nazev_cena_" + priceSubID)[0].value;
        }
    }
}
function highlight_options(field){
  var i,c;
  for(i in field.options){
    (c=field.options[i]).className=c.selected?'selected':'';
  }
}

function show_filtr() {
    if (document.getElementById("pokrocily_filtr").style.display == "none") {
        document.getElementById("pokrocily_filtr").style.display = "block";  
        document.getElementById("prehled_zajezdu").style.display = "none";
        document.getElementById("prehled_zajezdu_switch").value = "-1";  
        document.getElementById("pokrocile_filtry_switch").value = "1";   
    }else{
        document.getElementById("pokrocily_filtr").style.display = "none";
        document.getElementById("pokrocile_filtry_switch").value = "-1"; 
    }
}

function show_filtr_prehled_zajezdu() {
    if (document.getElementById("prehled_zajezdu").style.display == "none") {
        document.getElementById("prehled_zajezdu").style.display = "block"; 
        document.getElementById("pokrocily_filtr").style.display = "none";
        document.getElementById("prehled_zajezdu_switch").value = "1";
        document.getElementById("pokrocile_filtry_switch").value = "-1"; 
        document.getElementById("f_zobrazit_zajezdy").checked = false;      
    }else{
        document.getElementById("prehled_zajezdu").style.display = "none";
        document.getElementById("prehled_zajezdu_switch").value = "-1";
    }
}

function filtr_change_zobrazit_zajezdy() {
    if (document.getElementsByName("f_zajezd_od")[0].value != "" || document.getElementsByName("f_zajezd_do")[0].value != "" || document.getElementsByName("f_serial_aktivni_zajezd")[0].checked == true || document.getElementsByName("f_zajezd_objednavka")[0].checked == true || document.getElementsByName("f_zajezd_no_objednavka")[0].checked == true) {
        document.getElementsByName("f_zobrazit_zajezdy")[0].checked = true;        
    }
}

function searchTOK(id_serial) {
    var termin_od = $("#zajezd_termin_od").val();
    var termin_do = $("#zajezd_termin_do").val();

    if (termin_od == "" || termin_do == "") {
        //do nothing
        return;
    }
    $.ajax({
        type: 'POST',
        url: '?typ=cena_zajezd&pozadavek=ajax_get_ceny&id_serial=' + id_serial,
        data: {
            'klient_ajax': 'true',
            'termin_od': termin_od,
            'termin_do': termin_do
        },
        success: function (result) {
            showTable(result);
            updatePricesByKV();
        }
    });
}

function enableAllInputnput() {
    var elem_list = document.getElementsByTagName("input");
    for (var j = 0; j < elem_list.length; j++) {
        elem_list[j].disabled = false;
    }
}

function calculateAllCapacities() {


}

function computeCapacitiesTOK(id_sluzby, ok_list, total_capacity_array, free_capacity_array, multiplicator_array, prices_array) {
    //pro kazdy tok zjisti, zda se vytvari novy (vezme udaj z pole)
    //                      nebo zda se pouziva existujici - vezme se jeho aktualni kapacita
    //                      nebo zda je zaskrtnuto nevytvaret - 0
    //                      kazdy tok ma prirazeny "multiplikator" = hlavni kapacita, pokud neni zaskrtnuto "jako celek" jinak 1
    //                      navic pokud je zaskrtnuto ze je_vstupenka = 1, pak jeste prepise aktualni cenu sluzby dle ceny TOK
    //                      pokud je_vstupenka = 0, zrusi "disabled" pro cenu
    //window.alert(ok_list+"_"+id_sluzby);
    var celkova_kapacita = 0;
    var volna_kapacita = 0;
    var disable_capacity = false;
    var disable_cena = false;
    var cena = -1;

    var ok_array = ok_list.split(",");
    var length = ok_array.length;
    var ok = 0;
    var selected_tok = "";
    for (var i = 0; i < length; i++) {
        ok = ok_array[i];
        var tok_radios = document.getElementsByName("id_tok_" + id_sluzby + "_" + ok);
        
        
        for (var j = 0; j < tok_radios.length; j++) {
            if (tok_radios[j].checked) {
                selected_tok = tok_radios[j].value;
            }
        }
        
        if (selected_tok == "no") {
            //do nothing
        } else if (selected_tok == "new") {
            disable_capacity = true;
            celkova_kapacita += parseInt(document.getElementsByName("kapacita_tok_" + id_sluzby + "_" + ok)[0].value) * multiplicator_array[id_sluzby + "_" + ok];
            volna_kapacita += parseInt(document.getElementsByName("kapacita_tok_" + id_sluzby + "_" + ok)[0].value) * multiplicator_array[id_sluzby + "_" + ok];
        } else if (parseInt(selected_tok) > 0) {
            disable_capacity = true;
            celkova_kapacita += total_capacity_array[id_sluzby + "_" + ok + "_" + selected_tok] * multiplicator_array[id_sluzby + "_" + ok];
            volna_kapacita += free_capacity_array[id_sluzby + "_" + ok + "_" + selected_tok] * multiplicator_array[id_sluzby + "_" + ok];
            var je_vstupenka = document.getElementById("je_vstupenka_" + id_sluzby).checked;
            var je_vstupenka_hidden = document.getElementById("je_vstupenka_hidden_" + id_sluzby).value;
            if (je_vstupenka == true || je_vstupenka_hidden == "1") {
                //chceme upravit cenu dle seznamu
                cena = prices_array[id_sluzby + "_" + ok + "_" + selected_tok];
            }
            if(je_vstupenka == true ){
                disable_cena = true;
            }
        }

    }

    if (disable_capacity == true) {
        document.getElementsByName("kapacita_celkova_" + id_sluzby)[0].disabled = true;
    } else {
        document.getElementsByName("kapacita_celkova_" + id_sluzby)[0].disabled = false;
    }
    document.getElementsByName("kapacita_celkova_" + id_sluzby)[0].value = celkova_kapacita;
    document.getElementById("kapacita_volna_" + id_sluzby).value = volna_kapacita;
    if (cena != -1) {
        document.getElementById("castka_" + id_sluzby).value = cena;
    }
    if (disable_cena == true && cena != -1) {
        document.getElementsByName("castka_" + id_sluzby)[0].readOnly = true;
    } else {
        document.getElementsByName("castka_" + id_sluzby)[0].readOnly = false;
    }    
    
    document.getElementById("kapacita_volna_text_" + id_sluzby).innerHTML = volna_kapacita;

    //window.alert(celkova_kapacita+"_"+volna_kapacita+"_"+disable_capacity);
}

function showTable(result) {
    //    alert(msg);
    var div = $("#sluzby_zajezdu");
    if (result == "") {
        return;
    }
    div.html(result);
}

/*formular po kliknuti na kopirovat zajezd*/
function show_copy_zajezd_form(zmena_wraper_id, id_zajezd, id_serial, termin_od, termin_do, errorTOK, warningTOK, countTOK) {
    var wrapper = $("#" + zmena_wraper_id);
    var tokForm = "";
    if(countTOK > 0){
        if(errorTOK != ""){
           tokForm = "Vytvoøit TOK <input type='checkbox' value='1'  name='vytvorit_tok' disabled/><br/><span style=\"font-size:0.8em;font-style:italic;color:red\">"+errorTOK+"</span>";
        }else if(warningTOK != ""){
            tokForm = "Vytvoøit TOK <input type='checkbox' value='1'  name='vytvorit_tok' /><br/><span style=\"font-size:0.8em;font-style:italic;color:orange\">"+warningTOK+"</span>";
        }else{
            tokForm = "Vytvoøit TOK <input type='checkbox' value='1'  name='vytvorit_tok' checked='checked'  />";
        }
    }
     
    wrapper.html("<br/><form method='post' action='/admin/serial.php?id_serial=" + id_serial + "&id_zajezd=" + id_zajezd + "&typ=zajezd&pozadavek=copy'><strong>Kopírovat zájezd:</strong> <br/>Termín od: <input type='text' value='" + termin_od + "'  name='termin_od'  /> Termín do: <input type='text' value='" + termin_do + "'  name='termin_do'  /> <br/> "+tokForm+"<br/> <input type='submit' value='Kopírovat zájezd'/> ");
    return false;
}

function deleteSelected() {
    var data = "";
    var idSerial = getParameterByName("id_serial");

    $("input[name=zajezd_delete_ids]:checked").each(function () {
        data += "zajezd_delete_ids[]=" + $(this).val() + "&";
    });
    data = data.substr(0, data.length - 1);

    $.ajax({
        async: false,
        type: "POST",
        url: "serial.php?id_serial=" + idSerial + "&typ=zajezd&pozadavek=mass-delete",
        data: data
    });
    location.reload();
}

function soldoutSelected() {
    var data = "";
    var idSerial = getParameterByName("id_serial");

    $("input[name=zajezd_delete_ids]:checked").each(function () {
        data += "zajezd_delete_ids[]=" + $(this).val() + "&";
    });
    data = data.substr(0, data.length - 1);

    $.ajax({
        async: false,
        type: "POST",
        url: "serial.php?id_serial=" + idSerial + "&typ=zajezd&pozadavek=mass-soldout",
        data: data
    });
    location.reload();
}