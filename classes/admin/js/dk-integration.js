
    function retry_hubkog(id) {

        $.ajax({
            url: '/wp-admin/admin-ajax.php', // this is the object instantiated in wp_localize_script function
            type: 'POST',
            data: {
                action: 'retryhubkog', // this is the function in your functions.php that will be triggered
                id: id
            },
            success: function (data) {
                //console.log(data);
                location.reload();
            }
        });
    }
