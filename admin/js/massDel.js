//zapise do hidden inputu idecka ve formatu :id1::id2::id3:
function markUnmarkToDel(el) {    
    var idsMassdel = document.getElementById('ids-massdel');                                        
    var buttonMassdel = document.getElementById('button-massdel');                                        
    //pridej id (potazmo smaz, pokud uz tam je)
    if(isMarkedToDel(el.value, idsMassdel)) {
        idsMassdel.value = idsMassdel.value.replace(':' + el.value + ':', '');
    } else {
        idsMassdel.value += ':' + el.value + ':';
    }
    if(isEmpty(idsMassdel)) {
        buttonMassdel.disabled = "disabled";
    } else {
        buttonMassdel.disabled = "";
    }
}

/* Zjisti zda je id jiz oznaceno ke smazani */
function isMarkedToDel(id, idsMassdel) {
    var arr = idsMassdel.value.split('::');
    for(i = 0; i < arr.length; i++)
        if(arr[i].replace(/:/g, '') == id)
            return true;
    return false;
}

/* Zkontroluje zda je oznacen nejaky element ke smazani */
function isEmpty(idsMassdel) {
    return idsMassdel.value == '' ? true : false;
}