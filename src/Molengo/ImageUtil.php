<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2004-2014 odan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Molengo;

/**
 * Image Class
 * 
 * @version 14.11.11
 */
class ImageUtil
{

    /**
     * Save image object as file
     *
     * @param resource $image
     * @param string $strDestFilename
     * @param string $numQuality
     * @return boolean
     */
    public function convertImage(&$image, $strDestFilename, $numQuality = 100)
    {
        $boolReturn = false;
        $strExt = strtolower(pathinfo($strDestFilename, PATHINFO_EXTENSION));
        switch ($strExt) {
            case "jpeg":
            case "jpg":
                $boolReturn = imagejpeg($image, $strDestFilename, $numQuality);
                break;
            case "gif":
                $boolReturn = imagegif($image, $strDestFilename);
                break;
            case "png":
                $boolReturn = imagepng($image, $strDestFilename, $numQuality);
                break;
        }
        return $boolReturn;
    }

    /**
     * Converto image file to new format
     *
     * @param string $strSourceFilename
     * @param string $strDestFile
     * @param type $numQuality
     * @return boolean
     */
    public function convertFile($strSourceFilename, $strDestFile, $numQuality = 100)
    {
        $im = $this->getImage($strSourceFilename);
        if (!$im) {
            return false;
        }
        $boolReturn = $this->convertImage($im, $strDestFile, $numQuality);
        return $boolReturn;
    }

    /**
     * Add watermark to image
     *
     * @param string $strBackgroundFile background image filename
     * @param string $strWatermarkFile watermark image filename
     * @param array $arrParams optional parameters
     * @return resource image
     */
    public function addWatermark($strBackgroundFile, $strWatermarkFile, array $arrParams = array())
    {
        $w = gv($arrParams, 'w', 1024);
        $h = gv($arrParams, 'h', null);
        $boolSharpen = gv($arrParams, 'sharpen', false);
        $numTopPercent = gv($arrParams, 'top_percent', 5);
        $numLeftPercent = gv($arrParams, 'left_percent', 5);

        $imgWatermark = $this->getImage($strWatermarkFile);
        $imgBackground = $this->getImage($strBackgroundFile);

        $imgBackground = $this->resizeImage($imgBackground, $w, $h, false);

        $src_w = imagesx($imgBackground);
        $src_h = imagesy($imgBackground);

        $src_png_w = imagesx($imgWatermark);
        $src_png_h = imagesy($imgWatermark);

        $dst_w = $src_w;
        $dst_h = $src_h;

        $dst_png_w = $src_w / 3;
        $dst_png_h = intval($src_png_h * $dst_png_w / $src_png_w);


        $num_img2_left = ($dst_w / 100) * $numLeftPercent;
        $num_img2_top = ($dst_h / 100) * $numTopPercent;

        $out = imagecreatetruecolor($dst_w, $dst_h);

        // 1. layer
        // $dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h
        imagecopyresampled($out, $imgBackground, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);

        // append second layer (transparent png)
        imagecopyresampled($out, $imgWatermark, $num_img2_left, $num_img2_top, 0, 0, $dst_png_w, $dst_png_h, $src_png_w, $src_png_h);

        if ($boolSharpen == true) {
            $amount = 50;
            $radius = 0.5;
            $threshold = 3;
            $out = $this->unsharpMask($out, $amount, $radius, $threshold);
        }
        return $out;
    }

    /**
     * Returns image resource by filename
     *
     * @param string $strFilename
     * @return resource|false
     */
    public function getImage($strFilename)
    {
        $im = false;
        $size = getimagesize($strFilename);
        switch ($size["mime"]) {
            case "image/jpeg":
                $im = imagecreatefromjpeg($strFilename);
                break;
            case "image/gif":
                $im = imagecreatefromgif($strFilename);
                break;
            case "image/png":
                $im = imagecreatefrompng($strFilename);
                break;
            case "image/bmp":
            case "image/x-ms-bmp":
                $im = $this->createImageFromBmp($strFilename);
                break;
        }
        return $im;
    }

    /**
     * Create image resource from bmp file
     *
     * @param string $filename
     * @return resource
     */
    public function createImageFromBmp($filename)
    {
        // open the file in binary mode
        if (!$f1 = fopen($filename, "rb")) {
            return false;
        }

        // load file header
        $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return false;
        }

        // load bmp headers
        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
                '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
                '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);
        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }

        // load color palette
        $PALETTE = array();
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        // create image
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24) {
                    $COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
                } elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack("n", substr($IMG, $P, 2));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 2) % 2 == 0) {
                        $COLOR[1] = ($COLOR[1] >> 4);
                    } else {
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
                    if (($P * 8) % 8 == 0) {
                        $COLOR[1] = $COLOR[1] >> 7;
                    } elseif (($P * 8) % 8 == 1) {
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    } elseif (($P * 8) % 8 == 2) {
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    } elseif (($P * 8) % 8 == 3) {
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    } elseif (($P * 8) % 8 == 4) {
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    } elseif (($P * 8) % 8 == 5) {
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    } elseif (($P * 8) % 8 == 6) {
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    } elseif (($P * 8) % 8 == 7) {
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else {
                    return false;
                }
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P+=$BMP['decal'];
        }

        // close the file
        fclose($f1);
        return $res;
    }

    /**
     * Resize image resource
     *
     * @param resource $image
     * @param int $width
     * @param int $height
     * @param bool $sharpen
     * @return resource
     */
    public function resizeImage($image, $width, $height = null, $sharpen = true)
    {
        $width_orig = imagesx($image);
        $height_orig = imagesy($image);

        if ($width > $width_orig) {
            $height = $height_orig;
            $width = $width_orig;
        }

        if ($height == null) {
            // proportional resize
            $height = intval($height_orig * $width / $width_orig);
        }

        // Resample
        $image_p = imagecreatetruecolor($width, $height);
        //imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        $this->copyImageResampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig, 3);

        if ($sharpen == true) {
            $amount = 50;
            $radius = 0.5;
            $threshold = 3;
            $image_p = $this->unsharpMask($image_p, $amount, $radius, $threshold);
        }

        // Output
        //imagejpeg($image_p, $destination, 100);

        return $image_p;
    }

    /**
     * Resize image
     *
     * @param string $strSourceFilename
     * @param string $strDestFilename
     * @param int $numWidth
     * @param int $numHeight
     * @param bool $boolSharpen
     * @return bool|null
     */
    public function resizeFile($strSourceFilename, $strDestFilename, $numWidth, $numHeight = null, $boolSharpen = true)
    {
        $image = $this->getImage($strSourceFilename);
        $image2 = $this->resizeImage($image, $numWidth, $numHeight, $boolSharpen);
        // save to file
        $boolReturn = imagejpeg($image2, $strDestFilename, 100);
        return $boolReturn;
    }

    /**
     * Plug-and-Play copyImageResampled function replaces much slower imagecopyresampled.
     *
     * Just include this function and change all "imagecopyresampled" references to "copyImageResampled".
     *
     * Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
     * Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
     *
     * Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
     *
     * Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
     * 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
     * 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
     * 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
     * 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
     * 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.
     *
     * @param resource $dst_image
     * @param resource $src_image
     * @param int $dst_x
     * @param int $dst_y
     * @param int $src_x
     * @param int $src_y
     * @param int $dst_w
     * @param int $dst_h
     * @param int $src_w
     * @param int $src_h
     * @param int $quality
     * @return boolean
     */
    public function copyImageResampled(&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3)
    {
        if (empty($src_image) || empty($dst_image) || $quality <= 0) {
            return false;
        }
        if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
            $temp = imagecreatetruecolor($dst_w * $quality + 1, $dst_h * $quality + 1);
            imagecopyresized($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
            imagecopyresampled($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
            imagedestroy($temp);
        } else {
            imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        }
        return true;
    }

    /**
     * Unsharp Mask for PHP - version 2.1.1
     * Unsharp mask algorithm by Torstein Hansi 2003-07.
     * thoensi_at_netcom_dot_no.
     * Please leave this notice.
     *
     * New:
     * - In version 2.1 (February 26 2007) Tom Bishop has done some important speed enhancements.
     * - From version 2 (July 17 2006) the script uses the imageconvolution function in PHP
     * version >= 5.1, which improves the performance considerably.
     * http://vikjavev.no/computing/ump.php
     *
     * Unsharp masking is a traditional darkroom technique that has proven very suitable for
     * digital imaging. The principle of unsharp masking is to create a blurred copy of the image
     * and compare it to the underlying original. The difference in colour values
     * between the two images is greatest for the pixels near sharp edges. When this
     * difference is subtracted from the original image, the edges will be accentuated.
     *
     * The Amount parameter simply says how much of the effect you want. 100 is 'normal'.
     * Radius is the radius of the blurring circle of the mask. 'Threshold' is the least
     * difference in colour values that is allowed between the original and the mask. In practice
     * this means that low-contrast areas of the picture are left unrendered whereas edges
     * are treated normally. This is good for pictures of e.g. skin or blue skies.
     *
     * Any suggenstions for improvement of the algorithm, expecially regarding the speed
     * and the roundoff errors in the Gaussian blur process, are welcome.
     * Amount:  	  80 	(typically 50 - 200)
     * Radius: 	0.5	(typically 0.5 - 1)
     * Threshold: 	3	(typically 0 - 5)
     *
     * @param resource $img
     * @param float $amount
     * @param float $radius
     * @param int $threshold
     * @return resource
     */
    protected function unsharpMask($img, $amount, $radius, $threshold)
    {
        // $img is an image that is already created within php using
        // imgcreatetruecolor. No url! $img must be a truecolor image.
        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount = $amount * 0.016;
        if ($radius > 50) {
            $radius = 50;
        }
        $radius = $radius * 2;
        if ($threshold > 255) {
            $threshold = 255;
        }
        $radius = abs(round($radius));     // Only integers make sense.
        if ($radius == 0) {
            return $img;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $imgCanvas = imagecreatetruecolor($w, $h);
        $imgBlur = imagecreatetruecolor($w, $h);

        // Gaussian blur matrix:
        //
        //    1    2    1
        //    2    4    2
        //    1    2    1
        //
        //////////////////////////////////////////////////


        if (function_exists('imageconvolution')) {
            // PHP >= 5.1
            $matrix = array(
                array(1, 2, 1),
                array(2, 4, 2),
                array(1, 2, 1)
            );
            imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
            imageconvolution($imgBlur, $matrix, 16, 0);
        } else {
            // Move copies of the image around one pixel at the time and merge them with weight
            // according to the matrix. The same matrix is simply repeated for higher radii.
            for ($i = 0; $i < $radius; $i++) {
                imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
                imagecopymerge($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
                imagecopymerge($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
                imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);

                imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333); // up
                imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
            }
        }

        if ($threshold > 0) {
            // Calculate the difference between the blurred pixels and the original
            // and set the pixels
            for ($x = 0; $x < $w - 1; $x++) {
                // each row
                for ($y = 0; $y < $h; $y++) {
                    // each pixel
                    $rgbOrig = imagecolorat($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($imgBlur, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    // When the masked pixels differ less from the original
                    // than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                    $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                    $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                    if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                        $pixCol = imagecolorallocate($img, $rNew, $gNew, $bNew);
                        imagesetpixel($img, $x, $y, $pixCol);
                    }
                }
            }
        } else {
            for ($x = 0; $x < $w; $x++) {
                // each row
                for ($y = 0; $y < $h; $y++) {
                    // each pixel
                    $rgbOrig = imagecolorat($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($imgBlur, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                    if ($rNew > 255) {
                        $rNew = 255;
                    } elseif ($rNew < 0) {
                        $rNew = 0;
                    }
                    $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                    if ($gNew > 255) {
                        $gNew = 255;
                    } elseif ($gNew < 0) {
                        $gNew = 0;
                    }
                    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                    if ($bNew > 255) {
                        $bNew = 255;
                    } elseif ($bNew < 0) {
                        $bNew = 0;
                    }
                    $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
                    imagesetpixel($img, $x, $y, $rgbNew);
                }
            }
        }
        imagedestroy($imgCanvas);
        imagedestroy($imgBlur);

        return $img;
    }

}
