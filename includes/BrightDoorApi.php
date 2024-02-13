<?php

/** 
* For notes and test, see the API documentation:
* https://api.brightdoor.com/swagger/index.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BrightDoorApi
{
	protected $apiUrl = 'https://api.brightdoor.com/';
	protected $apiKey = null;
	protected $apiUser = null;
	protected $bdcClient = null;
	protected $bdcDatabase = null;
	

	public function __construct( $integrationSettings )
	{	
		$this->apiKey = $integrationSettings['apiKey'];
		$this->apiUser = $integrationSettings['apiUser'];	
		$this->bdcClient = $integrationSettings['bdcClient'];	
		$this->bdcDatabase = $integrationSettings['bdcDatabase'];	
	}
	
	/** 
	 * default parameters required for authenticating API request
	 * @return array
	 */
	public function auth_params()
	{
		return [
			'Username'	=> $this->apiUser,
			'Password'	=> $this->apiKey,
			'BDCClient'	=> $this->bdcClient,
			'BDCDatabase' => $this->bdcDatabase
		];
	}

	/**
	 * @param 	string 	$action API endpoint
	 * @param 	array 	$data	Request body
	 * @param	string	$method Default: GET
	 * @return 	array/mixed  Decoded JSON response body (array) or error message
	 */
	public function make_request( $action, $data = [], $method = 'GET' )
	{	
		if (empty($data)){
			return;
		}
		// Get API Credentials
		$data['UserDetails'] = $this->auth_params();

		// Build request URL
		$request_url = untrailingslashit( $this->apiUrl ) . '/Contact/' . $action;
		
		// Execute request based on method
		switch ( $method ) {
			
			case 'POST':

				$args = array(
                    'body'    => json_encode($data),
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                );
				$response = wp_remote_post( $request_url, $args );
				break;
				
			case 'GET':
				$data = array_merge( $this->auth_params(), $data);

				$args['headers'] = [
					'Content-Type'  => 'application/json',
				];

				// Build request query string
				$query = '?' . http_build_query( $data );

				$response = wp_remote_get( $request_url . $query, $args );
				break;
		}
					
		// If WP_Error, die. Otherwise, return decoded JSON
		if ( is_wp_error( $response ) ) {
		   return $response;
		} else {
			return json_decode( $response['body'], true );
		}	
	}
	
	/**
	 * Test the provided API credentials. 
	 * Return true if response pass
	 * 
	 * @access public
	 * @return bool
	 */
	public function auth_test()
	{	
		// Build query string
		$request_params = $this->auth_params();
		$request_params['ContactId'] = 1;
		$request_params = http_build_query( $request_params );

		// Build request URL
		$request_url = untrailingslashit( $this->apiUrl ) . '/Contact/GetPortalGreetings' . '?' . $request_params;
		
		// Execute request
		$response = wp_remote_get( $request_url );			

		// If invalid content type, API URL is invalid
		if ( is_wp_error( $response ) || strpos( $response['headers']['content-type'], 'application/json' ) != 0 && strpos( $response['headers']['content-type'], 'application/json' ) > 0 )
			throw new \Exception( 'Invalid API URL.' );

		// If status code is not "200 - success" the API credentials are invalid
		if (200 !== wp_remote_retrieve_response_code( $response )){
			throw new \Exception( 'Invalid API Credentials.' );
		}
		
		return true;
	}
	
	
	/**
	 * Add or edit a contact.
	 * 
	 * @access public
	 * @param mixed $contact
	 * @return array
	 */
	public function sync_contact( $contact )
	{			
		$data['ContactDetails'] = $contact; 
		return $this->make_request( 'CreateOrUpdate', $data, 'POST' );	
	}

	/**
	 * Add or edit a contact.
	 * 
	 * @access public
	 * @param  string $email
	 * @return array with contact ID
	 * 
	 */
	public function get_contact_by_email( $email )
	{	
		$data = ['EmailAddress' => $email];
		return $this->make_request( 'GetContactByEmail', $data, 'GET' );	
	}

	/** retrieve the custom fields and attributes from Brightdoor
	 * CURRENTLY THIS DOES NOT WORK to retrieve all attributes
	 * 
	 * @return Array of options
	 */
	public function get_custom_fields()
    {
		// Contact ID 2 is the Brightdoor default "Mr &  Mrs Brightdoor"
		$request = [ 'id' => 2 ];

        $response = $this->make_request('GetContactById', $request, 'GET');

        if ( is_wp_error($response) || !isset($response->data) || !isset($response->data['contactAttributes']) ){
            return [];
        }


		$attributes = $response->data['contactAttributes'];

		$customFields = [];

		// map the attributes to an array [id] => label
		foreach ( $attributes as $attribute){
			// $customFields[] = [
			// 	'key' => $attribute['contactAttributeDefId'],
			// 	'label' => $attribute['contactAttributeDefLbl']
			// ];
			$customFields[ $attribute['contactAttributeDefId'] ] = $attribute['contactAttributeDefLbl'];
		
		}

        return $customFields;
    }

}
