<?php
namespace StagingHubkog\admin;
use GFAPI;

class HubkogResultsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $settings;
    public $product_interest_terms;
    public $advanced_options_array;
    public $grouped_product_interest;
    private $fields;
    public $items;

    /**
     * ADDING PLUGIN PAGE
     * ADDING PLUGIN OPTIONS PAGE
     * INITIALISING PRODUCT INTEREST/NAMES, CURRENTLY NOT DYNAMIC
     */
    public function __construct($params = null)
    {
        if( $params['activate'] === true ){

            if(isset($params['settings'])){
                $this->settings = $params['settings'];
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

        $table = new Link_List_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo $this->settings['page']['h1']; ?></h1>

            Currently only failed sends display here
            <?php $table->display(); ?>
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

        /*foreach ($this->settings['fields'] as $field){
            add_settings_field(
                $field['id'],
                $field['title'],
                array($this, $field['callback']),
                $this->settings['page']['page'],
                $this->settings['page']['section_id']
            );
        }*/

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
            $grouped_product_interest = [];
            $grouped_product_interest = $this->options['grouped_product_interest'];

            printf(
                '<textarea type="text" id="grouped_product_interest" name="%s" rows="10" cols="100"/>%s</textarea><br /><label style="text-color:grey;">1 product per line</label>',
                $this->settings['page']['option_name'].'[grouped_product_interest]',
                isset($this->options['grouped_product_interest'])?$this->options['grouped_product_interest']:''
            );

        }
    }


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


}

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Link_List_Table extends \WP_List_Table {

    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    function __construct() {

        parent::__construct( array(
            'singular'=> 'wp_list_text_link', //Singular label
            'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
            'ajax'   => false //We won't support Ajax for this table
        ) );
    }

    public function get_columns() {
        return $columns= array(
            'id'=>__('ID'),
            'data'=>__('Data'),
            'hubkog_uid'=>__('Uid'),
            'Actions'=>__("Button")
        );
    }

    public function get_sortable_columns() {
        return $sortable = array(
            'id'=>'id',
        );
    }

    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();

        /* -- Preparing your query -- */
        $query = "SELECT * FROM ".$wpdb->prefix."staging_hubkog WHERE hubkog_uid is null";
        //die($query);

        /* -- Ordering parameters -- */
        //Parameters that are going to be used to order the result
        $orderby = !empty($_GET["orderby"]) ? $_GET["orderby"] : 'ASC';
        $order = !empty($_GET["order"]) ? $_GET["order"] : '';
        if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

        /* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = 10;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? $_GET["paged"] : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
        if(!empty($paged) && !empty($perpage)){ $offset=($paged-1)*$perpage; $query.=' LIMIT '.(int)$offset.','.(int)$perpage; }
        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ) );
        //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns = $this->get_columns();
        $_wp_column_headers[$screen->id]=$columns;

        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results($query);
    }


    /**
     * Display the rows of records in the table
     * @return string, echo the markup of the rows
     */
    function display_rows() {


        //Get the records registered in the prepare_items method
        $records = $this->items;

        //Get the columns registered in the get_columns and get_sortable_columns methods
        //list( $columns, $hidden ) = $this->get_columns();

        $columns = $this->get_columns();
        $hidden = [];

        //Loop for each record
        if(!empty($records)){foreach($records as $rec){

            //print_r($columns);
            //print_r($hidden);
            //Open the line
            echo '<tr id="record_'.$rec->id.'">';
            foreach ( $columns as $column_name => $column_display_name ) {

                //Style attributes for each col
                $class = "class='$column_name column-$column_name'";
                $style = "";
                if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
                $attributes = $class . $style;

                //Display the cell
                $data = unserialize($rec->data);
                if($data === false){
                    $data = json_decode($rec->data, true);
                }

                $name = is_array($data) && isset($data['name'])?$data['name']:'-';

                //die($column_name);
                switch ( $column_name ) {
                    case "id":  echo '<td '.$attributes.'>'.stripslashes($rec->id).'</td>';   break;
                    case "data": echo '<td '.$attributes.'>'.$name.'</td>'; break;
                    case "hubkog_uid": echo '<td '.$attributes.'>'.$rec->hubkog_uid.'</td>'; break;
                    case "Actions": echo '<td '.$attributes.' style="text-align:right;">'."<button data-id='".$rec->id."' onclick=\"staging_retry_hubkog($(this).data('id'))\">Retry</button>".'</td>'; break;
                }
            }

            //Close the line
            echo'</tr>';
        }}
    }

}
