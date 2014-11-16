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
 * Real slim and fast Url mapping
 *
 * @version 2014.11.09
 *
 * Example
 * <code>
 * $router = new \Molengo\SmartUrl();
 *
 * // default page (index)
 * $router->on('^/$', 'get', function () {
 *      $ctl = new IndexController();
 *      $ctl->index();
 * });
 *
 * // normal page
 * $router->on('^/test', 'get', function () {
 *      $ctl = new TestController();
 *      $ctl->index();
 * });
 *
 * // page with url parameters
 * // http://www.example.com/hello/max/123
 * $router->on('/hello/(?<name>\w+)/id/(?<id>\d+)', 'get', function ($arrParams) {
 *      printr($arrParams);
 * });
 *
 * // page with GET and POST
 * $router->on('^/contact$', 'get,post', function () {
 *      $ctl = new ContactController();
 *      $ctl->index();
 * });
 * </code>
 */
class SmartUrl
{

    protected $arrRoutes = array();

    /**
     * Map URL pattern to callback function
     * @param string $strMethod (GET, POST, etc) combine with , (e.g 'GET,POST')
     * @param string $strPattern uri regex pattern
     * @param mixed $callback callback function (is_callable)
     */
    public function on($strMethod, $strPattern, $callback)
    {
        $strMethod = strtolower($strMethod);
        if ($strMethod === 'get') {
            $this->arrRoutes['get'][$strPattern] = $callback;
        } else {
            $arrMethods = explode(',', $strMethod);
            foreach ($arrMethods as $strMethod) {
                $this->arrRoutes[$strMethod][$strPattern] = $callback;
            }
        }
    }

    /**
     * Start url router and map url to callback function
     *
     * @return void
     */
    public function run()
    {
        if (empty($this->arrRoutes)) {
            $this->sendError();
            exit;
        }

        $strRequestMethod = strtolower($_SERVER['REQUEST_METHOD']);

        $strUri = $this->getRoutingPath(true);
        $boolMatch = false;

        //check rules against uri
        $arrParams = array();
        if (!empty($this->arrRoutes[$strRequestMethod])) {
            foreach ($this->arrRoutes[$strRequestMethod] as $strPattern => $callback) {
                if (preg_match_all('#' . $strPattern . '#', $strUri, $arrParams)) {
                    $this->cleanParams($arrParams);
                    $boolMatch = true;
                    $callback($arrParams);
                    break;
                }
            }
        }

        if (!$boolMatch) {
            $this->sendError();
        }
        exit;
    }

    /**
     * Send error message
     */
    protected function sendError()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        echo 'Error 404 - File not found';
    }

    /**
     * Remove unnecessary values from array
     *
     * @param array $arrParams
     */
    protected function cleanParams(&$arrParams)
    {
        if (empty($arrParams)) {
            return $arrParams;
        }

        foreach ($arrParams as $k => $v) {
            if (is_numeric($k)) {
                unset($arrParams[$k]);
                continue;
            }
            if (!isset($v[1])) {
                $arrParams[$k] = $v[0];
            }
        }
    }

    /**
     * Returns the url leading up to the current script.
     * Used to make the webapp portable to other locations.
     *
     * @param bool $boolWithQuerystring
     * @return string uri
     */
    public function getRoutingPath($boolWithQuerystring = true)
    {
        // get URI from URL
        $strUri = $this->getUri();

        // detect and remove subfolder from URI
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $strDirname = dirname($_SERVER['SCRIPT_NAME']);
            $numLen = strlen($strDirname);
            if ($numLen > 0 && $strDirname != '/' && $strDirname != "\\") {
                $strUri = substr($strUri, $numLen);
            }
        }

        if ($boolWithQuerystring === false) {
            if (isset($_SERVER['QUERY_STRING'])) {
                $strQueryString = $_SERVER['QUERY_STRING'];
                if (!empty($strQueryString) && !is_numeric($strQueryString)) {
                    // remove query string
                    $strUri = substr($strUri, 0, strlen($strUri) - strlen($strQueryString) - 1);
                }
            }
        }

        return $strUri;
    }

    /**
     * Returns current URI
     *
     * @return string
     */
    protected function getUri()
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
     * Add index page rule
     */
    public function addIndexRule()
    {
        // default
        $this->on('get,post', '^/$', function () {
            $this->callController(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        });
    }

    /**
     * Add xdebugger rule
     */
    public function addXdebugRule()
    {
        // xdebug start
        $this->on('get,post', '^/\?XDEBUG_SESSION_START=netbeans-xdebug$', function () {
            $this->callController(array(
                'controller' => 'index',
                'action' => 'index'
            ));
        });
    }

    /**
     * Add assets url rules
     */
    public function addAssetRule()
    {
        $this->on('get', '^/assets/page/(?<type>(js|css))/(?<page>.*)\.(js|css)$', function ($arrParams) {
            $arrParams = array(
                'controller' => $arrParams['page'],
                'action' => 'sendFileContent',
                'params' => $arrParams
            );
            $this->callController($arrParams);
        });
    }

    /**
     * Add default controler/action rule
     */
    public function addControllerRule()
    {
        // controller/action
        $this->on('get,post', '^/(?<controller>\w+)\/?(?<action>\w+)?\?{0,1}', function ($arrParams) {
            $this->callController($arrParams);
        });
    }

    /**
     * Call controller action
     *
     * @param array $arrParams
     */
    protected function callController($arrParams)
    {
        if (empty($arrParams['controller'])) {
            $strController = 'index';
        } else {
            $strController = $arrParams['controller'];
        }
        $strController = str_replace('_', ' ', strtolower($strController));
        $strController = ucwords(strtolower($strController));
        $strController = str_replace(' ', '', $strController);
        $strController = '\\Controller\\' . $strController . 'Controller';
        if (empty($arrParams['action'])) {
            $strAction = 'index';
        } else {
            $strAction = $arrParams['action'];
        }
        $reflection = new \ReflectionMethod($strController, $strAction);
        if ($reflection->isPublic()) {
            $obj = new $strController();
            if (isset($arrParams['params'])) {
                $obj->{$strAction}($arrParams['params']);
            } else {
                $obj->{$strAction}();
            }
        }
        exit;
    }
}
