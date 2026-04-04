<?php
require_once __DIR__ . '/../logic/SelectByScreenshot.php';

class ImageTest {
    private $selector;

    public function __construct() {
        $this->selector = new SelectByScreenshot();
    }

    public function testSmallImageNoResize() {
        $tmpPathSmall = sys_get_temp_dir() . '/test_small.png';
        $smallImg = imagecreatetruecolor(800, 600);
        $red = imagecolorallocate($smallImg, 255, 0, 0);
        imagefill($smallImg, 0, 0, $red);
        imagepng($smallImg, $tmpPathSmall);
        imagedestroy($smallImg);

        $b64Small = $this->selector->getCompressedImageBase64($tmpPathSmall);
        $decodedSmall = base64_decode($b64Small);
        $imgSmallDecoded = imagecreatefromstring($decodedSmall);
        $widthSmall = imagesx($imgSmallDecoded);
        $heightSmall = imagesy($imgSmallDecoded);

        imagedestroy($imgSmallDecoded);
        unlink($tmpPathSmall);

        if ($widthSmall !== 800 || $heightSmall !== 600) {
            throw new Exception("Small image dimensions changed to {$widthSmall}x{$heightSmall}");
        }
    }

    public function testLargeImageResized() {
        $tmpPathLarge = sys_get_temp_dir() . '/test_large.jpg';
        $largeImg = imagecreatetruecolor(3000, 2000);
        $blue = imagecolorallocate($largeImg, 0, 0, 255);
        imagefill($largeImg, 0, 0, $blue);

        imagejpeg($largeImg, $tmpPathLarge, 100);
        imagedestroy($largeImg);

        // Append 2.5MB of data to artificially inflate the file size without crashing memory
        $fs = fopen($tmpPathLarge, 'a');
        fwrite($fs, str_repeat('0', 2.5 * 1024 * 1024));
        fclose($fs);

        $b64Large = $this->selector->getCompressedImageBase64($tmpPathLarge);
        $decodedLarge = base64_decode($b64Large);
        $imgLargeDecoded = @imagecreatefromstring($decodedLarge);

        if ($imgLargeDecoded === false) {
            unlink($tmpPathLarge);
            throw new Exception("Large image decoding failed.");
        }

        $widthLarge = imagesx($imgLargeDecoded);
        $heightLarge = imagesy($imgLargeDecoded);
        $finalSizeMb = strlen($decodedLarge) / 1024 / 1024;
        
        imagedestroy($imgLargeDecoded);
        unlink($tmpPathLarge);

        if ($widthLarge !== 1500 || $heightLarge !== 1000) {
            throw new Exception("Large image dimensions incorrect: {$widthLarge}x{$heightLarge}");
        }
    }
}
