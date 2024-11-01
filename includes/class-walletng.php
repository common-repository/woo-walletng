<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tbz_WC_Wallet_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id		   			= 'walletng';
		$this->method_title 	    = 'wallet.ng';
		$this->has_fields 	    	= false;

		// Load the form fields
		$this->init_form_fields();

		// Load the settings
		$this->init_settings();

		// Get setting values
		$this->title 				= 'wallet.ng';
		$this->description 			= $this->get_option( 'description' );
		$this->enabled            	= $this->get_option( 'enabled' );

		$this->testmode				= $this->get_option( 'testmode' );

		$this->payment_page  		= $this->get_option( 'payment_page' );

		$this->payment_logo 		= $this->get_option( 'payment_logo' );

		$this->test_public_key  	= $this->get_option( 'test_public_key' );
		$this->test_secret_key  	= $this->get_option( 'test_secret_key' );

		$this->live_public_key  	= $this->get_option( 'live_public_key' );
		$this->live_secret_key  	= $this->get_option( 'live_secret_key' );

		$this->public_key      		= $this->testmode === 'yes' ? $this->test_public_key : $this->live_public_key;
		$this->secret_key      		= $this->testmode === 'yes' ? $this->test_secret_key : $this->live_secret_key;

		$this->test_payment_url 	= 'https://fences.wallet.ng/transactions/new';
		$this->test_query_url 		= 'https://fences.wallet.ng/transactions/details';

		$this->live_payment_url 	= 'https://bethel.wallet.ng/transactions/new';
		$this->live_query_url		= 'https://bethel.wallet.ng/transactions/details';

		$this->payment_url 			= $this->testmode === 'yes' ? $this->test_payment_url : $this->live_payment_url;

		$this->query_url 			= $this->testmode === 'yes' ? $this->test_query_url : $this->live_query_url;

		$this->notify_url        	= WC()->api_request_url( 'Tbz_WC_Wallet_Gateway' );

		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Payment listener/API hook
		add_action( 'woocommerce_api_tbz_wc_wallet_gateway', array( $this, 'verify_transaction' ) );

		// Check if the gateway can be used
		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = false;
		}

	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 */
	public function is_valid_for_use() {

		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'wc_wallet_supported_currencies', array( 'NGN' ) ) ) ) {

			$this->msg = 'wallet.ng does not support your store currency. Kindly set it to NGN (&#8358) <a href="' . admin_url( 'admin.php?page=wc-settings&tab=general' ) . '">here</a>';

			return false;

		}

		return true;

	}

	/**
	 * Display wallet.ng payment icon
	 */
	public function get_icon() {

		$icon  = '<img src="' . WC_HTTPS::force_https_url( plugins_url( 'assets/images/walletng.png' , TBZ_WC_WNG_MAIN_FILE ) ) . '" alt="payment method" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );

	}

	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {

		if ( $this->enabled == "yes" ) {

			if ( ! ( $this->public_key && $this->secret_key ) ) {

				return false;

			}

			return true;

		}

		return false;

	}

    /**
     * Admin Panel Options
    */
    public function admin_options() {

    	?>

    	<h3>wallet.ng</h3>

        <?php

		if ( $this->is_valid_for_use() ){

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';

        }
		else {	 ?>
			<div class="inline error"><p><strong>wallet.ng payment gateway Disabled</strong>: <?php echo $this->msg ?></p></div>

		<?php }

    }

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable',
				'label'       => 'Enable wallet.ng Payment Gateway',
				'type'        => 'checkbox',
				'description' => 'Enable wallet.ng as a payment option on the checkout page.',
				'default'     => 'no',
				'desc_tip'    => true
			),
			'description' => array(
				'title' 		=> 'Description',
				'type' 			=> 'textarea',
				'description' 	=> 'This controls the payment method description which the user sees during checkout.',
    			'desc_tip'      => true,
				'default' 		=> 'Complete payment using your wallet account, bank account & local or international debit card'
			),
			'testmode' => array(
				'title'       => 'Test mode',
				'label'       => 'Enable Test Mode',
				'type'        => 'checkbox',
				'description' => 'Test mode enables you to test payments before going live. <br />Once you want to start accepting real payment uncheck this.',
				'default'     => 'yes',
				'desc_tip'    => true
			),
			'test_public_key' => array(
				'title'       => 'Test Public Key',
				'type'        => 'text',
				'description' => 'Enter your Test Public Key here.',
				'default'     => ''
			),
			'test_secret_key' => array(
				'title'       => 'Test Secret Key',
				'type'        => 'text',
				'description' => 'Enter your Test Secret Key here',
				'default'     => ''
			),
			'live_public_key' => array(
				'title'       => 'Live Public Key',
				'type'        => 'text',
				'description' => 'Enter your Live Public Key here.',
				'default'     => ''
			),
			'live_secret_key' => array(
				'title'       => 'Live Secret Key',
				'type'        => 'text',
				'description' => 'Enter your Live Secret Key here.',
				'default'     => ''
			),
			'payment_page' => array(
				'title'       => 'Payment Page',
				'type'        => 'select',
				'description' => 'Inline shows the payment popup directly on your website while Redirect takes you to wallet.ng to make the payment.',
				'default'     => '',
				'desc_tip'    => false,
				'options'     => array(
					'inline'   	=> 'Inline',
					'redirect' 	=> 'Redirect'
				)
			),
			'payment_logo' => array(
				'title'       => 'Payment Logo',
				'type'        => 'text',
				'description' => 'Enter the link to a image to be displayed on the payment popup',
				'default'     => ''
			)
		);

	}

	/**
	 * Outputs scripts used for paystack payment
	 */
	public function payment_scripts() {

		if ( ! is_checkout_pay_page() ) {
			return;
		}

		if ( 'inline' != $this->payment_page ) {
			return;
		}

		if ( $this->enabled === 'no' ) {
			return;
		}

		$order_key 		= urldecode( $_GET['key'] );
		$order_id  		= absint( get_query_var( 'order-pay' ) );

		$order  		= wc_get_order( $order_id );

		$payment_method = method_exists( $order, 'get_payment_method' ) ? $order->get_payment_method() : $order->payment_method;

		if ( $this->id !== $payment_method ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'jquery' );

		if ( $this->testmode === 'yes' ) {

			wp_enqueue_script( 'walletng', 'https://docs.wallet.ng/wallet-inline-test.js', array( 'jquery' ), TBZ_WC_WNG_VERSION, false );

		} else {

			wp_enqueue_script( 'walletng', 'https://docs.wallet.ng/wallet-inline.js', array( 'jquery' ), TBZ_WC_WNG_VERSION, false );

		}

		wp_enqueue_script( 'wc_walletng', plugins_url( 'assets/js/wallet'. $suffix . '.js', TBZ_WC_WNG_MAIN_FILE ), array( 'jquery', 'walletng' ), TBZ_WC_WNG_VERSION, false );

		$wallet_params = array(
			'public_key' => $this->public_key
		);

		if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {

			$first_name  	= method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
			$last_name  	= method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;

			$email  		= method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;

			$transaction_id = 'WC|' . uniqid() .'|'. $order_id;

			$amount 		= $order->get_total();

			$the_order_id 	= method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
	        $the_order_key 	= method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->order_key;

	        $description    = 'Payment for Order ID: ' . $order_id . ' on ' . get_bloginfo( 'name' );

	        $logo 			= $this->payment_logo;

			if ( $the_order_id == $order_id && $the_order_key == $order_key ) {

				$wallet_params['amount']    = $amount;
				$wallet_params['name']  	= $first_name . ' ' . $last_name;
				$wallet_params['email']     = $email;
				$wallet_params['txn_id']    = $transaction_id;
				$wallet_params['desc']      = $description;
				$wallet_params['logo']      = $logo;

			}

			update_post_meta( $order_id, '_wc_wallet_txn_id', $transaction_id );

		}

		wp_localize_script( 'wc_walletng', 'tbz_wc_wallet_params', $wallet_params );

	}

	/**
	 * Load admin scripts
	 */
	public function admin_scripts() {

		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'jquery' );

		wp_enqueue_script( 'wc_wallet_admin', plugins_url( 'assets/js/wallet-admin' . $suffix . '.js', TBZ_WC_WNG_MAIN_FILE ), array( 'jquery' ), TBZ_WC_WNG_VERSION, true );

	}

	/**
	 * Process the payment
	 */
	public function process_payment( $order_id ) {

		if ( 'inline' == $this->payment_page ) {

			$order = wc_get_order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);

		} else {

			$response = $this->get_payment_link( $order_id );

			if ( 'success' == $response['result'] ) {

		        return array(
		        	'result' 	=> 'success',
					'redirect'	=> $response['redirect']
		        );

			} else {

				wc_add_notice( 'Unable to connect to the payment gateway, please try again.', 'error' );

		        return array(
		        	'result' 	=> 'fail',
					'redirect'	=> ''
		        );

			}

		}

	}

	/**
	 * Displays the payment page
	 */
	public function receipt_page( $order_id ) {

		$order = wc_get_order( $order_id );

		echo '<p>Thank you for your order, please click the button below to pay with wallet.ng.</p>';

		echo '<div id="wc_wallet_form"><form id="order_review" method="post" action="'. WC()->api_request_url( 'Tbz_WC_Wallet_Gateway' ) .'"></form><button class="button alt" id="tbz-wc-wallet-payment-button">Pay Now</button> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">Cancel order &amp; restore cart</a></div>
			';

	}

    /**
     * Get wallet.ng payment link
    **/
	public function get_payment_link( $order_id ) {

		$order = wc_get_order( $order_id );

		$first_name  	= method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name() : $order->billing_first_name;
		$last_name  	= method_exists( $order, 'get_billing_last_name' ) ? $order->get_billing_last_name() : $order->billing_last_name;

		$email  		= method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;

		$billing_phone  = method_exists( $order, 'get_billing_phone' ) ? $order->get_billing_phone() : $order->billing_phone;

		$transaction_id = 'WC|' . uniqid() .'|'. $order_id;

		$body = array(
			'MerchantReference' 	=> $transaction_id,
			'Amount'				=> $order->get_total(),
			'Name'					=> $first_name . ' ' . $last_name,
			'Email'					=> $email,
			'PhoneNumber'			=> $billing_phone,
			'RedirectUrl'			=> $this->notify_url,
			'SecretKey'				=> $this->secret_key
		);

		update_post_meta( $order_id, '_wc_walletng_txn_id', $transaction_id );

		$headers = array(
			'Content-Type'	=> 'application/json',
			'Authorization' => 'Bearer ' . $this->public_key
		);

	    $args = array(
			'headers'	=> $headers,
			'body'		=> json_encode( $body ),
	        'timeout'   => 60
	    );

    	$request = wp_remote_post( $this->payment_url, $args );

    	$response_code = wp_remote_retrieve_response_code( $request );

      	if ( ! is_wp_error( $request ) && in_array( $response_code, array( 200, 202 ) ) ) {

            $body = json_decode( wp_remote_retrieve_body( $request ) );

	        $response = array(
	        	'result'	=> 'success',
	        	'redirect'	=> $body->Message
	        );

      	} else {

	        $response = array(
	        	'result'	=> 'fail',
	        	'redirect'	=> ''
	        );

      	}

	    return $response;

	}

	/**
	 * Verify wallet.ng payment
	 */
	public function verify_transaction() {

		@ob_clean();

		if ( isset( $_REQUEST['MerchantRef'] ) || ( $_REQUEST['tbz_wc_wallet_txnref'] ) ) {

			if ( isset( $_REQUEST['MerchantRef'] ) ) {
				$txn_ref = $_REQUEST['MerchantRef'];
			}

			if ( isset( $_REQUEST['tbz_wc_wallet_txnref'] ) ) {
				$txn_ref = $_REQUEST['tbz_wc_wallet_txnref'];
			}

			$txn_details    = $this->get_transaction_details( $txn_ref );

			if ( '200' == $txn_details['ResponseCode'] && '100' == $txn_details['TransactionStatusCode'] ) {

				$order_details 	= explode( '|', $txn_ref );

				$order_id 		= (int) $order_details[2];

		        $order 			= wc_get_order( $order_id );

		        if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {

		        	wp_redirect( $this->get_return_url( $order ) );

					exit;

		        }

        		$order_total		= $order->get_total();

        		$amount_paid		= number_format( $txn_details['Amount'], 2, '.', '' );

        		if ( isset( $_REQUEST['tbz_wc_wallet_walletref'] ) ) {
					$wallet_txn_ref = $_REQUEST['tbz_wc_wallet_walletref'];
        		}

        		if ( isset( $_REQUEST['Ref'] ) ) {
					$wallet_txn_ref = $_REQUEST['Ref'];
        		}

				// check if the amount paid is less than the order amount.
				if ( $amount_paid < $order_total ) {

					$order->update_status( 'on-hold', '' );

					add_post_meta( $order_id, '_transaction_id', $wallet_txn_ref, true );

					$notice = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
					$notice_type = 'notice';

					// Add Customer Order Note
                    $order->add_order_note( $notice, 1 );

                    // Add Admin Order Note
                    $order->add_order_note( '<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>&#8358;' . $amount_paid . '</strong> while the total order amount is <strong>&#8358;' . $order_total . '</strong><br />wallet.ng Transaction Reference: ' . $wallet_txn_ref );

                    if( function_exists( 'wc_reduce_stock_levels' ) ) {

                    	wc_reduce_stock_levels( $order_id );

                    } else {

						$order->reduce_order_stock();

                    }

					wc_add_notice( $notice, $notice_type );

				} else {

					$order->payment_complete( $wallet_txn_ref );

					$order->add_order_note( sprintf( 'Payment via wallet.ng successful (Reference: %s)', $wallet_txn_ref ) );

				}

				wc_empty_cart();

			} else {

				$order_details 	= explode( '|', $txn_ref );

				$order_id 		= (int) $order_details[2];

		        $order 			= wc_get_order( $order_id );

				$order->update_status( 'failed', 'Payment failed.' );

			}

			wp_redirect( $this->get_return_url( $order ) );

			exit;
		}

		wp_redirect( wc_get_page_permalink( 'cart' ) );

		exit;

	}

	/**
	 * Validate a wallet.ng payment
	 */
	public function get_transaction_details( $txn_ref ) {

		$headers = array(
			'Content-Type'	=> 'application/json',
			'Authorization' => 'Bearer ' . $this->public_key
		);

		$body = array(
			'SecretKey'				=> $this->secret_key,
			'TransactionReference'	=> $txn_ref
		);

		$args = array(
			'headers'	=> $headers,
			'body'		=> json_encode( $body ),
			'timeout'	=> 90
		);

		$request = wp_remote_post( $this->query_url, $args );

      	if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

      		$response = json_decode( wp_remote_retrieve_body( $request ) );

      	} else {

      		$response['ResponseCode'] = '400';
      		$response['ResponseDescription'] = 'Can\'t verify payment. Contact us for more details about the order and payment status.';

      	}

      	return (array) $response;

	}

}