
    function staging_retry_hubkog(id) {

        $.ajax({
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'staging_retryhubkog', // this is the function in your functions.php that will be triggered
                id: id
            },
            success: function (data) {
                //console.log(data);
                location.reload();
            }
        });
    }
