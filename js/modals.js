
function openModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.show();
}

function closeModal(element){
    id = element.getAttribute('value');
    modal = $(id);
    modal.hide();
}