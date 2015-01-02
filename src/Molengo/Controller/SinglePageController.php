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

namespace Molengo\Controller;

use App;

class SinglePageController extends \Molengo\Controller\BaseController
{

    protected $cache;

    public function __construct()
    {
        parent::__construct();
        $this->cache = App::getCache();
    }

    public function getFiles($controller)
    {
        $arrFiles = $this->call($controller, 'getPageFiles');
        if (!empty($arrFiles)) {
            foreach ($arrFiles as $i => $strFile) {
                $arrFiles[$i] = $this->getRealFilename($strFile);
            }
        }
        return $arrFiles;
    }

    /**
     * Returns full path and filename
     *
     * @param string $strFilename
     * @return string
     */
    protected function getRealFilename($strFilename)
    {
        $boolIsAbsolute = substr($strFilename, 0, 1) === '/' ||
                substr($strFilename, 1, 1) === ':';

        if (!$boolIsAbsolute) {
            $strFilename = G_VIEW_DIR . '/' . $strFilename;
        }
        return $strFilename;
    }

    /**
     * Returns files content for single page app
     *
     * @return array
     */
    public function getPageContent($controller, $arrParams)
    {
        $arrReturn = array();
        $cache = App::getCache();
        $strPage = $arrParams['page'];

        $strHtml = '';
        $arrEl = array();

        // get controller files
        $arrFiles = $this->getFiles($controller, $arrParams);

        $arrReturn['elements'] = null;
        $arrReturn['html'] = '';

        if (empty($arrFiles)) {
            throw new \Exception('No page files found');
        }

        foreach ($arrFiles as $strFile) {
            $strExt = file_extension($strFile);
            if ($strExt == 'css') {
                if (true) {
                    $arrEl[] = array(
                        'tag' => 'link',
                        'attr' => array(
                            'type' => "text/css",
                            'rel' => 'stylesheet',
                            'data-page' => 1,
                            'href' => sprintf('assets/page/css/%s.css', $strPage)
                        )
                    );
                }

                if (false) {
                    // inline css will not work with relative url/path in css

                    $arrEl[] = array(
                        'tag' => 'style',
                        'prob' => array(
                            'innerHTML' => $cache->getFileContent($strFile)
                        ),
                        'attr' => array(
                            'data-page' => 1,
                        )
                    );
                }
            } elseif ($strExt == 'js') {

                if (G_DEBUG) {
                    // load js in extra request for debugging
                    $arrEl[] = array(
                        'tag' => 'script',
                        'attr' => array(
                            'type' => "text/javascript",
                            'data-page' => 1,
                            'src' => sprintf('assets/page/js/%s.js', $strPage)
                        )
                    );
                } else {
                    // load js direct into content (much faster)
                    $arrEl[] = array(
                        'tag' => 'script',
                        'prob' => array(
                            'innerHTML' => $cache->getFileContent($strFile)
                        ),
                        'attr' => array(
                            'type' => "text/javascript",
                            'data-page' => 1,
                        )
                    );
                }
            } else {
                // html
                $strHtml .= $cache->getFileContent($strFile);
            }
        }

        $arrReturn['elements'] = $arrEl;
        $arrReturn['html'] = $strHtml;
        return $arrReturn;
    }

    public function sendFileContent($controller, $arrParams = array())
    {
        $res = App::getResponse();
        $cache = App::getCache();
        $strType = gv($arrParams, 'type');
        $strPage = gv($arrParams, 'page');

        $res->setHeader('Content-Encoding: gzip');

        if ($strType === 'js') {
            $res->setHeader('Content-Type: text/javascript;charset=UTF-8');
        }
        if ($strType === 'css') {
            $res->setHeader('Content-Type: text/css;charset=UTF-8');
        }
        // no caching
        // HTTP 1.1.
        $res->setHeader('Cache-Control: no-cache, no-store, must-revalidate');
        // HTTP 1.0.
        $res->setHeader('Pragma: no-cache');
        // Proxies
        $res->setHeader('Expires: 0');

        // get files from this page
        $arrFiles = $this->getFiles($controller, $strPage);

        // append content by filetype
        $strContent = '';
        if (!empty($arrFiles)) {
            foreach ($arrFiles as $strFile) {
                if ($strType == file_extension($strFile)) {
                    $strContent .= $cache->getFileContent($strFile);
                }
            }
        }

        // print content
        echo $strContent;
    }

}
