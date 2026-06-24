<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAppIcons extends Command
{
    protected $signature   = 'app:generate-icons';
    protected $description = 'Generate PWA app icons with dark background from the logo PNG';

    public function handle(): int
    {
        $source = public_path('images/gghilogoapp.png');

        if (! file_exists($source)) {
            $this->error('Source logo not found: ' . $source);
            return self::FAILURE;
        }

        // Background: #0d1b2e (dark navy matching the sidebar)
        $bg = ['r' => 13, 'g' => 27, 'b' => 46];

        $configs = [
            ['icon-192.png',     192, 0.15],  // any — 15% padding
            ['icon-512.png',     512, 0.15],  // any — 15% padding
            ['icon-maskable.png', 512, 0.20], // maskable — 20% padding = safe zone
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

        $this->newLine();
        $this->info('Icons generated in public/images/ — upload them to Hostinger.');

        return self::SUCCESS;
    }
}
