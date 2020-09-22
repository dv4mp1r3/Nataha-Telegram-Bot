<?php

declare(strict_types=1);

namespace Misc;

/**
 * Жалкая копия https://t.me/vsratoslavbot, но у которой нормальная отрисовка поверх белого фона
 * Class TelegramNeVsratoslavBot
 * @package Misc
 */
class NeVsratoslav extends SecurityExpert
{

    protected $image;

    private int $fontSize;

    private int $textX;

    private int $textY;

    /**
     * @param string $imageBytes прочитанный в строку файл
     * @param string $text текст который нужно добавить
     * @param string $fontPath полный путь к шрифтам
     * @return string обработанное изображение, готовое к сохранению в файл или передаче
     * @throws \Exception
     */
    public function addTextToImage(string $imageBytes, string $text, string $fontPath) : string
    {
        $this->loadImage($imageBytes);
        $this->calculateTextPosition($text, $fontPath);
        $black = imagecolorallocate($this->image, 0, 0, 0);
        $white = imagecolorallocate($this->image, 255, 255, 255);

        imagettftext($this->image, $this->fontSize, 0, $this->textX+2, $this->textY+2, $black, $fontPath, $text);
        imagettftext($this->image, $this->fontSize, 0, $this->textX, $this->textY, $white, $fontPath, $text);

        ob_start();
        imagejpeg($this->image);
        $imageBytes = ob_get_contents();
        ob_end_clean();

        return $imageBytes;
    }

    public function closeImage() : void
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
    }

    public function __destruct()
    {
        $this->closeImage();
    }

    /**
     * @param string $imageBytes прочитанный в строку файл
     * @throws \Exception
     */
    private function loadImage(string $imageBytes): void
    {
        $image = imagecreatefromstring($imageBytes);
        if (is_resource($image)) {
            $this->image = $image;
            return;
        }
        throw new \Exception("Can't load image from string");
    }

    private function calculateTextPosition(string $text, string $fontPath): void
    {
        $sourceImageWidth = imagesx($this->image) * 0.75;
        $sourceImageHeight = imagesy($this->image) * 0.75;
        $supposedFontSize = 1;
        $dummy = imagecreatetruecolor(1, 1);
        $colorBlack = imagecolorallocate($dummy, 0, 0, 0);
        while (true) {
            $bbox = imagettftext($dummy, $supposedFontSize, 0, 0, 0, $colorBlack, $fontPath, $text);
            $x3 = $bbox[4] - $bbox[0];
            $y3 = $bbox[5] - $bbox[1];
            if ($y3 < 0) {
                $y3 *= -1;
            }

            if (($sourceImageWidth - $x3) <= 0) {
                break;
            }

            $this->fontSize = $supposedFontSize;
            $this->textY = (int)(($sourceImageHeight - $y3) / 0.75);
            $this->textX = (int)((($sourceImageWidth / 2) - ($x3 / 2.75)) / 0.75);
            $supposedFontSize++;
        }
        imagedestroy($dummy);
    }

}