function ObjednavkyValidator() {

}

ObjednavkyValidator.validateUcastnikCreate = function (popupErr, inpPrijmeni, inpJmeno) {
    var valid = true;
    var messages = [];

    if(inpPrijmeni.val() == "") {
        valid = false;
        inpPrijmeni.addClass('err');
        messages.push('Pøíjmení nesmí být prázdné');
    } else {
        inpPrijmeni.removeClass('err');
    }

    if(inpJmeno.val() == "") {
        valid = false;
        inpJmeno.addClass('err');
        messages.push('Jméno nesmí být prázdné');
    } else {
        inpJmeno.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba pøi vytváøení úèastníka', messages);

    return valid;
};

ObjednavkyValidator.validateSluzbaCreate = function(popupErr, inpNazev, inpCastka, inpPocet) {
    var valid = true;
    var messages = [];

    if(inpNazev.val() == "") {
        valid = false;
        inpNazev.addClass('err');
        messages.push('Název nesmí být prázdný');
    } else {
        inpNazev.removeClass('err');
    }

    if(inpCastka.val() == "" || inpCastka.val() < 0) {
        valid = false;
        inpCastka.addClass('err');
        messages.push('Èástka musí být kladné èíslo');
    } else {
        inpCastka.removeClass('err');
    }

    if(inpPocet.val() == "" || inpPocet.val() <= 0) {
        valid = false;
        inpPocet.addClass('err');
        messages.push('Poèet musí být èíslo vìtší než 0');
    } else {
        inpPocet.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba pøi vytváøení služby', messages);

    return valid;
};

ObjednavkyValidator.validateSlevaCreate = function(popupErr, inpNazev, inpVyse, inpTyp) {
    var valid = true;
    var messages = [];

    if(inpNazev.val() == "") {
        valid = false;
        inpNazev.addClass('err');
        messages.push('Název nesmí být prázdný');
    } else {
        inpNazev.removeClass('err');
    }

    if(inpVyse.val() == "" || inpVyse.val() < 0) {
        valid = false;
        inpVyse.addClass('err');
        messages.push('Výše musí být kladné èíslo');
    } else {
        inpVyse.removeClass('err');
    }

    if(inpTyp.val() == "") {
        valid = false;
        inpTyp.addClass('err');
        messages.push('Typ nesmí být prázdný');
    } else {
        inpTyp.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba pøi vytváøení slevy', messages);

    return valid;
};

ObjednavkyValidator.validatePlatbaCreate = function(popupErr, inpTypDokladu, inpCastka) {
    var valid = true;
    var messages = [];

    if(inpTypDokladu.val() == "") {
        valid = false;
        inpTypDokladu.addClass('err');
        messages.push('Typ dokladu nesmí být prázdný');
    } else {
        inpTypDokladu.removeClass('err');
    }

    if(inpCastka.val() == "" || inpCastka.val() < 0) {
        valid = false;
        inpCastka.addClass('err');
        messages.push('Èástka musí být kladné èíslo');
    } else {
        inpCastka.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba pøi vytváøení platby', messages);

    return valid;
};