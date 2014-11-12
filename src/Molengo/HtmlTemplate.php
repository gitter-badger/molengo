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
        $this->renderBlocks();

        // post global vars to the template scope
        if (!empty($this->arrVars)) {
            extract($this->arrVars, EXTR_REFS);
        }

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
                    $strContent = $this->cache->getFileContent($strFilename);
                    $strCode = '<script type="text/javascript">' . $strContent . '</script>' . "\n";
                } else {
                    $strCacheUrl = $this->cache->getFileUrl($strFilename);
                    $strCode = '<script type="text/javascript" src="' . $strCacheUrl . '"></script>' . "\n";
                }
                $this->arrBlocks['js'][] = $strCode;
            } elseif ($strExt == 'css') {
                if ($boolInline) {
                    $strContent = $this->cache->getFileContent($strFilename);
                    $strCode = '<style>' . $strContent . '</style>' . "\n";
                } else {
                    $strCacheUrl = $this->cache->getFileUrl($strFilename);
                    $strCode = '<link rel="stylesheet" type="text/css" href="' . $strCacheUrl . '" media="all" />' . "\n";
                }
                $this->arrBlocks['css'][] = $strCode;
            } else {
                $this->arrBlocks['content'][] = $this->cache->compileFile($strFilename);
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

}
