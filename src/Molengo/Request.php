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
 * Request represents an HTTP request
 *
 * @version 14.11.22
 */
class Request
{

    /**
     * Gets a HTTP "parameter" value (GET,POST,...)
     *
     * @param string $strKey parameter name
     * @param string $strRequestOrder the order of the methods (gpcesrxij)
     * @param mixed $mixDefault default value
     * @param boolean $boolTrim trim value(s)
     * @return mixed
     */
    public function param($strKey, $strRequestOrder = 'gp', $mixDefault = null, $boolTrim = false)
    {
        $mixReturn = $mixDefault;
        $numLen = strlen($strRequestOrder);

        // methods
        for ($i = 0; $i < $numLen; $i++) {
            $strM = $strRequestOrder[$i];

            switch ($strM) {
                case 'g':
                    if (isset($_GET[$strKey])) {
                        $mixReturn = $_GET[$strKey];
                    }
                    break;

                case 'p':
                    if (isset($_POST[$strKey])) {
                        $mixReturn = $_POST[$strKey];
                    } else {
                        $arrPost = $this->getPost();
                        if (isset($arrPost[$strKey])) {
                            $mixReturn = $arrPost[$strKey];
                        }
                        unset($arrPost);
                    }
                    break;

                case 'c':
                    if (isset($_COOKIE[$strKey])) {
                        $mixReturn = $_COOKIE[$strKey];
                    }
                    break;

                case 's':
                    if (isset($_SESSION[$strKey])) {
                        $mixReturn = $_SESSION[$strKey];
                    }
                    break;


                case 'r':
                    if (isset($_REQUEST[$strKey])) {
                        $mixReturn = $_REQUEST[$strKey];
                    }
                    break;

                case 'f':
                    if (isset($_FILES[$strKey])) {
                        $mixReturn = $_FILES[$strKey];
                    }
                    break;

                case 'e':
                    if (isset($_ENV[$strKey])) {
                        $mixReturn = $_ENV[$strKey];
                    }
                    break;

                case 'x':
                    if (isset($_SERVER[$strKey])) {
                        $mixReturn = $_SERVER[$strKey];
                    }
                    break;

                case 'i':
                    $mixReturn = $this->getContent();
                    break;

                case 'j':
                    $mixReturn = $this->getJson();
                    break;

                default:
            }
        }

        $mixReturn = ($boolTrim) ? trim_array($mixReturn) : $mixReturn;
        return $mixReturn;
    }

    /**
     * Get HTTP-Method
     * @return string|null GET,POST,PUT,DELETE,OPTIONS,TRACE,UNLINK,LINK
     */
    public function getMethod()
    {
        $strReturn = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
        return $strReturn;
    }

    /**
     * Check the HTTP-Method
     * @param string $strMethod get,post,put,delete,head,options
     * @return boolean
     */
    public function is($strMethod)
    {
        $strMethod = strtolower($strMethod);
        $boolReturn = (strtolower($this->getMethod()) === $strMethod);
        return $boolReturn;
    }

    /**
     * Get Http query string
     * @return string|null query string for the Request
     */
    public function getQueryString()
    {
        $strReturn = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
        return $strReturn;
    }

    /**
     * Get HTTP Header Informations from request
     * @return array
     */
    public function getHeaders()
    {
        $arrHeaders = array();
        foreach ($_SERVER as $key => $value) {
            if (strlen($key) > 5 && substr($key, 0, 5) == 'HTTP_') {
                $arrHeaders[$key] = $value;
            }
        }
        return $arrHeaders;
    }

    /**
     * Gets HTTP header from request data
     * @param string $strKey
     * @returns string|null
     */
    public function getHeader($strKey)
    {
        static $arrHeaders = null;

        if ($arrHeaders === null) {
            $arrHeaders = $this->getHeaders();
        }

        $strReturn = isset($arrHeaders[$strKey]) ? $arrHeaders[$strKey] : null;
        return $strReturn;
    }

    /**
     * Get HTTP-Request Content as string
     * The request will automatically decoded
     *
     * @return string|null
     */
    public function getContent()
    {
        $strRequest = file_get_contents('php://input');

        if (!isset($strRequest)) {
            return $strRequest;
        }

        if (!isset($_SERVER['HTTP_CONTENT_ENCODING'])) {
            return $strRequest;
        }

        // Decode gzip/deflate encoded http requests
        // http://bit.ly/LWzHI8
        if ($_SERVER['HTTP_CONTENT_ENCODING'] === 'gzip') {
            // Decode a gzip encoded message (when Content-encoding = gzip)
            $strRequest = gzinflate(substr($strRequest, 10));
        } elseif ($_SERVER['HTTP_CONTENT_ENCODING'] === 'deflate') {
            // Decode a zlib deflated message (when Content-encoding = deflate)
            $strRequest = unpack('n', substr($strRequest, 0, 2));
            // detect broken deflate request
            if ($strRequest[1] % 31 == 0) {
                $strRequest = gzuncompress($strRequest);
            } else {
                $strRequest = gzinflate($strRequest);
            }
        }
        return $strRequest;
    }

    /**
     * Return POST form data parameter / variables
     * @return null
     */
    public function getPost()
    {
        $arrReturn = null;

        if (!isset($_SERVER['HTTP_CONTENT_ENCODING'])) {
            return $_POST;
        }

        // decode request content
        $strRequest = self::getContent();
        if (isset($strRequest)) {
            // Parses the string into variables
            parse_str($strRequest, $arrReturn);
        }
        return $arrReturn;
    }

    /**
     * Get Json Request as Array
     * @return mixed
     */
    public function getJson()
    {
        $strJson = self::getContent();
        $arrJson = decode_json($strJson);
        return $arrJson;
    }

    /**
     * Get current URL
     *
     * @param boolean $boolQueryString - with querystring or not
     * @return string
     */
    public function getUrl($boolQueryString = true)
    {
        $strUrl = $this->getUrlHost();
        $strUri = $this->getUrlPath();

        if ($boolQueryString === false) {
            // remove query string from uri
            $strQueryString = $this->getQueryString();
            if (!empty($strQueryString) && !is_numeric($strQueryString)) {
                $strUri = substr($strUri, 0, strlen($strUri) - strlen($strQueryString) - 1);
            }
        }
        $strUrl .= $strUri;
        return $strUrl;
    }

    /**
     * Returns the path from current URL
     * @return string
     */
    public function getUrlPath()
    {
        $strReturn = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $strReturn = $_SERVER['REQUEST_URI'];
        } else {
            $strReturn = $_SERVER['PATH_INFO'];
        }
        return $strReturn;
    }

    /**
     * Returns a URL rooted at the base url for all relative URLs in a document
     *
     * @param string $strInternalUri the route
     * @param boolean $boolAbsoluteUrl return absolute or relative url
     * @return string base url for $strInternalUri
     */
    public function getBaseUrl($strInternalUri, $boolAbsoluteUrl = true)
    {
        $strReturn = '';
        $strInternalUri = str_replace('\\', '/', $strInternalUri);
        $strDirname = str_replace('\\', '', dirname($_SERVER['SCRIPT_NAME']));
        $strReturn = $strDirname . '/' . ltrim($strInternalUri, '/');
        $strReturn = str_replace('//', '/', $strReturn);

        if ($boolAbsoluteUrl === true) {
            $strReturn = $this->getUrlHost() . $strReturn;
        }
        return $strReturn;
    }

    /**
     * Returns current url
     * @return string
     */
    public function getUrlHost()
    {
        $strUrl = '';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $strUrl = "https://";
        } else {
            $strUrl = "http://";
        }
        if ($_SERVER["SERVER_PORT"] != "80") {
            $strUrl .= $_SERVER["HTTP_HOST"] . ":" . $_SERVER["SERVER_PORT"];
        } else {
            $strUrl .= $_SERVER["HTTP_HOST"];
        }
        return $strUrl;
    }

    /**
     * Returns Accept-Language to detect a user's language.
     *
     * Produce the following structure for my Accept-Language string:
     * Returns: Array([en-ca] => 1, [en] => 0.8, [en-us] => 0.6, [de-de] => 0.4, [de] => 0.2)
     *
     * Source: http://www.thefutureoftheweb.com/blog/use-accept-language-header
     *
     * @param array $arrDefault
     * @return array
     */
    public function getLanguage($arrDefault = array())
    {
        $arrLangs = $arrDefault;
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $arrLangs;
        }
        // break up string into pieces (languages and q factors)
        $arrLangParse = array();
        $strRegex = '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i';
        preg_match_all($strRegex, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $arrLangParse);

        if (count($arrLangParse[1])) {
            // create a list like "en" => 0.8
            $arrLangs = array_combine($arrLangParse[1], $arrLangParse[4]);

            // set default to 1 for any without q factor
            foreach ($arrLangs as $lang => $val) {
                if ($val === '') {
                    $arrLangs[$lang] = 1;
                }
            }

            // sort list based on value
            arsort($arrLangs, SORT_NUMERIC);
        }

        return $arrLangs;
    }

    /**
     * Returns whether request has been made using any secure layer (ssl)
     *
     * @return bool
     */
    public function isHttps()
    {
        $boolReturn = false;
        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER["HTTPS"] == 'on') {
                $boolReturn = true;
            }
        }
        return $boolReturn;
    }

    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * It works if your JavaScript library set an X-Requested-With HTTP header.
     * It is known to work with jQuery, Prototype, Mootools.
     *
     * @return boolean
     */
    public function isXhr()
    {
        // headers X-Requested-With == XMLHttpRequest
        $boolReturn = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        return $boolReturn;
    }

    /**
     * Returns true if a JSON-RCP request has been received
     * @return boolean
     */
    public function isJsonRpc()
    {
        $boolReturn = $_SERVER['REQUEST_METHOD'] == 'POST' &&
                !empty($_SERVER['CONTENT_TYPE']) &&
                (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

        return $boolReturn;
    }

    /**
     * Returns true if runing from localhost
     *
     * @return bool
     */
    public function isLocalhost()
    {
        $boolReturn = isset($_SERVER['REMOTE_ADDR']) &&
                ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' ||
                $_SERVER['REMOTE_ADDR'] === '::1');
        return $boolReturn;
    }

    /**
     * Send forbidden response
     *
     * @return void
     */
    public function forbidden()
    {
        header('HTTP/1.1 403 Forbidden', true, 403);
    }

}
