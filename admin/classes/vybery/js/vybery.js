/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    var formFilterSerialy = $('#form-filter');
    var formSerialy = $('#form-serialy');
    var cbFSerialNoZajezd = $("input[name='f_serial_no_zajezd']");
    var cbFSerialNoAktivniZajezd = $("input[name='f_serial_no_aktivni_zajezd']");
    var cbFSerialAktivniZajezd = $("input[name='f_serial_aktivni_zajezd']");
    var cbFZajezdObjednavka = $("input[name='f_zajezd_objednavka']");
    var cbFZajezdNoObjednavka = $("input[name='f_zajezd_no_objednavka']");

    //tlacitka
    $('#btn-filter').click(function () {
        formFilterSerialy.submit();
        return false;
    });
    $('#btn-delete-all').click(function () {
        formSerialy.submit();
        return false;
    });

    //checkboxy - nektere kombinace zaskrtnuti jsou vyloucene nebo nezadouci
    cbFSerialNoZajezd.click(function () {
        if(cbFSerialNoZajezd.prop('checked')) {
            cbFSerialNoAktivniZajezd.prop('checked', false);
            cbFSerialAktivniZajezd.prop('checked', false);
            cbFZajezdObjednavka.prop('checked', false);
            cbFZajezdNoObjednavka.prop('checked', true);
        }
    });
    cbFSerialNoAktivniZajezd.click(function () {
        if(cbFSerialNoAktivniZajezd.prop('checked')) {
            cbFSerialNoZajezd.prop('checked', false);
        }
    });
    cbFSerialAktivniZajezd.click(function () {
        if(cbFSerialAktivniZajezd.prop('checked')) {
            cbFSerialNoZajezd.prop('checked', false);
        }
    });
    cbFZajezdNoObjednavka.click(function () {
        if(!cbFZajezdNoObjednavka.prop('checked')) {
            cbFSerialNoZajezd.prop('checked', false);
        }
    });
});