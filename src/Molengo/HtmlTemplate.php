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
 * PHP Template Engine
 *
 * @version 2014.09.17
 */
class HtmlTemplate
{

    // Template files
    protected $arrFiles = array();
    // Default layout template
    protected $strLayoutFile = null;
    // View vars
    protected $arrVars = array();
    // Blocks with rendered content from html,css,js
    protected $arrBlocks = array();
    // Cache object
    protected $cache;
    // FileSystem
    protected $fs;
    protected $numMinMode = 0;
    protected $numCacheMode = 1;
    protected $strCacheDir = '';

    public function __construct()
    {
        $this->fs = new \Molengo\FileSystem();
    }

    public function setTemplateDir($strTplDir)
    {
        $this->strTplDir = $strTplDir;
    }

    /**
     * Set default layout template
     *
     * @param string $strLayoutFile
     */
    public function setLayoutFile($strLayoutFile)
    {
        $this->strLayoutFile = $this->getRealFilename($strLayoutFile);
    }

    /**
     * Set cache object (required)
     * @param $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Set a template variable
     *
     * You pass name/value pairs, or associative arrays containing
     * the name/value pairs.
     *
     * $tpl->set('str_var', 'my value');
     * $tpl->set(<array>);
     *
     * @param mixed $mixKey
     * @param mixed $mixValue
     * @return void
     */
    public function set($mixKey, $mixValue = null)
    {
        if (empty($mixKey)) {
            return;
        }
        if (is_array($mixKey)) {
            $this->arrVars = array_merge($this->arrVars, $mixKey);
        } else {
            $this->arrVars[(string) $mixKey] = $mixValue;
        }
    }

    /**
     * Get template variable value
     *
     * @param string $strKey
     * @param mixed $mixDefault
     * @return mixed
     */
    public function get($strKey, $mixDefault = '')
    {
        $strReturn = gv($this->arrVars, $strKey, $mixDefault);
        return $strReturn;
    }

    /**
     * Render and include layout template
     *
     * @param string $strLayoutFile (optional)
     */
    public function render($strLayoutFile = null)
    {
        if (!empty($this->arrVars)) {
            extract($this->arrVars, EXTR_REFS);
        }

        $this->renderBlocks();

        // include layout template
        if ($strLayoutFile !== null) {
            include $this->getRealFilename($strLayoutFile);
        } elseif ($this->strLayoutFile != null) {
            include $this->strLayoutFile;
        }
    }

    /**
     * Render blocks
     *
     * @return boolean
     */
    protected function renderBlocks()
    {
        if (empty($this->arrFiles)) {
            return false;
        }

        $this->arrBlocks = array();
        $this->arrBlocks['css'] = array();
        $this->arrBlocks['js'] = array();
        $this->arrBlocks['content'] = array();

        // files
        foreach ($this->arrFiles as $arrFile) {
            $strFilename = $arrFile['filename'];
            $boolInline = $arrFile['inline'];
            $strExt = $this->fs->extension($strFilename);

            if ($strExt == 'js') {
                if ($boolInline) {
                    $strContent = $this->getFileContent($strFilename);
                    $strCode = '<script type="text/javascript">' . $strContent . '</script>' . "\n";
                } else {
                    $strCacheUrl = $this->getFileUrl($strFilename);
                    $strCode = '<script type="text/javascript" src="' . $strCacheUrl . '"></script>' . "\n";
                }
                $this->arrBlocks['js'][] = $strCode;
            } elseif ($strExt == 'css') {
                if ($boolInline) {
                    $strContent = $this->getFileContent($strFilename);
                    $strCode = '<style>' . $strContent . '</style>' . "\n";
                } else {
                    $strCacheUrl = $this->getFileUrl($strFilename);
                    $strCode = '<link rel="stylesheet" type="text/css" href="' . $strCacheUrl . '" media="all" />' . "\n";
                }
                $this->arrBlocks['css'][] = $strCode;
            } else {
                $this->arrBlocks['content'][] = $this->compileFile($strFilename);
            }
        }

        // The content block contains the contents of the rendered view.
        if (!empty($this->arrBlocks['content'])) {
            $this->arrBlocks['content'] = implode("", $this->arrBlocks['content']);
        } else {
            $this->arrBlocks['content'] = '';
        }

        // The script, css and meta blocks contain any content defined in the
        // views using the built-in HTML helper. Useful for including
        // JavaScript and CSS files from views.
        if (!empty($this->arrBlocks['js'])) {
            $this->arrBlocks['js'] = implode("\t", $this->arrBlocks['js']);
        } else {
            $this->arrBlocks['js'] = '';
        }

        if (!empty($this->arrBlocks['css'])) {
            $this->arrBlocks['css'] = implode("\t", $this->arrBlocks['css']);
        } else {
            $this->arrBlocks['css'] = '';
        }

        $this->arrFiles = array();
    }

    /**
     * Fetch block
     *
     * @param string $strName
     * @param mixed $mixDefault
     * @return mixed
     */
    public function block($strName, $mixDefault = '')
    {
        if (isset($this->arrBlocks[$strName])) {
            return $this->arrBlocks[$strName];
        } else {
            return $mixDefault;
        }
    }

    /**
     * Returns full path and filename
     *
     * @param string $strFilename
     * @return string
     */
    public function getRealFilename($strFilename)
    {
        $boolIsAbsolute = substr($strFilename, 0, 1) === '/' ||
                substr($strFilename, 1, 1) === ':';

        if (!$boolIsAbsolute) {
            $strFilename = $this->strTplDir . '/' . $strFilename;
        }
        return $strFilename;
    }

    /**
     * Add template/Js/Css file
     *
     * @param string $strFilename
     * @param boolean $boolInline
     */
    public function addFile($strFilename, $boolInline = false)
    {
        $this->arrFiles[] = array(
            'filename' => $this->getRealFilename($strFilename),
            'inline' => $boolInline
        );
    }

    /**
     * Add array of templates/Js/Css files
     *
     * @param array $arrFiles
     * @param boolean $boolInline
     */
    public function addFiles(array $arrFiles, $boolInline = false)
    {
        if (empty($arrFiles)) {
            return false;
        }

        foreach ($arrFiles as $strFilename) {
            $this->addFile($strFilename, $boolInline);
        }
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
        $strFilename = $this->getRealCacheFilename($strFilename);

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

        $strCacheFile = $this->getRealCacheFilename($strCacheFile);
        return $strCacheFile;
    }

    /**
     * Returns realpath from filename
     *
     * @param string $strFilename
     * @return string
     */
    public function getRealCacheFilename($strFilename)
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
            if (!empty($this->arrVars)) {
                extract($this->arrVars, EXTR_REFS);
            }
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
