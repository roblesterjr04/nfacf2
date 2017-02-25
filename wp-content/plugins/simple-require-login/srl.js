jQuery(document).ready(function($){
    $('.srl-role-set').hide();
    if($('#srl-yesno').val() == 'Yes') $('.srl-role-set').show();
    $('#srl-yesno').change(function(){
        $('.srl-role-set').hide();
        if($(this).val() == 'Yes') {
            $('.srl-role-set').show();
        }
    });
});
