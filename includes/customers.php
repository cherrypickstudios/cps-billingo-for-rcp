<?php

// No direct access
defined('ABSPATH') or die('Hey, do not do this 😱');

class B4RCP_Customers {

  private static $instance = null; // Instance of this class.

  /**
   * Constructor, sets up option fields and menus
   */
  private function __construct() {
    // add customr fields to registration form and edit profile form
    add_action( 'rcp_after_password_registration_field',  array( $this, 'add_customer_public_fields' ) );
    add_action( 'rcp_profile_editor_after',               array( $this, 'add_customer_public_fields' ) );
    // validate filds on registration
    add_action( 'rcp_form_errors',                        array( $this, 'validate_customer_fields_on_register' ) );
    // save fields on registration form submit
    add_action( 'rcp_form_processing',                    array( $this, 'save_customer_fields_on_register' ), 10, 2 );

    // add fields to edit customer table admin
    add_action( 'rcp_edit_member_after',                  array( $this, 'add_customer_admin_fields' ) );
    // save fields on edit customer
    add_action( 'rcp_user_profile_updated',               array( $this, 'save_customer_fields_on_profile_save' ), 10 );
    add_action( 'rcp_edit_member',                        array( $this, 'save_customer_fields_on_profile_save' ), 10 );
  }

  /**
   * Creates or returns an instance of this class.
   */
  public static function get_instance() {
    if ( null == self::$instance ) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   * Adds the custom fields to the registration form and profile editor
   */
  public function add_customer_public_fields() {
    $user_id = get_current_user_id();

    // get meta field values
    $billing_name =                 get_user_meta( $user_id, 'b4rcp_billing_name', true );
    $billing_address_countrycode =  get_user_meta( $user_id, 'b4rcp_billing_address_countrycode', true );
    $billing_address_zipcode =      get_user_meta( $user_id, 'b4rcp_billing_address_zipcode', true );
    $billing_address_city =         get_user_meta( $user_id, 'b4rcp_billing_address_city', true );
    $billing_address_street =       get_user_meta( $user_id, 'b4rcp_billing_address_street', true );
    $tax_number =                   get_user_meta( $user_id, 'b4rcp_tax_number', true );

    // display input fields
    ?>
      <p>
        <label for="b4rcp_billing_name"><?php _e( 'Billing name', 'billingo-for-rcp' ); ?></label>
        <input name="b4rcp_billing_name" id="b4rcp_billing_name" type="text" value="<?php echo esc_attr( $billing_name ); ?>"/>
      </p>
      <p>
        <label for="b4rcp_billing_address_countrycode"><?php _e( 'Billing address', 'billingo-for-rcp' ); ?></label>
        <?php echo b4rcp_get_country_select_field( $billing_address_countrycode ? $billing_address_countrycode : 'HU', 'b4rcp_billing_address_countrycode', 'b4rcp_billing_address_countrycode' ); ?>
      </p>
      <p>
        <input 
          name="b4rcp_billing_address_zipcode" 
          id="b4rcp_billing_address_zipcode" 
          placeholder="<?php _e( 'Zip code', 'billingo-for-rcp' ); ?>" 
          type="text" 
          value="<?php echo esc_attr( $billing_address_zipcode ); ?>"
        />
      </p>
      <p>
        <input 
          name="b4rcp_billing_address_city" 
          id="b4rcp_billing_address_city" 
          placeholder="<?php _e( 'City', 'billingo-for-rcp' ); ?>" 
          type="text" 
          value="<?php echo esc_attr( $billing_address_city ); ?>"
        />
      </p>
      <p>
        <input 
          name="b4rcp_billing_address_street" 
          id="b4rcp_billing_address_street" 
          placeholder="<?php _e( 'Street & Apartment', 'billingo-for-rcp' ); ?>" 
          type="text" 
          value="<?php echo esc_attr( $billing_address_street ); ?>"
        />
      </p>
      <p>
        <label for="b4rcp_tax_number"><?php _e( 'Tax number', 'billingo-for-rcp' ); ?>
          <span class="field-optional"> (<?php _e( 'Optional', 'billingo-for-rcp' ); ?>)</span>
          
        </label>
        <input 
          name="b4rcp_tax_number" 
          id="b4rcp_tax_number" 
          placeholder="<?php _e( 'Only for companies', 'billingo-for-rcp' ); ?>" 
          type="text" 
          value="<?php echo esc_attr( $tax_number ); ?>"
        />
      </p>
    <?php
  }

  /**
   * Adds the custom fields to the customer edit screen on admin
   */
  public function add_customer_admin_fields( $user_id = 0 ) {
    // get meta field values
    $billing_name = get_user_meta( $user_id, 'b4rcp_billing_name', true );
    $billing_address_countrycode = get_user_meta( $user_id, 'b4rcp_billing_address_countrycode', true );
    $billing_address_zipcode = get_user_meta( $user_id, 'b4rcp_billing_address_zipcode', true );
    $billing_address_city = get_user_meta( $user_id, 'b4rcp_billing_address_city', true );
    $billing_address_street = get_user_meta( $user_id, 'b4rcp_billing_address_street', true );
    $tax_number   = get_user_meta( $user_id, 'b4rcp_tax_number', true );

    $partner_id = $this->get_billingo_partner( $user_id );
		$partners_invoices_url = BILLINGO_APP_URL . '/document/list?partner_id='. $partner_id;
    
    // display input fields
    ?>
      <tr>
        <th scope="row" class="row-title">
          <label for="b4rcp_billing_name"><?php _e( 'Billing name', 'billingo-for-rcp' ); ?></label>
        </th>
        <td>
          <input name="b4rcp_billing_name" id="b4rcp_billing_name" type="text" value="<?php echo esc_attr( $billing_name ); ?>"/>
        </td>
      </tr>
      <tr>
        <th scope="row" class="row-title">
          <label for="b4rcp_billing_address"><?php _e( 'Billing address', 'billingo-for-rcp' ); ?></label>
        </th>
        <td>
          <p>
            <?php echo b4rcp_get_country_select_field( $billing_address_countrycode ? $billing_address_countrycode : 'HU', 'b4rcp_billing_address_countrycode', 'b4rcp_billing_address_countrycode' ); ?>
          </p>
          <p>
            <input 
              name="b4rcp_billing_address_zipcode" 
              id="b4rcp_billing_address_zipcode" 
              placeholder="<?php _e( 'Zip code', 'billingo-for-rcp' ); ?>" 
              type="text" 
              value="<?php echo esc_attr( $billing_address_zipcode ); ?>"
            />
          </p>
          <p>
            <input 
              name="b4rcp_billing_address_city" 
              id="b4rcp_billing_address_city" 
              placeholder="<?php _e( 'City', 'billingo-for-rcp' ); ?>" 
              type="text" 
              value="<?php echo esc_attr( $billing_address_city ); ?>"
            />
          </p>
          <p>
            <input 
              name="b4rcp_billing_address_street" 
              id="b4rcp_billing_address_street" 
              placeholder="<?php _e( 'Street & Apartment', 'billingo-for-rcp' ); ?>" 
              type="text" 
              value="<?php echo esc_attr( $billing_address_street ); ?>"
            />
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row" class="row-title">
          <label for="b4rcp_tax_number"><?php _e( 'Tax number', 'billingo-for-rcp' ); ?></label>
        </th>
        <td>
          <input name="b4rcp_tax_number" id="b4rcp_tax_number" type="text" value="<?php echo esc_attr( $tax_number ); ?>"/>
        </td>
      </tr>
      <tr>
        <th scope="row" class="row-title">
          <label for="b4rcp_partner_id"><?php _e( 'Billingo partner id', 'billingo-for-rcp' ); ?></label>
        </th>
        <td>
          <input name="b4rcp_partner_id" id="b4rcp_partner_id" type="text" value="<?php echo esc_attr( $partner_id ); ?>"/>
					<a href="<? echo ($partners_invoices_url); ?>" target="_blank">Partner's invoices in Billingo</a><br/>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <h4><?php _e( 'Billingo partner connection log', 'billingo-for-rcp' ); ?></h4>
          <?php echo $this->get_partner_connection_log_table( $user_id ); ?>
        </td>
      </tr>
    <?php
  }

  /**
   * Determines if there are problems with the registration data submitted
   */
  public function validate_customer_fields_on_register( $posted ) {
    if ( is_user_logged_in() ) {
      return;
    }

    if( empty( $posted['b4rcp_billing_name'] ) ) {
      rcp_errors()->add( 'invalid_billing_name', __( 'Please enter a billing name', 'billingo-for-rcp' ), 'register' );
    }

    if( empty( $posted['b4rcp_billing_address_countrycode'] ) ) {
      rcp_errors()->add( 'invalid_billing_address_countrycode', __( 'Please enter a country code', 'billingo-for-rcp' ), 'register' );
    } else if ( strlen($posted['b4rcp_billing_address_countrycode']) != 2  ) {
      rcp_errors()->add( 'invalid_billing_address_countrycode', __( 'Country code must be 2 characters. E.g. HU, DE, AT etc.', 'billingo-for-rcp' ), 'register' );
    }

    if( empty( $posted['b4rcp_billing_address_zipcode'] ) ) {
      rcp_errors()->add( 'invalid_billing_address_zipcode', __( 'Please enter a zip code', 'billingo-for-rcp' ), 'register' );
    }

    if( empty( $posted['b4rcp_billing_address_city'] ) ) {
      rcp_errors()->add( 'invalid_billing_address_city', __( 'Please enter a city', 'billingo-for-rcp' ), 'register' );
    } else if ( strlen($posted['b4rcp_billing_address_city']) < 2  ) {
      rcp_errors()->add( 'invalid_billing_address_city', __( 'City must be at least 2 characters', 'billingo-for-rcp' ), 'register' );
    }

    if( empty( $posted['b4rcp_billing_address_street'] ) ) {
      rcp_errors()->add( 'invalid_billing_address_street', __( 'Please enter street & apartment', 'billingo-for-rcp' ), 'register' );
    } else if ( strlen($posted['b4rcp_billing_address_street']) < 2  ) {
      rcp_errors()->add( 'invalid_billing_address_street', __( 'Street & apartment must be at least 2 characters', 'billingo-for-rcp' ), 'register' );
    }

  }

  /**
   * Stores the information submitted during registration
   */
  public function save_customer_fields_on_register( $posted, $user_id ) {

    if( !empty( $posted['b4rcp_billing_name'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_name', sanitize_text_field( $posted['b4rcp_billing_name'] ) );
    }

    if( !empty( $posted['b4rcp_billing_address_countrycode'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_countrycode', sanitize_text_field( $posted['b4rcp_billing_address_countrycode']) );
    }
    if( !empty( $posted['b4rcp_billing_address_zipcode'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_zipcode', sanitize_text_field( $posted['b4rcp_billing_address_zipcode']) );
    }
    if( !empty( $posted['b4rcp_billing_address_city'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_city', sanitize_text_field( $posted['b4rcp_billing_address_city'] ) );
    }
    if( !empty( $posted['b4rcp_billing_address_street'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_street', sanitize_text_field( $posted['b4rcp_billing_address_street'] ) );
    }

    if( !empty( $posted['b4rcp_tax_number'] ) ) {
      update_user_meta( $user_id, 'b4rcp_tax_number', sanitize_text_field( $posted['b4rcp_tax_number'] ) );
    }

    $partner_id = $this->create_billingo_partner( $user_id );
    if( $partner_id ) {
      update_user_meta( $user_id, 'b4rcp_partner_id', $partner_id );
    }

  }
  
  /**
   * Stores the information submitted profile update
   */
  public function save_customer_fields_on_profile_save( $user_id ) {

    if( !empty( $_POST['b4rcp_billing_name'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_name', sanitize_text_field( $_POST['b4rcp_billing_name'] ) );
    }

    if( !empty( $_POST['b4rcp_billing_address_countrycode'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_countrycode', sanitize_text_field( $_POST['b4rcp_billing_address_countrycode'] ) );
    }
    if( !empty( $_POST['b4rcp_billing_address_zipcode'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_zipcode', sanitize_text_field( $_POST['b4rcp_billing_address_zipcode'] ) );
    }
    if( !empty( $_POST['b4rcp_billing_address_city'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_city', sanitize_text_field( $_POST['b4rcp_billing_address_city'] ) );
    }
    if( !empty( $_POST['b4rcp_billing_address_street'] ) ) {
      update_user_meta( $user_id, 'b4rcp_billing_address_street', sanitize_text_field( $_POST['b4rcp_billing_address_street'] ) );
    }

    update_user_meta( $user_id, 'b4rcp_tax_number', sanitize_text_field( $_POST['b4rcp_tax_number'] ) );

    if( !empty( $_POST['b4rcp_partner_id'] ) && is_admin() ) {
      update_user_meta( $user_id, 'b4rcp_partner_id', sanitize_text_field( $_POST['b4rcp_partner_id'] ) );
    }

    $this->update_billingo_partner( $user_id );

  }

  /**
   * Returns with partner id of customer or creates a new one
   */
  public function get_billingo_partner( $user_id, $return_with_id = true ) {
    $partner_id = get_user_meta( $user_id, 'b4rcp_partner_id', true );
    
    if ( !empty($partner_id) ) {
      // Check if partner is exist
      $args = array(
        'method' => 'GET',
        'headers' => array( 
          'X-API-KEY' => b4rcp_get_api_key(),
        ),
      );
      $url = BILLINGO_API_URL .'/partners/'. $partner_id;

      $response = wp_remote_request( $url, $args );
      $response_code = wp_remote_retrieve_response_code( $response );
      $response_body = wp_remote_retrieve_body( $response );

      if ( $response_code == 200 ) {
        return $return_with_id ? $partner_id : json_decode($response_body);
      }

    }

    $this->log_partner_connection( $user_id, 'Billingo partner with this id is deleted or the could not be found. </br>Response code: '. $response_code );
    $partner = $this->create_billingo_partner( $user_id, false );
    if( $partner ) {
      update_user_meta( $user_id, 'b4rcp_partner_id', $partner->id );
      return $partner->id;
    }

    return false;

  }

  /**
   * Creates new billingo partner and returns with its partner_id
   */
  public function create_billingo_partner( $user_id, $return_with_id = true ) {
    $name = get_user_meta( $user_id, 'b4rcp_billing_name', true );
		$email = get_userdata($user_id)->user_email;
    
    $country_code = get_user_meta( $user_id, 'b4rcp_billing_address_countrycode', true );
    if ( !$country_code || strlen( $country_code ) != 2 ) { // set HU is there any is issue with given country code 
      $country_code = 'HU';
      update_user_meta( $user_id, 'b4rcp_billing_address_countrycode', $country_code );
      $this->log_partner_connection( $user_id, 'Something was wrong with the given country code so HU was set instead.' );
    }

		$address = array( 
			'country_code'  => $country_code,
			'post_code' 		=> get_user_meta( $user_id, 'b4rcp_billing_address_zipcode', true ),
			'city'					=> get_user_meta( $user_id, 'b4rcp_billing_address_city', true ),
			'address'				=> get_user_meta( $user_id, 'b4rcp_billing_address_street', true ),
		);
    $tax_number = get_user_meta( $user_id, 'b4rcp_tax_number', true );
    $tax_type = !empty($tax_number) ? 'HAS_TAX_NUMBER' : 'NO_TAX_NUMBER';

    $args = array(
      'method' => 'POST',
      'headers' => array( 
        'X-API-KEY' => b4rcp_get_api_key(),
      ),
      'body' 		=> json_encode( array( 
				'name' => $name,
				'address' => $address,
				'emails' => [$email],
        'taxcode' => $tax_number,
				'tax_type' => $tax_type,
				'custom_billing_settings' => array(
          'payment_method' => 'bankcard',
					'document_form' => 'electronic',
				),
      )),
    );
    $url = BILLINGO_API_URL .'/partners';

    $response = wp_remote_request( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

    if( $response_code == 201 ) {
      $partner = json_decode($response_body);
      $this->log_partner_connection( $user_id, 'Partner created succesfully.', $partner->id );
      return $return_with_id ? $partner->id : $partner;
    } else {
      $this->log_partner_connection( $user_id, 'Error while creating billingo partner. </br>Response code: '. $response_code .'</br>Response body: '. $response_body );
      if( $response_code == 401) {
        b4rcp_invalidate_connection();
      }
      return false;
    }

  }

  /**
   * Updates billingo partner if any realted field has chenged
   */
  public function update_billingo_partner( $user_id ) {
    $partner = $this->get_billingo_partner( $user_id, false );

    if( !$partner ) {
      return;
    }

    $name = get_user_meta( $user_id, 'b4rcp_billing_name', true );
		$email = get_userdata( $user_id )->user_email;

    $country_code = get_user_meta( $user_id, 'b4rcp_billing_address_countrycode', true );
    if ( !$country_code || strlen( $country_code ) != 2 ) { // set HU is there any is issue with given country code 
      $country_code = 'HU';
      update_user_meta( $user_id, 'b4rcp_billing_address_countrycode', $country_code );
      $this->log_partner_connection( $user_id, 'Something was wrong with the given country code so HU was set instead.' );
    }

		$address = array( 
			'country_code'  => $country_code,
			'post_code' 		=> get_user_meta( $user_id, 'b4rcp_billing_address_zipcode', true ),
			'city'					=> get_user_meta( $user_id, 'b4rcp_billing_address_city', true ),
			'address'				=> get_user_meta( $user_id, 'b4rcp_billing_address_street', true ),
		);
    $tax_number = get_user_meta( $user_id, 'b4rcp_tax_number', true );
    $tax_type = !empty($tax_number) ? 'HAS_TAX_NUMBER' : 'NO_TAX_NUMBER';

    // Returns if nothing changed
    if ( 
      $partner->name == $name &&
      $partner->emails[0] == $email && 
      $partner->taxcode == $tax_number && 
      $partner->tax_type == $tax_type && 
      $partner->address->country_code == $address['country_code'] && 
      $partner->address->post_code == $address['post_code'] && 
      $partner->address->city == $address['city'] &&
      $partner->address->address == $address['address']
    ) {
      return;
    }

    $args = array(
      'method' => 'PUT',
      'headers' => array( 
        'X-API-KEY' => b4rcp_get_api_key(),
      ),
      'body' 		=> json_encode( array( 
				'name' => $name,
				'address' => $address,
				'emails' => [$email],
				'taxcode' => $tax_number,
				'tax_type' => $tax_type,
				'custom_billing_settings' => array(
          'payment_method' => 'bankcard',
					'document_form' => 'electronic',
				),
      )),
    );
    $url = BILLINGO_API_URL .'/partners/' . $partner->id;

    $response = wp_remote_request( $url, $args );
    $response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

    if( $response_code == 200 ) {
      //$partner = json_decode($response_body);
      $this->log_partner_connection( $user_id, 'Partner updated succesfully.', $partner->id );
      return;
    } else {
      $this->log_partner_connection( $user_id, 'Error while updating billingo partner. </br>Response code: '. $response_code .'</br>Response body: '. $response_body );
      if( $response_code == 401) {
        b4rcp_invalidate_connection();
      }
      return;
    }
  }

  /**
   * Displays partner connection events log on customer screen
   */
  public function get_partner_connection_log_table( $user_id ) {
    $log = get_user_meta( $user_id, 'b4rcp_partner_connection_log', true );
    
    if( is_array($log) ) {
      $o .= '<table>';
        $o .= '<tr>';
            $o .= '<th>'. __( 'Date', 'billingo-for-rcp' ) .'</th>'; 
            $o .= '<th>'. __( 'Message', 'billingo-for-rcp' ) .'</th>'; 
            $o .= '<th>'. __( 'Partner id', 'billingo-for-rcp' ) .'</th>'; 
        $o .= '</tr>';
        foreach ($log as $key => $log_item) {
          $o .= '<tr>';
            $o .= '<td>'. $log_item['date'] .'</td>'; 
            $o .= '<td>'. $log_item['message'] .'</td>'; 
            $o .= '<td><a href="'. BILLINGO_APP_URL .'/document/list?partner_id='. $log_item['partner_id'] .'" target="_blank">'. $log_item['partner_id'] .'</a></td>'; 
          $o .= '</tr>';
        }
      $o .= '</table>';
    } else {
      $o = __( 'There is no log to display', 'billingo-for-rcp' );
    }

    return $o;
  }
  
  /**
   * Log partner connection events
   */
  public function log_partner_connection( $user_id, $message, $partner_id = 0 ) {
    $log = get_user_meta( $user_id, 'b4rcp_partner_connection_log', true );
    $log_item = array(
			'date' 	=> date('Y-m-d H:i:s'),
			'message' => $message,
      'partner_id' => $partner_id,
		);

    if( is_array( $log ) ){
      $log[] = $log_item;
    } else {
      $log = [$log_item];
    }

    update_user_meta( $user_id, 'b4rcp_partner_connection_log', $log );
  }
  
}

return B4RCP_Customers::get_instance();