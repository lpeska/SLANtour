$(document).ready(function () {
    submitPdfAction();
});

function submitPdfAction() {
    $('#btn-zoprazit-fp-pdf').on('click', function (e) {
        e.preventDefault();
        var form = $('#form-filter');
        var defAction = form.attr('action');
        form.attr('action', $(this).attr('href'));
        form.attr('target', '_blank');
        form.submit();
        form.attr('target', '');
        form.attr('action', defAction);
    })
}