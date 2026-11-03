<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate high-resolution PNG Base64 Data URI for <img> embedding.
     * Prevents browser SVG shrink-wrap and rendering collapse.
     */
    public function generateDataUri(string $data, int $scale = 6): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'scale' => $scale,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'bgColor' => [255, 255, 255],
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Generate raw binary PNG bytes (used for verification & decoding tests).
     */
    public function generatePng(string $data, int $scale = 6): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => $scale,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'bgColor' => [255, 255, 255],
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * Generate inline SVG markup with explicit dimensions, background rect, and dark paths.
     */
    public function generateSvg(string $data, int $size = 180): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
        ]);

        $svg = (new QRCode($options))->render($data);

        // Inject explicit width, height, and display attributes into root <svg>
        if (! preg_match('/<svg[^>]*\bwidth=/i', $svg)) {
            $svg = preg_replace(
                '/<svg\b([^>]*)>/i',
                '<svg$1 width="' . $size . '" height="' . $size . '" style="display:block;width:' . $size . 'px;height:' . $size . 'px;max-width:100%;">',
                $svg,
                1
            );
        }

        // Ensure white background rect is present so QR is never transparent
        if (! str_contains($svg, '<rect')) {
            $svg = preg_replace(
                '/(<svg[^>]*>)/i',
                '$1<rect width="100%" height="100%" fill="#ffffff"/>',
                $svg,
                1
            );
        }

        return $svg;
    }
}

