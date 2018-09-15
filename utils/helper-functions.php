<?php
/**
 * Takes a file (full file path) and rotates the image if needed
 */
function maybe_rotate_image($file) {
    $exif = exif_read_data($file);
    $orientation = $exif['Orientation'];
    
    if (!empty($orientation)) {
        switch ($orientation) {
            case 3:
                $deg = 180;
                break;

            case 6:
                $deg = 270;
                break;

            case 8:
                $deg = 90;
                break;
            default;
                break;
        }

        if ($deg) {
            $filen = explode('.', $file);
            $ext = end($filen);

            if ($ext === 'png') {
                $img_new = imagecreatefrompng($file);
                $img_new = imagerotate($img_new, $deg, 0);

                // Save rotated image
                imagepng($img_new, $file);
            } else {
                $img_new = imagecreatefromjpeg($file);
                $img_new = imagerotate($img_new, $deg, 0);

                // Save rotated image
                imagejpeg($img_new, $file, 80);
            }
        }
    }
}