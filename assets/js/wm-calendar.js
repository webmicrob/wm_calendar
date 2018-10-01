jQuery(document).ready(function($) {

    $(".ajaxCbox").click(function(e){
        e.preventDefault();
        var data = {
            'action': 'wmc_load_events',
            'wmc_date': $(this).data('date')
        };
        $.post(wmc_vars.ajaxurl, data, function (response) {
            console.log(response);

            $.colorbox({html:response});
        }, 'json');
        return false;
    });



});

/*
http://wesbos.com/template-strings-html/
const markup = '<ul class="dogs">
${dogs.map(dog => <li>${dog.name} is ${dog.age * 7}</li>`).join('')}
</ul>';
*/
