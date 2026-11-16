<?php
function createSamplePhoto(string $path, string $name, string $dni, string $rank): void {
    $im = imagecreatetruecolor(200, 250);
    $bg = imagecolorallocate($im, 35, 55, 40);
    $skin = imagecolorallocate($im, 215, 175, 140);
    $hair = imagecolorallocate($im, 30, 25, 20);
    $suit = imagecolorallocate($im, 20, 30, 22);
    $gold = imagecolorallocate($im, 210, 170, 70);
    $white = imagecolorallocate($im, 245, 245, 245);
    
    imagefilledrectangle($im, 0, 0, 200, 250, $bg);
    // Shoulders
    imagefilledellipse($im, 100, 260, 180, 140, $suit);
    // Neck
    imagefilledrectangle($im, 85, 150, 115, 190, $skin);
    // Head
    imagefilledellipse($im, 100, 120, 90, 110, $skin);
    // Hair
    imagefilledarc($im, 100, 105, 95, 90, 180, 360, $hair, IMG_ARC_PIE);
    // Collar gold
    imageline($im, 70, 200, 130, 200, $gold);
    imageline($im, 70, 201, 130, 201, $gold);
    
    // Labels
    imagestring($im, 3, 20, 15, 'COEDE - DEMO', $gold);
    imagestring($im, 2, 20, 210, $rank . ' ' . $name, $white);
    imagestring($im, 2, 20, 225, 'DNI: ' . $dni, $white);
    imagejpeg($im, $path, 90);
    imagedestroy($im);
}

@mkdir('storage/photos', 0777, true);
@mkdir('storage/templates', 0777, true);

createSamplePhoto('storage/photos/45892134.jpg', 'CRICRI_2026', '45892134', 'EP');
createSamplePhoto('storage/photos/43110294.jpg', 'Zavala C.', '43110294', 'My. EP');

// Crear fotos para demostración de carga ZIP masiva
createSamplePhoto('storage/templates/71029384.jpg', 'Rojas M.', '71029384', 'Cap. EP');
createSamplePhoto('storage/templates/72849102.jpg', 'Gutierrez P.', '72849102', 'Tte. EP');
createSamplePhoto('storage/templates/08928374.jpg', 'Paredes B.', '08928374', 'Civil');

$zip = new ZipArchive();
if ($zip->open('storage/templates/fotos_personal_demo.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $zip->addFile('storage/templates/71029384.jpg', '71029384.jpg');
    $zip->addFile('storage/templates/72849102.jpg', '72849102.jpg');
    $zip->addFile('storage/templates/08928374.jpg', '08928374.jpg');
    $zip->close();
    echo "Fotos sintéticas y ZIP de demostración creados exitosamente.\n";
}

