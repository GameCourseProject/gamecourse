
function openModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.show();
    debugger
    $( id + " input:text,"+ id +" textarea").first().focus();
}

function closeModal(element){
    id = element.getAttribute('value');
    modal = $(id);
    modal.hide();
}