//todo zaclenit tohle okno jako composite pod PopupCommonWindow - mozna podedit (jestli to v JS jde)
function PopupInputWindow() {
    var popup, lightbox, body;
    var btnSave, btnCancel;
    var inpAll;
    var data;
    var debugFlag = true;
    var reloadWindowFlag = true;

    /**
     * Initialize components of UI and their events
     */
    this.init = function () {
        initComponents();
        initEvents();
    };

    /**
     * Displays popup window on screen
     * @param headerLabel
     * @param allowedRows pole povolenych elementu ve formatu ['element-name', ...]
     * @param requestURL URL kam se ma odeslat pozadavek na ulozeni dat
     * @param dataURL URL odkud se maji stahnout dodatecna data (jako options selectoru)
     */
    this.showPopup = function (headerLabel, allowedRows, requestURL, dataURL) {
        setHeader(headerLabel);
        showAllowedRows(allowedRows);
        centerWindow();
        initSaveBtn(requestURL);
        showWindow();
        readData(dataURL);
    };

    /**
     * Stop reloading window after submit for all instances of popup window
     */
    this.setReloadWindowOff = function () {
        reloadWindowFlag = false;
    };

    /**
     * Reads data for popup winow inputs
     */
    function readData(url) {
        //send request to server
        if(url == null)
            return;

        $.ajax({
            url: url,
            method: 'post',
            success: function (response) {
                if (debugFlag)
                    console.log(response);
                setData($.parseJSON(response));
            },
            error: function (response) {
                if (debugFlag)
                    console.log(response);
                data = null;
            }
        });
    }

    /**
     * Sets header of popup
     * @param headerLabel
     */
    function setHeader(headerLabel) {
        popup.find('.header').html(headerLabel);
    }

    /**
     * Shows allowed rows only
     * @param allowedRows
     */
    function showAllowedRows(allowedRows) {
        //hide all rows but actions
        popup.find('.body').find('.row').hide();
        popup.find('.body').find('.row.actions').show();

        //show allowed rows
        for (var i = 0; i < allowedRows.length; i++) {
            var element = popup.find('.body').find('.row').find('[name^=' + allowedRows[i] + ']');
            element.parent().show();
        }
    }

    /**
     * Position popup window in the middle of screen
     */
    function centerWindow() {
        popup.css("top", Math.max(0, (($(window).height() - popup.outerHeight()) / 2) + $(window).scrollTop()) + "px");
        popup.css("left", Math.max(0, (($(window).width() - popup.outerWidth()) / 2) + $(window).scrollLeft()) + "px");
    }

    /**
     * Initialize save button, which sends request to the server
     * @param requestUrl
     */
    function initSaveBtn(requestUrl) {
        btnSave.on('click', function () {
            //geather all display data
            var dataElements = $();
            var dataElementsToSend = popup.find('.body').find(':visible').find('[name]');
            dataElementsToSend.each(function () {
                dataElements = dataElements.add($(this));
            });

            //serialize them
            var dataSerialized = dataElements.serialize();

            //send request to server
            $.ajax({
                url: requestUrl,
                method: 'post',
                data: dataSerialized,
                success: function (response) {
                    debugFlag ? console.log(response) : null;
                    dispatchSaveBtnResponse(response, requestUrl, dataSerialized);
                },
                error: function (response) {
                    debugFlag ? console.log(response) : null;
                    hideWindow(false);
                }
            });
        });
    }

    function showWindow() {
        lightbox.show();
        popup.slideDown(250);
    }

    /**
     * Hides popup window and hides body overlay
     * @param reloadPage should page be reloaded after window is hid
     */
    function hideWindow(reloadPage) {
        popup.slideUp(250, function () {
            lightbox.hide();
            if (reloadWindowFlag && reloadPage) {
                window.location.reload();
            }

            //remove error from all inputs
            popup.find('.err').removeClass('err');
        });
    }

    /**
     * Dispatches response to request initiated by save button
     * @param response
     * @param requestUrl
     * @param dataSerialized
     */
    function dispatchSaveBtnResponse(response, requestUrl, dataSerialized) {
        if (!response) {
            hideWindow(true);
            return;
        }

        var responseParsed = $.parseJSON(response);

        if (responseParsed['status'] == 'warning') {
            //poud uzivatel volbu potvrdi - znovu odesli request
            if (confirm(responseParsed['warning-msg'])) {
                $.ajax({
                    url: requestUrl,
                    method: 'post',
                    data: dataSerialized + '&confirmed=1',
                    success: function (response) {
                        debugFlag ? console.log(response) : null;
                        hideWindow(true);
                    },
                    error: function (response) {
                        debugFlag ? console.log(response) : null;
                        hideWindow(false);
                    }
                });
            }
        } else if (responseParsed['status'] == 'validation-error') {
            var messages = [];
            $.each(responseParsed['error-list'], function(i, error) {
                debugFlag ? console.log(error['elementId'] + ': ' + error['message']) : null;

                //show error on element
                var errorElement = $('#' + error['elementId']);
                errorElement.addClass('err');
                errorElement.prev().addClass('err');
                messages.push(error['message']);
            });

            //popup message window
            var popupMessageWindow = new PopupCommonWindow();
            popupMessageWindow.init('popupValidation');
            popupMessageWindow.showPopup(responseParsed['header'], messages);
        }
    }

    /**
     * Search and instantiate UI elements
     */
    function initComponents() {
        popup = $('#popupInput');
        lightbox = $('#lightbox');
        body = $('body');
        btnSave = popup.find('.body').find('.row.actions').find('[name=save]');
        btnCancel = popup.find('.body').find('.row.actions').find('[name=cancel]');
        inpAll = popup.find('.row').find('input[type=text], select, textarea');
    }

    /**
     * Init events
     */
    function initEvents() {
        //stop events propagation
        popup.on('click', function(event) {
            event.stopImmediatePropagation();
        });

        //resize popup on window resize
        $(window).resize(function() {
            centerWindow();
        });

        //cancel on esc
        body.on('keyup', handleEscape);

        //submit on enter
        inpAll.on('keyup', function(e) {
            if(e.keyCode == 13)
                btnSave.trigger('click');
        });

        //cancel button
        btnCancel.on('click', function () {
            hideWindow(false);
        });
    }

    /**
     * @param data Data ve formatu {'klic': 'hodnota', ...}
     */
    function setData(data) {
        $.each(data, function (elementName, element) {
            popup.find('.body').find('.row').find('[name=' + elementName + ']').val(element);
        });
    }

    function handleEscape(e) {
        if(e.keyCode == 27)
            hideWindow();
    }
}

PopupInputWindow._instance = null;

/**
 * Get single instance
 * @returns PopupInputWindow
 */
PopupInputWindow._getInstance = function () {
    if (PopupInputWindow._instance == null)
        PopupInputWindow._instance = new PopupInputWindow();
    return PopupInputWindow._instance;
};