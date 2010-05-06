<?php

// This a modified version of Justin@AWS Sera_Sqs_Client file you may find here:
// http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1180

/**
 * SQS client, derived from reference client provided by Amazon
 *
 * @author Tommy Lacroix <tlacroix at orange n_osp_am tango dot com>
 * @author Lachlan Donald <lachlan@ljd.cc>
 */
class Sera_Sqs_Client
{
	const ENDPOINT = 'http://queue.amazonaws.com';
	const RETRY = 5;

	public $queueName;
	private $accessKey;
	private $secretKey;
	private $endpoint;
	private $activeQueueURL;

	/**
	 * Constructor
	 * @param string $accessKey
	 * @param string $secretKey
	 * @param string $endpoint	http://queue.amazonaws.com
	 * @param string $queueName	optional
	 */
	public function __construct($accessKey, $secretKey, $queueName = '', $endpoint = self::ENDPOINT)
	{
		// Save properties
		$this->queueName = $queueName;
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
		$this->endpoint = $endpoint;

		// Set queue name
		$this->setActiveQueueURL($queueName == '' ?
			$endpoint : "$endpoint/$queueName");
	}

	/**
	 * Set the full URI for the active queue
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
	 * @return SimpleXMLElement
	 */
	public function ListQueues()
	{
		$params = array();
		$result = $this->_dispatchRequest('ListQueues', $params);
		if ($result->ListQueuesResult->QueueUrl != NULL)
		{
			return $result->ListQueuesResult->QueueUrl;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Create a new queue, and set the active queue for this client
	 * @param string	$QueueName
	 * @return SimpleXMLElement
	 */
	public function CreateQueue($QueueName)
	{
		$params = array();
		$params['QueueName'] = $QueueName;
		$result = $this->_dispatchRequest('CreateQueue', $params);
		if ($result->CreateQueueResult->QueueUrl != NULL)
		{
			$q = $result->CreateQueueResult->QueueUrl;
			$this->setactiveQueueURL($q);
			return $q;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Set the active queue, and then delete it
	 * Note: this will delete ALL messages in your queue, so use this function with caution!
	 * @param string	$ActiveQueueURL
	 * @return true
	 */
	public function DeleteQueue($ActiveQueueURL)
	{
		$this->setactiveQueueURL($ActiveQueueURL);

		$params = array();
		$result = $this->_dispatchRequest('DeleteQueue', $params);
		if ($result->Error->Code != NULL)
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
		return true;
	}

	/**
	 * Send a message to your queue
	 * @param string	$MessageBody
	 * @return SimpleXMLElement		MessageId
	 */
	public function SendMessage($MessageBody)
	{
		$params = array();
		$params['MessageBody'] = $MessageBody;
		$result = $this->_dispatchRequest('SendMessage', $params);
		if ($result->SendMessageResult->MessageId != NULL)
		{
			return $result->SendMessageResult->MessageId;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Get a queue attribute
	 * @param string	$Attribute
	 * @return SimpleXMLElement		Value
	 */
	public function GetQueueAttributes($Attribute)
	{
		$params = array();
		$params['AttributeName'] = $Attribute;
		$result = $this->_dispatchRequest('GetQueueAttributes', $params);
		if ($result->GetQueueAttributesResult->Attribute != NULL)
		{
			return $result->GetQueueAttributesResult->Attribute->Value;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Changes a messages visiblity
	 * @param string	$ReceiptHandle
	 * @param int	$VisibilityTimeout
	 * @return SimpleXMLElement		ResponseMetadata->RequestId->Value
	 */
	public function ChangeMessageVisibility($ReceiptHandle, $VisibilityTimeout)
	{
		$params = array();
		$params['ReceiptHandle'] = (string) $ReceiptHandle;
		$params['VisibilityTimeout'] = $VisibilityTimeout;
		$result = $this->_dispatchRequest('ChangeMessageVisibility', $params);

		if ($result->ResponseMetadata->RequestId != NULL)
		{
			return $result->ResponseMetadata->RequestId->Value;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Get a message(s) from your queue
	 * @param integer 	$MaxNumberOfMessage	(optional, 1-10)
	 * @param integer 	$VisibilityTimeout	(optional, 0-7200 seconds)
	 * @return SimpleXMLElement
	 */
	public function ReceiveMessage($MaxNumberOfMessages = -1, $VisibilityTimeout = -1)
	{
		$params = array();
		if ($VisibilityTimeout > -1) $params['VisibilityTimeout'] = $VisibilityTimeout;
		if ($MaxNumberOfMessages > -1) $params['MaxNumberOfMessages'] = $MaxNumberOfMessages;
		$result = $this->_dispatchRequest('ReceiveMessage', $params);
		if ($result->ReceiveMessageResult->Message != NULL)
		{
			return $result->ReceiveMessageResult->Message;
		}
		else
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
	}

	/**
	 * Delete a message
	 * @param string	$ReceiptHandle
	 * @return true
	 */
	public function DeleteMessage($ReceiptHandle)
	{
		$params = array();
		$params['ReceiptHandle'] = (string) $ReceiptHandle;
		$result= $this->_dispatchRequest('DeleteMessage', $params);
		if ($result->Error->Code != NULL)
		{
			throw( new Sera_Sqs_AwsException($result->Error->Code) );
		}
		return true;
	}

	/**
	 * Send a query request and return a SimpleXMLElement object
	 * @param string $action
	 * @param array $params
	 * @return SimpleXMLElement
	 */
	private function _dispatchRequest($action, $params=array())
	{
		$retry = false;
		$retryCount = 0;

		do
		{
			$request = $this->_createRequest($action, $params);

			// dispatch request
			$response = $this->fetch_curl(
				$request->url,
				$request->method,
				$request->params
			);

			// always retry certain errors
			if($response->code >= 500 && $response->code < 600 && $retryCount < 5)
			{
				$retry = true;
				$retryCount++;
				$delay = $retryCount * 500000;
				//printf("retrying %s (returned %d) (retry %d, delay of %dms)\n",
				//	$action, $response->code, $retryCount, $delay / 1000);

				usleep($delay);
			}
			else if($retryCount > 0 && $response->code == 200)
			{
				//printf("retry for %s succeeded\n",$action);
				$retry = false;
			}
		}
		while($retry);

		return new SimpleXMLElement($response->body);
	}

	private function _createRequest($action, $params=array())
	{
		if(!is_array($params)) $params = array();

		// build parameters
		$params['Action'] = $action;
		$params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
		$params['Version'] = '2009-02-01';
		$params['AWSAccessKeyId'] = $this->accessKey;
		$params['SignatureVersion'] = '1';

		// build our string to sign
		uksort($params, 'strcasecmp');
		$stringToSign = '';
		foreach ($params as $key => $val)
		{
			$stringToSign = $stringToSign . "$key$val";
		}

		// sign the string
		$params['Signature'] = $this->hmac_hash($stringToSign);

		// set our endpoint
		$endpoint = ($action == 'ListQueues' || $action == 'CreateQueue')
			? $this->endpoint
			: $this->activeQueueURL
			;

		return (object) array(
			'action'=>$action,
			'params'=>$params,
			'url'=>$endpoint,
			'method'=>strlen(http_build_query($params))>1024 ? 'post' : 'get'
			);
	}

	/**
	 * Fetch with curl
	 *
	 * @param string 	$url
	 * @param string 	$qs
	 * @param bool		$post
	 * @return array(output,httpCode)
	 * @internal
	 */
	private function fetch_curl($url, $method, $params)
	{
		$curl = curl_init();

		if($method == 'get')
		{
			$url .= '?' . http_build_query($params);
		}

		// set URL and other appropriate options
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_VERBOSE, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		//curl_setopt($curl, CURLOPT_PROXY, '172.16.242.1:8080');

		if($method == 'post')
		{
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
		}

		if(($curlResponse = curl_exec($curl)) === false)
		{
			throw new Sera_Sqs_ConnectionException('Curl error: ' . curl_error($curl),
				curl_errno($curl));
		}

		$output = array(
			'body'=>$curlResponse,
			'code'=>curl_getinfo($curl,CURLINFO_HTTP_CODE)
			);

		// close handle
		curl_close($curl);

		return (object)$output;
	}

	/**
	 * HMAC function using hash PECL (http://ca.php.net/manual/en/book.hash.php)
	 * @param string $stringToSign
	 * @return string	base64 encoded hmac
	 * @internal
	 */
	private function hmac_hash($stringToSign) {
		return base64_encode(pack('H*',hash_hmac('sha1', $stringToSign, $this->secretKey)));
	}
}