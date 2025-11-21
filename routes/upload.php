<?php
require_once('utils/authorize.php');

header('Content-Type: application/json');

if (!empty($_FILES['file'])) {
    $uploaded_as = basename($_FILES['file']['name']);
    $module_id = $_POST['moduleId'];
    $random = rand(1111111111, 9999999999);
    $path_info = pathinfo($_FILES['file']['name']);

    $filename = $path_info['filename'];
    $extension = strtolower($path_info['extension']);
    
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

            // Load image using GD
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $src_image = imagecreatefromjpeg($full_path);
                    break;
                case 'png':
                    $src_image = imagecreatefrompng($full_path);
                    break;
                case 'gif':
                    $src_image = imagecreatefromgif($full_path);
                    break;
                default:
                    $src_image = null;
            }

            if ($src_image) {
                $sizes = [
                    'large' => 2000,
                    'medium' => 800,
                    'thumb' => 200
                ];

                foreach ($sizes as $prefix => $max_size) {
                    list($width, $height) = getimagesize($full_path);

                    // Calculate new dimensions while preserving aspect ratio
                    if ($width > $height) {
                        $new_width = $max_size;
                        $new_height = intval($height * $max_size / $width);
                    } else {
                        $new_height = $max_size;
                        $new_width = intval($width * $max_size / $height);
                    }

                    $dst_image = imagecreatetruecolor($new_width, $new_height);

                    // Preserve transparency for PNG/GIF
                    if ($extension === 'png' || $extension === 'gif') {
                        imagecolortransparent($dst_image, imagecolorallocatealpha($dst_image, 0, 0, 0, 127));
                        imagealphablending($dst_image, false);
                        imagesavealpha($dst_image, true);
                    }

                    imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    $output_path = "$path/{$prefix}_$new_name";
                    switch ($extension) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($dst_image, $output_path, 90);
                            break;
                        case 'png':
                            imagepng($dst_image, $output_path);
                            break;
                        case 'gif':
                            imagegif($dst_image, $output_path);
                            break;
                    }

                    imagedestroy($dst_image);
                }

                imagedestroy($src_image);
            }
        }

        $query = "INSERT INTO media (name, filename, module, uploaded_by, upload_date, uploaded_as) 
                  VALUES ('$label', '$new_name', '$module_id', $user_id, NOW(), '$uploaded_as')";
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

function maybe_rotate_image($file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Only JPEGs have EXIF rotation info
    if ($extension !== 'jpg' && $extension !== 'jpeg') {
        return;
    }

    if (!function_exists('exif_read_data')) {
        return; // EXIF functions not available
    }

    $exif = @exif_read_data($file);
    if (!$exif || !isset($exif['Orientation'])) {
        return;
    }

    $orientation = $exif['Orientation'];
    if ($orientation == 1) {
        return; // no rotation needed
    }

    $img = imagecreatefromjpeg($file);

    switch ($orientation) {
        case 3:
            $img = imagerotate($img, 180, 0);
            break;
        case 6:
            $img = imagerotate($img, -90, 0);
            break;
        case 8:
            $img = imagerotate($img, 90, 0);
            break;
        default:
            return;
    }

    // Overwrite the original file
    imagejpeg($img, $file, 90);
    imagedestroy($img);
}

