$(document).ready(function () {
    $('#btn-generuj-pdf').on("click", function () {
        $('#frm-platebni-doklad').submit();
        //reloadovat page hned po submitu formulare do target="_blank" buhvi proc nejde
        setTimeout(function () {
            window.location.reload();
        }, 1000);
        return false;
    });
    $('#btn-send-pdf').on("click", function() {
        sendPdfEmails();
        return false;
    });
    $('#btn-add-email').on("click", function() {
        addEmail();
        return false;
    });
});

/**
 * Posle pozadavek na server o odeslani faktur na zaskrtle emaily
 */
function sendPdfEmails() {
    var idObjednavky = getParameterByName('id_objednavka');
    var btnSendPdf = $("#btn-send-pdf");
    $("#email-status").html("");

    $.post("platebni_doklad.php?page=ajax&action=send-emails&id_objednavka=" + idObjednavky, $("#form-send-pdf").serialize())
        .success(function (response) {
            response = JSON.parse(response);
            viewStatus(response);
        })
        .always(function () {
            btnSendPdf.removeClass("disabled");
        });
}