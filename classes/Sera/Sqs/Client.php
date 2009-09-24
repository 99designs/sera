<?php
//*********************************************************************************************************************
// Copyright 2008 Amazon Technologies, Inc.
// Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in
// compliance with the License.
//
// You may obtain a copy of the License at:http://aws.amazon.com/apache2.0  This file is distributed on
// an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//
// See the License for the specific language governing permissions and limitations under the License.
//*********************************************************************************************************************

//
// This a modified version of Justin@AWS Sera_Sqs_Client file you may find here:
// http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1180
//
// Modiciations are minor. The library doesn't rely on PEAR Crypt_HMAC and HTTP_Request anymore.

class Sera_Sqs_Client {

	const ENDPOINT = 'http://queue.amazonaws.com';

	public $queueName;
	private $accessKey;
	private $secretKey;
	private $endpoint;
	private $activeQueueURL;
	private $fetchFunction;

	/**
	 * Constructor
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $accessKey
	 * @param string $secretKey
	 * @param string $endpoint	http://queue.amazonaws.com
	 * @param string $queueName	optional
	 * @return Sera_Sqs_Client
	 */
	public function __construct($accessKey, $secretKey, $queueName = '', $endpoint = self::ENDPOINT)
	{
		// Save properties
		$this->queueName = $queueName;
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
		$this->endpoint = $endpoint;

		// Set queue name
		if($queueName == '')
		{
			$this->setactiveQueueURL($endpoint);
		} else {
			$this->setactiveQueueURL($endpoint . "/" . $queueName);
		}

		// Select fetch function
		if (function_exists('curl_init')) $this->fetchFunction = 'fetch_curl';
			else $this->fetchFunction = 'fetch_urlwrappers';

		// Select hash function
		if (function_exists('hash_hmac')) {
			$this->hmacFunction = 'hmac_hash';
		} else if (function_exists('mhash')) {
			$this->hmacFunction = 'hmac_mhash';
		} else if (class_exists('Crypt_HMAC')) {
			$this->hmacFunction = 'hmac_pear';
		} else {
			$this->hmacFunction = 'hmac_lance';
		}
	}

	/**
	 * Set the full URI for the active queue
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string	Queue URL (http://queue.amazonaws.com/myQueue) or name (myQueue)
	 * @return void
	 */
	public function setActiveQueueURL($queueURL)
	{
		// Prepend the endpoint if no http:// prefix found
		if (strpos($queueURL,'http://') === false)
		{
			$queueURL = $this->endpoint . '/' . $queueURL;
		}

		// Actually set it
		$this->activeQueueURL = $queueURL;
	}

	/**
	 * Get an array of queues
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @return SimpleXMLElement
	 */
	public function ListQueues()
	{
		$params = array();
		$result = $this->makeRequest('ListQueues', $params);
		if ($result->ListQueuesResult->QueueUrl != NULL)
		{
			return $result->ListQueuesResult->QueueUrl;
		}
		else
		{
			throw( new Exception($result->Error->Code) );
		}
	}

	/**
	 * Create a new queue, and set the active queue for this client
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param string	$QueueName
	 * @return SimpleXMLElement
	 */
	public function CreateQueue($QueueName)
	{
		$params = array();
		$params['QueueName'] = $QueueName;
		$result = $this->makeRequest('CreateQueue', $params);
		if ($result->CreateQueueResult->QueueUrl != NULL)
		{
			$q = $result->CreateQueueResult->QueueUrl;
			$this->setactiveQueueURL($q);
			return $q;
		}
		else
		{
			throw( new Exception($result->Error->Code) );
		}
	}

	/**
	 * Set the active queue, and then delete it
	 *
	 * Note: this will delete ALL messages in your queue, so use this function with caution!
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param string	$ActiveQueueURL
	 * @return true
	 */
	public function DeleteQueue($ActiveQueueURL)
	{
		$this->setactiveQueueURL($ActiveQueueURL);

		$params = array();
		$result = $this->makeRequest('DeleteQueue', $params);
		if ($result->Error->Code != NULL)
		{
			throw( new Exception($result->Error->Code) );
		}
		return true;
	}

	/**
	 * Send a message to your queue
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param string	$MessageBody
	 * @return SimpleXMLElement		MessageId
	 */
	public function SendMessage($MessageBody)
	{
		$params = array();
		$params['MessageBody'] = $MessageBody;
		$result = $this->makeRequest('SendMessage', $params);
		if ($result->SendMessageResult->MessageId != NULL)
		{
			return $result->SendMessageResult->MessageId;
		}
		else
		{
			throw( new Exception($result->Error->Code) );
		}
	}

	/**
	 * Get a queue attribute
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param string	$Attribute
	 * @return SimpleXMLElement		Value
	 */
	public function GetQueueAttributes($Attribute)
	{
		$params = array();
		$params['AttributeName'] = $Attribute;
		$result = $this->makeRequest('GetQueueAttributes', $params);
		if ($result->GetQueueAttributesResult->Attribute != NULL)
		{
			return $result->GetQueueAttributesResult->Attribute->Value;
		}
		else
		{
			throw( new Exception($result->Error->Code) );
		}
	}

	/**
	 * Get a message(s) from your queue
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param integer	$MaxNumberOfMessage	(optional, 1-10)
	 * @param integer	$VisibilityTimeout	(optional, 0-7200 seconds)
	 * @return SimpleXMLElement
	 */
	public function ReceiveMessage($MaxNumberOfMessages = -1, $VisibilityTimeout = -1)
	{
		$params = array();
		if ($VisibilityTimeout > -1) $params['VisibilityTimeout'] = $VisibilityTimeout;
		if ($MaxNumberOfMessages > -1) $params['MaxNumberOfMessages'] = $MaxNumberOfMessages;
		$result = $this->makeRequest('ReceiveMessage', $params);
		if ($result->ReceiveMessageResult->Message != NULL)
		{
			return $result->ReceiveMessageResult->Message;
		}
		else
		{
			throw( new Exception($result->Error->Code) );
		}
	}

	/**
	 * Delete a message
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @param string	$ReceiptHandle
	 * @return true
	 */
	public function DeleteMessage($ReceiptHandle)
	{
		$params = array();
		$params['ReceiptHandle'] = $ReceiptHandle;
		$result= $this->makeRequest('DeleteMessage', $params);
		if ($result->Error->Code != NULL)
		{
			throw( new Exception($result->Error->Code) );
		}
		return true;
	}

	/**
	 * Send a query request and return a SimpleXMLElement object
	 *
	 * @author Justin@AWS <http://developer.amazonwebservices.com/connect/profile.jspa?userID=28471>
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $action
	 * @param array $params
	 * @return SimpleXMLElement
	 */
	public function makeRequest($action, $params)
	{
		if ($params == '') $params = array();

		$retryCount = 0;
		do
		{
			$retry = false;
			$timestamp = time();

			// Add Actions
			$params['Action'] = $action;
			$params['Expires'] = gmdate('Y-m-d\TH:i:s\Z', $timestamp + 10);
			$params['Version'] = '2008-01-01';
			$params['AWSAccessKeyId'] = $this->accessKey;
			$params['SignatureVersion'] = '1';

			// build our string to sign
			uksort($params, 'strcasecmp');
			$stringToSign = '';
			foreach ($params as $key => $val)
			{
				$stringToSign = $stringToSign . "$key$val";
			}

			// Sign the string
			$params['Signature'] = $this->{$this->hmacFunction}($stringToSign);

			$request = '';
			foreach ($params as $key => $val)
			{
				$request .= $key . '=' .  urlencode($val) . '&';
			}
			// get rid of the last &
			$request = substr($request, 0, strlen($request) - 1);

			// set our endpoint, keeping in mind that not all actions require a queue name in the URI
			$endpoint = ($action == 'ListQueues' || $action == 'CreateQueue') ? $this->endpoint : $this->activeQueueURL;

			// request
			$req = $this->{$this->fetchFunction}(
				$endpoint,
				$request,
				strlen($request) > 1024		// Go into POST mode if request is more than 1k long
			);

			// check if we should retry this request
			$responseCode = $req[1];

			// you should always retry a 5xx error, as some of these are expected
			if($responseCode >= 500 && $responseCode < 600 && $retryCount <= 5)
			{
				$retry = true;
				$retryCount++;
				//echo $responseCode, ' : retrying ', $action, ' request (', $retryCount, ')', "\n<br />\n";
				sleep($retryCount / 4 * $retryCount);
			}
		}
		while($retry == true);

		$xml = $req[0];

		// PHP 5 - The easier way!
		$data = new SimpleXMLElement($xml);

		return $data;
	}

	/**
	 * Fetch with curl
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string 	$url
	 * @param string 	$qs
	 * @param bool		$post
	 * @return array(output,httpCode)
	 * @internal
	 */
	private function fetch_curl($url, $qs, $post) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, $post);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_URL, $url . (!$post ? '?'.$qs : ''));
		if ($post) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $qs);
		}

		// Execute
		$output = array();
		$output[0] = curl_exec($curl);
		$output[1] = curl_getinfo($curl,CURLINFO_HTTP_CODE);

		// Close handle
		curl_close($curl);

		return $output;
	}

	/**
	 * Fetch with urlwrappers
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string 	$url
	 * @param string 	$qs
	 * @param bool		$post
	 * @return array(output,httpCode)
	 * @internal
	 */
	private function fetch_urlwrappers($url, $qs, $post) {
		$output = array();

		if ($post) {
			$opts = array(
			  'http'=>array(
			    'method'=>"POST",
			    'header'=>"Content-type: application/x-www-form-urlencoded\r\n" .
			              "Content-length: " . strlen($qs),
			    'content'=>$qs
			  )
			);
			$context = stream_context_create($opts);
		} else {
			$opts = array(
			  'http'=>array(
			    'method'=>"GET"
			  )
			);
			$context = stream_context_create($opts);
			$url .= '?'.$qs;
		}

		$output[0] = '';
		$f = @fopen($url,'r',null,$context);
		if (!$f) {
			$output[0] = '';
			$output[1] = 404;
			return $output;
		}
		while (!feof($f)) {
			$output[0] .= fread($f,1024);
		}
		$meta_data = stream_get_meta_data($f);
		if (preg_match('/^HTTP\/1\.[01] ([0-9]{3})/',$meta_data['wrapper_data'][0],$m)) {
			$output[1] = $m[1];
		} else {
			$output[1] = false;
		}
		fclose($f);

		$output[2] = 'wrappers';

		return $output;
	}

	/**
	 * HMAC function using hash PECL (http://ca.php.net/manual/en/book.hash.php)
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $stringToSign
	 * @return string	base64 encoded hmac
	 * @internal
	 */
	private function hmac_hash($stringToSign) {
		return base64_encode(pack('H*',hash_hmac('sha1', $stringToSign, $this->secretKey)));
	}

	/**
	 * HMAC function using mhash (http://ca.php.net/manual/en/book.hash.php)
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $stringToSign
	 * @return string	base64 encoded hmac
	 * @internal
	 */
	private function hmac_mhash($stringToSign) {
		return base64_encode(mhash(MHASH_SHA1, $stringToSign, $this->secretKey));
	}

	/**
	 * HMAC function using PEAR Crypt_HMAC class (http://pear.php.net/package/Crypt_HMAC)
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $stringToSign
	 * @return string	base64 encoded hmac
	 * @internal
	 */
	private function hmac_pear($stringToSign) {
		$hasher =& new Crypt_HMAC($this->secretKey, "sha1");
		return base64_encode(pack('H*', ($hasher->hash($stringToSign))));
	}

	/**
	 * HMAC function using Lance's function (http://ca.php.net/manual/en/function.mhash.php, see comments)
	 *
	 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
	 * @param string $stringToSign
	 * @return string	base64 encoded hmac
	 * @internal
	 */
	private function hmac_lance($stringToSign) {
	    $b = 64;
		if (strlen($this->secretKey) > $b) {
			$key = pack("H*",sha1($this->secretKey));
		} else {
			$key = $this->secretKey;
		}
		$key  = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return base64_encode(pack('H*', sha1($k_opad  . pack("H*",sha1($k_ipad . $stringToSign)))));
	}
}

?>