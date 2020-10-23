
function openModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.show();
    $( id + " input:text,"+ id +" textarea").first().focus();
}

function closeModal(element){
    id = element.getAttribute('value');
    modal = $(id);
    modal.hide();
}

function removeModal(element){
    id = element.getAttribute('value');
    modal = $(id);
    modal.remove();
}

function giveError(message){
    modal = $("<div class='modal' id='error-api-box'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append( $('<button class="close_btn icon" value="#error-api-box" onclick="removeModal(this)"></button>'));
    verification.append( $('<div class="error_warning warning">'+ message +'</div>'));
    verification.append( $('<div class="confirmation_btns"><button value="#error-api-box" onclick="removeModal(this)">Confirm</button></div>'))
    modal.append(verification);
    $("body").append(modal);
    modal.show();
}