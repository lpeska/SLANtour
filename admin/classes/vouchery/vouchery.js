$(document).ready(function () {
    $('#btn-generate-pdf-objednavka-objekt').on("click", function () {
        alert("asd");
    });
    var form = $('#form-generate-pdf');
    $('#btn-generate-pdf-voucher').on("click", function () {
        form.submit();
    });
    $('#btn-generate-pdf-objednavka-objekt').on("click", function () {
        alert("asd");
        var actionAttr = form.attr("action");
        actionAttr = actionAttr.replace(/(page=).*?(&)/,'$1' + "create-pdf-objednavka-objekt" + '$2');
        alert(actionAttr);
        form.attr("action", "actionAttr");return;
        form.submit();
    });
});