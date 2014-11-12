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

class BaseController extends \Molengo\Object
{

    public function __construct()
    {
        $this->initLayout();
    }

    /**
     * Init Layout. Load global template variables
     */
    protected function initLayout()
    {

    }

    /**
     * Request
     *
     * @return \Molengo\Request HTTP Request
     */
    protected function request()
    {
        return App::getRequest();
    }

    /**
     *
     * @return \Molengo\Response HTTP Request
     */
    protected function response()
    {
        return App::getResponse();
    }

    /**
     * Return html template object (singleton)
     * @return HtmlTemplate
     */
    protected function template()
    {
        return App::getTemplate();
    }

    protected function session()
    {
        return App::getSession();
    }

    /**
     * Returns page assets (js, css)
     *
     * @return array
     */
    protected function assets()
    {
        return array();
    }

    /**
     * JsonRpc request handler
     *
     * @throws \Exception
     */
    public function rpc()
    {
        $rpc = new \Molengo\JsonRpcServer();
        $rpc->setController($this);
        $rpc->on('beforeCall', array($this, 'beforeCall'));
        return $rpc->run();
    }

    /**
     * Returns permission status before calling a function.
     * You should override this function
     *
     * @param array $arrParams
     * @return boolean
     */
    protected function beforeCall($arrParams)
    {
        return true;
    }

    /**
     * Returns files content for single page app
     *
     * @return array
     */
    public function getPageContent($arrParams)
    {
        $sp = new \Molengo\SinglePage();
        $sp->on('beforeCall', array($this, 'beforeCall'));
        $arrReturn = $sp->getPageContent($this, $arrParams);
        return $arrReturn;
    }

    /**
     * Send files content like js, css
     *
     * @param array $arrParams
     * @return void
     */
    public function sendFileContent($arrParams)
    {
        // check user login
        if (!App::getUser()->isAuth()) {
            exit;
        }

        $sp = new \Molengo\SinglePage();
        $sp->on('beforeCall', array($this, 'beforeCall'));
        $sp->sendFileContent($this, $arrParams);
    }

    /**
     * Returns page files for single page app
     *
     * @return array
     */
    protected function getPageFiles()
    {
        return array();
    }
}
