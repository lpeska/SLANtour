/**
 * Trida je zavisla na knihovne typeahead (typeahead.bundle.js) a handlebars (handlebars-v2.0.0.js)
 */
function TypeaheadSetup() {

    var typeaheadElement;
    var name;
    var bloodhound;
    var minLength;
    var hint;
    var displayValue;
    var that = this;

    this.init = function(typeaheadElement, name, bloodhound, minLength, hint, displayValue) {
        that.typeaheadElement = typeaheadElement;
        that.name = name;
        that.bloodhound = bloodhound;
        that.minLength = minLength;
        that.hint = hint;
        that.displayValue = displayValue;
        setup();
    };

    this.confirm = function(callback) {
        that.typeaheadElement.on('typeahead:autocompleted', callback);
        that.typeaheadElement.on('typeahead:selected', callback);
    };

    function setup() {
        that.typeaheadElement.typeahead({
            hint: that.hint,
            highlight: true,
            minLength: that.minLength
        }, {
            name: that.name,
            displayKey: Handlebars.compile('{{' + that.displayValue + '}}'), //zobrazi se po vybrani elementu
            source: that.bloodhound.ttAdapter(),
            templates: {
                suggestion: Handlebars.compile('<p>[{{id}}] {{nazev}}</p>') //zobrazi se v select boxu pro kazdy zaznam
            }
        });
    }
}

/**
 * Pomocna funkce pro normalizovani znaku ze vstupu uzivatele
 * @param q
 * @returns {*}
 */
TypeaheadSetup.queryTokenizer = function (q) {
    var normalized = Bloodhound.diacriticsNormalize(q);
    return Bloodhound.tokenizers.whitespace(normalized);
};

/**
 * Ziska vzdalena data ze serveru a vrati je jako instanci Bloodhound
 * @param params
 * @param remote
 * @param url
 * @returns {Bloodhound}
 */
TypeaheadSetup.loadRemoteData = function(params, remote, url) {
    var settings = {
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value', 'id'), //podle nich vyhledava - value = nazev bez diakritiky
        queryTokenizer: TypeaheadSetup.queryTokenizer,
        limit: 15
    };

    if (remote) {
        settings.remote = {
            url: url + params + '&cache=' + (new Date()).getTime(),
            filter: function (data) {
                var resultList = data.map(function (item) {
                    return {
                        id: item.id,
                        value: Bloodhound.diacriticsNormalize(item.nazev), //uloz si nazev bez diakritiky do pole value
                        nazev: item.nazev,
                        prijmeni: item.prijmeni,
                        jmeno: item.jmeno,
                        titul: item.titul,
                        datumNarozeni: item.datumNarozeni,
                        rodneCislo: item.rodneCislo,
                        email: item.email,
                        telefon: item.telefon,
                        cisloOP: item.cisloOP,
                        cisloPasu: item.cisloPasu,
                        mesto: item.mesto,
                        ulice: item.ulice,
                        psc: item.psc,
                        dlouhodobe_zajezdy: item.dlouhodobe_zajezdy
                    };
                });
                return resultList;
            }
        }
    } else {
        settings.prefetch = {
            url: url + params,
            filter: function (data) {
                var resultList = data.map(function (item) {
                    return {
                        id: item.id,
                        value: Bloodhound.diacriticsNormalize(item.nazev), //uloz si nazev bez diakritiky do pole value
                        nazev: item.nazev,
                        dlouhodobe_zajezdy: item.dlouhodobe_zajezdy
                    };
                });
                return resultList;
            }
        }
    }
    var bloodhound = new Bloodhound(settings);
    bloodhound.initialize(true);

    return bloodhound;
};

/**
 * Vraci objekt tridy TypeaheadSetup
 * @returns {TypeaheadSetup}
 */
TypeaheadSetup._factory = function() {
  return new TypeaheadSetup();
};