<?php
/**
 * Plugin Name: CF7 Honeypot
 * Description: Adds honeypot fields to all Contact Form 7 forms.
 * Version: 1.0
 * Author: Sahil Ahlawat
 */

// Check if Contact Form 7 is installed and activated
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {

    // Adding input fields to all CF7 forms
    add_filter('wpcf7_form_elements', 'cf7_add_honeypot_fields');
    function cf7_add_honeypot_fields($html) {
    $options = get_option('cf7_honeypot_field_names');
    $other_field_name = empty($options['other']) ? 'other' : $options['other'];
    $custom_field_name = empty($options['custom']) ? 'custom' : $options['custom'];

    $other_field = '<input type="hidden" name="' . $other_field_name . '" value="" />';
    $custom_field = '<input type="hidden" name="' . $custom_field_name . '" value="tmp tech nounce" />';
    return $other_field . $custom_field . $html;
}

    // Verifying form submissions
    add_action('wpcf7_before_send_mail', 'cf7_honeypot_check');
    function cf7_honeypot_check($contact_form) {
    $options = get_option('cf7_honeypot_field_names');
    $other_field_name = empty($options['other']) ? 'other' : $options['other'];
    $custom_field_name = empty($options['custom']) ? 'custom' : $options['custom'];

    $submission = WPCF7_Submission::get_instance();
    $posted_data = $submission->get_posted_data();

    if (isset($posted_data[$other_field_name]) && isset($posted_data[$custom_field_name])) {
        if (empty($posted_data[$other_field_name]) && (trim($posted_data[$custom_field_name]) == 'tmp tech nounce')) {
            return;
        }
    }

    $response = array(
        'mailSent' => false,
        'into' => '#' . $contact_form->id(),
        'message' => 'Insecure form submission',
        'status' => 'validation_failed'
    );

    echo json_encode($response);
    die();
}

  add_action('admin_menu', 'cf7_honeypot_menu');
function cf7_honeypot_menu() {
    add_options_page(
        'CF7 Honeypot Settings',
        'CF7 Honeypot',
        'manage_options',
        'cf7-honeypot',
        'cf7_honeypot_settings_page'
    );
}

function cf7_honeypot_settings_page() {
    ?>
    <div class="wrap">
        <h1>CF7 Honeypot Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cf7_honeypot_settings');
            do_settings_sections('cf7_honeypot');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


  add_action('admin_init', 'cf7_honeypot_settings_init');
function cf7_honeypot_settings_init() {
    register_setting('cf7_honeypot_settings', 'cf7_honeypot_field_names');

    add_settings_section(
        'cf7_honeypot_section',
        'Field Names',
        '',
        'cf7_honeypot'
    );

    add_settings_field(
        'cf7_honeypot_field_other',
        'Other Field Name',
        'cf7_honeypot_field_other_callback',
        'cf7_honeypot',
        'cf7_honeypot_section'
    );

    add_settings_field(
        'cf7_honeypot_field_custom',
        'Custom Field Name',
        'cf7_honeypot_field_custom_callback',
        'cf7_honeypot',
        'cf7_honeypot_section'
    );
}

function cf7_honeypot_field_other_callback() {
    $options = get_option('cf7_honeypot_field_names');
    echo "<input name='cf7_honeypot_field_names[other]' type='text' value='{$options['other']}' />";
}

function cf7_honeypot_field_custom_callback() {
    $options = get_option('cf7_honeypot_field_names');
    echo "<input name='cf7_honeypot_field_names[custom]' type='text' value='{$options['custom']}' />";
}

?>
