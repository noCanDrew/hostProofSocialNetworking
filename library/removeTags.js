// stackoverflow.com/questions/295566/sanitize-rewrite-html-on-the-client-side
// Removes tags from text that mnay contain HTML.
// Used as a post process for text display client side. Once client side has decrypted 
// text from server, the text is then stripped of all tags so that malicious injections 
// are prevented prior to text laoding on page.
var tagBody = '(?:[^"\'>]|"[^"]*"|\'[^\']*\')*';
var tagOrComment = new RegExp(
    '<(?:'
    // Comment body.
    + '!--(?:(?:-*[^->])*--+|-?)'
    // Special "raw text" elements whose content should be elided.
    + '|script\\b' + tagBody + '>[\\s\\S]*?</script\\s*'
    + '|style\\b' + tagBody + '>[\\s\\S]*?</style\\s*'
    // Regular name
    + '|/?[a-z]'
    + tagBody
    + ')>',
    'gi'
);

function removeTags(html){
    var oldHtml;
    do{
        oldHtml = html;
        html = html.replace(tagOrComment, '');
    } while (html !== oldHtml);
    return html.replace(/</g, '&lt;');
    return html;
}