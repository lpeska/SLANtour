function ObjednavkyShowHideBlocks() {
    var osobyOrganizaceSectionToggle, sluzbySectionToggle, slevySectionToggle, financeSectionToggle, poznamkyTsSectionToggle, btnControlToggleAll;
    var blockUcastnici, blockNeobjednaneSluzby, blockNeobjednaneSlevy, blockFakturyPlatby, blockPoznamkyTs;
    var blockObjectsAll;
    var TOGGLE_MODE_HIDDEN = 0, TOGGLE_MODE_VISIBLE = 1, DATA_ID_CLICKED_NO = 0, DATA_ID_CLICKED_YES = 1, DATA_ID_TOGGLE_MODE = 'toggle-mode',
        DATA_ID_CLICKED_MODE = 'clicked-mode', TXT_EXPAND = 'Rozbalit', TXT_COLLAPSE = 'Zabalit';

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
        var sectionOsobyOrganizace = $('#section-osoby-organizace');
        var sectionSluzby = $('#section-sluzby');
        var sectionSlevy = $('#section-slevy');
        var sectionFinance = $('#section-finance');
        var sectionPoznamkyTs = $('#section-poznamky-ts');

        osobyOrganizaceSectionToggle = sectionOsobyOrganizace.find('.btn-toggle');
        sluzbySectionToggle = sectionSluzby.find('.btn-toggle');
        slevySectionToggle = sectionSlevy.find('.btn-toggle');
        financeSectionToggle = sectionFinance.find('.btn-toggle');
        poznamkyTsSectionToggle = sectionPoznamkyTs.find('.btn-toggle');

        blockUcastnici = sectionOsobyOrganizace.find('#block-ucastnici');
        blockNeobjednaneSluzby = sectionSluzby.find('#block-neobjednane-sluzby');
        blockNeobjednaneSlevy = sectionSlevy.find('#block-neobjednane-slevy');
        blockFakturyPlatby = sectionFinance.find('#block-faktury-platby');
        blockPoznamkyTs = sectionPoznamkyTs.find('#block-poznamky-ts');

        btnControlToggleAll = $('#btn-toggle-all');

        //init all block objects
        blockObjectsAll = [
            {'toggleBtn': osobyOrganizaceSectionToggle, 'block': blockUcastnici},
            {'toggleBtn': sluzbySectionToggle, 'block': blockNeobjednaneSluzby},
            {'toggleBtn': slevySectionToggle, 'block': blockNeobjednaneSlevy},
            {'toggleBtn': financeSectionToggle, 'block': blockFakturyPlatby},
            {'toggleBtn': poznamkyTsSectionToggle, 'block': blockPoznamkyTs}
        ];
    }

    /**
     * Init events
     */
    function initEvents() {
        //osoby / organizace
        initToggle({'toggleBtn': osobyOrganizaceSectionToggle, 'block': blockUcastnici});

        //sluzby
        initToggle({'toggleBtn': sluzbySectionToggle, 'block': blockNeobjednaneSluzby});

        //slevy
        initToggle({'toggleBtn': slevySectionToggle, 'block': blockNeobjednaneSlevy});

        //finance
        initToggle({'toggleBtn': financeSectionToggle, 'block': blockFakturyPlatby});

        //poznamky / ts
        initToggle({'toggleBtn': poznamkyTsSectionToggle, 'block': blockPoznamkyTs});

        //toggle all
        btnControlToggleAll.data(DATA_ID_TOGGLE_MODE, TOGGLE_MODE_HIDDEN);
        btnControlToggleAll.find('.btn-toggle').addClass('rotate-right-90');
        btnControlToggleAll.on('click', function () {
            var actualMode = $(this).data(DATA_ID_TOGGLE_MODE);
            if (actualMode == TOGGLE_MODE_HIDDEN) {
                showAll(blockObjectsAll);
                $(this).data(DATA_ID_TOGGLE_MODE, TOGGLE_MODE_VISIBLE);
                $(this).find('.btn-toggle').removeClass('rotate-right-90');
                $(this).find('.label').html(TXT_COLLAPSE);
            } else {
                hideAll(blockObjectsAll);
                $(this).data(DATA_ID_TOGGLE_MODE, TOGGLE_MODE_HIDDEN);
                $(this).find('.btn-toggle').addClass('rotate-right-90');
                $(this).find('.label').html(TXT_EXPAND);
            }
        });
    }

    function initToggle(toggleBox) {
        toggleBox.toggleBtn.parent().on('click', function () {
            if(toggleBox.block.is(":hidden")) {
                show(toggleBox);
            } else {
                hide(toggleBox);
            }
            toggleBox.toggleBtn.data(DATA_ID_CLICKED_MODE, DATA_ID_CLICKED_YES);
        });
        toggleBox.toggleBtn.parent().mouseenter(function () {
            toggleBox.toggleBtn.data(DATA_ID_CLICKED_MODE, DATA_ID_CLICKED_NO);
            if (toggleBox.toggleBtn.hasClass('rotate-right-90'))
                toggleBox.toggleBtn.removeClass('rotate-right-90');
            else
                toggleBox.toggleBtn.addClass('rotate-right-90');
        });
        toggleBox.toggleBtn.parent().mouseleave(function () {
            //pouze pokud jsem nekliknul, tak nic jinak ji otocit
            if (toggleBox.toggleBtn.data(DATA_ID_CLICKED_MODE) == DATA_ID_CLICKED_NO) {
                if (toggleBox.toggleBtn.hasClass('rotate-right-90'))
                    toggleBox.toggleBtn.removeClass('rotate-right-90');
                else
                    toggleBox.toggleBtn.addClass('rotate-right-90');
            }
        });
    }

    function show(blockObject) {
        //save toggle state on server
        $.ajax({
            url: ObjednavkyShowHideBlocks.BASE_REQ_URL + 'action=section-toggle-save',
            type: 'post',
            data: {'toggle-state-add': blockObject.block.attr('id')},
            success: function(response) {
                //console.log("show: " + response);
            }
        });

        blockObject.block.slideDown();
        blockObject.toggleBtn.removeClass('rotate-right-90');
    }

    function showAll(blockObjects) {
        for (var i = 0; i < blockObjects.length; i++) {
            show(blockObjects[i]);
        }
    }

    function hide(blockObject) {
        //save toggle state on server
        $.ajax({
            url: ObjednavkyShowHideBlocks.BASE_REQ_URL + 'action=section-toggle-save',
            type: 'post',
            data: {'toggle-state-remove': blockObject.block.attr('id')},
            success: function(response) {
                //console.log(response);
            }
        });

        blockObject.block.slideUp();
        blockObject.toggleBtn.addClass('rotate-right-90');
    }

    function hideAll(blockObjects) {
        for (var i = 0; i < blockObjects.length; i++) {
            hide(blockObjects[i]);
        }
    }
}

ObjednavkyShowHideBlocks._instance = null;
ObjednavkyShowHideBlocks.BASE_REQ_URL = window.location.origin + '/admin/objednavky.php?page=ajax&';

/**
 * Get single instance
 * @returns ObjednavkyShowHideBlocks
 */
ObjednavkyShowHideBlocks._getInstance = function () {
    if (ObjednavkyShowHideBlocks._instance == null)
        ObjednavkyShowHideBlocks._instance = new ObjednavkyShowHideBlocks();
    return ObjednavkyShowHideBlocks._instance;
};