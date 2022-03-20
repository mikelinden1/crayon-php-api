<?php
require_once('utils/authorize.php');

$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$input      = json_decode(file_get_contents('php://input'), true);
$first_name = mysqli_real_escape_string($dbc, $input['first_name']);
$last_name  = mysqli_real_escape_string($dbc, $input['last_name']);
$email      = mysqli_real_escape_string($dbc, $input['email']);
$password   = mysqli_real_escape_string($dbc, $input['password']);
$stripe_id  = mysqli_real_escape_string($dbc, $input['stripe_id']);

$request_method = $_SERVER['REQUEST_METHOD'];

$stmt = $dbc->stmt_init();

switch ($request_method) {
    case 'GET':
        $output = array();

        $stmt->prepare('select id, first_name, last_name, email, create_date, last_login, login_count, stripe_id from accounts');
        $stmt->execute();
        $stmt->bind_result($id, $first_name, $last_name, $email, $create_date, $last_login, $login_count, $stripe_id);

        while ($stmt->fetch()) {
            $row = array(
                'id' => $id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'create_date' => $create_date,
                'last_login' => $last_login,
                'login_count' => $login_count,
                'stripe_id' => $stripe_id
            );
            
            array_push($output, $row);
        }

        header('Content-Type: application/json');

        echo json_encode(array(
            'accounts' => $output
        ));

        exit;
    case 'POST':
        $password_hashed = hash_password($password);
        $ref_code = generate_referal_code($first_name . ' ' . $last_name);

        $stmt->prepare('insert accounts (first_name, last_name, email, password, stripe_id, referral_code) values (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $first_name, $last_name, $email, $password_hashed, $stripe_id, $ref_code);
        $stmt->execute();

		echo $stmt->insert_id;
		exit;
    case 'PUT':
        $id = (int)$request_components[1];

        if (empty($id)) {
            http_response_code(404);
            die(json_encode(error_response('No Id')));
        }

		if (empty($password)) {
    		$stmt->prepare('update accounts set first_name=?, last_name=?, email=?, stripe_id=? where id=?');
            $stmt->bind_param('ssssi', $first_name, $last_name, $email, $stripe_id, $id);
        } else {
            $password_hashed = hash_password($password);

    		$stmt->prepare('update accounts set first_name=?, last_name=?, email=?, password=?, stripe_id=? where id=?');
            $stmt->bind_param('sssssi', $first_name, $last_name, $email, $password_hashed, $stripe_id, $id);
		}

        $stmt->execute();

		exit;
    case 'DELETE':
        $id = (int)$request_components[1];

        if (empty($id)) {
            http_response_code(404);
            die(json_encode(error_response('No Id')));
        }

        $stmt->prepare('delete from accounts where id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

		echo 'ok';
		exit;
    default:
        http_response_code(401);
        die('Unsupported request method');
}

$stmt->close();

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
}
function generate_referal_code($name) {
    $name = trim($name);
    $code = '';
    if (!empty($name)) {
        $name_parts = explode(' ', $name);
        $code .= $name_parts[0];

        if (count($name_parts) > 1) {
            $code .= $name_parts[1][0]; 
        }
    }

    $code .= dechex(time());

    return strToLower($code);
}