jQuery(document).ready(function($) {
    function toggleEnvironmentFields() {
        var environment = $('#woocommerce_paysquad_environment').val();
        if (environment === 'sandbox') {
            $('.paysquad-sandbox-field').closest('tr').show();
            $('.paysquad-production-field').closest('tr').hide();
        } else {
            $('.paysquad-sandbox-field').closest('tr').hide();
            $('.paysquad-production-field').closest('tr').show();
        }
    }

    // Initially hide all fields
    $('.paysquad-sandbox-field').closest('tr').hide();
    $('.paysquad-production-field').closest('tr').hide();

    toggleEnvironmentFields();

    $('#woocommerce_paysquad_environment').change(function() {
        toggleEnvironmentFields();
    });
});
