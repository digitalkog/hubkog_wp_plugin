<?php
namespace HkHubkog;
use HkHubkog\admin\HkHubkogSettingsPage;

class HkHubkogIntegrationCore
{

    private $api_url;
    private $hubkog_options;
    private $separate_house_no;
    private $appointment_to_notes;
    private $settings;
    private $form_type;
    private $used_fields;
    private $raw_products = [];


    public function __construct($params = null)
    {
        register_activation_hook( HK_PLUGIN_FILE, array( $this, 'create_database_table') );

        if(!empty($params)){
            $this->settings = $params;
        }

        if(!empty($params['SettingsPage'])){
            new admin\HkHubkogSettingsPage($params['SettingsPage']);
        }

        if(!empty($params['ResultsPage'])){
            new admin\HubkogResultsPage($params['ResultsPage']);
        }

        self::init();
    }

    public function init(){

        add_action( 'admin_enqueue_scripts', array( $this, 'hk_custom_scripts') );
        add_action( 'wp_ajax_retryhubkog', array( $this, 'retry_hubkog') );
        add_action( "wp_ajax_nopriv_retryhubkog", array( $this, 'retry_hubkog') );
        add_filter( 'cron_schedules', array( $this, 'hk_add_cron_interval') );
        add_action( 'hk_cron_hook', array( $this, 'hubkog_cron_exec') );

        if ( ! wp_next_scheduled( 'hk_cron_hook' ) ) {
            wp_schedule_event( time(), 'everyminute', 'hk_cron_hook' );
        }

        if( is_plugin_active('gravityforms/gravityforms.php') ) {
            add_action( 'gform_after_submission', array( $this, 'post_to_third_party'), 10, 2 );
            //stuart: this is currently empty
            $this->hubkog_options = get_option('hk_hubkog_options');
            $this->api_url = $this->hubkog_options['api_url'];
            add_filter(
                'plugin_action_links_hubkog-integration/hubkog-integration.php',
                array( $this, 'hubkog_integration_settings_link' )
            );
        }
    }

    function hk_add_cron_interval( $schedules ) {
        $schedules['everyminute'] = array(
            'interval'  => 60, // time in seconds
            'display'   => 'Every Minute'
        );
        return $schedules;
    }

    public function create_database_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubkog';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix ."hubkog` (
          `id` int NOT NULL AUTO_INCREMENT,
          `data` text,
          `hubkog_uid` varchar(25) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB $charset_collate";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function get_lead_source_info($entry_id = null) {
    global $wpdb;

//die("EID: " . $entry_id);
        if(!is_numeric($entry_id)) return false;

        $sql = "SELECT entry_id, meta_key, meta_value, item_index FROM " . $wpdb->prefix . "gf_entry_meta
                WHERE entry_id IN(" . $entry_id . ")
                AND ( meta_key IN (
					'_afl_wc_utm_cookie_consent',
					'_afl_wc_utm_cookie_expiry',
					'_afl_wc_utm_conversion_type',
					'_afl_wc_utm_conversion_lag_human',
					'_afl_wc_utm_conversion_lag',
					'_afl_wc_utm_conversion_ts',
					'_afl_wc_utm_conversion_date_utc',
					'_afl_wc_utm_conversion_date_local',
					'_afl_wc_utm_sess_visit',
					'_afl_wc_utm_sess_visit_date_utc',
					'_afl_wc_utm_sess_visit_date_local',
					'_afl_wc_utm_sess_landing',
					'_afl_wc_utm_sess_landing_clean',
					'_afl_wc_utm_sess_referer',
					'_afl_wc_utm_sess_referer_clean',
					'_afl_wc_utm_sess_ga',
					'_afl_wc_utm_utm_1st_url',
					'_afl_wc_utm_utm_1st_url_clean',
					'_afl_wc_utm_utm_1st_visit',
					'_afl_wc_utm_utm_1st_visit_date_utc',
					'_afl_wc_utm_utm_1st_visit_date_local',
					'_afl_wc_utm_utm_source_1st',
					'_afl_wc_utm_utm_medium_1st',
					'_afl_wc_utm_utm_campaign_1st',
					'_afl_wc_utm_utm_term_1st',
					'_afl_wc_utm_utm_content_1st',
					'_afl_wc_utm_utm_url',
					'_afl_wc_utm_utm_url_clean',
					'_afl_wc_utm_utm_visit',
					'_afl_wc_utm_utm_visit_date_utc',
					'_afl_wc_utm_utm_visit_date_local',
					'_afl_wc_utm_utm_source',
					'_afl_wc_utm_utm_medium',
					'_afl_wc_utm_utm_campaign',
					'_afl_wc_utm_utm_term',
					'_afl_wc_utm_utm_content',
					'_afl_wc_utm_gclid_url',
					'_afl_wc_utm_gclid_url_clean',
					'_afl_wc_utm_gclid_visit',
					'_afl_wc_utm_gclid_visit_date_utc',
					'_afl_wc_utm_gclid_visit_date_local',
					'_afl_wc_utm_gclid_value',
					'_afl_wc_utm_fbclid_url',
					'_afl_wc_utm_fbclid_url_clean',
					'_afl_wc_utm_fbclid_visit',
					'_afl_wc_utm_fbclid_visit_date_utc',
					'_afl_wc_utm_fbclid_visit_date_local',
					'_afl_wc_utm_fbclid_value',
					'_afl_wc_utm_msclkid_url',
					'_afl_wc_utm_msclkid_url_clean',
					'_afl_wc_utm_msclkid_visit',
					'_afl_wc_utm_msclkid_visit_date_utc',
					'_afl_wc_utm_msclkid_visit_date_local',
					'_afl_wc_utm_msclkid_value',
					'_afl_wc_utm_created_by') )";

        $results = $wpdb->get_results($sql);


        if(!is_null($results)){
		return $results;
        }
	return null;
    }


    /**
     * ADDING CSS & JS TO THE PLUGIN IN ADMIN AREA
     */
    public function hk_custom_scripts()
    {
        wp_enqueue_script( 'dk_integration_js', plugins_url( 'admin/js/dk-integration.js', __FILE__), array(), '1.0', true );
        wp_enqueue_style( 'dk_integration_css', plugins_url( 'admin/css/dk-integration.css', __FILE__), array(), '1.0', true );
    }

    /**
     * A FUNCTION TO WRITE CUSTOM VARIABLES IN THE LOG FILE
     * @param $message
     * @param string $hint
     * @param string $writing_option
     */
    public static function custom_logs($message, $hint = '', $writing_option = 'w')
    {
        $message = json_encode($message);
        if( !is_file( plugin_dir_path( __FILE__ ) . "/custom_logs.log") &&
            !file_exists(plugin_dir_path( __FILE__ ) . "/custom_logs.log") )
        {
            shell_exec("touch " . plugin_dir_path( __FILE__ ) . "/custom_logs.log" );
        }
        $file = fopen( plugin_dir_path( __FILE__ ) . "../includes/custom_logs.log",$writing_option);
        if( !empty( $hint ) ) {
            fwrite($file, "\n" . date('d-m-Y h:i:s') . " :: #####################" . $hint . '#####################');
        }
        if( is_array($message) && count($message) > 0 ) {
            fwrite($file, "\n" . date('d-m-Y h:i:s') . " :: " . print_r($message, TRUE));
        } else {
            fwrite($file, "\n" . date('d-m-Y h:i:s') . " :: " . $message);
        }
        fclose($file);
    }

    /**
     * FUNCTION TO FETCH FILTERED DATA AND POST THEM USING GIVEN API DETAILS
     * @param $entry
     * @param $form
     */
    public function post_to_third_party( $entry, $form ) {
        global $wpdb;
        //stuart - put condition back in before live:
        if( empty($this->hubkog_options['hk_gravity_form_' . $form['id']]) && !empty($this->api_url) ) {

            $data = self::format_for_hubkog($entry, $form);

            $wpdb->insert($wpdb->prefix . 'hubkog', array('data'=> json_encode($data), 'created_at' => current_time('mysql', true)));

            $last_insert = $wpdb->insert_id;

            $curl = curl_init();

            $option_array = array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $this->hubkog_options['api_key']
                ),
                CURLOPT_POSTFIELDS => http_build_query($data)
            );

            curl_setopt_array($curl, $option_array);

            $response = curl_exec($curl);

            $data = json_decode($response, true);

            if(isset($data['data']) && !empty($data['data']['uid'])){
                $wpdb->update($wpdb->prefix . 'hubkog', array('hubkog_uid' => $data['data']['uid'], 'updated_at' => current_time('mysql', true)), array('id' => $last_insert));
            }

            if (!$data['success']) {
                self::custom_logs("Hubkog submission failed");
                self::custom_logs(json_encode($data));
            }

            /*if(isset($data['data']) && !empty($data['data']['uid'])){
                $wpdb->update($wpdb->prefix . 'hubkog', array('hubkog_uid' => $data['data']['uid']), array('id' => $last_insert));
            }

            if (!$data['success']) {
                self::custom_logs("Hubkog submission failed");
                self::custom_logs(json_encode($data));
            }*/

        }

    }

    public function hubkog_cron_exec(){
        global $wpdb;

        $result = $wpdb->get_results (
            "SELECT * FROM  " . $wpdb->prefix . "hubkog WHERE hubkog_uid IS NULL AND created_at <= (now()  - INTERVAL 5 MINUTE)" );
            //"SELECT * FROM  " . $wpdb->prefix . "hubkog WHERE hubkog_uid IS NULL" );


        foreach($result as $r){
            $this->retry_post_to_third_party($r->id);
        }
    }

    public static function __callStatic(string $name, array $arguments)
    {
        // TODO: Implement __callStatic() method.
    }

    public function retry_hubkog(){
            //die(json_encode(array('tester' => false)));

        if(!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])){
            die(json_encode(array('success' => false)));
        }
        $result = $this->retry_post_to_third_party($_REQUEST['id']);
        if($result === true){
            die(json_encode(array('success' => true)));
        }


        die(json_encode(array('success' => false,'data'=> $result )));
    }
    public function retry_post_to_third_party ( $entry_id ) {
//die("ID: " . $entry_id);
        global $wpdb;
            $result = $wpdb->get_row (
                "
                    SELECT * 
                    FROM  " . $wpdb->prefix . "hubkog
                        WHERE id = $entry_id AND hubkog_uid IS NULL
                " );


            $data = unserialize($result->data);
            if($data === false){
                $data = json_decode($result->data, true);
            }
//print_r($data);
//die();
if(!is_array($data)){
return;
}
            $curl = curl_init();

            $option_array = array(
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer " . $this->hubkog_options['api_key']
                ),
                CURLOPT_POSTFIELDS => http_build_query($data)
            );

            curl_setopt_array($curl, $option_array);

            $response = curl_exec($curl);

            $data = json_decode($response, true);

            //die($response);

            /*if (!$data['success']) {
                self::custom_logs("Hubkog retry failed: " . $result->id);
                return $data;
            } else {
                $wpdb->update($wpdb->prefix . 'hubkog', array('hubkog_uid' => $data['data']['uid']), array('id' => $result->id));
                return true;
            }*/

            if(isset($data['data']) && !empty($data['data']['uid'])){
                $wpdb->update($wpdb->prefix . 'hubkog', array('hubkog_uid' => $data['data']['uid'], 'updated_at' => current_time('mysql', true)), array('id' => $result->id));
            }

            if (!$data['success']) {
                self::custom_logs("Hubkog submission failed");
                self::custom_logs(json_encode($data));
                return false;
            }

            return true;
    }

    /**
     * SUPPLYING FORM ELEMENTS AS REQUESTED
     * SOME DATA NEEDS FORMATTING LIKE PRODUCTS SELECTED WHILE ENQUIRING
     * @param $entry
     * @param $form
     * @param $look_for
     * @return array|bool|mixed
     */
    private function get_form_element($entry, $form, $look_for, $required_field_type = '') {
       /* echo "<pre>";
        print_r($entry);*/
        foreach($form['fields'] as $fieldObj) {
            $field_type = $fieldObj->type;
            if ( preg_match('/Enquiry type/i', $look_for) &&
                self::verify_field_match($fieldObj, $look_for, $required_field_type)
            ) {
                $output = [];
                for($i=0;$i<15;$i++) {
                    $array_value = (string) $fieldObj->id . '.' . $i;
                    if( isset($entry[$array_value]) ) {
                        $this->used_fields[] = $fieldObj->id . '.' . $i;
                        $output[] = $entry[$array_value];
                    }
                }
                return array_filter($output);
            }
            if (preg_match('/street/i', $look_for) &&
                $fieldObj->label === 'Address' )
            {
                $this->used_fields[] = $fieldObj->id;
                return trim($entry[$fieldObj->id]);
            }
            if ( preg_match('/products/i', $look_for) &&
                !empty(self::check_for_product_interest_field($fieldObj->label)) &&
                in_array($fieldObj->type, ['hidden', 'checkbox'])
            ) {
                //print_r("Is Products 2");
                return array_filter( self::get_product_interest($entry, $fieldObj) );
            }
            if ( self::verify_field_match($fieldObj, $look_for, $required_field_type) )
            {
                if( preg_match('/date/i', $look_for) ) {
                    $originalDate = trim($entry[$fieldObj->id]);
                    return date("d-m-Y", strtotime($originalDate));
                } else {
                    $this->used_fields[] = $fieldObj->id;
		    if($look_for != "opt in"){
	                    return trim($entry[$fieldObj->id]);
		
		    }
                }
            }
            if(preg_match('/opt in/i', $look_for) &&
                in_array($fieldObj->type, ['hidden', 'checkbox'])
            && ($fieldObj->label === 'Opt in flag' 
		|| $fieldObj->label ==="Opt in/out status")){

		if($fieldObj->label ==="Opt in/out status")
		{
                $this->used_fields[] = $fieldObj->id;
			if(strpos($entry[$fieldObj->id.".1"], "happy") == 0){
				return 0;
			} else {
				return 1;
			}
                /*print_r("OPT FLAG CHECK: " . $entry[$fieldObj->id]);
		print_r($fieldObj->id);
		print_r($entry);*/

		} else {
                	$this->used_fields[] = $fieldObj->id;
                	return trim($entry[$fieldObj->id]);
		}
            }

            unset($field_type);
        }
        return false;
    }

    /**
     * GETTING ALL PRODUCTS FROM THE FORM SUBMITTED
     * FILTERING THE DATA SO WE CAN RETURN IT INTO AN ARRAY FOR PRODUCTINTEREST1 AND PRODUCTINTEREST2 VARIABLE
     * @param $entry
     * @param $fieldObj
     * @return array|bool
     */
    private function get_product_interest($entry, $fieldObj) {

        if ($fieldObj->type === 'hidden') {
            //echo "\n"."HIDDEN"."\n";
            $return = [];
                $products = explode(",", $entry[$fieldObj->id]);
                foreach($products as $product){
                    $product = trim($product);
                    if(!empty($product)){
                        $this->raw_products[] = $product;
                    }
                    $return[] = self::check_and_return_product_ab_term($product);
                }

            return $return;
        }
        elseif ($fieldObj->type === 'checkbox') {
            //echo "\n"."CHECKBOX"."\n";
            $return = [];
            for($i=0;$i<100;$i++) {
                if( !empty($entry[$fieldObj->id . '.' .$i]) ) {
                    $this->used_fields[] = $fieldObj->id . '.' . $i;
                    $product = trim($entry[$fieldObj->id . '.' .$i]);
                    if(!empty($product)){
                        $this->raw_products[] = $product;
                    }
                    $return[] = self::check_and_return_product_ab_term($product);
                }
            }
            return $return;
        }
        return false;
    }

    /**
     * CHECKING IF THE LABEL BELONGS TO THE PRODUCTS FIELD LIKE CONSERVATORY, ORANGERY ETC
     * ADD NEW TITLE IF NEW PRODUCT SECTION/FORM ADDED
     * @param $label
     * @return bool
     */
    private function check_for_product_interest_field($label, $type = 'products') {
        if( $type === 'Enquiry type' ) {
            if (preg_match('/Enquiry type/i', $label)
            ) {
                return true;
            }
        } else {
            if (preg_match('/product interest/i', $label) ||
                preg_match('/What would you like a quote for\?/i', $label) ||
                preg_match('/Brochure Names/i', $label) ||
                preg_match('/Product enquiry/i', $label)
            ) {
                //store which form type with selectors.
                $this->form_type = $label;
                return true;
            }
        }
        return false;
    }

    /**
     * DYNAMICALLY GETTING PRODUCT NAMES FROM THE SETTINGS -> DK SETTINGS PAGE FROM WP
     * PRODUCT NAMES/INTEREST TERMS ARE STORED AN ARRAY IN WP_OPTIONS TABLE
     * @param $product_name
     * @return bool|mixed
     */
    private function check_and_return_product_ab_term ($product_name) {
        $dkSettingPageObj = new HkHubkogSettingsPage($this->settings['SettingsPage']);

        $grouped_product_interest = $dkSettingPageObj->grouped_product_interest;

        $terms = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $grouped_product_interest)));
        $product_name = trim($product_name);

        foreach($terms as $term) {
            if(strcasecmp($term, $product_name) === 0) {
                return $term;
            }
        }

        usort($terms, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach($terms as $term) {
            if( self::check_for_similar_term($term, $product_name) ) {
                return $term;
            }
        }
        return false;
    }

    /**
     * PRODUCT OPTIONS CONSERVATORIES, ORGANGERIES, WINDOWS, DOORS, ROOFS ETC ARE RANDOMLY USED ON FORMS
     * WHEN POST DATA IS RECEIVED THESE PRODUCTS NAMES ARE NOT CONSISTENT
     * THIS FUNCTION HELPS TO DECIDE WHICH TERM IS USED, @TERMS COMES FROM DK SETTINGS -> PRODUCT INTEREST TERMS
     * WHEN SUBMITTING DATA TO API IT NEEDS ONE PRODUCT AT A TIME
     * @param $term
     * @param $product_name
     * @return bool
     */
    private function check_for_similar_term( $term, $product_name ) {
        $term = trim($term);
        $product_name = trim($product_name);

        if(empty($term) || empty($product_name)){
            return false;
        }

        foreach([rtrim($term, 's'), rtrim($term, 'y')] as $pattern) {
            if(!empty($pattern) && preg_match('/'.preg_quote($pattern, '/').'/i', $product_name)) {
                return true;
            }
        }

        if( strlen($term) > 6 &&
            preg_match('/'.preg_quote(substr($term, 0, -3), '/').'/i', $product_name) ) {
            return true;
        }
        return false;
    }

    public function hubkog_integration_settings_link( $links ) {
        $url = esc_url( add_query_arg(
            'page',
            'hk_hubkog_setting_admin',
            get_admin_url() . 'admin.php'
        ) );
        $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
        array_push(
            $links,
            $settings_link
        );
        return $links;
    }

    private function get_city_from_postcode( $postcode ) {
        if( !empty($postcode) ) {
            $url="https://api.postcodes.io/postcodes/".$postcode;
            if( $api_output = @file_get_contents($url) ) {
                if(!empty($api_output)) {
                    $vars = json_decode($api_output, true);
                    if (isset($vars['result'])) {
                        $result = $vars['result'];
                        if (isset($result['admin_district'])) {
                            return $result['admin_district'];
                        }
                    }
                }
            }
        }
        return null;
    }

    private function get_town( $entry, $form, $postcode ) {
        $output = self::get_form_element($entry, $form, 'town');
        if( empty($output) ) {
            $output = self::get_city_from_postcode($postcode);
        }
        return $output;
    }

    private function dk_sanitize_text( $title ) {
        return preg_replace('/[^A-za-z0-9\ ]/i', '', trim($title) );
    }

    private function verify_field_match($fieldObj, $look_for, $required_field_type) {
        if( preg_match( '/'.$look_for.'/i', $fieldObj->label ) &&
            empty($required_field_type) )
        {
            return true;
        }
        if( !empty($required_field_type) &&
            preg_match('/'.$required_field_type.'/i', $fieldObj->type) &&
            preg_match( '/'.$look_for.'/i', $fieldObj->label ) )
        {
            return true;
        }
        return false;
    }

    private function format_for_hubkog($entry, $form) {
        $this->raw_products = [];
        $spam_status = false;
        if (class_exists('\additional_email_notification_using_dk_fm_tool')) {
            $franchiseSpam = new \additional_email_notification_using_dk_fm_tool();
            if (method_exists($franchiseSpam, 'check_spam_status')) {
                $spam_status = $franchiseSpam->check_spam_status($form, $entry);
            }
        }

        $street_address = self::get_form_element($entry, $form, 'House Number');
        $postcode = self::get_form_element($entry, $form, 'postcode');
        //removed as it sends incorrect info to customer
        //$town = self::get_town($entry, $form, $postcode);
        $town = null;
        $product_interest = self::get_form_element($entry, $form, 'products','checkbox');
        $enquiry_type = self::get_form_element($entry, $form, 'Enquiry type', 'checkbox');
        $comments = self::get_form_element($entry, $form, 'further information', 'textarea');
        $location = $this->get_form_element($entry, $form, 'location');
        $date = $this->get_form_element($entry, $form, 'date', 'date');
        $time = $this->get_form_element($entry, $form, 'time', 'select');
        $opt_out = $this->get_form_element($entry, $form, 'opt in', 'checkbox')?0:1;

        $params = [
            'name' =>  self::get_form_element($entry, $form, 'title') .' '.
                self::get_form_element($entry, $form, 'first') .' '.
                self::get_form_element($entry, $form, 'surname'),
            'form' => $form['title'],
            'title' => self::get_form_element($entry, $form, 'title'),
            'forename' => self::get_form_element($entry, $form, 'first'),
            'surname' => self::get_form_element($entry, $form, 'surname'),
            'phone' => self::get_form_element($entry, $form, 'telephone'),
            'email' => self::get_form_element($entry, $form, 'email'),
            'user_ip' => $_SERVER['REMOTE_ADDR'],
            'house_number' => !empty($street_address) ? $street_address : self::get_form_element($entry, $form, 'Address Line 1'),
            'postcode' => $postcode,
            'products_of_interest' => $product_interest,
            'spam' => ($spam_status === true)?1:0,
            'source_url' => $entry['source_url'],
            'user_agent' => $entry['user_agent'],
            'gravity_forms_id' => $entry['id'],
            'gravity_forms_created' => $entry['date_created']
        ];

        if(!empty($this->raw_products)){
            $params['raw_products'] = array_values(array_unique($this->raw_products));
        }


        if(!empty($town)){
            $params['town'] = $town;
        }

        if(!empty($enquiry_type)){
            $params['enquiry_type'] = $enquiry_type;
        }

        if(!empty($comments)){
            $params['comments'] = $comments;
        }

        if(!empty($date)){
            $params['appointment_date'] = $date;
        }

        if(!empty($time)){
            $params['appointment_time'] = $time;
        }

        if(!empty($location)){
            $params['appointment_location'] = $location;
        }

        if( is_numeric( $opt_out ) ){
            $params['opt_out'] = $opt_out;
        }

        foreach($entry as $k => $v){
            if( !empty( $v ) && is_numeric( $k ) && !in_array( $k, $this->used_fields ) ){
                $fieldName  = explode(".", $k);
                $fieldName = $fieldName[0];

                foreach($form['fields'] as $f){
                    if($f['id'] == $fieldName){
                        if( strpos($k,'.') > 0 ){
                            $params[ $f['label'] ][] = $v;
                        } else {
                            $params[ $f['label'] ] = $v;
                        }

                    }
                }

            }
        }

	$lead_source = self::get_lead_source_info($entry['id']);
	if(!is_null($lead_source)){
		foreach ($lead_source as $k => $v){
			$params[$v->meta_key] = $v->meta_value;
		}
	}
/*echo "<pre>";
print_r($params);

die("after source");*/
        /*echo "<pre>";
        print_r($entry);
        print_r($form);
        print_r($params);
        die();*/

        return $params;
    }

    public function dk_trim_text( $message, $limit = 255 ) {
        if( strlen($message) > $limit ) {
            return mb_strimwidth( $message, 0, $limit, '...');
        }
        return $message;
    }


}
