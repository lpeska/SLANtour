function ObjednavkyButtons() {
    var btnTermin, datePicker, popupDatePicker, calendarIco, body, state, btnsUcastnikEdit, btnsPlatbaEdit, btnTsPoznamky,
        btnTsTajnePoznamky, btnTsDopravaCS, btnTsStravovaniCS, btnTsUbytovaniCS, btnTsPojisteniCS, btnsPlatbaRemove,
        btnPlatbaAdd, btnsFakturaProvizePay, btnsUcastnikRemove, btnsUcastnikStorno, btnsUcastnikStornoUndo, btnUcastnikCreate,
        btnProvizniAgenturaChange, btnObjednavajiciOsobaChange, btnObjednavajiciOrganizaceChange, btnsSluzbyMinus,
        btnsSluzbyPlus, btnsSluzbyStornoMinus, btnsSluzbyStornoPlus, btnsSluzbyAdd, btnSluzbyCreate, btnsSlevyAdd,
        btnsSlevySluzbaAdd, btnsSlevyRemove, btnsSlevyMinus, btnsSlevyPlus, btnSlevyCreate, btnsRelodSluzbaCena,
        btnControlHelp, btnEditSerial, btnProvizeEdit, btnTsKUhradeZalohaCS, btnTsKUhradeDoplatekCS, btnTsKUhradeCelkemCS;
    var inpProvizniAgentura, inpObjednavajiciOsoba, inpObjednavajiciOrganizace, inpUserSurname, inpStavStornoPoplatek, inpSerialEditTermin,
        inpStavStornoPoplatekCK, inpAllInputs, inpEditSerial;
    var hidEditSerial;
    var selectStavObjednavky, selectEditZajezd;
    var tooltipList;
    var typeaheadProvizniAgentura, typeaheadObjednavajiciOsoba, typeaheadObjednavajiciOrganizace, typeaheadUserSurname, typeaheadEditSerial;
    var moduleData;
    var popupInputWindow, popupMessageWindow;
    var idObjednavka;

    /**
     * Initialize components of UI and their events
     */
    this.init = function () {
        initComponents();
        initEvents();
    };

    /**
     * Search and instantiate UI elements
     */
    function initComponents() {
        //global data
        moduleData = $('#module-data');
        idObjednavka = moduleData.data('id-objednavka');

        //common
        inpAllInputs = $('input, select, textarea');

        //controls
        body = $('body');
        btnControlHelp = $('#btn-help');
        tooltipList = body.find('.main [title]:visible');

        //obecne info
        btnEditSerial= $('#btn-edit-serial');
        typeaheadEditSerial = TypeaheadSetup._factory();
        inpEditSerial = $('#inp-edit-serial');
        hidEditSerial = $('#hid-edit-serial-id');
        selectEditZajezd = $('#select-edit-zajezd');
        inpSerialEditTermin = $('#inp-serial-edit-termin');
        btnTermin = $('#btn-termin');
        datePicker = $('#datepicker');
        calendarIco = $('.calendar-ico');
        state = $('#state');
        selectStavObjednavky = $('#stav-stav-objednavky');    //inside popup window
        inpStavStornoPoplatek = $('#stav-storno-poplatek');    //inside popup window
        inpStavStornoPoplatekCK = $('#stav-storno-poplatek-ck');    //inside popup window

        //osoby / organizace
        btnsUcastnikEdit = $('.btn-ucastnik-edit');
        btnsUcastnikRemove = $('.btn-ucastnik-remove');
        btnsUcastnikStorno = $('.btn-ucastnik-storno');
        btnsUcastnikStornoUndo = $('.btn-ucastnik-storno-undo');
        btnUcastnikCreate = $('#btn-ucastnik-create');
        btnProvizniAgenturaChange = $('#btn-provizni-agentura-change');
        btnObjednavajiciOsobaChange = $('#btn-objednavajici-osoba-change');
        btnObjednavajiciOrganizaceChange = $('#btn-objednavajici-organizace-change');
        inpProvizniAgentura = $('#inp-provizni-agentura');
        inpObjednavajiciOsoba = $('#inp-objednavajici-osoba');
        inpObjednavajiciOrganizace = $('#inp-objednavajici-organizace');
        inpUserSurname = $('#inp-user-surname');
        typeaheadProvizniAgentura = TypeaheadSetup._factory();
        typeaheadObjednavajiciOsoba = TypeaheadSetup._factory();
        typeaheadObjednavajiciOrganizace = TypeaheadSetup._factory();
        typeaheadUserSurname = TypeaheadSetup._factory();

        //sluzby
        btnsSluzbyMinus = $('.btn-sluzby-minus');
        btnsSluzbyPlus = $('.btn-sluzby-plus');
        btnsSluzbyStornoMinus = $('.btn-sluzby-storno-minus');
        btnsSluzbyStornoPlus = $('.btn-sluzby-storno-plus');
        btnsSluzbyAdd = $('.btn-sluzby-add');
        btnSluzbyCreate = $('#btn-sluzby-create');
        btnsRelodSluzbaCena = $('#section-sluzby').find('.row .reload');

        //slevy
        btnsSlevyMinus = $('.btn-slevy-minus');
        btnsSlevyPlus = $('.btn-slevy-plus');
        btnsSlevySluzbaAdd = $('.btn-slevy-sluzba-add');
        btnsSlevyAdd = $('.btn-slevy-add');
        btnsSlevyRemove = $('.btn-slevy-remove');
        btnSlevyCreate = $('#btn-slevy-create');

        //finance
        btnsFakturaProvizePay = $('.btn-faktura-provize-pay');
        btnsPlatbaEdit = $('.btn-platba-edit');
        btnsPlatbaRemove = $('.btn-platba-remove');
        btnPlatbaAdd = $('#btn-platba-add');
        btnProvizeEdit = $('#btn-provize-edit');

        //ts / poznamky
        btnTsPoznamky = $('#btn-ts-poznamky');
        btnTsTajnePoznamky = $('#btn-ts-tajne-poznamky');
        btnTsDopravaCS = $('#btn-ts-doprava-cs');
        btnTsStravovaniCS = $('#btn-ts-stravovani-cs');
        btnTsUbytovaniCS = $('#btn-ts-ubytovani-cs');
        btnTsPojisteniCS = $('#btn-ts-pojisteni-cs');
        btnTsKUhradeZalohaCS = $('#btn-ts-k-uhrade-zaloha');
        btnTsKUhradeDoplatekCS = $('#btn-ts-k-uhrade-doplatek');
        btnTsKUhradeCelkemCS = $('#btn-ts-k-uhrade-celkem');

        //popup input window
        popupInputWindow = PopupInputWindow._getInstance();
        popupInputWindow.init();

        //popup message window
        popupMessageWindow = new PopupCommonWindow();
        popupMessageWindow.init('popupValidation');
    }

    /**
     * Init events
     */
    function initEvents() {
        initCommon();
        initControllEvents();
        initObecneInfoEvents();
        initOsobyOrganizaceEvents();
        initSluzbyEvents();
        initSlevyEvents();
        initFinanceEvents();
        initTsPoznamkyEvents();
    }

    function initCommon() {
        //remove error from all inputs on focus
        inpAllInputs.on('focus', function() {
            $(this).removeClass('err');
            $("label[for='" + $(this).attr('id') + "']").removeClass('err');
        });
    }

    function initControllEvents() {
        btnControlHelp.on('click', function () {
            //temp remove class
            tooltipList.each(function() {
                if($(this).hasClass('trans-400-lin')) {
                    $(this).data('trans-class', 'trans-400-lin');
                    $(this).removeClass('trans-400-lin');
                }
            });
            tooltipList.addClass('tt-highlight');
            tooltipList.fadeTo(800, 1).fadeTo(800, 0).fadeTo(800, 1).fadeTo(800, 0).fadeTo(800, 1).fadeTo(800, 0).fadeTo(800, 1);
            setTimeout(function () {
                tooltipList.removeClass('tt-highlight');
                //add temp removed class back
                tooltipList.each(function() {
                    if($(this).data('trans-class') == 'trans-400-lin')
                        $(this).addClass('trans-400-lin');
                });
            }, 6000);
        });
    }

    function initObecneInfoEvents() {
        initDatePickers(idObjednavka);

        //edit serial
        btnEditSerial.on('click', function(event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace seriálu a zájezdu',
                ['inp-edit-serial-storno-poplatek', 'edit-serial', 'edit-zajezd', 'sluzby-wrapper'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=obecne-info-serial-save&idObjednavka=' + idObjednavka,
                null
            );

            //typeahead
            var baseTypeaheadUrl = 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1';
            typeaheadEditSerial.init(inpEditSerial, "edit-serial", TypeaheadSetup.loadRemoteData('&ajax=serialy', false, baseTypeaheadUrl), 1, false, "value");
            //on serial selection
            typeaheadEditSerial.confirm(function (e, serial) {
                e.preventDefault();

                //has dlouhodobe zajezdy?
                if(serial.dlouhodobe_zajezdy == 1) {
                    inpSerialEditTermin.parent().show();
                }

                var emptyOption = $("<option></option>").text('[id] a období zájezdu').val(0);
                var sluzbyWrapper = $("#sluzby-wrapper");
                sluzbyWrapper.empty();
                hidEditSerial.val(serial.id);
                $.ajax({
                    type: 'GET',
                    url: 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1&oldZajezdy=true&ajax=zajezdy&serial_id=' + serial.id,
                    success: function (result) {
                        selectEditZajezd.empty();
                        selectEditZajezd.append(emptyOption);
                        $.each(result, function (key, value) {
                            selectEditZajezd.append($("<option></option>").attr("value", value.id).text("[" + value.id + "] " + value.nazev));
                        });

                        //on zajezd selection
                        selectEditZajezd.on('change', function () {
                            var zajezdId = $(this).val();
                            $.ajax({
                                url: 'https://' + window.location.host + '/objednavka-proces/index.php?page=ajax-sluzby&zajezd_id=' + zajezdId
                            }).success(function (data) {
                                sluzbyWrapper.empty();

                                if (data == "")
                                    return;

                                var sluzby = $.parseJSON(data);
                                for (var i = 0; i < sluzby.length; i++) {
                                    var row = $("<div class='row'></div>");
                                    var inputName = $("<label class='long'></label>").html(sluzby[i].nazev + ": ");
                                    var inputPocet = $("<input type='text' class='smallNumber' name='sluzby[" + i + "][pocet]'/>").val(0);
                                    var inputCastka = $("<span class='smallNumber'></span>").html(sluzby[i].castka + " Kè");
                                    var inputHidId = $("<input type='hidden' name='sluzby[" + i + "][id]'/>").val(sluzby[i].id);
                                    row.append(inputName).append(inputPocet).append(inputCastka).append(inputHidId);
                                    sluzbyWrapper.append(row);
                                }
                            }).fail(function() {

                            });
                        });
                    },
                    error: function () {
                        selectEditZajezd.empty();
                        selectEditZajezd.append(emptyOption);
                    }
                });
            });
            //remove serial id when serial is deleted
            inpEditSerial.on('blur', function() {
                if($(this).val().trim() == '')
                    hidEditSerial.val('');
            });
            //remove the default 'enter to send form' behaviour of input
            inpEditSerial.off('keyup');
        });

        state.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Stav objednávky',
                ['stav-stav-objednavky'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=obecne-info-stav-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=obecne-info-stav-load&idObjednavka=' + idObjednavka
            );
            selectStavObjednavky.change();  //explicitly trigger change to init default selection of state selectbox
        });
        selectStavObjednavky.on('change', function () {
            //note stav storno a storno CK - spravne by bylo nacist id stavu z konstant prez ajax, ale temer jiste se nebudou menit
            if ($(this).val() == 8) {
                inpStavStornoPoplatek.parent().show();
                inpStavStornoPoplatekCK.parent().hide();
            } else if ($(this).val() == 10) {
                inpStavStornoPoplatek.parent().show();
                inpStavStornoPoplatekCK.parent().hide();
            } else if ($(this).val() == 9) {
                inpStavStornoPoplatekCK.parent().show();
                inpStavStornoPoplatek.parent().hide();
            } else {
                inpStavStornoPoplatekCK.parent().hide();
                inpStavStornoPoplatek.parent().hide();
            }
        });
    }

    function initOsobyOrganizaceEvents() {
        var baseTypeaheadUrl = 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1';

        //typeahead provizni agentura
        typeaheadProvizniAgentura.init(inpProvizniAgentura, "provizni-agentura", TypeaheadSetup.loadRemoteData('&ajax=agentury&cache=' + (new Date()).getTime(), false, baseTypeaheadUrl), 1, false, "value");
        inpProvizniAgentura.on('blur', function () {
            inpProvizniAgentura.val('');
        });
        typeaheadProvizniAgentura.confirm(function (e, data) {
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-provizni-agentura-save&idOrganizace=' + data.id + '&idObjednavka=' + idObjednavka,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });

        //typeahead objednavajici osoba
        typeaheadObjednavajiciOsoba.init(inpObjednavajiciOsoba, "objednavajici-osoba", TypeaheadSetup.loadRemoteData('&ajax=osoby', true, baseTypeaheadUrl + '&query=%QUERY&'), 2, false, "prijmeni");
        inpObjednavajiciOsoba.on('blur', function () {
            inpObjednavajiciOsoba.val('');
        });
        typeaheadObjednavajiciOsoba.confirm(function (e, data) {
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-objednavajici-osoba-save&idUser=' + data.id + '&idObjednavka=' + idObjednavka,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });

        //typeahead objednavajici organizace
        typeaheadObjednavajiciOrganizace.init(inpObjednavajiciOrganizace, "objednavajici-organizace", TypeaheadSetup.loadRemoteData('&ajax=organizace&cache=' + (new Date()).getTime(), false, baseTypeaheadUrl + '&query=%QUERY&'), 2, false, "value");
        inpObjednavajiciOrganizace.on('blur', function () {
            inpObjednavajiciOrganizace.val('');
        });
        typeaheadObjednavajiciOrganizace.confirm(function (e, data) {
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-objednavajici-organizace-save&idOrganizace=' + data.id + '&idObjednavka=' + idObjednavka,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });

        //typeahead ucastnik
        typeaheadUserSurname.init(inpUserSurname, "user-name", TypeaheadSetup.loadRemoteData('&ajax=osoby', true, baseTypeaheadUrl + '&query=%QUERY&'), 2, false, "prijmeni");
        typeaheadUserSurname.confirm(function (e, data) {
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-add-existing&idUser=' + data.id + '&idObjednavka=' + idObjednavka,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                },
                always: function () {
                    inpUserSurname.val('');
                }
            });
        });

        //btns ucastnik
        btnsUcastnikEdit.on('click', function (event) {
            event.stopPropagation();

            var idUser = $(this).parents('.row').data('user-id'); //data umistena v elementu .row
            var userFullName = $(this).parents('.row').data('user-name');
            popupInputWindow.showPopup(
                'Editace úèastníka ' + userFullName,
                ['user-titul', 'user-jmeno', 'user-prijmeni', 'user-datum-narozeni', 'user-rodne-cislo', 'user-email', 'user-telefon', 'user-cislo-op', 'user-cislo-pasu', 'user-ulice', 'user-mesto', 'user-psc'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-save&idUcastnik=' + idUser,
                ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-load&idObjednavka=' + idObjednavka + '&idUcastnik=' + idUser
            );
        });
        btnsUcastnikRemove.on('click', function () {
            if (confirm('Opravdu odebrat úèastníka?')) {
                var idUser = $(this).parents('.row').data('user-id'); //data umistena v elementu .row
                $.ajax({
                    url: ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-remove&idUcastnik=' + idUser + '&idObjednavka=' + idObjednavka,
                    type: 'get',
                    success: function (response) {
                        //console.log(response);
                        window.location.reload();
                    }
                });
            }
        });
        btnsUcastnikStorno.on('click', function (event) {
            event.stopPropagation();

            var idUser = $(this).parents('.row').data('user-id'); //data umistena v elementu .row
            popupInputWindow.showPopup(
                'Které služby se mají stornovat?',
                ['sluzby-storno-pocet', 'sluzby-storno-pocet-empty'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-storno-save&idObjednavka=' + idObjednavka + '&idUcastnik=' + idUser,
                null
            );
        });
        btnsUcastnikStornoUndo.on('click', function (event) {
            event.stopPropagation();

            var idUser = $(this).parents('.row').data('user-id'); //data umistena v elementu .row
            popupInputWindow.showPopup(
                'Které služby se mají zpìtnì objednat?',
                ['sluzby-storno-undo-pocet', 'sluzby-storno-undo-pocet-empty'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=osoby-ucastnik-storno-undo&idObjednavka=' + idObjednavka + '&idUcastnik=' + idUser,
                null
            );
        });
        btnUcastnikCreate.on('click', function (event) {
            event.stopPropagation();

            var frmUcastniciAdd = $('#frm-ucastnici-add');
            var inpPrijmeni = frmUcastniciAdd.find('[name="user-prijmeni"]');
            var inpJmeno = frmUcastniciAdd.find('[name="user-jmeno"]');
            if (ObjednavkyValidator.validateUcastnikCreate(popupMessageWindow, inpPrijmeni, inpJmeno))
                frmUcastniciAdd.submit();
        });
    }

    function initSluzbyEvents() {
        btnsSluzbyMinus.on('click', function () {
            var idSluzba = $(this).parents('.row').data('sluzba-id');   //data umistena v elementu .row
            var pocetSluzba = $(this).parents('.row').data('sluzba-pocet'); //data umistena v elementu .row
            var typSluzba = $(this).parents('.row').data('sluzba-typ'); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-minus&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka + '&typ=' + typSluzba + '&pocet=' + pocetSluzba,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSluzbyPlus.on('click', function () {
            var idSluzba = $(this).parents('.row').data('sluzba-id'); //data umistena v elementu .row
            var typSluzba = $(this).parents('.row').data('sluzba-typ');
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-plus&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka + '&typ=' + typSluzba,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSluzbyStornoMinus.on('click', function (event) {
            event.stopPropagation();

            var idSluzba = $(this).parents('.row').data('sluzba-id'); //data umistena v elementu .row
            var typSluzba = $(this).parents('.row').data('sluzba-typ');
            popupInputWindow.showPopup(
                'Zpìtné objednání služby',
                ['sluzby-storno-undo-pocet-' + idSluzba],
                ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-storno-minus&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka + '&typ=' + typSluzba,
                null
            );
        });
        btnsSluzbyStornoPlus.on('click', function (event) {
            event.stopPropagation();

            var idSluzba = $(this).parents('.row').data('sluzba-id'); //data umistena v elementu .row
            var typSluzba = $(this).parents('.row').data('sluzba-typ');
            popupInputWindow.showPopup(
                'Storno služby',
                ['sluzby-storno-pocet-' + idSluzba],
                ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-storno-plus&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka + '&typ=' + typSluzba,
                null
            );
        });
        btnsSluzbyAdd.on('click', function () {
            var idSluzba = $(this).parents('.row').data('sluzba-id'); //data umistena v elementu .row
            var pocet = $(this).parents('.row').find('[name=pocet]').val();
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-add&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka,
                type: 'post',
                data: {'pocet': pocet},
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnSluzbyCreate.on('click', function (event) {
            event.stopPropagation();

            var frmSluzbyCreate = $('#frm-sluzby-create');
            var inpNazev = frmSluzbyCreate.find('[name="nazev-sluzby"]');
            var inpCastka = frmSluzbyCreate.find('[name="castka"]');
            var inpPocet = frmSluzbyCreate.find('[name="pocet"]');
            if (ObjednavkyValidator.validateSluzbaCreate(popupMessageWindow, inpNazev, inpCastka, inpPocet))
                frmSluzbyCreate.submit();
        });
        btnsRelodSluzbaCena.on('click', function () {
            if (confirm('Opravdu naèíst èástku ze služby zájezdu?')) {
                var idSluzba = $(this).parents('.row').data('sluzba-id'); //data umistena v elementu .row
                $.ajax({
                    url: ObjednavkyButtons.BASE_REQ_URL + 'action=sluzby-price-refresh&idSluzba=' + idSluzba + '&idObjednavka=' + idObjednavka,
                    type: 'get',
                    success: function (response) {
                        //console.log(response);
                        window.location.reload();
                    }
                });
            }
        });
    }

    function initSlevyEvents() {
        btnsSlevyMinus.on('click', function () {
            var idSleva = $(this).parents('.row').data('sleva-id');   //data umistena v elementu .row
            var pocetSleva = $(this).parents('.row').data('sleva-pocet'); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=slevy-minus&idSleva=' + idSleva + '&idObjednavka=' + idObjednavka + '&pocet=' + pocetSleva,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSlevyPlus.on('click', function () {
            var idSleva = $(this).parents('.row').data('sleva-id'); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=slevy-plus&idSleva=' + idSleva + '&idObjednavka=' + idObjednavka,
                type: 'get',
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSlevySluzbaAdd.on('click', function () {
            var idSleva = $(this).parents('.row').data('sleva-id'); //data umistena v elementu .row
            var pocet = $(this).parents('.row').find('[name=pocet]').val(); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=slevy-sluzba-add&idSleva=' + idSleva + '&idObjednavka=' + idObjednavka,
                type: 'post',
                data: {'pocet': pocet},
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSlevyAdd.on('click', function () {
            var nazevSlevy = $(this).parents('.row').data('sleva-nazev'); //data umistena v elementu .row
            var velikostSlevy = $(this).parents('.row').data('sleva-velikost-slevy'); //data umistena v elementu .row
            var menaSlevy = $(this).parents('.row').data('sleva-mena'); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=slevy-add&idObjednavka=' + idObjednavka,
                type: 'post',
                data: {'nazev-slevy': nazevSlevy, 'velikost-slevy': velikostSlevy, 'mena-slevy': menaSlevy},
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnsSlevyRemove.on('click', function () {
            var nazevSlevy = $(this).parents('.row').data('sleva-nazev'); //data umistena v elementu .row
            var velikostSlevy = $(this).parents('.row').data('sleva-velikost-slevy'); //data umistena v elementu .row
            $.ajax({
                url: ObjednavkyButtons.BASE_REQ_URL + 'action=slevy-remove&idObjednavka=' + idObjednavka,
                type: 'post',
                data: {'nazev-slevy': nazevSlevy, 'velikost-slevy': velikostSlevy},
                success: function (response) {
                    //console.log(response);
                    window.location.reload();
                }
            });
        });
        btnSlevyCreate.on('click', function (event) {
            event.stopPropagation();

            var frmSlevyCreate = $('#frm-slevy-create');
            var inpNazev = frmSlevyCreate.find('[name="nazev-slevy"]');
            var inpCastka = frmSlevyCreate.find('[name="vyse-slevy"]');
            var inpPocet = frmSlevyCreate.find('[name="typ-slevy"]');
            if (ObjednavkyValidator.validateSlevaCreate(popupMessageWindow, inpNazev, inpCastka, inpPocet))
                frmSlevyCreate.submit();
        });
    }

    function initFinanceEvents() {
        btnsFakturaProvizePay.on('click', function () {
            if (confirm('Opravdu zaplatit provizní fakturu?')) {
                var idFakturaProvize = $(this).parents('.row').data('faktura-prodejce-id'); //data umistena v elementu .row
                $.ajax({
                    url: ObjednavkyButtons.BASE_REQ_URL + 'action=finance-faktura-provize-pay&idFakturaProvize=' + idFakturaProvize,
                    type: 'get',
                    success: function (response) {
                        //console.log(response);
                        window.location.reload();
                    }
                });
            }
        });
        btnsPlatbaEdit.on('click', function (event) {
            event.stopPropagation();

            var idPlatba = $(this).parents('.row').data('platba-id'); //data umistena v elementu .row
            var platbaVystaveno = $(this).parents('.row').data('platba-vystaveno');
            popupInputWindow.showPopup(
                'Editace platby è. ' + idPlatba + ' (' + platbaVystaveno + ')',
                ['platba-cislo-dokladu', 'platba-typ-dokladu', 'platba-castka', 'platba-splatnost-do', 'platba-uhrazeno', 'platba-zpusob-uhrady'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=finance-platba-save&idObjednavka=' + idObjednavka + '&idPlatba=' + idPlatba,
                ObjednavkyButtons.BASE_REQ_URL + 'action=finance-platba-load&idObjednavka=' + idObjednavka + '&idPlatba=' + idPlatba);
        });
        btnsPlatbaRemove.on('click', function () {
            if (confirm('Opravdu odebrat platbu?')) {
                var idPlatba = $(this).parents('.row').data('platba-id'); //data umistena v elementu .row
                $.ajax({
                    url: ObjednavkyButtons.BASE_REQ_URL + 'action=finance-platba-remove&idObjednavka=' + idObjednavka + '&idPlatba=' + idPlatba,
                    type: 'get',
                    success: function (response) {
                        //console.log(response);
                        window.location.reload();
                    }
                });
            }
        });
        btnPlatbaAdd.on('click', function (event) {
            event.stopPropagation();

            var frmPlatbaAdd = $('#frm-platba-add');
            var inpTypDokladu = frmPlatbaAdd.find('[name="typ-dokladu"]');
            var inpCastka = frmPlatbaAdd.find('[name="castka"]');
            if (ObjednavkyValidator.validatePlatbaCreate(popupMessageWindow, inpTypDokladu, inpCastka))
                frmPlatbaAdd.submit();
        });
        btnProvizeEdit.on('click', function(event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace provize',
                ['provize-castka'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=finance-provize-save&idObjednavka=' + idObjednavka,
                null
            );
        });
    }

    function initTsPoznamkyEvents() {
        btnTsPoznamky.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Poznámka"',
                ['ts-poznamky'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-poznamky-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-poznamky-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsTajnePoznamky.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Tajná poznámka"',
                ['ts-tajne-poznamky'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-tajne-poznamky-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-tajne-poznamky-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsDopravaCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Doprava (cest. sml.)"',
                ['ts-doprava-cs'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-doprava-cs-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-doprava-cs-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsStravovaniCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Stravování (cest. sml.)"',
                ['ts-stravovani-cs'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-stravovani-cs-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-stravovani-cs-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsUbytovaniCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Ubytování (cest. sml.)"',
                ['ts-ubytovani-cs'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-ubytovani-cs-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-ubytovani-cs-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsPojisteniCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "Pojištìní (cest. sml.)"',
                ['ts-pojisteni-cs'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-pojisteni-cs-save&idObjednavka=' + idObjednavka,
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-pojisteni-cs-load&idObjednavka=' + idObjednavka
            );
        });
        btnTsKUhradeZalohaCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "K úhradì záloha (cest. smlouva)"',
                ['ts-k-uhrade-zaloha-castka', 'ts-k-uhrade-zaloha-datum'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-k-uhrade-zaloha-cs-save&idObjednavka=' + idObjednavka,
                null
            );
        });
        btnTsKUhradeDoplatekCS.on('click', function (event) {
            event.stopPropagation();

            popupInputWindow.showPopup(
                'Editace textu tiskové sestavy "K úhradì doplatek (cest. smlouva)"',
                ['ts-k-uhrade-doplatek-castka', 'ts-k-uhrade-doplatek-datum'],
                ObjednavkyButtons.BASE_REQ_URL + 'action=ts-k-uhrade-doplatek-cs-save&idObjednavka=' + idObjednavka,
                null
            );
        });
    }

    /**
     * Init datePicker
     * @param idObjednavka
     */
    function initDatePickers(idObjednavka) {
        //daterangepicker - je hodne spatne napsany - je treba ho vytvorit a schovat. Musel jsem odstranit jejich on blur funkci, ktera kalendar schovavala a udelat si vlastni zpusob (jinak neslo pouzit tlacitko spolu s on focus na inputu pro spusteni kalendare)
        if (datePicker.length) {
            datePicker.daterangepicker(ObjednavkyButtons.DATEPICKED_OPTIONS);
            var dateRPicker = datePicker.data().daterangepicker;
            dateRPicker.hide();
            body.on('click', function (event) {
                dateRPicker.clickCancel(event);
            });
            datePicker.on('click', function (event) {
                event.stopPropagation();
            });
            calendarIco.on('click', function (event) {
                event.stopPropagation();
                dateRPicker.toggle();
            });
            $('.daterangepicker').on('click', function (event) {
                event.stopPropagation(); //jinak kalendar zmizi, kdyz na nej kamkoliv kliknu
            });
            datePicker.on('apply.daterangepicker', function () {
                $.ajax({
                    url: ObjednavkyButtons.BASE_REQ_URL + 'action=obecne-info-termin-save&idObjednavka=' + idObjednavka,
                    type: 'post',
                    data: {'obecne-info-termin': datePicker.val()},
                    success: function (response) {
                        //console.log(response)
                        window.location.reload();
                    }
                });
            });
        }

        if (inpSerialEditTermin.length) {
            inpSerialEditTermin.daterangepicker(ObjednavkyButtons.DATEPICKED_OPTIONS);
            var dateRPicker2 = inpSerialEditTermin.data().daterangepicker;
            dateRPicker2.hide();
            var popupInputElemnt = $('#popupInput');
            popupInputElemnt.find('.header').on('click', function(event) {
                dateRPicker2.clickCancel(event);
            });
            popupInputElemnt.find('.body').on('click', function(event) {
                dateRPicker2.clickCancel(event);
            });
            body.on('click', function (event) {
                dateRPicker2.clickCancel(event);
            });
            inpSerialEditTermin.on('click', function (event) {
                event.stopPropagation();
            });
            $('.daterangepicker').on('click', function (event) {
                event.stopPropagation(); //jinak kalendar zmizi, kdyz na nej kamkoliv kliknu
            });
            inpSerialEditTermin.on('apply.daterangepicker', function () {
                //$.ajax({
                //    url: ObjednavkyButtons.BASE_REQ_URL + 'action=obecne-info-termin-save&idObjednavka=' + idObjednavka,
                //    type: 'post',
                //    data: {'obecne-info-termin': datePicker.val()},
                //    success: function (response) {
                //        //console.log(response)
                //        window.location.reload();
                //    }
                //});
            });
        }
    }
}

ObjednavkyButtons._instance = null;
ObjednavkyButtons.BASE_REQ_URL = window.location.origin + '/admin/objednavky.php?page=ajax&';
ObjednavkyButtons.DATEPICKED_OPTIONS = {
    showDropdowns: true,
    format: 'D.M. YYYY',
    locale: {
        applyLabel: 'Potvrdit',
        cancelLabel: 'Zrušit',
        fromLabel: 'Termín od',
        toLabel: 'Termín do',
        daysOfWeek: ['Ne', 'Po', 'Út', 'St', 'Èt', 'Pá', 'So'],
        monthNames: ['leden', 'únor', 'bøezen', 'duben', 'kvìten', 'èerven', 'èervenec', 'srpen', 'záøí', 'øíjen', 'listopad', 'prosinec'],
        firstDay: 1,
        vyprodaneTerminy: false
    }
};

/**
 * Get single instance
 * @returns ObjednavkyButtons
 */
ObjednavkyButtons._getInstance = function () {
    if (ObjednavkyButtons._instance == null)
        ObjednavkyButtons._instance = new ObjednavkyButtons();
    return ObjednavkyButtons._instance;
};