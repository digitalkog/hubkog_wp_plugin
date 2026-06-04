<?php
/**
 * Plugin Name:       Staging HubKOG Integration
 * Plugin URI:        https://digitalkog.co.uk
 * Description:       Submitting website enquiries to the staging HubKOG API.
 * Version:           0.1
 * Requires at least: 4.9
 * Requires PHP:      7.2
 * Author:            DigitalKOG
 * Author URI:        https://www.digitalkog.co.uk
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       staging-hubkog-dealer-api-plugin
 * Domain Path:       /languages
 */
namespace StagingHubkog;

require dirname(__FILE__)."/vendor/autoload.php";

define('STAGING_HUBKOG_PLUGIN_FILE', __FILE__);
    $api_fields =
    [
        'form'=>'The Form name',
        'title' => 'Customer Title (Mr,Mrs etc.)',
        'forename' => 'Customer forename',
        'surname' => 'Customer surname',
        'phone' =>  "String. Or, array of phone numbers in the format [['name'=>'landline','value'=>'01772 123456']]",
        'email' =>  "String. Or, array of email addresses in the format [['value'=>'01772 123456']]",
        'opt_out' => "Marketing. suitable values: true, yes, checked, false, no: default will be yes",
        'house_number' => "Customer house number (or name)",
        'postcode' => "Customer postcode",
        'comments' => "Customer additional comments",
        'products_of_interest' => "String. Or, array of product names in the format ['orangeries', 'windows']"
    ];

new HkHubkogIntegrationCore(
    [
        'Core' => [
          'plugin' =>[
              'name' => "staging_hubkog"
          ]
        ],
        'ResultsPage' => [
            'activate' => true,
            'settings' => [
                'menu' => [
                    'page_title' => 'Staging HubKOG Results',
                    'menu_title' => 'Staging HubKOG Results',
                    'capability' => 'manage_options',
                    'menu_slug' => 'staging_hubkog_results'
                ],
                'page' => [
                    'option_name' => 'staging_hubkog_results',
                    'option_group_name' => 'staging_hubkog_results_group',
                    'page' => 'staging_hubkog_result_admin',
                    'section_id' => 'staging_hubkog_results_section_id',
                    'title' => 'Results',
                    'h1' => 'Staging HubKOG Results',
                    'section_info' => 'Failed staging HubKOG submissions:'
                ],

            ]
        ],
        'SettingsPage' => [
            'activate' => true,
            'settings' => [
                'menu' => [
                    'page_title' => 'Staging HubKOG Settings',
                    'menu_title' => 'Staging HubKOG API Settings',
                    'capability' => 'manage_options',
                    'menu_slug' => 'staging_hubkog_setting_admin'
                ],
                'page' => [
                    'option_name' => 'staging_hubkog_options',
                    'option_group_name' => 'staging_hubkog_options_group',
                    'page' => 'staging_hubkog_setting_admin',
                    'section_id' => 'staging_hubkog_section_id',
                    'title' => 'API',
                    'h1' => 'Staging HubKOG Settings',
                    'section_info' => 'Enter your staging API details below:'
                ],
                'fields' => [
                    [
                        'id' => 'api_url',
                        'title' => 'API URL',
                        'callback' => 'api_url_callback'
                    ],
                    [
                        'id' => 'api_key',
                        'title' => 'API Key',
                        'callback' => 'api_key_callback'
                    ],
                    [
                        'id' => 'select_forms',
                        'title' => 'Select forms to not integrate',
                        'callback' => 'select_forms_callback'
                    ],
                    [
                        'id' => 'grouped_product_interest',
                        'title' => 'Products of interest',
                        'callback' => 'product_interest_terms_callback'
                    ],
                ]
            ]
        ]
    ]
);
//$hubkog->init();
