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

class FileSystem
{

    /**
     * Checks whether a file or directory exists
     *
     * @param string $strFilename
     * @return bool
     */
    public function exist($strFilename)
    {
        return file_exists($strFilename);
    }

    /**
     * Makes directory
     *
     * @param string $strDir
     * @param int $numMode
     * @param bool $boolRecusrive
     * @return bool
     */
    public function mkdir($strDir, $numMode = 0777, $boolRecusrive = true)
    {
        if (!file_exists($strDir)) {
            return mkdir($strDir, $numMode, $boolRecusrive);
        }
        return true;
    }

    /**
     * Remove directory (recursive)
     *
     * @param string $strDir
     * @return bool
     */
    public function rmdir($strDir)
    {
        $arrFiles = array_diff(scandir($strDir), array('.', '..'));
        foreach ($arrFiles as $strFle) {
            $strDirFile = "$strDir/$strFle";
            if (is_dir($strDirFile)) {
                $this->rmdir($strDirFile);
            } else {
                unlink($strDirFile);
            }
        }
        return rmdir($strDir);
    }

    /**
     * Find pathnames matching a pattern (recursive)
     *
     * @param string $strPattern
     * @param int $numFlags
     * @return type
     */
    public function glob($strPattern, $numFlags = 0)
    {
        $arrFiles = glob($strPattern, $numFlags);
        foreach (glob(dirname($strPattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $arrFiles = array_merge($arrFiles, $this->glob($dir . '/' . basename($strPattern), $numFlags));
        }
        return $arrFiles;
    }

    /**
     * Unzip file
     *
     * @param string $strZipFile
     * @param string $strDestDir
     * @return boolean
     */
    public function unzip($strZipFile, $strDestDir)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($strZipFile);
        if ($res === true) {
            $zip->extractTo($strDestDir);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the extension of the filename
     *
     * @param string $strFilename
     * @return string
     */
    public function extension($strFilename)
    {
        return strtolower(pathinfo($strFilename, PATHINFO_EXTENSION));
    }

    /**
     * Returns the filename
     *
     * @param string $strFilename
     * @return string
     */
    public function filename($strFilename)
    {
        return pathinfo($strFilename, PATHINFO_FILENAME);
    }

    /**
     * Returns the basename
     *
     * @param string $strFilename
     * @return string
     */
    public function basename($strFilename)
    {
        return pathinfo($strFilename, PATHINFO_BASENAME);
    }

    /**
     * Returns the dirname
     *
     * @param string $strFilename
     * @return string
     */
    public function dirname($strFilename)
    {
        return pathinfo($strFilename, PATHINFO_DIRNAME);
    }

    /**
     * Create tempfile
     *
     * @param string $strExt
     * @param string $strPath
     * @return string
     */
    public function tempfile($strExt = '.tmp', $strPath = null)
    {
        $strPath = (empty($strPath)) ? G_TMP_DIR : $strPath;
        $strFile = sprintf("%s/%s%s", $strPath, sha1(uuid()), $strExt);
        if (!file_exists($strFile)) {
            touch($strFile);
            umask(0);
            chmod($strFile, 0777);
        } else {
            $strFile = '';
        }
        return $strFile;
    }

    /**
     * Returns mimetype by filename
     *
     * @param string $strFilename
     * @return string
     */
    public function mimetype($strFilename)
    {
        // for unknown types RFC 2046
        $strDefault = 'application/octet-stream';

        $mime = array();
        $mime['js'] = 'application/javascript';
        $mime['css'] = 'text/css';
        $mime['pdf'] = 'application/pdf';
        $mime['txt'] = 'text/plain';
        $mime['csv'] = 'text/comma-separated-values';
        $mime['doc'] = 'application/msword';
        $mime['xls'] = 'application/vnd.ms-excel'; // official
        $mime['ppt'] = 'application/mspowerpoint';
        $mime['xml'] = 'application/xml';
        $mime['zip'] = 'application/zip';
        $mime['htm'] = 'text/html';
        $mime['html'] = 'text/html';
        $mime['shtm'] = 'text/html';
        $mime['xhtm'] = 'application/xhtml+xml';
        $mime['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $mime['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $mime['pptx'] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        $mime['gif'] = 'image/gif';
        $mime['jpeg'] = 'image/jpeg';
        $mime['jpg'] = 'image/jpeg';
        $mime['png'] = 'image/png';
        $mime['tiff'] = 'image/tiff';
        $mime['tif'] = 'image/tiff';
        $mime['bmp'] = 'image/bmp';
        $mime['mp3'] = 'audio/mpeg';

        $strExt = '' . strtolower($this->extension($strFilename));
        return (isset($mime[$strExt])) ? $mime[$strExt] : $strDefault;
    }

}
