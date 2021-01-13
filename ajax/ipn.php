<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

//mc_gross

class PaypalIPN
{
    /** @var bool Indicates if the sandbox endpoint is used. */
    private $use_sandbox = false;
    /** @var bool Indicates if the local certificates are used. */
    private $use_local_certs = true;

    /** Production Postback URL */
    const VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    /** Sandbox Postback URL */
    const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    /** Response from PayPal indicating validation was successful */
    const VALID = 'VERIFIED';
    /** Response from PayPal indicating validation failed */
    const INVALID = 'INVALID';

    /**
     * Sets the IPN verification to sandbox mode (for use when testing,
     * should not be enabled in production).
     * @return void
     */
    public function useSandbox()
    {
        $this->use_sandbox = true;
    }

    /**
     * Sets curl to use php curl's built in certs (may be required in some
     * environments).
     * @return void
     */
    public function usePHPCerts()
    {
        $this->use_local_certs = false;
    }

    /**
     * Determine endpoint to post the verification data to.
     *
     * @return string
     */
    public function getPaypalUri()
    {
        if ($this->use_sandbox) {
            return self::SANDBOX_VERIFY_URI;
        } else {
            return self::VERIFY_URI;
        }
    }

    /**
     * Verification Function
     * Sends the incoming post data back to PayPal using the cURL library.
     *
     * @return bool
     * @throws Exception
     */
    public function retrieveIPN()
    {
        if ( ! count($_POST)) {
            throw new Exception("Missing POST Data");
        }

        $raw_post_data = file_get_contents('php://input');

        $fh = fopen("log_ipn.txt", "a");
        fwrite($fh, $raw_post_data."\n");
        fclose($fh);

        $raw_post_array = explode('&', $raw_post_data);

        $myPost = array();

        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                // Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
                if ($keyval[0] === 'payment_date') {
                    if (substr_count($keyval[1], '+') === 1) {
                        $keyval[1] = str_replace('+', '%2B', $keyval[1]);
                    }
                }
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        return $myPost;
    }
}

// verify
$p = new PaypalIPN();
$data = $p->retrieveIPN();

// connect to db
$host = 'localhost';
$db   = 'wsbdd';
$user = 'justin';
$pass = 'mysqlrootytooty1';

$dsn = "mysql:host=$host;dbname=$db;";

$options = [
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$sql = "INSERT INTO donos (email, mc_gross, `date`) VALUES (?, ?, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([$data['payer_email'], $data['mc_gross']]);