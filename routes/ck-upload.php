<?php
// require_once('utils/authorize.php');

if (!empty($_FILES['upload'])) {
    if (is_uploaded_file($_FILES['upload']['tmp_name'])) {
        $random = rand(1111111111, 9999999999);
        $path_info = pathinfo($_FILES['upload']['name']);

        $filename = $path_info['filename'];
        $extension = $path_info['extension'];

        $new_name = "$filename-$module_id-$random.$extension";
        $path = $_GET['uploadPath'];
        $path = "$path/$new_name";

        $user_id = $authorized_user->data->userId;

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $path)) {
    		$file_link = $_GET['uploadFullPath'] . '/' . $new_name;
        } else {
            $message = 'There was an error uploading the file. Check the upload path in config';
        }
    } else {
        $message = 'Error uploading file';
    }
} else {
    $message = 'No file to upload';
}

$func_num = $_GET['CKEditorFuncNum'];
echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($func_num, '$file_link', '$message');</script>";