<?php

namespace App\Services;

use RuntimeException;

class LoginCaptchaImage
{
    private int $width;

    private int $height;

    private string $pixels = '';

    /**
     * Fuente de cinco por siete pixeles. Mantenerla aqui evita depender de GD
     * o de una tipografia instalada en el servidor.
     *
     * @var array<string, array<int, string>>
     */
    private const FONT = [
        'A' => ['01110', '10001', '10001', '11111', '10001', '10001', '10001'],
        'B' => ['11110', '10001', '10001', '11110', '10001', '10001', '11110'],
        'C' => ['01111', '10000', '10000', '10000', '10000', '10000', '01111'],
        'D' => ['11110', '10001', '10001', '10001', '10001', '10001', '11110'],
        'E' => ['11111', '10000', '10000', '11110', '10000', '10000', '11111'],
        'F' => ['11111', '10000', '10000', '11110', '10000', '10000', '10000'],
        'G' => ['01111', '10000', '10000', '10111', '10001', '10001', '01111'],
        'H' => ['10001', '10001', '10001', '11111', '10001', '10001', '10001'],
        'J' => ['00111', '00010', '00010', '00010', '10010', '10010', '01100'],
        'K' => ['10001', '10010', '10100', '11000', '10100', '10010', '10001'],
        'L' => ['10000', '10000', '10000', '10000', '10000', '10000', '11111'],
        'M' => ['10001', '11011', '10101', '10101', '10001', '10001', '10001'],
        'N' => ['10001', '11001', '10101', '10011', '10001', '10001', '10001'],
        'P' => ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
        'Q' => ['01110', '10001', '10001', '10001', '10101', '10010', '01101'],
        'R' => ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
        'S' => ['01111', '10000', '10000', '01110', '00001', '00001', '11110'],
        'T' => ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
        'U' => ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
        'V' => ['10001', '10001', '10001', '10001', '10001', '01010', '00100'],
        'W' => ['10001', '10001', '10001', '10101', '10101', '10101', '01010'],
        'X' => ['10001', '10001', '01010', '00100', '01010', '10001', '10001'],
        'Y' => ['10001', '10001', '01010', '00100', '00100', '00100', '00100'],
        'Z' => ['11111', '00001', '00010', '00100', '01000', '10000', '11111'],
        '2' => ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
        '3' => ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
        '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
        '5' => ['11111', '10000', '10000', '11110', '00001', '00001', '11110'],
        '6' => ['01110', '10000', '10000', '11110', '10001', '10001', '01110'],
        '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
        '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
        '9' => ['01110', '10001', '10001', '01111', '00001', '00001', '01110'],
    ];

    public function render(string $code): string
    {
        $this->width = (int) config('login_captcha.width', 400);
        $this->height = (int) config('login_captcha.height', 80);

        if ($this->width < 240 || $this->height < 64) {
            throw new RuntimeException('El tamaño configurado para el CAPTCHA es demasiado pequeño.');
        }

        $this->createBackground();
        $this->drawBackgroundNoise();
        $this->drawCode($code);
        $this->drawForegroundNoise();

        return $this->encodePng();
    }

    private function createBackground(): void
    {
        $this->pixels = '';

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $variation = (int) floor(($x / $this->width) * 5);
                $this->pixels .= chr(247 - $variation).chr(251 - $variation).chr(248 - $variation);
            }
        }
    }

    private function drawBackgroundNoise(): void
    {
        // El ruido queda detrás del texto para dificultar la lectura automática
        // sin obligar al usuario a adivinar los caracteres.
        for ($line = 0; $line < 3; $line++) {
            $color = [random_int(181, 202), random_int(207, 224), random_int(190, 210)];
            $this->drawLine(
                random_int(0, 35),
                random_int(8, $this->height - 8),
                random_int($this->width - 35, $this->width - 1),
                random_int(8, $this->height - 8),
                $color
            );
        }

        for ($dot = 0; $dot < 90; $dot++) {
            $tone = random_int(185, 225);
            $this->setPixel(
                random_int(0, $this->width - 1),
                random_int(0, $this->height - 1),
                [$tone, min(230, $tone + 12), $tone]
            );
        }
    }

    private function drawCode(string $code): void
    {
        $characters = str_split($code);
        $scale = 7;
        $glyphWidth = 5 * $scale;
        $spacing = 14;
        $totalWidth = count($characters) * $glyphWidth + (count($characters) - 1) * $spacing;
        $startX = (int) floor(($this->width - $totalWidth) / 2);

        foreach ($characters as $index => $character) {
            $rows = self::FONT[$character] ?? null;

            if (! $rows) {
                throw new RuntimeException("No existe una forma definida para el caracter {$character}.");
            }

            $x = $startX + $index * ($glyphWidth + $spacing);
            $y = 15 + random_int(-2, 2);
            $slant = random_int(-1, 1);
            $color = [random_int(4, 16), random_int(91, 112), random_int(39, 57)];

            $this->drawGlyph($rows, $x, $y, $scale, $slant, $color);
        }
    }

    /**
     * @param  array<int, string>  $rows
     * @param  array<int, int>  $color
     */
    private function drawGlyph(array $rows, int $startX, int $startY, int $scale, int $slant, array $color): void
    {
        foreach ($rows as $rowIndex => $row) {
            $rowOffset = (int) round(($rowIndex - 3) * $slant / 2);

            foreach (str_split($row) as $columnIndex => $pixel) {
                if ($pixel !== '1') {
                    continue;
                }

                $blockX = $startX + $columnIndex * $scale + $rowOffset;
                $blockY = $startY + $rowIndex * $scale;

                for ($y = 0; $y < $scale; $y++) {
                    for ($x = 0; $x < $scale; $x++) {
                        $this->setPixel($blockX + $x, $blockY + $y, $color);
                    }
                }
            }
        }
    }

    private function drawForegroundNoise(): void
    {
        // Una sola línea fina mantiene la variación visual sin tapar el código.
        $this->drawLine(
            random_int(0, 20),
            random_int(18, $this->height - 18),
            random_int($this->width - 20, $this->width - 1),
            random_int(18, $this->height - 18),
            [random_int(70, 96), random_int(139, 158), random_int(91, 112)]
        );
    }

    /**
     * @param  array<int, int>  $color
     */
    private function drawLine(int $x1, int $y1, int $x2, int $y2, array $color): void
    {
        $deltaX = abs($x2 - $x1);
        $stepX = $x1 < $x2 ? 1 : -1;
        $deltaY = -abs($y2 - $y1);
        $stepY = $y1 < $y2 ? 1 : -1;
        $error = $deltaX + $deltaY;

        while (true) {
            $this->setPixel($x1, $y1, $color);

            if ($x1 === $x2 && $y1 === $y2) {
                break;
            }

            $doubleError = 2 * $error;

            if ($doubleError >= $deltaY) {
                $error += $deltaY;
                $x1 += $stepX;
            }

            if ($doubleError <= $deltaX) {
                $error += $deltaX;
                $y1 += $stepY;
            }
        }
    }

    /**
     * @param  array<int, int>  $color
     */
    private function setPixel(int $x, int $y, array $color): void
    {
        if ($x < 0 || $x >= $this->width || $y < 0 || $y >= $this->height) {
            return;
        }

        $offset = ($y * $this->width + $x) * 3;
        $this->pixels[$offset] = chr($color[0]);
        $this->pixels[$offset + 1] = chr($color[1]);
        $this->pixels[$offset + 2] = chr($color[2]);
    }

    private function encodePng(): string
    {
        $scanlines = '';
        $rowLength = $this->width * 3;

        for ($row = 0; $row < $this->height; $row++) {
            // El byte cero indica que la fila no usa filtros PNG.
            $scanlines .= "\x00".substr($this->pixels, $row * $rowLength, $rowLength);
        }

        $header = pack('NNCCCCC', $this->width, $this->height, 8, 2, 0, 0, 0);

        return "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', $header)
            .$this->pngChunk('IDAT', gzcompress($scanlines, 7))
            .$this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }
}
