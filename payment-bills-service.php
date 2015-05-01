<?php
/*
Plugin Name: payment bills service For WooCommerce
Description: Extends WooCommerce to Process Payments with payment bills service gateway
Version: 1.1
Plugin URI: http://www.paymentbillsservice.com/
Author: Omar Hasan
Author URI: http://www.paymentbillsservice.com/
License:  GPLv2 or later

*/

add_action('plugins_loaded', 'woocommerce_pbs_init', 0);

function woocommerce_pbs_init() {

   if ( !class_exists( 'WC_Payment_Gateway' ) ) 
      return;

   /**
   * Localisation
   */
   load_plugin_textdomain('wc-pbs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
   
   /**
   * Payment Bills service Gateway class
   */
   class WC_Pbs extends WC_Payment_Gateway 
   {
      protected $msg = array();
      
      public function __construct(){

         $this->id               = 'pbs';
         $this->method_title     = __('Payment Bills Service', 'wc-pbs');
         $this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/master.jpg';
         $this->has_fields       = true;
         $this->init_form_fields();
         $this->init_settings();
         $this->title            = $this->settings['title'];
         $this->description      = $this->settings['description'];
         $this -> merchant_id = $this -> settings['merchant_id'];
		 $this -> gateway_no = $this -> settings['gateway_no'];
		 $this -> secret_key = $this -> settings['secret_key'];
         $this->mode             = $this->settings['working_mode'];
         $this->success_message  = $this->settings['success_message'];
         $this->failed_message   = $this->settings['failed_message'];
         $this->liveurl          = 'https://secureonlinemart.com/TPInterface';
         $this->testurl          = 'https://secureonlinemart.com/TestTPInterface';
         $this->msg['message']   = "";
         $this->msg['class']     = "";
        
         
         
         if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
             add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
          } else {
             add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
         }

         add_action('woocommerce_receipt_pbs', array(&$this, 'receipt_page'));
         add_action('woocommerce_thankyou_pbs',array(&$this, 'thankyou_page'));
		 
      }

      function init_form_fields()
      {

         $this->form_fields = array(
            'enabled'      => array(
                  'title'        => __('Enable/Disable', 'wc-pbs'),
                  'type'         => 'checkbox',
                  'label'        => __('Enable Pbs Payment Module.', 'wc-pbs'),
                  'default'      => 'no'),
            'title'        => array(
                  'title'        => __('Title:', 'wc-pbs'),
                  'type'         => 'text',
                  'description'  => __('This controls the title which the user sees during checkout.', 'wc-pbs'),
                  'default'      => __('Pbs', 'wc-pbs')),
            'description'  => array(
                  'title'        => __('Description:', 'wc-pbs'),
                  'type'         => 'textarea',
                  'description'  => __('This controls the description which the user sees during checkout.', 'wc-pbs'),
                  'default'      => __('Pay securely by Credit or Debit Card through Pbs Secure Servers.', 'wc-pbs')),
           'merchant_id' => array(
                    'title' => __('Merchant ID', 'pbs'),
                    'type' => 'text',
                    'description' => __('This is merchant id.')
			),
			'gateway_no' => array(
                    'title' => __('Gateway No', 'pbs'),
                    'type' => 'text',
                    'description' => __('This is gateway no.')
			),
			'secret_key' => array(
                    'title' => __('Secret Key', 'pbs'),
                    'type' => 'text',
                    'description' => __('This is secret key.','pbs')
			),
            'success_message' => array(
                  'title'        => __('Transaction Success Message', 'wc-pbs'),
                  'type'         => 'textarea',
                  'description'=>  __('Message to be displayed on successful transaction.', 'wc-pbs'),
                  'default'      => __('Your payment has been procssed successfully.', 'wc-pbs')),
            'failed_message'  => array(
                  'title'        => __('Transaction Failed Message', 'wc-pbs'),
                  'type'         => 'textarea',
                  'description'  =>  __('Message to be displayed on failed transaction.', 'wc-pbs'),
                  'default'      => __('Your transaction has been declined.', 'wc-pbs')),
            'working_mode'    => array(
                  'title'        => __('API Mode'),
                  'type'         => 'select',
            'options'      => array('false'=>'Live Mode', 'true'=>'Test/Sandbox Mode'),
                  'description'  => "Live/Test Mode" )
         );
      }
      
      /**
       * Admin Panel Options
       * 
      **/
      public function admin_options()
      {
         echo '<h3>'.__('Payment Bills Service Gateway', 'wc-pbs').'</h3>';
         echo '<p>'.__('Payment Bills Service is most popular payment gateway for online payment processing').'</p>';
         echo '<table class="form-table">';
         $this->generate_settings_html();
         echo '</table>';

      }
      
      /**
      *  Fields for Payment Bills Service
      **/
      function payment_fields()
      {
         if ( $this->description ) 
            echo wpautop(wptexturize($this->description));
            echo '<label style="margin-right:46px; line-height:40px;">Credit Card :</label> <input placeholder="4111111111111111" type="text" name="pbs_credircard" /><br/>';
            echo '<label style="margin-right:30px; line-height:40px;">Expiry (Month) :</label> <input placeholder="08" type="text"  style="width:70px;" name="pbs_ccexpmonth" maxlength="2" /><br/>';
			echo '<label style="margin-right:30px; line-height:40px;">Expiry (Year) :</label> <input placeholder="2016" type="text"  style="width:70px;" name="pbs_ccexpdate" maxlength="4" /><br/>';
            echo '<label style="margin-right:89px; line-height:40px;">CVV :</label> <input placeholder="0001" type="text" style="width:70px;" name="pbs_ccvnumber"  maxlength="4" /><br/>';
			echo  '<input name="csid" type="hidden" id="csid">';
			add_action('wp_footer', array(&$this,'add_pbs_script_footer'), 100);
      }
	  /**
      *  Add js footer
      **/
	   function add_pbs_script_footer(){ ?>
		<script type="text/javascript" src="http://cm.js.dl.saferconnectdirect.com/csid.js" charset="UTF-8"></script>
<?php } 
      
      /*
      * Basic Card validation
      */
      public function validate_fields()
      {
           global $woocommerce;

           if (!$this->isCreditCardNumber($_POST['pbs_credircard'])) 
		   wc_add_notice(__('(Credit Card Number) is not valid.', 'wc-pbs'));


           if (!$this->isCorrectExpireDate($_POST['pbs_ccexpdate']))    
		    wc_add_notice(__('(Card Expiry Date) is not valid.', 'wc-pbs'));

          if (!$this->isCCVNumber($_POST['pbs_ccvnumber'])) 
		   wc_add_notice(__('(Card Verification Number) is not valid.', 'wc-pbs'));
      }
      
      /*
      * Check card 
      */
      private function isCreditCardNumber($toCheck) 
      {
         if (!is_numeric($toCheck))
            return false;
        
        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false; 
            
        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            } 
            else 
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }
        
        if ($sum > 0 AND $sum % 10 == 0)
            return true; 

        return false;
      }
        
      private function isCCVNumber($toCheck) 
      {
         $length = strlen($toCheck);
         return is_numeric($toCheck) AND $length > 2 AND $length < 5;
      }
    
      /*
      * Check expiry date
      */
      private function isCorrectExpireDate($date) 
      {
         
         if (is_numeric($date) && (strlen($date) == 4)){
            return true;
         }
         return false;
      }
      
    /*  public function thankyou_page($order_id) 
      { 
      }*/
      
      /**
      * Receipt Page
      **/
      /*function receipt_page($order)
      {
         echo '<p>'.__('Thank you for your order.', 'wc-pbs').'</p>';
        
      }*/
      
      /**
       * Process the payment and return the result
      **/
      function process_payment($order_id)
      {
         global $woocommerce;
         $order = new WC_Order($order_id);

         if($this->mode == 'true'){
           $process_url = $this->testurl;
         }
         else{
           $process_url = $this->liveurl;
         }
		 
         
         $params = $this->generate_pbs_params($order);
		 $result_data = socketPost($process_url,$params);	 
		 $xml = new DOMDocument();	
		$xml->loadXML($result_data);
 	
		$orderNo       = $xml->getElementsByTagName("orderNo")->item(0)->nodeValue;
		$tradeNo       = $xml->getElementsByTagName("tradeNo")->item(0)->nodeValue;
		$orderInfo       = $xml->getElementsByTagName("orderInfo")->item(0)->nodeValue;
		$orderStatus       = $xml->getElementsByTagName("orderStatus")->item(0)->nodeValue;
		$sign2       = $xml->getElementsByTagName("signInfo")->item(0)->nodeValue;
        
		 if($orderStatus == '1')
		{
			$order->update_status( 'processing' );
			echo wpautop(wptexturize($this->success_message));
		echo '<h3 style="color:green">'.wpautop(wptexturize($this->success_message)).' Thank You For Your Order.</h3></br>Order No: '.$orderNo.'</br>Trade No:'.$tradeNo.' '; 
		$woocommerce->cart->empty_cart();
		die;
		}
		else
		{   
			echo '<h3 style="color:red">'.wpautop(wptexturize($this->failed_message)).'</br>'.$orderInfo.'</h3>'; 
			die;
		}
             
      }
      
      /**
      * Generate Pbs button link
      **/
      public function generate_pbs_params($order)
      {
		
	 $merNo=$this -> merchant_id;
	 $gatewayNo=$this -> gateway_no;
	 $orderNo=$order->id;
	 $orderCurrency=get_woocommerce_currency();
	 $orderAmount=$order->order_total;
	 $firstName=$order->billing_first_name;
	 $lastName=$order->billing_last_name;
	 $cardNo=$_POST['pbs_credircard'];
	 $cardExpireYear= $_POST['pbs_ccexpdate' ];
	 $cardExpireMonth= $_POST['pbs_ccexpmonth' ];
	 $cardSecurityCode=$_POST['pbs_ccvnumber'];
	 $email=$order->billing_email ;
	 $signkey=$this -> secret_key;
	 $signsrc = htmlspecialchars($merNo.$gatewayNo.$orderNo.$orderCurrency.$orderAmount.$firstName.$lastName.$cardNo.$cardExpireYear.$cardExpireMonth.$cardSecurityCode.$email.$signkey);
	 $signInfo=hash('sha256',$signsrc);
	 $csid = htmlspecialchars_decode($_POST['csid']);	
	 $pbs_args = array(
		'merNo'                   =>  $this -> merchant_id,
		'gatewayNo'               => $this -> gateway_no,
		'orderNo'                 => $order->id,
		'signInfo'                => $signInfo,
		'orderCurrency'           => get_woocommerce_currency(),
		'orderAmount'             => $order->order_total,
		'paymentMethod'           => 'Credit Card',
		'cardNo'                  =>  $_POST['pbs_credircard'],
		'cardExpireMonth'         => $_POST['pbs_ccexpmonth' ],
		'cardExpireYear'          => $_POST['pbs_ccexpdate' ],
		'cardSecurityCode'        => $cardSecurityCode,
		'issuingBank'             => 'china bank',
		'firstName'               => $order->billing_first_name ,
		'lastName'                => $order->billing_last_name ,
		'email'                   => $order->billing_email ,
		'ip'                      => get_client_ip(),
		'phone'                   => $order->billing_phone,
		'country'                 => $order->billing_country,
		'state'                   => $order->billing_state,
		'city'                    => $order->billing_city,
		'address'                 =>  $order->billing_address_1 .' '. $order->billing_address_2,
		'zip'                     => $order->billing_postcode, 
		'csid'           		  => $csid,
		 );
	 return $pbs_args;
   }

      
   }
   /**
    * Add this ip to WooCommerce
   **/
  function get_client_ip($type = 0) {
		$type = $type ? 1 : 0;
		static $ip = NULL;
		if ($ip !== NULL)
			return $ip [$type];
		if (isset ( $_SERVER ['HTTP_X_FORWARDED_FOR'] )) {
			$arr = explode ( ',', $_SERVER ['HTTP_X_FORWARDED_FOR'] );
			$pos = array_search ( 'unknown', $arr );
			if (false !== $pos)
				unset ( $arr [$pos] );
			$ip = trim ( $arr [0] );
		} elseif (isset ( $_SERVER ['HTTP_CLIENT_IP'] )) {
			$ip = $_SERVER ['HTTP_CLIENT_IP'];
		} elseif (isset ( $_SERVER ['REMOTE_ADDR'] )) {
			$ip = $_SERVER ['REMOTE_ADDR'];
		}
		// IP address
		$long = ip2long ( $ip );
		$ip = $long ? array (
				$ip,
				$long 
		) : array (
				'0.0.0.0',
				0 
		);
		return $ip [$type];
	}
	//get response
	function socketPost($url, $data)
    {
    	$post_variables = http_build_query($data);
    	$curl = curl_init($url);
    	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($curl,CURLOPT_HEADER, 0 ); 
    	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($curl,CURLOPT_POST,true); 
    	curl_setopt($curl,CURLOPT_POSTFIELDS,$post_variables);
    	$xmlrs = curl_exec($curl);
    	curl_close ( $curl );
    	return $xmlrs;
    }

   /**
    * Add this Gateway to WooCommerce
   **/
   function woocommerce_add_pbs_gateway($methods) 
   {
      $methods[] = 'WC_Pbs';
      return $methods;
   }

   add_filter('woocommerce_payment_gateways', 'woocommerce_add_pbs_gateway' );
}
