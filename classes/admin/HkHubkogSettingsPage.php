<?php
namespace StagingHubkog\admin;
use GFAPI;

class HkHubkogSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $settings;
    //public $product_interest_terms;
    public $advanced_options_array;
    public $grouped_product_interest;
    private $fields;

    /**
     * ADDING PLUGIN PAGE
     * ADDING PLUGIN OPTIONS PAGE
     * INITIALISING PRODUCT INTEREST/NAMES, CURRENTLY NOT DYNAMIC
     */
    public function __construct($params = null)
    {
        $options = get_option('staging_hubkog_options');
        $this->grouped_product_interest = isset($options['grouped_product_interest']) ? $options['grouped_product_interest'] : '';

        if( $params['activate'] === true ){

            if(isset($params['settings'])){
                $this->settings = $params['settings'];
            }

            if(isset($params['grouped_product_interest'])){
                $this->grouped_product_interest = $params['grouped_product_interest'];
            }

            if( is_admin() ){
                add_action('admin_menu', array($this, 'add_plugin_page'));
                add_action('admin_init', array($this, 'page_init'));
            }

        }

    }


    /**
     * ADDING PLUGIN PAGE UNDER SETTINGS ON WP
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            $this->settings['menu']['page_title'],
            $this->settings['menu']['menu_title'],
            $this->settings['menu']['capability'],
            $this->settings['menu']['menu_slug'],
            array($this, 'create_admin_page')
        );
    }

    /**
     * SETTINGS -> DK SETTINGS ADMIN PAGE SET UP
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option($this->settings['page']['option_name']);
        ?>
        <div class="wrap">
            <h1><?php echo $this->settings['page']['h1']; ?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields($this->settings['page']['option_group_name']);
                do_settings_sections( $this->settings['page']['page'] );
                submit_button();
                self::dk_service_provider_message();
                self::custom_logs_display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * REGISTERING PLUGIN ADMIN PAGE SETTINGS TO WP
     * FIELDS FOR API DETAILS
     * GRAVITY FORMS LISTED WITH CHECKBOXES, THIS IS ON WHICH FORM PLUGIN NEEDS RUNNING
     * CUSTOM PRODUCT NAMES HARD CODED IN CONSTRUCTOR
     * CUSTOM FORM NAMES TO GO WITH QUOTETYPE
     */
    public function page_init()
    {
        register_setting(
            $this->settings['page']['option_group_name'], // Option group
            $this->settings['page']['option_name'], // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            $this->settings['page']['section_id'], // ID
            $this->settings['page']['title'], // Title
            array($this, 'print_section_info'), // Callback
            $this->settings['page']['page'] // Page
        );

        foreach ($this->settings['fields'] as $field){
            add_settings_field(
                $field['id'],
                $field['title'],
                array($this, $field['callback']),
                $this->settings['page']['page'],
                $this->settings['page']['section_id']
            );
        }

    }

    /**
     * SANITISING INPUT WHEN SAVING THE PLUGIN OPTIONS PAGE
     * TO CHECK AND FILTER DATA BEFORE STORING
     * TO DEBUG PICK ONE OF THE VARIABLE E.G. $input['api_key'] AND PRINT IT IN LOG FILE
     * @param $input
     * @return array
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['api_url']))
            $new_input['api_url'] = sanitize_text_field($input['api_url']);

        if (isset($input['api_key']))
            $new_input['api_key'] = sanitize_text_field($input['api_key']);

        if (isset($input['grouped_product_interest']))
            $new_input['grouped_product_interest'] = $input['grouped_product_interest'];


        if (self::gravityform_plugin_status()) {
            $forms = GFAPI::get_forms();

            /*GRAVITY FORMS CHECKBOX*/
            foreach ($forms as $form) {
                $option_key = 'staging_hubkog_gravity_form_' . $form['id'];
                if (isset($input[$option_key])) {
                    $new_input[$option_key] = $input[$option_key] ? 1 : 0;
                }
            }

            /*QUOTE TYPES: DEFAULT STRING TO PASS BASED ON FORMS*/
            foreach ($forms as $form) {
                $form_title = $form['title'];
                $sanitised_title = preg_replace('/ /i', '_', strtolower($form_title));
                if (isset($input['forms_quote_types'][$sanitised_title])) {
                    $new_input['forms_quote_types'][$sanitised_title] = $input['forms_quote_types'][$sanitised_title];
                }
            }
        }

        return $new_input;
    }

    /**
     * ADDITIONAL MESSAGE SECTION ON THE PLUGIN OPTIONS PAGE
     */
    public function print_section_info()
    {
        print $this->settings['page']['section_info'];
    }

    /**
     * API URL CALLBACK FUNCTION
     * TO PRINT AND SUPPLY VALUE STORED IN DB
     */
    public function api_url_callback()
    {
        printf(
            '<input type="text" id="api_url" name="'.$this->settings['page']['option_name'].'[api_url]" value="%s" class="dk_text_field"/>',
            isset($this->options['api_url']) ? esc_attr($this->options['api_url']) : ''
        );
    }

    /**
     * API KEY CALLBACK FUNCTION
     * TO PRINT AND SUPPLY VALUE STORED IN DB
     */
    public function api_key_callback()
    {
        printf(
            '<input type="password" id="api_key" name="'.$this->settings['page']['option_name'].'[api_key]" value="%s" class="dk_text_field"/>',
            isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : ''
        );
    }

    /**
     * CALLBACK FN TO PROVIDE LIST OF GRAVITY FORM
     * USERS CAN CHOOSE FORMS ON WHICH THE DATA IS SUBMITTED TO THIRD PARTY LIKE AB INITIO USING API
     * THIS OCCURS WHEN SUBMITTING FORM DATA
     */
    public function select_forms_callback()
    {
        if (self::gravityform_plugin_status()) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $fields = [];
                foreach($form['fields'] as $field){
                    $fields[] = [
                                    'id'=>$field['id'],
                                    'name'=>$field['label']
                                ];
                }
                $this->fields[$form['id']] = $fields;
                $option_key = 'staging_hubkog_gravity_form_' . $form['id'];
                printf(
                    '<input type="checkbox" id="form-list" name="'.$this->settings['page']['option_name'].'[%s]" value="1" %s /><label>%s</label>',
                    $option_key,
                    isset($this->options[$option_key]) ? 'checked' : '',
                    $form['title'] . ' (id:' . $form['id'] . ')'
                );
                echo '<br>';
            }
        }
    }

    /**
     * THIS IS A HARD CODED LIST SEE CONSTRUCTOR
     * [product_interest_terms][] CALLBACK FUNCTION
     * TO PRINT AND SUPPLY VALUE STORED IN DB
     */
    public function product_interest_terms_callback_b()
    {
        if (self::gravityform_plugin_status()) {
            foreach ($this->product_interest_terms as $term) {
                $lower_case_term = strtolower($term);
                printf(
                    '<label>' . $term . ': </label><input type="text" id="product_interest_terms" name="%s" value="%s"/><br>',
                    $this->settings['page']['option_name']."[product_interest_terms][" . $lower_case_term . "]",
                    isset($this->options['product_interest_terms'][$lower_case_term]) && !empty($this->options['product_interest_terms'][$lower_case_term]) ?
                        esc_attr($this->options['product_interest_terms'][$lower_case_term]) :
                        $term
                );
            }
        }
    }

    public function product_interest_terms_callback()
    {
        if (self::gravityform_plugin_status()) {
            printf(
                '<textarea type="text" id="grouped_product_interest" name="%s" rows="10" cols="100"/>%s</textarea><br /><label style="text-color:grey;">1 product per line</label>',
                $this->settings['page']['option_name'].'[grouped_product_interest]',
                isset($this->options['grouped_product_interest'])?$this->options['grouped_product_interest']:''
            );

        }
    }

    /**
     * [forms_quote_types][] CALLBACK FUNCTION
     * [forms_quote_types][] QUOTE TYPE OR CUSTOM FORM NAMES
     * THIS IS SELECTED AS DEFAULT FORM NAMES
     * USERS CAN MODIFY THEM IN SETTINGS -> DK SETTINGS
     * TO PRINT AND SUPPLY VALUE STORED IN DB
     */
    /*public function forms_quote_types_callback()
    {
        if (self::gravityform_plugin_status()) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $form_title = $form['title'];
                $option_key = 'staging_hubkog_gravity_form_' . $form['id'];
                $sanitised_title = preg_replace('/ /i', '_', strtolower($form_title));
                if (isset($this->options[$option_key])) {
                    printf(
                        '<label>' . $form_title . ': </label><input type="text" id="forms_quote_types" name="%s" value="%s"/><br>',
                        $this->settings['page']['option_name']."[forms_quote_types][" . $sanitised_title . "]",
                        isset($this->options['forms_quote_types'][$sanitised_title]) && !empty($this->options['forms_quote_types'][$sanitised_title]) ?
                            esc_attr($this->options['forms_quote_types'][$sanitised_title]) :
                            $form_title
                    );
                }
            }
        }
    }*/

    /**
     * ADVANCED OPTIONS FOR THE PLUGIN
     * THESE OPTIONS CAN BE ENABLED AND DISABLED USING CHECKBOX AGAINST THEM SEE DK SETTINGS -> ADVANCED OPTIONS
     * CURRENTLY IT HAS APPOINTMENT DETAILS TO NOTES AND SEPARATE HOUSE NO FEATURE
     * MORE OPTIONS CAN BE ADDED HERE
     */
    public function advanced_options_callback()
    {
        foreach ($this->advanced_options_array as $option_key => $option_value) {
            printf(
                '<input type="checkbox" id="advanced_options" name="'.$this->settings['page']['option_name'].'[advanced_options][%s]" value="1" %s /><label>%s</label>',
                $option_key,
                isset($this->options['advanced_options'][$option_key]) ? 'checked' : '',
                $option_value
            );
            printf("<br>");
        }
    }

    /**
     * PRINTING CUSTOM VARIABLES IN LOG FILE
     * @param $message
     */
    public static function custom_logs($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $file = fopen(plugin_dir_path(__FILE__) . "staging_hubkog_custom_logs.log", "w");
        fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message);
        fclose($file);
    }

    /**
     * SHOWING LOG OUTPUT ON THE PLUGIN SETTINGS PAGE
     * NOT WORKING CURRENTLY (PROBABLY FILE PERMISSION)
     */
    public function custom_logs_display()
    {
        $plugin_dir = plugin_dir_path(__FILE__);
        $custom_log_path = $plugin_dir . '/staging_hubkog_custom_logs.log';
        if (file_exists($custom_log_path)) {
            echo '<br><br>';
            echo '&nbsp;&nbsp;<a class="dk-log__toggle" target="_blank" href="#" title="">View Log</a>';
            $custom_log_content = file_get_contents($custom_log_path);
            echo '<div class="dk-log__view" style="max-width: 800px;width:100%;display:none;background: white;color: black;overflow: scroll;margin: 1rem;padding:1rem;border-radius: 5px;max-height: 500px;
    overflow: scroll;">';
            echo '<pre>';
            echo $custom_log_content;
            echo '</pre>';
            echo '</div>';
        }
    }

    /**
     * CHECKING GRAVITY FORM ACTIVE STATUS
     * @return bool
     */
    public function gravityform_plugin_status(): bool
    {
        if (is_plugin_active('gravityforms/gravityforms.php')) {
            return true;
        }
        return false;
    }

    /**
     * PRINTING SERVICE PROVIDER MESSAGE ON THE DK SETTINGS PAGE
     */
    private function dk_service_provider_message()
    {
        printf("<br>");
        printf("To add more options, contact <a href='https://digitalkog.co.uk/contact/' title='' target='_blank'>DigitalKOG</a>");
    }

}
