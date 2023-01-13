// $.ajax({
//     type: "POST",
//     datatype: "JSON",
//     data: {
//         action : 'ts_save_reply',
//         security: tjx.nonce,
//
//     },
//     url: tjx.ajax_url,
//     success( response ) {
//
//         console.log(response);
//
//     }
// });

jQuery(document).ready(function ($) {
    if ($("#ticket-detail").length > 0) {
        $("#agent").select2();
    }

    if ($("#user-selector-top").length > 0) {
        $("#user-selector-top").select2();
    }

    if ($("#agent-selector-top").length > 0) {
        $("#agent-selector-top").select2();
    }
    if ($("#status-selector-top").length > 0) {
        $("#status-selector-top").select2();
    }
    if ($("#priority-selector-top").length > 0) {
        $("#priority-selector-top").select2();
    }
    if ($("select[name=m]").length > 0) {
        $("select[name=m]").select2();
    }

    if ($("#variables").length > 0) {
        $("#variables").on('change', function () {

            var currentText = $("textarea[name='description']").val();
            $("textarea[name='description']").val(currentText + $("#variables :selected").val());

        });
    }

    if ($("#saved-reply").length > 0) {
        $("#saved-reply").on('change', function () {
            $.ajax({
                method: "POST",

                url: ajaxurl,
                data: {term: $("#saved-reply :selected").val(), ticket: $("#post_ID").val(), action: "get_saved_reply"},
            }).done(function (reply) {
                tinymce.activeEditor.execCommand("mceInsertContent", false, reply);
            })
        })
    }
    $('.posts').attr('id', 'posts');
    var posts_table = document.getElementById('posts');
});