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
 * Response represents an HTTP response
 *
 * @version 2014.08.27
 */
class Response
{

    /**
     * Send content to output buffer
     *
     * @param string $strData
     */
    public function send($strData)
    {
        echo $strData;
    }

    /**
     * Send a HTTP header
     *
     * @param string $strHeader
     * @param bool $boolReplace
     * @param int $numHttpCode
     * @return boolean
     *
     * Example
     * <code>
     * $res = new \Molengo\Response();
     * $res->setHeader('Content-Encoding: gzip');
     * $res->setHeader('Content-Type: text/html; charset=utf-8');
     * </code>
     */
    public function setHeader($strHeader, $boolReplace = true, $numHttpCode = null)
    {
        if (headers_sent()) {
            return false;
        }

        if ($strHeader === 'Content-Encoding: gzip') {
            if (!isset($_SERVER["HTTP_ACCEPT_ENCODING"])) {
                return false;
            }

            if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
                ob_start("ob_gzhandler");
            }
        }

        header($strHeader, $boolReplace, $numHttpCode);
        return true;
    }

    /**
     * Send http error message 404
     *
     * @param string $strMessage
     * @param boolean $boolExit
     */
    public function setHeader404($strMessage = null, $boolExit = true)
    {
        // default header for 404 error
        $this->setHeader($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);

        if ($strMessage !== null) {
            echo $strMessage;
        }

        if ($boolExit) {
            exit;
        }
    }

    /**
     * Set header content-type
     *
     * @param string $strType
     */
    public function setContentType($strType)
    {
        $this->setHeader('Content-type: ' . $strType);
    }

    /**
     * Clean output buffer
     */
    public function clean()
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }
    }

    /**
     * Send variable as json string
     *
     * @param mixed $mixValue
     * @return boolean
     */
    public function sendJson($mixValue)
    {
        $this->clean();
        header('Content-Type: application/json');
        echo encode_json($mixValue);
        return true;
    }

    /**
     * Redirect client (browser) and exit
     *
     * @param string $strUrl
     */
    public function redirect($strUrl)
    {
        $this->clean();
        $this->setHeader('Location: ' . $strUrl, true, 302);
        exit;
    }

    /**
     * Redirect by base url
     *
     * @param string $strBaseUrl
     */
    public function redirectBase($strBaseUrl)
    {
        $req = new Request();
        $strUrl = $req->getBaseUrl($strBaseUrl);
        $this->redirect($strUrl);
    }

    /**
     * Send file to output buffer (browser)
     *
     * @param string $strFilename
     * @param string $strAttFilename
     * @param string $strMime
     * @param bool $boolDownload
     * @return bool
     */
    public function sendFile($strFilename, $strAttFilename = null, $strMime = null, $boolDownload = true)
    {
        $boolReturn = false;

        if (!file_exists($strFilename)) {
            return $boolReturn;
        }
        if ($strAttFilename === null) {
            $strAttFilename = $strFilename;
        }
        if ($strMime === null) {
            $fs = new FileSystem();
            $strMime = $fs->mimetype($strAttFilename);
        }

        header('Pragma: public');
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $strMime);

        if ($boolDownload == true) {
            header('Content-Disposition: attachment; filename="' . basename($strAttFilename) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($strAttFilename) . '"');
        }

        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        //header('Content-Length: ' . filesize($strFilename));

        if (ob_get_length() > 0) {
            ob_clean();
            flush();
        }

        readfile($strFilename);
        return true;
    }
}
