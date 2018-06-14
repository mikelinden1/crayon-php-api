<?php
require_once('utils/preflight-check.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('utils/authorize.php');
require_once('utils/database-connection.php');
$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

header('Content-Type: application/json');

if (!empty($_FILES['file'])) {
    $uploaded_as = basename($_FILES['file']['name']);
    $module_id = $_POST['moduleId'];
    $random = rand(1111111111, 9999999999);
    $path_info = pathinfo($_FILES['file']['name']);

    $filename = $path_info['filename'];
    $extension = $path_info['extension'];

    $new_name = "$filename-$module_id-$random.$extension";
    $path = $_POST['uploadDir'];
    $path = "$path/$new_name";

    $user_id = $authorized_user->data->userId;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
        $query = "insert media (filename, module, uploaded_by, upload_date, uploaded_as) values ('$new_name', '$module_id', $user_id, NOW(), '$uploaded_as')";
		mysqli_query($dbc, $query);

		$new_id = mysqli_insert_id($dbc);

        $new_media = array(
            'id' => $new_id,
            'filename' => $new_name,
            'module' => $module_id,
            'uploaded_by' => $user_id,
            'uploaded_as' => $uploaded_as
        );

        echo json_encode(array(
            'success' => true,
            'newItem' => $new_media
        ));
    } else {
        error_response('There was an error uploading the file. Check the upload path in config');
    }
} else {
    error_response('No file to upload');
}

function error_response($msg) {
    return array('success' => false, 'msg' => $msg);
}