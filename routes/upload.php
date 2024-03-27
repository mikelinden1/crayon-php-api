<?php
require_once('utils/authorize.php');

header('Content-Type: application/json');

if (!empty($_FILES['file'])) {
    $uploaded_as = basename($_FILES['file']['name']);
    $module_id = $_POST['moduleId'];
    $random = rand(1111111111, 9999999999);
    $path_info = pathinfo($_FILES['file']['name']);

    $filename = $path_info['filename'];
    $extension = $path_info['extension'];
    $extension = strtolower($extension);
    
    $label = $filename;

    $filename = str_replace(' ', '-', $filename);
    $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
    $filename = strtolower($filename);
    $filename = substr($filename, 0, 20);


    $new_name = "$filename-$module_id-$random.$extension";
    $path = $_POST['uploadDir'];
    $full_path = "$path/$new_name";
    $user_id = $authorized_user->data->userId;
    $user_name = $authorized_user->data->name;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $full_path)) {
        $non_image_extensions = array('pdf');
        if (!in_array($extension, $non_image_extensions)) {
            maybe_rotate_image($full_path);

            // generate thumbnail images
            $imagick = new Imagick($full_path);
            
            $imagick->resizeImage(2000, 2000, Imagick::FILTER_LANCZOS, 1, true);
            $imagick->writeImage("$path/large_$new_name");

            $imagick->resizeImage(800, 800, Imagick::FILTER_LANCZOS, 1, true);
            $imagick->writeImage("$path/medium_$new_name");

            $imagick->resizeImage(200, 200, Imagick::FILTER_LANCZOS, 1, true);
            $imagick->writeImage("$path/thumb_$new_name");
        }

        $query = "insert media (name, filename, module, uploaded_by, upload_date, uploaded_as) values ('$label', '$new_name', '$module_id', $user_id, NOW(), '$uploaded_as')";
		mysqli_query($dbc, $query);

		$new_id = mysqli_insert_id($dbc);

        $new_media = array(
            'id' => $new_id,
            'name' => $label,
            'filename' => $new_name,
            'module' => $module_id,
            'upload_date' => date('m/d/Y g:i A'),
            'uploaded_by' => $user_name,
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