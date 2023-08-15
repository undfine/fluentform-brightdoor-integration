<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use \FluentForm\App\Services\Integrations\IntegrationManager;
use \FluentForm\Framework\Foundation\Application;
use \FluentForm\Framework\Helpers\ArrayHelper;

class FF_BrightDoor extends IntegrationManager
{
    public function __construct(Application $app = null)
    {
        parent::__construct(
            $app,
            'BrightDoor',
            'brightdoor',
            '_fluentform_brightdoor_settings',
            'fluentform_brightdoor_feed',
            16
        );

        $this->logo = plugin_dir_url( __DIR__ ) . '/assets/brightdoor.png';
		$this->category = 'crm';
        $this->description = 'Create signup forms in WordPress and connect to BrightDoor';

        $this->registerAdminHooks();

        // uncomment below to turn off async requests for debugging (useful on local environments or when WP-CHRON is not active)
        // add_filter('fluentform_notifying_async_brightdoor', '__return_false');
    }

    public function getGlobalFields($fields)
    {
        return [
            'logo' => $this->logo,
            'menu_title' => __('BrightDoor API Settings', 'ff_brightdoor'),
            'menu_description' => __('BrightDoor is an integrated email marketing, marketing automation, and small business CRM. Save time while growing your business with sales automation. Use Fluent Forms to collect customer information and automatically add it to your BrightDoor list. If you don\'t have an BrightDoor account, you can <a href="https://www.brightdoor.com/" target="_blank">sign up for one here.</a>', 'ff_brightdoor'),
            'valid_message' => __('Your BrightDoor configuration is valid', 'ff_brightdoor'),
            'invalid_message' => __('Your BrightDoor configuration is invalid', 'ff_brightdoor'),
            'save_button_text' => __('Save Settings', 'ff_brightdoor'),
            'fields' => [
                'apiUser' => [
                    'type' => 'text',
                    'placeholder' => 'username',
                    'label_tips' => __("Please provide your BrightDoor API User name", 'ff_brightdoor'),
                    'label' => __('BrightDoor API User Name', 'ff_brightdoor'),
                ],
                'apiKey' => [
                    'type' => 'password',
                    'placeholder' => 'password',
                    'label_tips' => __("Please enter your BrightDoor API password", 'ff_brightdoor'),
                    'label' => __('BrightDoor API Password', 'ff_brightdoor'),
                ],
                'bdcClient' => [
                    'type' => 'text',
                    'placeholder' => 'clientname',
                    'label_tips' => __("Please enter your BrightDoor Client ID", 'ff_brightdoor'),
                    'label' => __('BrightDoor Client ID', 'ff_brightdoor'),
                ],
                'bdcDatabase' => [
                    'type' => 'text',
                    'placeholder' => 'database',
                    'label_tips' => __("Please enter your BrightDoor Database Name", 'ff_brightdoor'),
                    'label' => __('BrightDoor Database Name', 'ff_brightdoor'),
                ]
            ],
            'hide_on_valid' => true,
            'discard_settings' => [
                'section_description' => 'Your BrightDoor API is connected',
                'button_text' => 'Disconnect BrightDoor',
                'data' => [
                    'apiKey' => ''
                ],
                'show_verify' => true
            ]
        ];
    }

    public function getGlobalSettings($settings)
    {
        $globalSettings = get_option($this->optionKey);
        if (!$globalSettings) {
            $globalSettings = [];
        }
        $defaults = [
            'apiKey' => '',
            'apiUser' => '',
            'bdcClient' => '',
            'bdcDatabase' => '',
            'status' => ''
        ];

        return wp_parse_args($globalSettings, $defaults);
    }

    public function saveGlobalSettings($settings)
    {
        // test for empty required fields and reset settings
        if (!$settings['apiKey'] || !$settings['apiUser'] || !$settings['bdcClient'] ) {
            $integrationSettings = [
                'apiKey' => '',
                'apiUser' => '',
                'bdcClient' => '',
                'bdcDatabase' => '',    
                'status' => false
            ];
            // reset the integration settings
            update_option($this->optionKey, $integrationSettings, 'no');
            wp_send_json_success([
                'message' => __('Your settings have been discarded', 'ff_brightdoor'),
                'status' => false
            ], 400);
        }

        try {
            $settings['status'] = false;
            update_option($this->optionKey, $settings, 'no');
            
            $api = $this->getApiClient();

            if ($api->auth_test()) {
                $settings['status'] = true;
                update_option($this->optionKey, $settings, 'no');

                return wp_send_json_success([
                    'status' => true,
                    'message' => __('Your settings has been updated!', 'ff_brightdoor')
                ], 200);
            }
            throw new \Exception('Invalid Credentials', 400);

        } catch (\Exception $e) {
            wp_send_json_error([
                'status' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }


    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'title' => $this->title . ' Integration',
            'logo' => $this->logo,
            'is_active' => $this->isConfigured(),
            'configure_title' => 'Configuration required!',
            'global_configure_url' => admin_url('admin.php?page=fluent_forms_settings#general-brightdoor-settings'),
            'configure_message' => 'BrightDoor is not configured yet! Please configure your BrightDoor API first',
            'configure_button_text' => 'Set BrightDoor API'
        ];
        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        return [
            'name' => '',
            'emailAddress' => '',
            'firstName' => '',
            'lastName' => '',
            'website' => '',
            'company' => '',
            'phone' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => '',
            'fields' => (object)[],
            'contact_fields' => (object)[],
            'contact_attributes' => [
                [
                    'item_value' => '',
                    'label' => ''
                ]
            ],
            'extra_fields' => [
                [
                    'item_value' => '',
                    'label' => ''
                ]
            ],
            'note' => '',
            'contact_status_id' => '6', // 6 = UG Prospect
            'contact_initial_type' => '6', // 6 = Internet Contact
            'contact_lead_source' => '22', // 22 = This website
            'conditionals' => [
                'conditions' => [],
                'status' => false,
                'type' => 'all'
            ],
            'check_existing_email' => false,
            'auto_reply' => false,
            'enabled' => true
        ];
        
    }

    public function getSettingsFields($settings, $formId)
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'required' => true,
                    'placeholder' => 'Your Feed Name',
                    'component' => 'text'
                ],
                [
                    'key' => 'contact_fields',
                    'require_list' => false,
                    'label' => 'Map Contact Fields',
                    'tips' => 'Select which Fluent Forms fields pair with their respective BrightDoor fields.',
                    'component' => 'map_fields',
                    'field_label_remote' => 'BrightDoor Field',
                    'field_label_local' => 'Form Field',
                    'primary_fileds' => [
                        [
                            'key' => 'emailAddress',
                            'label' => 'Email Address',
                            'required' => true,
                            'input_options' => 'emails'
                        ],
                        [
                            'key' => 'firstName',
                            'label' => 'First Name'
                        ],
                        [
                            'key' => 'lastName',
                            'label' => 'Last Name'
                        ],
                        [
                            'key' => 'phone',
                            'label' => 'Phone'
                        ],
                        [
                            'key' => 'address1',
                            'label' => 'Address Line 1'
                        ],
                        [
                            'key' => 'address2',
                            'label' => 'Address Line 2'
                        ],
                        [
                            'key' => 'city',
                            'label' => 'City'
                        ],
                        [
                            'key' => 'state',
                            'label' => 'State'
                        ],
                        [
                            'key' => 'zip',
                            'label' => 'Zip'
                        ],
                        [
                            'key' => 'country',
                            'label' => 'Country'
                        ],
                        [
                            'key' => 'contact_lead_source',
                            'label' => 'Lead Source',
                            'input_options' => 'selects'
                        ],
                           
                    ],
                ],
                [
                    'key'       => 'contact_attributes',
                    'label'     => __('Contact Attributes', 'fluentform'),
                    'tips'        => __('Add custom fields to match up with contact attribute fields. Values should be in an acceptable form (STRING or INT)', 'fluentform'),
                    'component' => 'dropdown_label_repeater',
                    'field_label' => 'Attribute Name',
                    'value_label' => 'Value',
                ], 
                [
                    'key'       => 'extra_fields',
                    'label'     => __('Additional Contact Fields', 'fluentform'),
                    'tips'        => __('Extra Contact fields to sync with Brightdoor. Values should be in an acceptable form (String, Integer or Boolean)', 'fluentform'),
                    'component' => 'dropdown_many_fields',
                    'field_label_remote' => 'Contact Field',
                    'field_label_local'  => 'Form Field',
                    'options' => [
                        'Honorific' => '',
                        'FirstName' => '',
                        'MiddleName' => '',
                        'LastName' => '',
                        'Suffix' => '',
                        'DisplayName' => '',
                        'Salutation' => '',
                        'SpouseFirst' => '',
                        'SpouseMiddle' => '',
                        'SpouseLast' => '',
                        'CompanyName' => '',
                        'PreferredCommunicationMethod' => '',
                        'ContactTypeId' => '',
                        'ContactStatusId' => '',
                        'ContactStatus' => '',
                        'ContactType' => '',
                        'ContactPassword' => '',
                        'AddToTour' => '',
                        'Active' => '',
                    ]
                ],  
                [
                    'key'         => 'contact_status_id',
                    'placeholder' => __('Contact Status', 'fluentform'),
                    'label'       => __('Contact Status', 'fluentform'),
                    'tips'        => __('Select the Initial Contact Status', 'fluentform'),
                    'component'   => 'select',
                    'required'    => true,
                    'is_multiple' => false,
                    'options'     => [
                            '1' => 'A+ Prospect',
                            '2' => 'A Prospect',
                            '3' => 'B Prospect',
                            '4' => 'C Prospect',
                            '5' => 'D Prospect',
                            '6' => 'UG Prospect',
                            '7' => 'Reservationist',
                            '8' => 'Release',
                            '9' => 'Wait List',
                            '10' => 'Pending Owner',
                            '12' => 'Owner',
                            '20' => 'Marketing',
                            '21' => 'Founder'                            
                        ]
                ],
                [
                    'key' => 'note',
                    'require_list' => false,
                    'label' => 'Note',
                    'tips' => 'You can write a note for this contact or add smart tags to include',
                    'component' => 'value_textarea'
                ],
               
                /* Add List Options
                [
                    'key' => 'list_id',
                    'label' => 'BrightDoor List',
                    'placeholder' => 'Select BrightDoor Mailing List',
                    'tips' => 'Select the BrightDoor Mailing List you would like to add your contacts to.',
                    'component' => 'list_ajax_options',
                    'options' => $this->getLists(),
                ], 
                /* [
                    'key' => 'auto_reply',
                    'label' => 'Autoreply',
                    'require_list' => true,
                    'tips' => 'When Autoreply is enabled, BrightDoor will allow emails to be sent automatically when the contact is added',
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Enable Instant Responder'
                ], */
                [
                    'key' => 'conditionals',
                    'label' => 'Conditional Logics',
                    'tips' => 'Allow BrightDoor integration conditionally based on your submission values',
                    'component' => 'conditional_block'
                ],
                [
                    'key' => 'check_existing_email',
                    'label' => 'Check for Existing Contact before updating',
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Check Existing'
                ],
                [
                    'key' => 'enabled',
                    'label' => 'Status',
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Enable This feed'
                ]
            ],
            //'button_require_list' => true,
            'integration_title' => $this->title
        ];
    }

    
    /** 
     * TODO: add list options
     * 
    protected function getLists()
    {
        $api = $this->getApiClient();
        if (!$api) {
            return [];
        }

        $lists = $api->get_lists();

        $formattedLists = [];
        foreach ($lists as $list) {
            if (is_array($list)) {
                $formattedLists[strval($list['id'])] = $list['name'];
            }
        }
        return $formattedLists;
    }

    */

    public function getAttributeFields()
	{
		$api = $this->getApiClient();
        return $api->get_custom_fields();
	}

    public function getMergeFields($list, $listId, $formId)
    {
        return [];
    }


    /*
     * Submission Broadcast Handler
     * NOTE: some fields require a JSON array to wrap the JSON object
	 * See the DTO info https://api.brightdoor.com/swagger/index.html
     */

    public function notify($feed, $formData, $entry, $form)
    {
        $feedData = $feed['processedValues'];

        if (!is_email($feedData['emailAddress'])) {
            $feedData['emailAddress'] = ArrayHelper::get($formData, $feedData['emailAddress']);
        }

        if (!is_email($feedData['emailAddress'])) {
            do_action('ff_integration_action_result', $feed, 'failed', 'API call has been skipped because no valid email available');
            return;
        }
        
        $mainFields = [
            'FirstName' => $feedData['firstName'],
            'LastName' => $feedData['lastName'],
            'ContactEmailAddresses' => array(
                [
                'EmailAddressLabelId' => 1,
                'EmailAddress' => $feedData['emailAddress'],
                // 'IsPreferred' => true,
                // 'IsDoNotEmail' => false,
                // 'IsDoNotEblast' => false 
                ]
            ),
            'ContactPhoneNumbers' => array(
                [
                'PhoneNumberLabelId' => 3, // 1:Home, 2:Work, 3:Mobile, 4:Pager, 5:Fax, 6:Other, 7:Spouse Home, 8:Spouse Work, 9:Spouse Mobile
                'PhoneNumber' => $feedData['phone'],
                // 'IsPreferred' => true,
                // 'IsDoNotText' => false         
                ]
            ),
            'ContactPhysicalAddresses' => array(
                [
                'PhysicalAddressLabelId' => 1,
                'Address1' => $feedData['address1'],
                'Address2' => $feedData['address2'],
                'City' => $feedData['city'],
                'State' => $feedData['state'],
                'Zip' => $feedData['zip'],
                'Country' => $feedData['country'],
                // 'IsPreferred' => true,
                // 'IsShipping' => true,
                // 'IsDoNotMail' => true
                ]
            ),
            'Notes' => $feedData['note']
        ];

        $newContactFields = [
            'ContactTypeId' => '1', // 1 = Prospect
            'ContactStatusId' => $feedData['contact_status_id'],
            'InitialContactType' => [
                'InitialContactTypeId' => $feedData['contact_initial_type'] 
            ], 
            'ContactLeadSources' => array([ 
                'LeadSourceId' => $feedData['contact_lead_source'] 
            ]), 
        ];

        if( $feedData['extra_fields']){
            $extraFields = [];

            foreach (ArrayHelper::get($feedData, 'extra_fields') as $item) {
                if (!empty($item['item_value'])){
                    $extraFields[ $item['label'] ] = $item['item_value'];
                }
            }
        }

        if( $feedData['contact_attributes']){
            $attributes = [];

            foreach (ArrayHelper::get($feedData, 'contact_attributes') as $item) {
                if (!empty($item['item_value'])){
                    $attributes[] = [
                        'ContactAttributeDefName' => $item['label'], 
                        'Value' => $item['item_value']
                    ];
                }

            }

            $newContactFields['ContactAttributes'] = $attributes;
        }
        

        // Get the API ready
        $api = $this->getApiClient();

        // if Checking for existing Email
        if( $feedData['check_existing_email']){
            
            $response = $api->get_contact_by_email($feedData['emailAddress']);

            if ( !is_wp_error($response) && is_array($response['data']) && isset($response['data']['id'])){
            
                // set the ID to update
                $mainFields['Id'] = $response['data']['id']; 
    
                // Remove the email address and new contact fields
                unset($mainFields['ContactEmailAddresses']);
                $newContactFields = [];
            
            } 
        }

        // Merge all contact fields
        // NOTE: Extra fields have the ability to override any of the other fields set by default or through the form
        $contactData = array_merge($mainFields, $newContactFields, $extraFields);
      

        /* Example of checkbox field 
        if (ArrayHelper::isTrue($feedData, 'checkbox_field')) {
            $contactData['checkbox_field[' . $list_id . ']'] = 1;
        }*/

        // remove any empty items in multidimensional array
        function multi_array_filter($arr){
            if ( is_array($arr) ){
                $arr = array_map('multi_array_filter', $arr);
                $arr = array_filter($arr);
            }
            return $arr;
        }

        $contactData = multi_array_filter($contactData);

        /** Uncomment this to test output without submitting data 
        * Requires the filter "fluentform_notifying_async_brightdoor" to return false (in constructor) ***
        die('<pre>' . print_r( $contactData, true ) . '</pre>');
        */
        
    

        // add filter hooks
        $contactData = apply_filters('fluentform_integration_data_'.$this->integrationKey, $contactData, $feed, $entry);

        // prepare the data and push to BrightDoor
        $response = $api->sync_contact($contactData);

        if (is_wp_error($response)) {
            do_action('ff_integration_action_result', $feed, 'failed', $response->get_error_message());
            return false;

        } else if ( wp_remote_retrieve_response_code($response) == 200) {
            do_action('ff_integration_action_result', $feed, 'success', 'Brightdoor has been successfully initialed and synced contact data');
            return true;
        }

        do_action('ff_integration_action_result', $feed, 'failed', $response['result_message']);
    }


    protected function getApiClient()
    {
        $settings = get_option($this->optionKey);
        return new BrightDoorApi($settings);
    }
}
