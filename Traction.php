<?php
/**
 * Class Traction.
 *
 * PHP api wrapper for Traction
 *
 * @author Rufus Post rufuspost@gmail.com
 * @date 02/12/2009
 *
 * @Modified by Simon Hoye
 * @date 04/03/2011
 */

class Traction {

/**
 * Traction gateway url
 *
 * @var string
 * @access public
 */
        public $gateway = 'au.api.tractionplatform.com/ext/';

/**
 * Traction response code 0 for success
 *
 * @var int
 * @access public
 */
        public $tracCode;

/**
 * Error returned by traction
 *
 * @var string
 * @access public
 */
        public $tracError;

/**
 * Curl transfer statistics
 *
 * @var array
 * @access public
 */
        public $transfer;

/**
 * Raw response headers
 *
 * @var string
 * @access public
 */
        public $response;

/**
 * Decoded headers
 *
 * @var array
 * @access public
 */
        public $headers;

/**
 * Endpoint data for traction auth
 *
 * @var array
 * @access public
 */
        public $endpoint = array();

/**
 * Whether to pass test string to traction
 *
 * @var boolean
 * @access private
 */
        public $test = false;

/**
 * Email Matchkey to be passed to traction function
 *
 * @var string
 * @access public
 */
        public $endpointEmail;

/**
 * Returns traction id if function successfull
 *
 * @var int
 * @access public
 */
        public $lastCustomerId;

/**
 * ssl
 *
 * @var boolean
 * @access public
 */
        public $secure = true;

/**
 * Initalise http transaction with traction using curl
 *
 * @param array $data   Array of parameters to required by Traction API
 * @param string $function  Traction Api to call
 * @access private
 * @static
 */
        private function initalise($data, $function) {
                $http = $this->secure?'https://':'http://';
                $url = $http.$this->gateway.$function;
                $post_data = $this->endpointEncode($this->endpointEmail).'&'.$this->dataEncode($data,$function);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);    
                curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Add this because of verification error during local dev possibly caused by an old MAMP installation
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                curl_setopt($ch, CURLOPT_SSLVERSION, 3);
                $this->response =curl_exec($ch);
                
                $this->transfer = curl_getinfo($ch);

                
                if (is_bool($this->response)) {
                        if ($this->response==false){
                                throw new Exception('No connection: ' . curl_error($ch));
                        } else {
                                $this->response=null;
                        }
                }
                if($this->response) $this->decodeResponse();

                curl_close($ch);
        }

/**
 * Decode Traction response
 *
 * @access private
 */
        private function decodeResponse() {
                $headers = array();
                $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $this->response));
                foreach( $fields as $field ) {
                        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                                if( isset($headers[$match[1]]) ) {
                                        $headers[$match[1]] = array($headers[$match[1]], $match[2]);
                                } else {
                                        $headers[$match[1]] = trim($match[2]);
                                }
                        }
                }
                $this->headers = $headers;
                $this->tracCode = $headers['Trac-Result'];
                if(isset($headers['Trac-Customerid'])) {
                    $this->lastCustomerId = $headers['Trac-Customerid'];
                }
                if(isset($headers['Trac-Error'])) {
                        $this->tracError = $headers['Trac-Error'];
                }
        }

/**
 * Create html encoded endpoint data
 *
 * @param string $email Email address for Traction Endpoint
 * @return string Html encoded data
 * @access private
 */
        private function endpointEncode($email) {

                if(empty($this->endpoint)) return false;

                if(!isset($this->endpoint['MATCHKEY'])) {
                        $this->endpoint['MATCHKEY'] = 'E';
                        $this->endpoint['MATCHVALUE'] = $email;
                }

                foreach($this->endpoint as $key => $val) {
                        $endpoint[strtoupper($key)]=$val;
                }
                if($this->test) $endpoint['TEST'] = '1';

                return http_build_query($endpoint, '', '&');
        }

/**
 * Create html encoded data
 *
 * @param array $data   Array of strings to encode
 * @param string $function    Which API function are we posting to
 * @return string Html encoded data
 * @access private
 */
        private function dataEncode($data, $function) {
                
                switch($function) {
                        case 'AddCustomer' :
                                $encData = $this->customerEncode($data);
                                return http_build_query(array(strtoupper('CUSTOMER')=>implode(chr(31), $encData)));
                                break;
                        case 'MultiSubscribe':
                                return http_build_query($data, '', '&');
                                break;
                        case 'Promotion':
                                return http_build_query($data, '', '&');
                                break;
                        case 'S2FS':
                                
                                return http_build_query($data, '', '&');
                                break;
                }                
        }

/**
 * Encode Customer Data
 *
 * @param array $data   Array of customer data to encode
 * @return array
 */

        private function customerEncode($data) {

                foreach($data as $key => $val) {
                        $encData[]=strtoupper($key).'|'.$val;
                }

                return $encData;
        }

/**
 * Call AddCustomer API
 *
 * @param array $data   Array of strings being sent to Traction API
 * @access public
 */
        public function AddCustomer($data) {
                $this->endpointEmail = $data['email'];

                try {
                        $this->initalise($data, 'AddCustomer');
                } catch (Exception $e) {
                        throw $e;
                }
                
        }



/**
 * Call MultipleSubscribe API
 *
 * @param array $data   Array of strings being passed to Traction API
 * @param string $customerEmail  Customers Email address for Endpoint Match Key
 * @access public
 */
 
        public function MultipleSubscribe($data, $customerEmail, $customerData = null) {
                
                $this->endpointEmail = $customerEmail;

                // If customer data exists encode and add to data array
                if($customerData) {
                        $encData = $this->customerEncode($customerData);
                        $data['CUSTOMER'] = implode(chr(31), $encData);     
                }

                try {
                        $this->initalise($data, 'MultiSubscribe');   
                } catch (Exception $e) {
                        throw $e;
                }
                

        }


/**
 * Call Promotion API
 *
 * @param array $data   Array of strings being passed to Traction API
 * @param array $customerData  Customer data to pass to Traction if customer needs to be Added/Updated
 * @access public
 */
        public function EnterPromotion($data, $customerData = null) {
                
                // If customer data exists encode and add to data array
                if($customerData) {
                        $encData = $this->customerEncode($customerData);
                        $data['CUSTOMER'] = implode(chr(31), $encData);
                        $this->endpointEmail = $customerData['EMAIL'];     
                }

                try {
                        $this->initalise($data, 'Promotion');
               
                } catch (Exception $e) {
                        
                        throw $e; 
                }
                
        }

 

/**
 * Call SendToFriend API
 *
 * @param array $sender
 * @param array $recipients
 * @access public
 */                    

        public function SendToFriend($sender, $recipients, $type) {

                $encSender = $this->customerEncode($sender);
                $data['SENDER'] = implode(chr(31), $encSender);
                $data['TYPE'] = $type;

                for($i = 0; $i < sizeof($recipients); $i++) {
                        $encRecipient = $this->customerEncode($recipients[$i]);
                        $name = 'RECIPIENT'.((string)$i + 1);
                        $data[$name] = implode(chr(31), $encRecipient);
                }
                   
                try {
                        $this->initalise($data, 'S2FS');
                } catch (Exception $e) {
                        throw $e;
                }
        }

/**
 * Call RSS Update API
 *
 *
 * @access public
 */

        public function RSSUpdate() {
                // TODO: connect to RSS Update API

        }



}  
?>
