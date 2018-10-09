<?php
require_once('utils/authorize.php');

$input = json_decode(file_get_contents('php://input'), true);

$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

$request_method = $_SERVER['REQUEST_METHOD'];

$stmt = $dbc->stmt_init();

$id         = (int)mysqli_real_escape_string($dbc, $input['id']);
$name       = mysqli_real_escape_string($dbc, $input['name']);

switch ($request_method) {
    case 'GET':
        $output = array();

        $stmt->prepare('select media.id, media.name, media.filename, media.module, media.upload_date, media.uploaded_as, users.name as uploaded_by from media inner join users on media.uploaded_by = users.id');
        $stmt->execute();
        $stmt->bind_result($id, $name, $filename, $module, $upload_date, $uploaded_as, $uploaded_by);

        while ($stmt->fetch()) {
            $row = array(
                'id' => $id,
                'name' => $name,
                'filename' => $filename,
                'module' => $module,
                'upload_date' => date('m/d/Y g:i A', strtotime($upload_date)),
                'uploaded_as' => $uploaded_as,
                'uploaded_by' => $uploaded_by
            );

            array_push($output, $row);
        }

        header('Content-Type: application/json');

        echo json_encode(array(
            'media' => $output
        ));

        exit;
    case 'PUT':
        if (empty($id)) {
            http_response_code(404);
            die(var_dump($input));
            die(json_encode(error_response('No Id')));
        }

        $stmt->prepare('update media set name=? where id=?');
        $stmt->bind_param('si', $name, $id);

        $stmt->execute();

        echo 'ok';

        exit;
    case 'DELETE':
        $request = $_SERVER['PATH_INFO'];
        $request_components = explode('/', $request);
        $id = (int)$request_components[2];

        if (empty($id)) {
            http_response_code(404);
            die(json_encode(error_response('No Id')));
        }

        $upload_path = $_GET['uploadDir'];

        $stmt->prepare('select filename from media where id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($filename);
        $stmt->fetch();

        $file_sizes = array('', 'thumb_', 'medium_', 'large_');

        foreach ($file_sizes as $size) {
            $filename = $upload_path . '/' . $size . $filename;

            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        $stmt->prepare('delete from media where id=?');
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