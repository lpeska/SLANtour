/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    var form = $('#form-generate-pdf');
    $('#btn-generate-pdf-voucher').click(function () {
        form.submit();
        //reloadovat page hned po submitu formulare do target="_blank" buhvi proc nejde
        setTimeout(function () {
            window.location.reload();
        }, 1000);
    });
    $('#btn-generate-pdf-objednavka-objekt').on("click", function () {
        var actionAttr = form.attr("action");
        actionAttr = actionAttr.replace(/(page=).*?(&)/,'$1' + "create-pdf-objednavka-objekt" + '$2');
        form.attr("action", actionAttr);
        form.submit();

        //reloadovat page hned po submitu formulare do target="_blank" buhvi proc nejde
        setTimeout(function () {
            window.location.reload();
        }, 1000);
    });
    checkOsobyCnt();
    $('.cb-osoba').click(function () {
        checkOsobyCnt();
    });
    $('#btn-add-email').click(function () {
        addEmail();
        return false;
    });
    $('input[name=rb-objekt]').click(function () {
        changeObjectEmail($(this));
    });
    $('#btn-send-pdf').click(function () {
        sendPdfEmails();
        return false;
    });

    //init wysiwyg
    makeWhizzyWig('cena-zahrnuje_', 'bullet indent outdent | undo redo | html');
});

/**
 * Zmeni email prave vybraneho objektu v sekci emaily
 * @param radioBtn vybrane radioBtn
 */
function changeObjectEmail(radioBtn) {
    if (radioBtn.is(':checked')) {
        var objektId = radioBtn.val();
        var objednavkaId = getParameterByName('id_objednavka');
        var securityCode = getParameterByName('security_code');
        $.ajax("vouchery_objednavka.php?page=ajax&action=get-obj-email&id_objednavka=" + objednavkaId + "&security_code=" + securityCode + "&id_objekt=" + objektId)
            .done(function (email) {
                var tblEmaily = $('#tbl-emaily');
                tblEmaily.find('tr:contains("objekt") td.email').html(email);
                tblEmaily.find('tr:contains("objekt") td input[type=checkbox]').val(email);
            });
    }
}

/**
 * Zkontroluje pocty osob u sluzeb a pokud je sluzba "neobsazena", zmeni pozadi sluzby
 */
function checkOsobyCnt() {
    var ownerIndex = $('#tbl-sluzby th:contains("poèet")').index() + 1;
    var pocetCol = $('#tbl-sluzby > tbody > tr > td:nth-child(' + ownerIndex + ')');
    var osobyTblBodys = $('#subtbl-osoby > tbody');
    for (var i = 0; i < pocetCol.length; i++) {
        var checkedPersonCnt = $(osobyTblBodys[i]).find('input[type=checkbox]:checked').length
        var pocetValue = $(pocetCol[i]).html();
        var tr = $(osobyTblBodys[i]).parent().parent().parent();
    }
}

/**
 * Posle pozadavek na server o odeslani voucheru na zaskrtle emaily
 */
function sendPdfEmails() {
    var objednavkaId = getParameterByName('id_objednavka');
    var securityCode = getParameterByName('security_code');
    var btnSendPdf = $("#btn-send-pdf");
    $("#email-status").html("");

    $.post("vouchery_objednavka.php?page=ajax&action=send-emails&id_objednavka=" + objednavkaId + "&security_code=" + securityCode, $("#form-send-pdf").serialize())
        .success(function (json) {
            json = JSON.parse(json);
            viewStatus(json);
        })
        .always(function () {
            btnSendPdf.removeClass("disabled");
        });
}