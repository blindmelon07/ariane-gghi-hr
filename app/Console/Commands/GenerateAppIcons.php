<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAppIcons extends Command
{
    protected $signature   = 'app:generate-icons {--badge : Only regenerate badge-96.png (notification status-bar icon)}';
    protected $description = 'Generate PWA app icons with dark background from the logo PNG';

    public function handle(): int
    {
        $source = public_path('images/gghilogoapp.png');

        if (! file_exists($source)) {
            $this->error('Source logo not found: ' . $source);
            return self::FAILURE;
        }

        if ($this->option('badge')) {
            $this->generateBadge($source);
            return self::SUCCESS;
        }

        // Background: #0d1b2e (dark navy matching the sidebar)
        $bg = ['r' => 13, 'g' => 27, 'b' => 46];

        $configs = [
            // Android PWA icons
            ['icon-192.png',      192, 0.15],
            ['icon-512.png',      512, 0.15],
            ['icon-maskable.png', 512, 0.20],

            // iOS apple-touch-icon (180×180 — used by all modern iPhones)
            ['apple-touch-icon.png', 180, 0.15],

            // iOS splash screens (portrait, @2x — covers most devices)
            // iPhone SE / 8 / 7 / 6s       750 × 1334
            // iPhone X / XS / 11 Pro       1125 × 2436
            // iPhone XR / 11               828 × 1792
            // iPhone 12 / 13 / 14          1170 × 2532
            // iPhone 12 Pro Max / 13 Pro Max / 14 Plus  1284 × 2778
            // iPhone 14 Pro                1179 × 2556
            // iPhone 14 Pro Max            1290 × 2796
        ];

        foreach ($configs as [$filename, $size, $padding]) {
            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, true);
            imagesavealpha($canvas, true);

            $bgColor = imagecolorallocate($canvas, $bg['r'], $bg['g'], $bg['b']);
            imagefill($canvas, 0, 0, $bgColor);

            $logo   = imagecreatefrompng($source);
            $logoW  = imagesx($logo);
            $logoH  = imagesy($logo);

            $padPx   = (int) ($size * $padding);
            $maxSize = $size - ($padPx * 2);
            $ratio   = min($maxSize / $logoW, $maxSize / $logoH);
            $scaledW = (int) ($logoW * $ratio);
            $scaledH = (int) ($logoH * $ratio);
            $destX   = (int) (($size - $scaledW) / 2);
            $destY   = (int) (($size - $scaledH) / 2);

            imagecopyresampled($canvas, $logo, $destX, $destY, 0, 0, $scaledW, $scaledH, $logoW, $logoH);

            $out = public_path('images/' . $filename);
            imagepng($canvas, $out, 6);
            imagedestroy($canvas);
            imagedestroy($logo);

            $this->line("  <info>✓</info> {$filename} ({$size}×{$size})");
        }

        // Generate iOS splash screens
        $this->newLine();
        $this->line('  Generating iOS splash screens...');

        $splashDir = public_path('images/splash');
        if (! is_dir($splashDir)) {
            mkdir($splashDir, 0755, true);
        }

        $splashSizes = [
            ['iphone-se',        750,  1334],
            ['iphone-x',        1125,  2436],
            ['iphone-xr',        828,  1792],
            ['iphone-12',       1170,  2532],
            ['iphone-12-max',   1284,  2778],
            ['iphone-14-pro',   1179,  2556],
            ['iphone-14-max',   1290,  2796],
            ['ipad',            1536,  2048],
            ['ipad-pro-11',     1668,  2388],
            ['ipad-pro-129',    2048,  2732],
        ];

        $logo   = imagecreatefrompng($source);
        $logoW  = imagesx($logo);
        $logoH  = imagesy($logo);

        foreach ($splashSizes as [$name, $w, $h]) {
            $canvas  = imagecreatetruecolor($w, $h);
            $bgColor = imagecolorallocate($canvas, $bg['r'], $bg['g'], $bg['b']);
            imagefill($canvas, 0, 0, $bgColor);

            // Logo at ~30% of the shorter dimension
            $maxLogoSize = (int) (min($w, $h) * 0.30);
            $ratio       = min($maxLogoSize / $logoW, $maxLogoSize / $logoH);
            $scaledW     = (int) ($logoW * $ratio);
            $scaledH     = (int) ($logoH * $ratio);
            $destX       = (int) (($w - $scaledW) / 2);
            $destY       = (int) (($h - $scaledH) / 2) - (int) ($h * 0.05);

            imagecopyresampled($canvas, $logo, $destX, $destY, 0, 0, $scaledW, $scaledH, $logoW, $logoH);

            // App name text below logo
            $textY   = $destY + $scaledH + (int) ($h * 0.04);
            $white   = imagecolorallocate($canvas, 255, 255, 255);
            $fontSize = max(3, (int) ($h * 0.018));
            imagestring($canvas, $fontSize, (int) (($w - strlen('GGHI HR Portal') * imagefontwidth($fontSize)) / 2), $textY, 'GGHI HR Portal', $white);

            $out = $splashDir . '/' . $name . '.png';
            imagepng($canvas, $out, 6);
            imagedestroy($canvas);

            $this->line("  <info>✓</info> splash/{$name}.png ({$w}×{$h})");
        }

        imagedestroy($logo);

        $this->generateBadge($source);

        $this->newLine();
        $this->info('All icons and splash screens generated in public/images/');
        $this->line('  Upload <comment>public/images/</comment> folder to Hostinger.');

        return self::SUCCESS;
    }

    /**
     * badge-96.png — the small icon Android shows in the status bar and next to
     * the app name on a notification. Android uses only the alpha channel and
     * paints the shape white, so an opaque icon becomes a white square. This is
     * the logo's silhouette in white on transparent, with strokes thickened a
     * little so they survive at 24dp.
     */
    private function generateBadge(string $source): void
    {
        $size = 96;
        $pad  = 6;

        $logo  = imagecreatefrompng($source);
        $ratio = min(($size - 2 * $pad) / imagesx($logo), ($size - 2 * $pad) / imagesy($logo));
        $w     = (int) round(imagesx($logo) * $ratio);
        $h     = (int) round(imagesy($logo) * $ratio);

        // Scale the logo down onto a transparent canvas, keeping its alpha.
        $scaled = imagecreatetruecolor($size, $size);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $logo, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        // White silhouette; each pixel takes the strongest opacity within 1px (dilation).
        $badge = imagecreatetruecolor($size, $size);
        imagealphablending($badge, false);
        imagesavealpha($badge, true);

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $alpha = 127; // GD: 0 = opaque, 127 = transparent
                for ($dx = -1; $dx <= 1; $dx++) {
                    for ($dy = -1; $dy <= 1; $dy++) {
                        $nx = $x + $dx;
                        $ny = $y + $dy;
                        if ($nx >= 0 && $ny >= 0 && $nx < $size && $ny < $size) {
                            $alpha = min($alpha, (imagecolorat($scaled, $nx, $ny) >> 24) & 0x7F);
                        }
                    }
                }
                imagesetpixel($badge, $x, $y, imagecolorallocatealpha($badge, 255, 255, 255, $alpha));
            }
        }
        imagedestroy($scaled);

        imagepng($badge, public_path('images/badge-96.png'), 9);
        imagedestroy($badge);

        $this->line("  <info>✓</info> badge-96.png ({$size}×{$size}, notification status-bar icon)");
    }
}
