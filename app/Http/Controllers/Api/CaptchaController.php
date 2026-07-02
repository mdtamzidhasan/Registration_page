<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    public function generate()
    {
        $text = strtoupper(Str::random(6));
        $key = (string) Str::uuid();

        // Store in cache for 3 minutes
        Cache::put("captcha:{$key}", $text, now()->addMinutes(3));

        // Generate image using GD
        $width = 150;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        // Background color
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Add some noise lines (distortion, makes bot-reading harder)
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Text color
        $textColor = imagecolorallocate($image, 51, 51, 51);

        // Write each character with slight random rotation/position for distortion
        $fontSize = 5; // built-in GD font size (1-5)
        $x = 15;
        for ($i = 0; $i < strlen($text); $i++) {
            $y = rand(10, 20);
            imagestring($image, $fontSize, $x, $y, $text[$i], $textColor);
            $x += 20;
        }

        // Add noise dots
        for ($i = 0; $i < 100; $i++) {
            $dotColor = imagecolorallocate($image, rand(180, 220), rand(180, 220), rand(180, 220));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $dotColor);
        }

        // Capture image output as base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return response()->json([
            'captcha_key'   => $key,
            'captcha_image' => 'data:image/png;base64,' . base64_encode($imageData),
        ]);
    }
}