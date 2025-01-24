function FinancniPohybySerial() {
    var frmSerialy, frmFilter;
    var btnZobrazitFP;
    var cbFSerialNoZajezd, cbFSerialNoAktivniZajezd, cbFSerialAktivniZajezd, cbFZajezdObjednavka, cbFZajezdNoObjednavka;
    var inpCalendar;

    /**
     * Initialize components of UI and their events
     */
    this.init = function () {
        initComponents();
        initEvents();
        initSerialFilterOptions();
    };

    /**
     * Search and instantiate UI elements
     */
    function initComponents() {
        frmFilter = $('#form-filter-serialy');
        frmSerialy = $('#form-serialy');
        btnZobrazitFP = $('#btn-zobrazit-fp');
        cbFSerialNoZajezd = $('#f-snz');
        cbFSerialNoAktivniZajezd = $('#f-snaz');
        cbFSerialAktivniZajezd = $('#f-sak');
        cbFZajezdObjednavka = $('#f-zso');
        cbFZajezdNoObjednavka = $('#f-zbo');
        inpCalendar = $('.calendar-ymd');
    }

    /**
     * Init button events
     */
    function initEvents() {
        btnZobrazitFP.on('click', function () {
            frmSerialy.submit();
            return false;
        });
    }

    /**
     * Checkboxy fitlru serialu - nektere kombinace zaskrtnuti jsou vyloucene nebo nezadouci
     */
    function initSerialFilterOptions() {
        cbFSerialNoZajezd.click(function () {
            if (cbFSerialNoZajezd.prop('checked')) {
                cbFSerialNoAktivniZajezd.prop('checked', false);
                cbFSerialAktivniZajezd.prop('checked', false);
                cbFZajezdObjednavka.prop('checked', false);
                cbFZajezdNoObjednavka.prop('checked', true);
            }
        });
        cbFSerialNoAktivniZajezd.click(function () {
            if (cbFSerialNoAktivniZajezd.prop('checked')) {
                cbFSerialNoZajezd.prop('checked', false);
            }
        });
        cbFSerialAktivniZajezd.click(function () {
            if (cbFSerialAktivniZajezd.prop('checked')) {
                cbFSerialNoZajezd.prop('checked', false);
            }
        });
        cbFZajezdNoObjednavka.click(function () {
            if (!cbFZajezdNoObjednavka.prop('checked')) {
                cbFSerialNoZajezd.prop('checked', false);
            }
        });
    }
    /**
     * Po kliknuti na tlacitko "vymazat filtr" vyprazdni vsechny pole formulare
     */
    this.emptySerialFilter = function() {
        
        cbFSerialNoAktivniZajezd.prop('checked', true);
        cbFSerialAktivniZajezd.prop('checked', true);
        cbFZajezdObjednavka.prop('checked', true);
        cbFZajezdNoObjednavka.prop('checked', true);
        
        $("#filter-id").val("");
        $("#filter-nazev").val("");
        $("#filter-id").val("");
        $(".calendar-ymd").val("");
        
        $('#filter-serial-typ option[selected="selected"]').each(
            function() {
                $(this).removeAttr('selected');
            }
        );
        $('#filter-zeme option[selected="selected"]').each(
            function() {
                $(this).removeAttr('selected');
            }
        );
        frmFilter.submit();
    }    
}

FinancniPohybySerial._instance = null;

/**
 * Get single instance
 * @returns FinancniPohybySerial
 */
FinancniPohybySerial._getInstance = function () {
    if (FinancniPohybySerial._instance == null)
        FinancniPohybySerial._instance = new FinancniPohybySerial();
    return FinancniPohybySerial._instance;
};