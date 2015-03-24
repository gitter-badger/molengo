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

/**
 * JSON-RPC 2.0 server implementation
 * http://www.jsonrpc.org/specification
 *
 */
trait JsonRpcController
{

    protected $numRpcLog = 1;

    /**
     * JsonRpc request handler
     *
     * @throws \Exception
     */
    public function rpc()
    {
        $arrRequest = array();

        try {

            if (!$this->request->isJsonRpc()) {
                throw new \Exception('Invalid Json-RPC request');
            }
            // parse json
            $strRequest = file_get_contents('php://input');
            $arrRequest = decode_json($strRequest);

            if (!$arrRequest || !is_array($arrRequest)) {
                throw new \Exception('Invalid Json-RPC request (empty)');
            }

            $arrResult = array();
            $strMethod = $arrRequest['method'];

            $class = new \ReflectionClass($this);
            if (!$class->hasMethod($strMethod)) {
                throw new \Exception("Method '$strMethod' not found");
            }

            // check if method is public
            $method = new \ReflectionMethod($this, $strMethod);
            if (!$method->isPublic()) {
                throw new \Exception("Action '$strMethod' is not public");
            }

            // check callback permission status
            if (isset($arrRequest['params'])) {
                $boolStatus = $this->beforeRpcCall($strMethod, $arrRequest['params']);
            } else {
                $boolStatus = $this->beforeRpcCall($strMethod);
            }
            if (!$boolStatus) {
                throw new \Exception('Permission denied', 403);
            }

            // call function
            if (isset($arrRequest['params'])) {
                $arrResult = $this->{$strMethod}($arrRequest['params']);
            } else {
                $arrResult = $this->{$strMethod}();
            }

            // create response with result
            $arrResponse = array();
            $arrResponse['jsonrpc'] = '2.0';
            $arrResponse['id'] = gv($arrRequest, 'id', null);
            $arrResponse['result'] = $arrResult;
            // send json rpc response
            $this->response->sendJson($arrResponse);
        } catch (\Exception $ex) {
            // create json-rpc error object (code und message)
            $arrResponse = array();
            $arrResponse['jsonrpc'] = '2.0';
            $arrResponse['id'] = gv($arrRequest, 'id', null);
            $arrResponse['error']['code'] = $ex->getCode();
            $arrResponse['error']['message'] = $ex->getMessage();
            // log error details
            $this->onRpcError($ex, $arrResponse);
            // send json rpc response
            $this->response->sendJson($arrResponse);
        }
    }

    /**
     * Returns permission status before calling a function.
     * You can override this function
     *
     * @return boolean
     */
    protected function beforeRpcCall()
    {
        return true;
    }

    /**
     * Log RPC error message
     *
     * @param \Exception $ex
     * @param string $mixValue value
     * @return void
     */
    protected function onRpcError($ex, $mixValue = null)
    {
        if (!$this->numRpcLog) {
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
        if ($this->numRpcLog >= 1) {
            $strError .= sprintf("Error: [%s] %s in %s on line %s.", $strErrorCodeText, $strErrorMessage, $strErrorFile, $numErrorLine);
        }

        if ($this->numRpcLog >= 2) {
            $strError .= sprintf("\nBacktrace:\n%s", $strErrorTrace);
        }

        if ($this->numRpcLog >= 3) {
            $strResponse = encode_json($mixValue);
            $strError .= sprintf("\nResponse:\n%s", $strResponse);
        }

        // verbose logging mode
        if ($this->numRpcLog >= 4) {
            $strError .= sprintf("\nGLOBALS:\n%s", dump_var($GLOBALS));
        }

        logmsg('error_rpc', $strError);
    }

}
