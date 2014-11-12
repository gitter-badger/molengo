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
 * Cache File
 * with Minify/Compressor for JS, CSS, HTML
 */
class CacheFile
{

    protected $numMinMode = 0;
    protected $numCacheMode = 1;
    protected $strCacheDir = '';
    // Filesystem
    protected $fs;

    public function __construct()
    {
        $this->fs = new \Molengo\FileSystem();
    }

    /**
     * Set cache directory
     *
     * @param string $strDir
     */
    public function setCacheDir($strDir)
    {
        $this->strCacheDir = $strDir;
        if (!is_dir($this->strCacheDir)) {
            $this->createDir($this->strCacheDir);
        }
    }

    /**
     * Set cache mode
     * 0 = no caching
     * 1 = cache enabled
     * @param integer $numValue
     */
    public function setCacheMode($numValue)
    {
        $this->numCacheMode = $numValue;
    }

    /**
     * Set minification mode for js and css
     * @param integer $numValue
     */
    public function setMinMode($numValue)
    {
        $this->numMinMode = $numValue;
    }

    /**
     * Return cache file content
     *
     * @param string $strFilename
     * @return string
     * @throws \Exception
     */
    public function getFileContent($strFilename)
    {
        $strFilename = $this->getRealFilename($strFilename);

        if (!file_exists($strFilename)) {
            throw new \Exception("File not found: " . basename($strFilename));
        }

        if (empty($this->strCacheDir)) {
            throw new \Exception("Cache dir not defined");
        }

        if ($this->numCacheMode) {
            $strCacheFile = $this->getCacheFile($strFilename);
            //$strReturn = $this->compileFile($strCacheFile);
            $strReturn = file_get_contents($strCacheFile);
        } else {
            if ($this->numMinMode) {
                $strReturn = $this->compressFile($strFilename);
            } else {
                $strReturn = $this->compileFile($strFilename);
            }
        }
        return $strReturn;
    }

    /**
     * Returns cache filename
     *
     * @param string $strFilename
     * @return string
     */
    public function getCacheFile($strFilename)
    {
        $strExt = $this->fs->extension($strFilename);
        if (empty($strExt)) {
            $strExt = '.cache';
        }

        // look for sha1 cache file
        $strLocale = '';
        if (isset($_SESSION['locale'])) {
            // create different hash for each language
            $strLocale = $_SESSION['locale'];
        }
        $strChecksum = sha1($strFilename . $strLocale);
        $strChecksumDir = $this->strCacheDir . '/' . substr($strChecksum, 0, 2);
        $strCacheFile = $strChecksumDir . '/' . substr($strChecksum, 2) . '.' . $strExt;

        // create cache dir
        if (!file_exists($strChecksumDir)) {
            $this->createDir($strChecksumDir);
        }

        if (!file_exists($strCacheFile)) {
            $this->touchCacheFile($strCacheFile);
        }

        $nmFileTime = filemtime($strFilename);
        $numCacheFileTime = filemtime($strCacheFile);
        $numCacheFileSize = filesize($strCacheFile);

        // compare modification time
        if (($nmFileTime != $numCacheFileTime) || ($numCacheFileSize == 0)) {
            // file has changed
            // update cache file
            if ($this->numMinMode) {
                $this->compressToFile($strFilename, $strCacheFile);
            } else {
                // copy the file to the cache folder
                copy($strFilename, $strCacheFile);
            }
            // sync timestamp
            $this->touchCacheFile($strCacheFile, $nmFileTime);
        }

        $strCacheFile = $this->getRealFilename($strCacheFile);
        return $strCacheFile;
    }

    /**
     * Returns realpath from filename
     *
     * @param string $strFilename
     * @return string
     */
    public function getRealFilename($strFilename)
    {
        $strFilename = realpath($strFilename);
        $strFilename = str_replace("\\", '/', $strFilename);
        return $strFilename;
    }

    /**
     * Returns url for filename
     *
     * @param string $strFilename
     * @return string
     */
    public function getFileUrl($strFilename)
    {
        // for url we need to cache it
        $strCacheFile = $this->getCacheFile($strFilename);
        $strFile = basename($strCacheFile);
        $strDir = pathinfo($strCacheFile, PATHINFO_DIRNAME);
        $arrDir = explode('/', $strDir);
        // cache/ab
        $arrDir = array_slice($arrDir, count($arrDir) - 2);
        // cache/ab/filename.ext
        $strPath = implode('/', $arrDir) . '/' . $strFile;
        // create url
        $req = new Request();
        $strCacheUrl = $req->getBaseUrl($strPath . '?' . gu(basename($strFilename)));
        return $strCacheUrl;
    }

    /**
     * Returns compressed file (js/css) content
     *
     * @param string $strFilename
     * @return string
     */
    public function compressFile($strFilename)
    {
        $strExt = $this->fs->extension($strFilename);
        $strReturn = $this->compileFile($strFilename);

        if ($strExt == 'js') {
            //$strReturn = Minify\JSMin::minify($strReturn);
            $strReturn = \JsMin\Minify::minify($strReturn);
        }

        if ($strExt == 'css') {
            //$strReturn = \Minify_CSS_Compressor::process($strReturn);
            $strReturn = Minify\MinifyCSS::process($strReturn);
        }
        return $strReturn;
    }

    /**
     * Compress file to destination file
     *
     * @param string $strFilename
     * @param string $strDestFilename
     * @return boolean
     */
    public function compressToFile($strFilename, $strDestFilename)
    {
        $boolReturn = true;
        $strContent = $this->compressFile($strFilename);
        file_put_contents($strDestFilename, $strContent);
        umask(0);
        chmod($strDestFilename, 0777);
        return $boolReturn;
    }

    /**
     * Parse php files
     *
     * @param string $strFilename
     * @return string parsed content
     */
    public function compileFile($strFilename)
    {
        $strExt = $this->fs->extension($strFilename);
        if ($strExt === 'php') {
            ob_start();
            require $strFilename;
            return ob_get_clean();
        } else {
            return file_get_contents($strFilename);
        }
    }

    /**
     * Create directory
     *
     * @param string $strDir
     */
    protected function createDir($strDir)
    {
        umask(0);
        mkdir($strDir, 0777, true);
        umask(0);
        chmod($strDir, 0777);
    }

    /**
     * Create file
     *
     * @param string $strFilename
     * @param int $numFileTime
     */
    protected function touchCacheFile($strFilename, $numFileTime = null)
    {
        if ($numFileTime === null) {
            touch($strFilename);
        } else {
            touch($strFilename, $numFileTime);
        }
        umask(0);
        chmod($strFilename, 0777);
        umask(0);
    }

    /**
     * Clear and remove cache directory
     */
    public function clearCache()
    {
        $this->fs->rmdir($this->strCacheDir);
    }

}
