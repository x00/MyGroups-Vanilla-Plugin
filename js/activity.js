jQuery(document).ready(function($){
    var attach = gdn.definition('AttachFile', '');
    if (attach) {
        $('form.Activity .Buttons').prepend(attach);
    }
    
    $('form.Activity').on('clearCommentForm', function() {
        $('form.Activity .AttachFileWrapper').remove(); 
        if (attach) {
            $('form.Activity .Buttons').prepend(attach);
        }
        $('form.Activity .editor-upload-previews').children().remove();
    });
});
