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

trait SinglePageController
{

    public function getFiles()
    {
        $arrFiles = $this->getPageFiles();
        if (!empty($arrFiles)) {
            foreach ($arrFiles as $i => $strFile) {
                $arrFiles[$i] = $this->view->getRealFilename($strFile);
            }
        }
        return $arrFiles;
    }

    /**
     * Returns files content for single page app
     *
     * @return array
     */
    public function getPageContent($arrParams)
    {
        $arrReturn = array();
        $strPage = $arrParams['page'];

        $strHtml = '';
        $arrEl = array();

        // get controller files
        $arrFiles = $this->getFiles($arrParams);

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
                            'innerHTML' => $this->view->getFileContent($strFile)
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
                            'innerHTML' => $this->view->getFileContent($strFile)
                        ),
                        'attr' => array(
                            'type' => "text/javascript",
                            'data-page' => 1,
                        )
                    );
                }
            } else {
                // html
                $strHtml .= $this->view->getFileContent($strFile);
            }
        }

        $arrReturn['elements'] = $arrEl;
        $arrReturn['html'] = $strHtml;
        return $arrReturn;
    }

    public function sendFileContent($arrParams = array())
    {
        $strType = gv($arrParams, 'type');
        $strPage = gv($arrParams, 'page');

        $this->response->setHeader('Content-Encoding: gzip');

        if ($strType === 'js') {
            $this->response->setHeader('Content-Type: text/javascript;charset=UTF-8');
        }
        if ($strType === 'css') {
            $this->response->setHeader('Content-Type: text/css;charset=UTF-8');
        }
        // no caching
        // HTTP 1.1.
        $this->response->setHeader('Cache-Control: no-cache, no-store, must-revalidate');
        // HTTP 1.0.
        $this->response->setHeader('Pragma: no-cache');
        // Proxies
        $this->response->setHeader('Expires: 0');

        // get files from this page
        $arrFiles = $this->getFiles($strPage);

        // append content by filetype
        $strContent = '';
        if (!empty($arrFiles)) {
            foreach ($arrFiles as $strFile) {
                if ($strType == file_extension($strFile)) {
                    $strContent .= $this->view->getFileContent($strFile);
                }
            }
        }

        // print content
        echo $strContent;
    }

    /**
     * Returns page files for single page app
     *
     * @var string $strPage page (optional)
     * @return array
     */
    protected function getPageFiles($strPage = null)
    {
        return array();
    }

}
