/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    analyze_vzorec();
});
//analyzuje vzorec, vypise moznost editace typu promennych
function analyze_vzorec() {
    var vzorec = document.getElementById("vzorec").value;
    var res = vzorec.match(/[a-zA-Z][a-zA-Z0-9_]*/g);
    
    var index;
    var generatedHTML = "<table>";
    for (index = 0; index < res.length; ++index) {
        var type = "";
        if(document.getElementById( ("type_"+res[index]) ) !== null){
            //stejna promenna jiz existuje, zkopirujeme nastaveni typu - 
            var select = document.getElementById( ("type_"+res[index]) )
            type = select.options[select.selectedIndex].value;
            
        }else if(nazvy_promennych.indexOf(res[index])>=0){
            //stejna promenna existuje v seznamu z editace, vezmu typ odsud
            type = typy_promennych[nazvy_promennych.indexOf(res[index])];
        }
        generatedHTML += generateVariable(res[index], type, index);
    }
    generatedHTML += "</table>";
    document.getElementById("promenne").innerHTML = generatedHTML;
    check_vzorec(vzorec);
}

function generateVariable(varName, varType, i){
    i++;
    var options = generateTypeOptions(varType);
    if(default_values[i] === undefined){
        default_values[i] = "";
    }
    var generatedHTML = "<tr><td>Promìnná "+i+": "+varName+"<input type=\"hidden\" name=\"nazev_promenne_"+i+"\" value=\""+varName+"\" /> \n\
                         <td>Typ: <select name=\"typ_promenne_"+i+"\" id=\"type_"+varName+"\">"+options+"</select>\n\
                         <td>Defaultní hodnota: <input name=\"default_value_"+i+"\" id=\"default_value_"+varName+"\" type=\"text\" value=\""+default_values[i-1]+"\" />\n\
                         <td><span title=\"U této promìnné se neuvažuje mìna (typicky provize v procentech apod.)\">Bez mìny:</span><input name=\"bez_meny_"+i+"\" id=\"bez_meny_"+varName+"\" type=\"checkbox\" value=\"1\" "+(bez_meny[i-1]>0 ? "checked=\"checked\"" : "")+" />   <br/>";
    return generatedHTML;
}

function generateTypeOptions(varType){
    var optionVal = ["const","timeMap","external","letuska"]
    var optionText = ["Konstanta","Cenová mapa","GoGlobal API","Letuška API"]
    var i;
    var options = "";
    for (i = 0; i < optionVal.length; ++i) {
        var selected = "";
        if(optionVal[i]==varType){
            selected = "selected=\"selected\"";
        }
        options += "<option value=\""+optionVal[i]+"\" "+selected+">"+optionText[i]+"</option>";
    }

    return options;
}

function check_vzorec(vzorec){
    vzorec = vzorec.replace(/[a-zA-Z][a-zA-Z0-9_]*/g,1);
    try {
        var res = eval(vzorec);
        document.getElementById("vzorec_check").innerHTML = "<span class=\"green\">Vzorec je syntakticky správný</span><br/>";
    }
    catch(err) {
        document.getElementById("vzorec_check").innerHTML = "<span class=\"red\">Vzorec obsahuje syntaktickou chybu: "+err.message+"</span><br/>";
    }
}
