<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$image = imagecreatefrompng('images/wmsu logo.png');
if (!$image) {
    echo "Failed to load image.";
    exit;
}

$width = imagesx($image);
$height = imagesy($image);

// Find bounding box of non-white pixels
$minX = $width;
$maxX = 0;
$minY = $height;
$maxY = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($image, $x, $y);
        $colors = imagecolorsforindex($image, $rgb);
        if ($colors['red'] != 255 || $colors['green'] != 255 || $colors['blue'] != 255) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

if ($minX > $maxX || $minY > $maxY) {
    echo "No non-white pixels found.";
    exit;
}

// Crop the image
$cropWidth = $maxX - $minX + 1;
$cropHeight = $maxY - $minY + 1;
$cropped = imagecreatetruecolor($cropWidth, $cropHeight);
imagealphablending($cropped, false);
imagesavealpha($cropped, true);
$transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
imagefill($cropped, 0, 0, $transparent);

imagecopy($cropped, $image, 0, 0, $minX, $minY, $cropWidth, $cropHeight);

// Make white transparent
$white = imagecolorallocate($cropped, 255, 255, 255);
imagecolortransparent($cropped, $white);

$result = imagepng($cropped, 'images/wmsu logo_no_bg.png');
imagedestroy($image);
imagedestroy($cropped);
if ($result) {
    echo "Background removed and cropped successfully.";
} else {
    echo "Failed to save image.";
}
?>
