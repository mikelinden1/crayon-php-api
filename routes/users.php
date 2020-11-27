<?php
require_once('utils/authorize.php');

$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$input = json_decode(file_get_contents('php://input'), true);
$name       = mysqli_real_escape_string($dbc, $input['name']);
$username   = mysqli_real_escape_string($dbc, $input['username']);
$password   = mysqli_real_escape_string($dbc, $input['password']);

$request_method = $_SERVER['REQUEST_METHOD'];

$stmt = $dbc->stmt_init();

switch ($request_method) {
    case 'GET':
        $output = array();

        $stmt->prepare('select id, name, username from users');
        $stmt->execute();
        $stmt->bind_result($id, $name, $username);

        while ($stmt->fetch()) {
            $row = array(
                'id' => $id,
                'name' => $name,
                'username' => $username
            );
            
            array_push($output, $row);
        }

        header('Content-Type: application/json');

        echo json_encode(array(
            'users' => $output
        ));

        exit;
    case 'POST':
        $password_hashed = hash_password($password);

        $stmt->prepare('insert users (name, username, password_hashed) values (?, ?, ?)');
        $stmt->bind_param('sss', $name, $username, $password_hashed);
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
    		$stmt->prepare('update users set name=?, username=? where id=?');
            $stmt->bind_param('ssi', $name, $username, $id);
        } else {
            $password_hashed = hash_password($password);

    		$stmt->prepare('update users set name=?, username=?, password_hashed=? where id=?');
            $stmt->bind_param('sssi', $name, $username, $password_hashed, $id);
		}

        $stmt->execute();

		exit;
    case 'DELETE':
        $id = (int)$request_components[1];

        if (empty($id)) {
            http_response_code(404);
            die(json_encode(error_response('No Id')));
        }

        $stmt->prepare('delete from users where id=?');
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