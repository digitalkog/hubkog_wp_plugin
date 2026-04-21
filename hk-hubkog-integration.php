<?php
/**
 * Plugin Name:       HK Hubkog Integration plugin
 * Plugin URI:        https://digitalkog.co.uk
 * Description:       Submitting website enquiries to HubKog.
 * Version:           0.1
 * Requires at least: 4.9
 * Requires PHP:      7.2
 * Author:            DigitalKOG
 * Author URI:        https://www.digitalkog.co.uk
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hk-hubkog-dealer-api-plugin
 * Domain Path:       /languages
 */
namespace HkHubkog;

require dirname(__FILE__)."/vendor/autoload.php";

define('HK_PLUGIN_FILE', __FILE__);
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
              'name' => "Hk-hubkog-integration"
          ]
        ],
        'ResultsPage' => [
            'activate' => true,
            'settings' => [
                'menu' => [
                    'page_title' => 'Hubkog Results',
                    'menu_title' => 'HK HubKog Results',
                    'capability' => 'manage_options',
                    'menu_slug' => 'hubkog_results'
                ],
                'page' => [
                    'option_name' => 'hk_hubkog_results',
                    'option_group_name' => 'hubkog_results_group',
                    'page' => 'hubkog_result_admin',
                    'section_id' => 'hubkog_results_section_id',
                    'title' => 'Results',
                    'h1' => 'HubKOG Results',
                    'section_info' => 'Failed Hubkog Submissions:'
                ],

            ]
        ],
        'SettingsPage' => [
            'activate' => true,
            'settings' => [
                'menu' => [
                    'page_title' => 'Settings Admin',
                    'menu_title' => 'HK HubKog API Settings',
                    'capability' => 'manage_options',
                    'menu_slug' => 'hk_hubkog_setting_admin'
                ],
                'page' => [
                    'option_name' => 'hk_hubkog_options',
                    'option_group_name' => 'hk_hubkog_options_group',
                    'page' => 'hk_hubkog_setting_admin',
                    'section_id' => 'hk_hubkog_section_id',
                    'title' => 'API',
                    'h1' => 'HubKOG Settings V3',
                    'section_info' => 'Enter your API details below:'
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
