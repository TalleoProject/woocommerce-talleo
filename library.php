<?php
/**
 * library.php
 *
 * @author mtl1979 <monni1995@gmail.com>
 *
 * TA4yACzMYuFYq7V6xVAWYHeS39jQ8w4mKRowpY6NskGuS1rZpjcWuCpdeCypwUCJrK9mGqVW9o1pY2EG3HW7BZkR2YRcc4YNa
 *
 * Reality is the concensus constructed between your neurons.
 */
class Talleo_Library {
    protected $url = null, $is_debug = false, $parameters_structure = 'array';
    protected $curl_options = array(
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 8
    );
    protected $host;
    protected $port;
    protected $pass;

    private $httpErrors = array(
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        408 => '408 Request Timeout',
        500 => '500 Internal Server Error',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable'
    );

    public function __construct($pHost, $pPort, $pPassword) {
        $this->validate(false === extension_loaded('curl'), 'The curl extension must be loaded to use this class!');
        $this->validate(false === extension_loaded('json'), 'The json extension must be loaded to use this class!');
        $this->host = $pHost;
        $this->port = $pPort;
        $this->password = $pPassword;
        $this->url = $pHost . ':' . $pPort . '/json_rpc';
    }

    public function validate($pFailed, $pErrMsg) {
        if ($pFailed) {
            echo $pErrMsg;
        }
    }

    public function setDebug($pIsDebug) {
        $this->is_debug = !empty($pIsDebug);
        return $this;
    }

    public function setCurlOptions($pOptionsArray) {
        if (is_array($pOptionsArray)) {
            $this->curl_options = $pOptionsArray + $this->curl_options;
        }
        else {
            echo 'Invalid options type.';
        }
        return $this;
    }

    public function _run($method, $params) {

        $result = $this->request($method, $params, $this->password);
        return $result;
    }

    private function request($pMethod, $pParams, $pPassword) {

        static $requestId = 0;
        $requestId++;

        if(!$pParams) {
            $pParams = json_decode('{}');
        }

        $request = json_encode(array('jsonrpc' => '2.0', 'method' => $pMethod, 'params' => $pParams, 'id' => $requestId, 'password' => $pPassword));
        $responseMessage = $this->getResponse($request);

        //If debug is enabled
        $this->debug($this->pass + 'Url: ' . $this->url . "\r\n", false);
        $this->debug('Request: <br> <br> ' . $request . "\r\n", false);
        $this->debug(' <br>Response: <br> <br> ' . $responseMessage . "\r\n", true);

        $responseDecoded = json_decode($responseMessage, true);

	//Validate response
        $this->validate(empty($responseDecoded['id']), 'Invalid response data structure: ' . $responseMessage);
        $this->validate($responseDecoded['id'] != $requestId, 'Request id: ' . $requestId . ' is different from Response id: ' . $responseDecoded['id']);

        if(isset($responseDecoded['error'])) {
            $errorMessage = 'Request have returned error: ' . $responseDecoded['error']['message'] . '; ' . "\n" . 'Request: ' . $request . '; ';

            if (isset($responseDecoded['error']['data'])) {
                $errorMessage .= "\n" . 'Error data: ' . $responseDecoded['error']['data'];
            }

            $this->debug($errorMessage."\r\n", false);

            $errorMessage = "There has been an error processing your request, please contact the site administration.".

            $this->validate(!is_null($responseDecoded['error']), $errorMessage);
        }

        return $responseDecoded['result'];
    }

    protected function debug($pAdd, $pShow = false) {
        static $debug, $startTime;

        if(false === $this->is_debug) {
            return;
        }

        $debug .= $pAdd;

        $startTime = empty($startTime) ? array_sum(explode(' ', microtime())) : $startTime;

        if (true === $pShow and !empty($debug)) {

            $endTime = array_sum(explode(' ', microtime()));
            $debug .= 'Request time: ' . round($endTime - $startTime, 3) . ' s Memory usage: ' . round(memory_get_usage() / 1024) . " kb\r\n";
            echo nl2br($debug);
            flush();
            $debug = $startTime = null;
        }
    }

    //Curl Request
    protected function getResponse($pRequest) {
        $ch = curl_init();
        if (!$ch) {
            throw new RuntimeException('Could\'t initialize a cURL session');
        }

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (!curl_setopt_array($ch, $this->curl_options)) {
            throw new RuntimeException('Error while setting curl options');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (isset($this->httpErrors[$httpCode])) {
            echo 'Response HTTP Error - ' . $this->httpErrors[$httpCode];
        }

        if (0 < curl_errno($ch)) {
           echo '[ERROR] Failed to connect to walletd at ' . $this->host . ' port '. $this->port .'</br>';
        }

        curl_close($ch);
        return $response;
    }

    //Here is where you can start adding methods supported below:
    //
    //https://wiki.bytecoin.org/wiki/Bytecoin_RPC_Wallet_JSON_RPC_API

    public function getBalance() {
        $balance = $this->_run('getBalance', null);
        return $balance;
    }

    public function getStatus() {
        $status = $this->_run('getStatus', null);
        return $status;
    }

    public function getPayment($lastBlockHash, $paymentId) {
        $payment_param = array('blockCount' => 3, 'blockHash' => $lastBlockHash, 'paymentId' => $paymentId);
        $get_payments = $this->_run('getTransactions', $payment_param);
        return $get_payments;
    }
}
