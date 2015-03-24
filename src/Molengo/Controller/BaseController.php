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

class BaseController
{

    /** @var \App WebApp */
    public $app;

    /** @var \Molengo\Request HTTP request */
    public $request;

    /** @var \Molengo\Response HTTP response */
    public $response;

    /** @var \Molengo\Session HTTP session  */
    public $session;

    /** @var \Molengo\HtmlTemplate View template */
    public $view;

    /** @var \Molengo\Model\UserModel */
    public $user;

    /** @var array Events */
    protected $arrEvent = array();

    /** @var bool */
    protected $boolAuth = true;

    public function __construct()
    {
        $this->app = \App::getInstance();
        $this->request = $this->app->getRequest();
        $this->response = $this->app->getResponse();
        $this->session = $this->app->getSession();
        $this->view = $this->app->getView();
        $this->user = $this->app->getUser();
    }

    /**
     * Returns page assets (js, css)
     *
     * @return array
     */
    protected function getAssets()
    {
        return array();
    }

    /**
     * Returns translated text
     *
     * @return array
     */
    protected function getTextAssets()
    {
        return array();
    }

    /**
     * Set text to global layout
     *
     * @return void
     */
    protected function setTextAssets()
    {
        $arrText = $this->getTextAssets();
        if (!empty($arrText)) {
            $strJs = encode_json($arrText);
            $strJs = sprintf("<script>\$d.addText(%s);</script>", $strJs);
            $this->view->set('jstext', $strJs);
        }
    }

}
