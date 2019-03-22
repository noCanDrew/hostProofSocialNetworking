function ui_displayAlert(message, closable, type){
    if(type == "loading") title = '<i class="fa fa-spinner fa-spin"></i> &nbsp;Loading';
    else if(type == "notice") title = '<i class="fa fa-exclamation" aria-hidden="true"></i> &nbsp;Notice';
    else title = '<i class="fa fa-lightbulb-o" aria-hidden="true"></i> &nbsp;Tips'

    list = '<ul class = "tips">';
    for(a = 0; a < message.length; a++){
        list += '<li class = "tip">' + message[a] + '</li>';
    }
    list += '</ul>';

    shade = "";
    closer = "";
    if(closable){
        shade = '<div id = "pageShade" class = "pageShade" onclick="ui_closeAlert()"></div>';
        closer = 
            '<div style = "float:right">' +
                '<a onclick="ui_closeAlert()">' +
                    '<i class="fa fa-times" aria-hidden="true"></i>' +
                '</a>' +
            '</div>';
    } else {
        shade = '<div id = "pageShade" class = "pageShade"></div>';
    }

    document.body.innerHTML += shade +
        '<div id = "pageAlert" class = "pageAlert">' +
            '<div class = "indexSectionContainerHeader">' +
                title + 
                closer + 
            '</div>' +
            list +
        '</div>'
    ;
}

function ui_closeAlert(){
    if(document.getElementById("pageShade")){
        elem = document.getElementById("pageShade");
        elem.parentNode.removeChild(elem);
    }
    if(document.getElementById("pageAlert")){
        elem = document.getElementById("pageAlert");
        elem.parentNode.removeChild(elem);
    }
}








signUpAlert = [
    'Username must be between 1 and 16 characters.',
    'Username may only contain alphanumerical characters.',
    'Password must be exactly 16 characters.',
    'Passwords should be sufficiently hard to guess. There are no requirments other than it is 16 characters in length, but it is highly recomended you use upper and lower case letters, numbers, and keyboard special characters.',
    'Note: There is no account reecovery because such a mechanism requires the loss of anonymity. So do not forget your login credentials!'
];

loginAlert = [
    'Username must be between 1 and 16 characters.',
    'Username may only contain alphanumerical characters.',
    'Password must be exactly 16 characters.',
];

makeNewInvitesAlert = [
    'This form is to be used in the creation of new chat rooms.',
    'To invite a user to this chat room, enter there user name in the "users" field.',
    'To invite multiple users to the same chat, simply seperate each of their names with a comma (,).',
    'Their is a max of 1000 characters, or 100 users.',
    'The "Chat name" can be between 1 and 32 characters.',
    'Once submitted and verified, each invite will be sent out. All users must respond with an "accept" or "decline" before any user will see the chat appear in their "Chats." This is to ensure cryptographic/security protocols are met.' 
];

chatInviteAlert = [
    'When invited by other users to join a chat, a message from them will appear here.',
    'To join the chat or decline the invite, simply click "accept" or "decline" respectively.',
    'If you accept, you may not see the new chat appear immediately in "Chats." This is because all invites to a given chat must be responded to by all users invited to said chat prior the the chat\'s ultimate creation.',
    'Note: It is important to maintain common curtacy and respond with an "accept" or "decline" relatively quickly because the chat\'s creation cannot be completed until all users invited have responded. This is to ensure the integrity of the cryptographic/security protocols in use by this messanger.'
];

chatsAlert = [
    'The chats you are a member of are listed below.',
    'You can enter any of the chat rooms by simply clicking on the chat\'s name.',
    'Some chat\'s you have accepted invites to may not appear here. This is because not every user invited to said chats has responded to their invites. Once they have, the given chats will appear below.'
];

chatAlert = [
    'To submit a message to chat, simply type your message in the "message" text field and then click the submit button (paper airplane icon).',
    'To use emotji, click the "emoji" button (smiley face icon) then select the emote you want.',
    'Note: messages are limited to 256 characters.',
    'To display an image in chat, paste the image url into the "message" text field then click submit as normal.',
    'Note: images must be from an "https" domain and must be one of the following types: png, jpg, jpeg, gif.' 
];
