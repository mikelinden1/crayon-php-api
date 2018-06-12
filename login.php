<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Content-Type');

require_once('database-connection.php');
require_once('vendor/autoload.php');

use \Firebase\JWT\JWT;

$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$input = json_decode(file_get_contents('php://input'),true);

$email = mysqli_real_escape_string($dbc, trim($input['username']));
$password = mysqli_real_escape_string($dbc, trim($input['password']));

require_once('do_login.php');

$output = do_login($email, $password, $dbc);

header('Content-Type: application/json');
echo json_encode($output);

mysqli_close($dbc);


function do_login($username, $password, $dbc) {
    $valid_login = false;

    if (!empty($username) && !empty($password)) {
    	$stmt = $dbc->stmt_init();
    	$stmt->prepare('SELECT id, name, password_hashed FROM users WHERE username');
    	$stmt->bind_param('s', $username);
    	$stmt->execute();
    	$stmt->store_result();

    	if ($stmt->num_rows == 1) {
    		$stmt->bind_result($user_id, $user_name, $db_password);
    		$stmt->fetch();

    		if ($db_password === $password) {
        		$valid_login = true;
    		}
        }
    }

    mysqli_close($dbc);

    if (!$valid_login) {
        return array('success' => false, 'errorMessage' => 'Your email or password is incorrect. Please try again.');
    }

    $tokenId    = base64_encode(mcrypt_create_iv(32));
    $issuedAt   = time();
    $notBefore  = $issuedAt;
    $expire     = $notBefore + 60*60*24*30;
    $serverName = 'mikelinden.com';

    /*
     * Create the token as an array
     */
    $data = array(
        'iat'  => $issuedAt,         // Issued at: time when the token was generated
        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss'  => $serverName,       // Issuer
        'nbf'  => $notBefore,        // Not before
        'exp'  => $expire,           // Expire
        'data' => array(                  // Data related to the signer user
            'userId'   => $user_id, // userid from the users table
            'name' => $user_name, // User name
            'username' => $username // User email
        )
    );

    /*
     * Extract the key, which is coming from the config file.
     *
     * Best suggestion is the key to be a binary string and
     * store it in encoded in a config file.
     *
     * Can be generated with base64_encode(openssl_random_pseudo_bytes(64));
     *
     * keep it secure! You'll need the exact key to verify the
     * token later.
     */

    $secretKey = md5("Kinetek1!!@#$");

    /*
     * Encode the array to a JWT string.
     * Second parameter is the key to encode the token.
     *
     * The output string can be validated at http://jwt.io/
     */
    $jwt = JWT::encode($data, $secretKey, 'HS512');

    return array(
        'success' => true,
        'token' => $jwt,
    	'name' => $user_name,
    	'username' => $username,
    	'userId' => $user_id
    );
}
?>