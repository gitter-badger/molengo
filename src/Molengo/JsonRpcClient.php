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
 * JsonRpc Client
 *
 * @version 2014.08.27
 *
 * Example:
 * <code>
 * $rpc = new JsonRpcClient();
 * $rpc->setUrl('http://www.example.com/rpc.php');
 *
 * $arrParams = array();
 * $arrParams['text'] = 'hello json server';
 * $arrResponse = $rpc->call('echo', $arrParams);
 *
 * if($arrResponse['result']['status'] == '1') {
 *     echo $arrResponse['result']['echo'];
 * } else {
 *     echo $arrResponse['error']['message'];
 * }
 * </code>
 */
class JsonRpcClient
{

    protected $arrResponse = array();
    protected $strUrl = '';
    protected $numTimelimit = 300;

    /**
     * Init
     */
    public function __construct()
    {
        ini_set('memory_limit', '-1');
        $this->setTimelimit($this->numTimelimit);
        $this->clear();
    }

    /**
     * Cleanup
     *
     * @return void
     */
    public function clear()
    {
        $this->arrResponse = array();
    }

    /**
     * Set Json-RPC endpoint url
     *
     * @param string $strUrl
     */
    public function setUrl($strUrl)
    {
        $this->strUrl = $strUrl;
    }

    /**
     * Set http timelimit
     *
     * @param int $numTimelimit seconds
     */
    public function setTimelimit($numTimelimit)
    {
        $this->numTimelimit = $numTimelimit;
        set_time_limit($this->numTimelimit);
    }

    /**
     * Execute Json-RPC Request
     *
     * @param string $strMethod
     * @param array $arrParams
     * @return array
     */
    public function call($strMethod, $arrParams = null)
    {
        $this->clear();

        $arrRequest = array();
        $arrRequest['jsonrpc'] = '2.0';
        $arrRequest['id'] = uniqid('', true);
        $arrRequest['method'] = $strMethod;
        if (isset($arrParams)) {
            $arrRequest['params'] = $arrParams;
        }

        $strJson = encode_json($arrRequest);
        $strResponse = $this->post($strJson);
        $this->arrResponse = decode_json($strResponse);
        return $this->arrResponse;
    }

    /**
     * Returns response result value
     *
     * @param string $strKey
     * @param mixed $mixDefault
     * @return mixed
     */
    public function getResult($strKey = null, $mixDefault = null)
    {
        $mixReturn = $mixDefault;

        if (empty($this->arrResponse)) {
            return $mixDefault;
        }

        if ($strKey === null) {
            if (isset($this->arrResponse['result'])) {
                $mixReturn = $this->arrResponse['result'];
            }
        } else {
            if (isset($this->arrResponse['result'][$strKey])) {
                $mixReturn = $this->arrResponse['result'][$strKey];
            }
        }

        return $mixReturn;
    }

    /**
     * Returns last error message
     *
     * @return string
     */
    public function getError()
    {
        $strReturn = null;
        if (isset($this->arrResponse['error']['message'])) {
            $strReturn = $this->arrResponse['error']['message'];
        }
        return $strReturn;
    }

    /**
     * HTTP POST-Request
     *
     * @param string $strContent
     * @return boolean
     */
    protected function post($strContent = '')
    {
        $strReturn = '';

        $arrDefaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $this->strUrl,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => $this->numTimelimit,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => $strContent,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, $arrDefaults);

        $strReturn = curl_exec($ch);
        $numHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($numHttpCode < 200 || $numHttpCode >= 300) {
            $numError = curl_errno($ch);
            $strError = curl_error($ch);
            $strText = trim(strip_tags($strReturn));
            curl_close($ch);
            throw new \Exception(trim("HTTP Error [$numHttpCode] $strError. $strText"), $numError);
        }

        curl_close($ch);
        return $strReturn;
    }
}
