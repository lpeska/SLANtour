function PopupCommonWindow() {
    var popup, lightbox, body;
    var btnOk;
    var ANIMATION_SPEED = 150;

    /**
     * Initialize components of UI and their events
     */
    this.init = function (elementId) {
        initComponents(elementId);
        initEvents();
    };

    /**
     * Displays popup window on screen
     * @param headerLabel
     * @param messages
     */
    this.showPopup = function (headerLabel, messages) {
        popup.find('.header').html(headerLabel);

        showMessages(messages); //todo toto uz by melo byt v nejakem konkretnim okne, ne v CommonWindow - zvazit zda i header by mel byt mimo

        centerWindow();
        showWindow();
    };

    function showMessages(messages) {
        if($.isArray(messages)) {
            popup.find('.main-content').html('');
            $.each(messages, function (key, message) {
                var actualHTML = popup.find('.main-content').html();
                popup.find('.main-content').html(actualHTML + '<div class="row err">' + message + '</div>');
            });
        }
    }

    function initComponents(elementId) {
        popup = $('#' + elementId);
        lightbox = $('#lightbox');
        body = $('body');
        btnOk = popup.find('input[name=ok]');
    }

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

        //ok button
        btnOk.on('click', function (event) {
            event.stopImmediatePropagation();
            hideWindow();
        });
    }

    function handleEscape(e) {
        if(e.keyCode == 27)
            hideWindow();
    }

    function showWindow() {
        lightbox.css('z-index', parseInt(lightbox.css('z-index')) + 1); //note neni to moc sikovny - raci bych videl zmenu jen z-indexu lightboxu
        popup.css('z-index', parseInt(popup.css('z-index')) + 1);
        lightbox.show();
        
        popup.slideDown(ANIMATION_SPEED);
    }

    /**
     * Hides popup window and hides body overlay
     * @param reloadPage should page be reloaded after window is hid
     */
    function hideWindow() {
        lightbox.css('z-index', parseInt(lightbox.css('z-index')) - 1); //note neni to moc sikovny - raci bych videl zmenu jen z-indexu lightboxu
        popup.css('z-index', parseInt(popup.css('z-index')) - 1);
        if($('.popup:visible').length == 0)
            lightbox.hide();

        popup.slideUp(ANIMATION_SPEED);
    }

    /**
     * Position popup window in the middle of screen
     */
    function centerWindow() {
        popup.css("top", Math.max(0, (($(window).height() - popup.outerHeight()) / 2) + $(window).scrollTop()) + "px");
        popup.css("left", Math.max(0, (($(window).width() - popup.outerWidth()) / 2) + $(window).scrollLeft()) + "px");
    }
}