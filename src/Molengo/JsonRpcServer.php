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
 * JSON-RPC 2.0 server implementation
 * http://www.jsonrpc.org/specification
 *
 * @version 14.11.10
 */
class JsonRpcServer extends \Molengo\Object
{

    protected $controller;
    protected $numLoggingMode = 1;

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Set Logging Mode
     *
     * @param int $numLoggingMode how many details
     * 0 = None
     * 1 = Default
     * 2 = Backtrace
     * 3 = Response
     * 4 = Verbose (Dump)
     */
    public function setLoggingMode($numLoggingMode)
    {
        $this->numLoggingMode = $numLoggingMode;
    }

    /**
     * JsonRpc request handler
     *
     * @throws \Exception
     */
    public function run()
    {
        $arrRequest = array();

        try {
            if (!$this->isRpc()) {
                throw new \Exception('Invalid Json-RPC request');
            }

            $strRequest = file_get_contents('php://input');
            $arrRequest = decode_json($strRequest);

            if (!$arrRequest || !is_array($arrRequest)) {
                throw new \Exception('Invalid Json-RPC request (empty)');
            }

            $arrResult = array();

            $strRequestMethod = $arrRequest['method'];

            // call function
            if (isset($arrRequest['params'])) {
                $arrResult = $this->call($this->controller, $strRequestMethod, $arrRequest['params']);
            } else {
                $arrResult = $this->call($this->controller, $strRequestMethod, null);
            }

            // create response with result
            $arrResponse = array();
            $arrResponse['jsonrpc'] = '2.0';
            $arrResponse['id'] = gv($arrRequest, 'id', null);
            $arrResponse['result'] = $arrResult;
            $strResponse = encode_json($arrResponse);

            // send json string
            if (ob_get_length() > 0) {
                ob_clean();
            }
            $this->sendHeader();
            echo $strResponse;
        } catch (\Exception $ex) {
            // create json-rpc error object (code und message)
            $arrResponse = array();
            $arrResponse['jsonrpc'] = '2.0';
            $arrResponse['id'] = gv($arrRequest, 'id', null);
            $arrResponse['error']['code'] = $ex->getCode();
            $arrResponse['error']['message'] = $ex->getMessage();
            $strResponse = encode_json($arrResponse);

            // log error details
            $this->logError($ex, $strResponse);

            if (ob_get_length() > 0) {
                ob_clean();
            }

            $this->sendHeader();
            echo $strResponse;
        }
    }

    /**
     * Return if a JSON-RCP request has been received
     *
     * @return boolean
     */
    public function isRpc()
    {
        $boolReturn = $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_SERVER['CONTENT_TYPE']);
        $boolReturn = $boolReturn && (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
        return $boolReturn;
    }

    /**
     * Send forbidden response and exit
     *
     * @return void
     */
    protected function exitForbidden()
    {
        header('HTTP/1.1 403 Forbidden', true, 403);
        exit;
    }

    /**
     * Send Json response header
     *
     * @return void
     */
    protected function sendHeader()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
                if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                    ob_start('ob_gzhandler');
                    header('Content-Encoding: gzip');
                }
            }
        }
    }

    /**
     * Log error message
     *
     * @param \Exception $ex
     * @param string $strResponse json response
     */
    protected function logError($ex, $strResponse = null)
    {
        if ($this->numLoggingMode == 0) {
            return;
        }
        // get error infos
        $numErrorCode = $ex->getCode();
        $strErrorCodeText = error_type_text($numErrorCode);
        $strErrorFile = $ex->getFile();
        $numErrorLine = $ex->getLine();
        $strErrorMessage = $ex->getMessage();
        $strErrorTrace = $ex->getTraceAsString();

        // log error details
        $strError = '';
        if ($this->numLoggingMode >= 1) {
            $strError .= sprintf("Error: [%s] %s in %s on line %s.", $strErrorCodeText, $strErrorMessage, $strErrorFile, $numErrorLine);
        }

        if ($this->numLoggingMode >= 2) {
            $strError .= sprintf("\nBacktrace:\n%s", $strErrorTrace);
        }

        if ($this->numLoggingMode >= 3) {
            $strError .= sprintf("\nResponse:\n%s", $strResponse);
        }

        // verbose logging mode
        if ($this->numLoggingMode >= 4) {
            $strError .= sprintf("\nGLOBALS:\n%s", dump_var($GLOBALS));
        }

        logmsg('error_rpc', $strError);
    }
}
