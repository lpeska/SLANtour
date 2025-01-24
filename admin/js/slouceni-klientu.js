//onpage load init
var selected_001 = false, selected_002 = false, selected_003 = false;
$(document).ready(function () {
    //init check all/uncheck all checkboxes
    $('#select_all_001').click(function () {
        selected_001 = !selected_001;
        $('.cb_001').prop('checked', selected_001);
    });
    $('#select_all_002').click(function () {
        selected_002 = !selected_002;
        $('.cb_002').prop('checked', selected_002);
    });
    $('#select_all_003').click(function () {
        selected_003 = !selected_003;
        $('.cb_003').prop('checked', selected_003);
    });
    $("#delete-001").click(confirmDelete);
    $("#delete-002").click(confirmDelete);
    $("#delete-003").click(confirmDelete);
    $("#merge-001").click(confirmMerge);
})

function confirmDelete() {
    return confirm("Opravdu smazat?");
}

function confirmMerge() {
    return confirm("Opravdu slouèit všechny klienty?");
}

/**
 * Rozbalovaci menu idecek
 */
function showSibling(el) {
    var sibling = $(el).siblings()[0];
    $(sibling).toggle();
    if ($(sibling).css("display") == "none")
        $(el).html("+ rozbalit");
    else
        $(el).html("- zabalit");

    return false;
}

/**
 * Posun klienty ke slouceni na pozici kotvy
 */
function moveClientsToMerge(id) {
    var idToMoveTo = "tr_" + id;
    var eCToMerge = $('#clients-to-merge');
    var top = $("#" + idToMoveTo).offset().top - eCToMerge.offset().top;
    eCToMerge.css('margin-top', top + 'px');

    return false;
}

/**
 * Ajax call na smazani klienta na serveru s potvrzenim a odpovedi, pokud se klient nepodaril smazat
 * @param el element ktery event spustil
 * @param id identifikator klienta ke smazani
 */
function removeClientAjax(el, id) {
    if(!confirm("Opravdu smazat klienta?"))
        return;
    $.ajax("slouceni_klientu.php?page=ajax&action=delete-client&id=" + id)
        .done(function (response) {
            var toDel = el.parentNode;
            toDel.parentNode.removeChild(toDel);
        })
        .fail(function () {
            alert("Klienta se nepodaøilo smazat");
        });

    return false;
}