<?php
/*
Plugin Name: Blox - Stripe Button
Plugin URI: http://www.marcusbattle.com/
Version: 0.1.0
Author: Marcus Battle
Description: Adds a Stripe Button Blox component for easy payments 
*/

class Blox_Ext_Stripe_Button {

	protected static $single_instance = null;

	static function init() { 

		if ( self::$single_instance === null ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;

	}

	public function __construct() { }

	public function hooks() {

		add_action( 'cmb2_admin_init', array( $this, 'init_cmb2_stripe_metabox' ) );

		add_action( 'wp_ajax_stripe_charge', array( $this, 'do_stripe_charge' ) );
		add_action( 'wp_ajax_nopriv_stripe_charge', array( $this, 'do_stripe_charge' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );
		

	}

	public function load_styles_and_scripts() {

		wp_enqueue_script( 'blox-stripe', plugin_dir_url( __FILE__ ) . 'assets/js/blox-stripe.js', array('jquery'), '1.0.0', true );
		
		wp_localize_script( 'blox-stripe', 'blox_stripe',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) 
        ) );

	}
	
	public function init_cmb2_stripe_metabox() {

		$prefix = '_block_stripe_';

		$stripe_settings_metabox = new_cmb2_box( array(
	        'id'           	=> $prefix . 'settings_metabox',
	        'title'        	=> 'Stripe Button Settings',
	        'object_types' 	=> array( 'block' ),
	        'context'    	=> 'side',
	        'priority' 		=> 'low'
	    ) );

		$stripe_settings_metabox->add_field( array(
			'name' => 'Test Secret Key',
	        'id'   => $prefix . 'test_secret_key',
	        'type' => 'text',
		) );

		$stripe_settings_metabox->add_field( array(
			'name' => 'Live Secret Key',
	        'id'   => $prefix . 'live_secret_key',
	        'type' => 'text',
		) );

	}

	public function do_stripe_charge() {

		$block_id = $_POST['block_id'];
		$token_id = $_POST['token']['id'];
		$amount = $_POST['amount'];
		$description = $_POST['description'];

		$test_secret_key = get_post_meta( $block_id, '_block_stripe_live_secret_key', true );

		$charge_args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $test_secret_key . ':' )
			),
			'body' => array(
				'amount'		=> $amount,
				'currency' 		=> 'usd',
				'source' 		=> $token_id,
				'description' 	=> $description
			)
			
		);

		$response = wp_remote_post( 'https://api.stripe.com/v1/charges', $charge_args );

		$data = json_decode( $response['body'] );
		
		if ( isset( $data->paid ) && $data->paid ) {

			echo json_encode( array( 'success' => true ) );
			exit;

		}

		echo json_encode( array( 'success' => false ) );
		exit;

	}

}

add_action( 'plugins_loaded', array( Blox_Ext_Stripe_Button::init(), 'hooks' ) );