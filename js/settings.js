$(document).ready(function(){

        $.ajax(OC.generateUrl('apps/mail/settings'), {
            data:{},
            type:'GET',
            success:function (jsondata) {
                    var source   = $("#mail-settings-template").html();
                    var template = Handlebars.compile(source);
                    var html = template(jsondata);
                    $('#app-settings-content').html(html);
            },
            error: function() {
//					OC.msg.finishedAction('', '');
            }
        });

});
