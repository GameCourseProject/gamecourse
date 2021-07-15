function openModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.show();
    $(id + " input:text," + id + " textarea").first().focus();

}

function closeModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.hide();
}

// function for add_part modal in view editor
function resetModal(element) {
    closeModal(element);
    $('.part_option.focus').removeClass('focus');
    $('#template_selection').hide();
    $('button:contains("Add Item")').prop('disabled', true);
}

function removeModal(element) {
    id = element.getAttribute('value');
    modal = $(id);
    modal.remove();
}

function giveMessage(message) {
    modal = $("<div class='modal' id='message-api-box'></div>");
    verification = $("<div class='verification modal_content'></div>");
    verification.append($('<button class="close_btn icon" value="#message-api-box" onclick="removeModal(this)"></button>'));
    verification.append($('<div class="message_warning warning">' + message + '</div>'));
    verification.append($('<div class="confirmation_btns"><button value="#message-api-box" onclick="removeModal(this)">Confirm</button></div>'))
    modal.append(verification);
    $("body").append(modal);
    modal.show();
}

function alertUpdate(data, err) {
    if (err) {
        giveMessage(err.description);
        return;
    }
    if (data && Object.keys(data.updatedData).length > 0) {
        var output = "";
        for (var i in data.updatedData) {
            output += data.updatedData[i] + '\n';
        }
        giveMessage(output);
    }
    location.reload();

}