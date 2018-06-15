<?php
require_once('vendor/autoload.php');
// require_once('utils/hash-password.php');

use \Firebase\JWT\JWT;

$username = mysqli_real_escape_string($dbc, trim($input['username']));
$password = mysqli_real_escape_string($dbc, trim($input['password']));

header('Content-Type: application/json');

if (empty($username) || empty($password)) {
    die(json_encode(error_response('Please enter both a username and password')));
}

$output = do_login($username, $password, $dbc);

echo json_encode($output);

mysqli_close($dbc);

function do_login($username, $password, $dbc) {
    $valid_login = false;

    if (!empty($username) && !empty($password)) {
    	$stmt = $dbc->stmt_init();
    	$stmt->prepare('SELECT id, name, password_hashed FROM users WHERE username=?');
    	$stmt->bind_param('s', $username);
    	$stmt->execute();
    	$stmt->store_result();

    	if ($stmt->num_rows == 1) {
    		$stmt->bind_result($user_id, $user_name, $db_password);
    		$stmt->fetch();

            if (password_verify($password, $db_password)) {
        		$valid_login = true;
    		}
        }
    }

    if (!$valid_login) {
        return error_response('Your username or password is incorrect. Please try again.');
    }

    $tokenId    = base64_encode(mcrypt_create_iv(32));
    $issuedAt   = time();
    $notBefore  = $issuedAt;
    $expire     = $notBefore + 60*60*24*30;
    $serverName = 'mikelinden.com';

    $data = array(
        'iat'  => $issuedAt,         // Issued at: time when the token was generated
        'jti'  => $tokenId,          // Json Token Id: an unique identifier for the token
        'iss'  => $serverName,       // Issuer
        'nbf'  => $notBefore,        // Not before
        'exp'  => $expire,           // Expire
        'data' => array(                  // Data related to the signer user
            'userId'   => $user_id,
            'name' => $user_name,
            'username' => $username
        )
    );

    global $jwt_secret_key;
    global $jwt_hashing_algorithm;

    $jwt = JWT::encode($data, $jwt_secret_key, $jwt_hashing_algorithm);

    return array(
        'success' => true,
        'token' => $jwt,
    	'name' => $user_name,
    	'username' => $username,
    	'userId' => $user_id
    );
}