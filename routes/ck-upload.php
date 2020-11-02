<?php
require_once('utils/authorize.php');

$upload_full_path = isset($_GET['uploadFullPath']) ? $_GET['uploadFullPath'] : '/uploads';
$upload_path = isset($_GET['uploadPath']) ? $_GET['uploadPath'] : '../uploads';

$func_num = isset($_GET['CKEditorFuncNum']) ? $_GET['CKEditorFuncNum'] : 0;

if (!empty($_FILES['upload'])) {
    if (is_uploaded_file($_FILES['upload']['tmp_name'])) {
        $random = rand(1111111111, 9999999999);
        $path_info = pathinfo($_FILES['upload']['name']);

        $filename = $path_info['filename'];
        $extension = $path_info['extension'];

        $extension = strtolower($extension);

        $filename = str_replace(' ', '-', $filename);
        $filename = preg_replace('/[^A-Za-z0-9\-]/', '', $filename);
        $filename = strtolower($filename);
        $filename = substr($filename, 0, 20);

        $new_name = "$filename-$random.$extension";
        $path = $upload_path;
        $path = "$path/$new_name";

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $path)) {
            maybe_rotate_image($path);
    		$file_link = $upload_full_path . '/' . $new_name;
        } else {
            $message = 'There was an error uploading the file. Check the upload path in config';
        }
    } else {
        $message = 'Error uploading file';
    }
} else {
    $message = 'No file to upload';
}

echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($func_num, '$file_link', '$message');</script>";