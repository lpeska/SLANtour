function ObjednavkyValidator() {

}

ObjednavkyValidator.validateUcastnikCreate = function (popupErr, inpPrijmeni, inpJmeno) {
    var valid = true;
    var messages = [];

    if(inpPrijmeni.val() == "") {
        valid = false;
        inpPrijmeni.addClass('err');
        messages.push('P��jmen� nesm� b�t pr�zdn�');
    } else {
        inpPrijmeni.removeClass('err');
    }

    if(inpJmeno.val() == "") {
        valid = false;
        inpJmeno.addClass('err');
        messages.push('Jm�no nesm� b�t pr�zdn�');
    } else {
        inpJmeno.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba p�i vytv��en� ��astn�ka', messages);

    return valid;
};

ObjednavkyValidator.validateSluzbaCreate = function(popupErr, inpNazev, inpCastka, inpPocet) {
    var valid = true;
    var messages = [];

    if(inpNazev.val() == "") {
        valid = false;
        inpNazev.addClass('err');
        messages.push('N�zev nesm� b�t pr�zdn�');
    } else {
        inpNazev.removeClass('err');
    }

    if(inpCastka.val() == "" || inpCastka.val() < 0) {
        valid = false;
        inpCastka.addClass('err');
        messages.push('��stka mus� b�t kladn� ��slo');
    } else {
        inpCastka.removeClass('err');
    }

    if(inpPocet.val() == "" || inpPocet.val() <= 0) {
        valid = false;
        inpPocet.addClass('err');
        messages.push('Po�et mus� b�t ��slo v�t�� ne� 0');
    } else {
        inpPocet.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba p�i vytv��en� slu�by', messages);

    return valid;
};

ObjednavkyValidator.validateSlevaCreate = function(popupErr, inpNazev, inpVyse, inpTyp) {
    var valid = true;
    var messages = [];

    if(inpNazev.val() == "") {
        valid = false;
        inpNazev.addClass('err');
        messages.push('N�zev nesm� b�t pr�zdn�');
    } else {
        inpNazev.removeClass('err');
    }

    if(inpVyse.val() == "" || inpVyse.val() < 0) {
        valid = false;
        inpVyse.addClass('err');
        messages.push('V��e mus� b�t kladn� ��slo');
    } else {
        inpVyse.removeClass('err');
    }

    if(inpTyp.val() == "") {
        valid = false;
        inpTyp.addClass('err');
        messages.push('Typ nesm� b�t pr�zdn�');
    } else {
        inpTyp.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba p�i vytv��en� slevy', messages);

    return valid;
};

ObjednavkyValidator.validatePlatbaCreate = function(popupErr, inpTypDokladu, inpCastka) {
    var valid = true;
    var messages = [];

    if(inpTypDokladu.val() == "") {
        valid = false;
        inpTypDokladu.addClass('err');
        messages.push('Typ dokladu nesm� b�t pr�zdn�');
    } else {
        inpTypDokladu.removeClass('err');
    }

    if(inpCastka.val() == "" || inpCastka.val() < 0) {
        valid = false;
        inpCastka.addClass('err');
        messages.push('��stka mus� b�t kladn� ��slo');
    } else {
        inpCastka.removeClass('err');
    }

    if(!valid)
        popupErr.showPopup('Chyba p�i vytv��en� platby', messages);

    return valid;
};