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

class BaseController
{

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
        $this->request = \App::getRequest();
        $this->response = \App::getResponse();
        $this->session = \App::getSession();
        $this->view = \App::getView();
        $this->user = \App::getUser();
    }

    /**
     * Register event callback
     *
     * @param type $strEvent
     * @param type $callback
     */
    protected function on($strEvent, $callback)
    {
        $this->arrEvents[$strEvent][] = $callback;
    }

    /**
     * Trigger event
     *
     * @param string $strEvent
     * @param array $arrParams
     * @return boolean
     */
    protected function trigger($strEvent, array $arrParams = null)
    {
        $boolReturn = false;
        if (!empty($this->arrEvents[$strEvent])) {
            foreach ($this->arrEvents[$strEvent] as $event) {
                $boolReturn = $event[0]->$event[1]($arrParams);
                if ($boolReturn === false) {
                    return false;
                }
            }
        }
        return $boolReturn;
    }

    /**
     * Call a PHP class function with optional parameters and return result
     *
     * @param object $controller
     * @param string $strAction e.g. ClassName.echo
     * @param array $arrParams parameter for function
     * @return mixed return value from function
     * @throws \Exception
     */
    protected function call($controller, $strAction, $arrParams = null)
    {
        $arrResult = null;
        //$strMethod = substr(strrchr($strAction, "."), 1);
        // check if function exist
        $class = new \ReflectionClass($controller);
        if (!$class->hasMethod($strAction)) {
            throw new \Exception("Action '$strAction' not found");
        }

        $arrCallbackParams = array('method' => $strAction);
        if (!$this->trigger('beforeCall', $arrCallbackParams)) {
            throw new Exception('Permission denied', 403);
        }

        // call function
        if ($arrParams === null) {
            $arrResult = $controller->{$strAction}();
        } else {
            $arrResult = $controller->{$strAction}($arrParams);
        }
        return $arrResult;
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

    /**
     * JsonRpc request handler
     *
     * @throws \Exception
     */
    public function rpc()
    {
        $rpc = new \Molengo\Controller\JsonRpcController();
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
        $sp = new \Molengo\Controller\SinglePageController();
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
        $sp = new \Molengo\Controller\SinglePageController();
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
