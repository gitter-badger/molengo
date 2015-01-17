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

namespace Molengo\Model;

class BaseModel
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

    /** @var DbMySql */
    protected $db = null;

    /**
     * Constructor injection
     *
     * @param mixed $db database object
     */
    public function __construct(&$db = null)
    {
        $this->request = \App::getRequest();
        $this->response = \App::getResponse();
        $this->session = \App::getSession();
        $this->view = \App::getView();

        if ($db === null) {
            // default connection
            $this->db = \App::getDb();
        } else {
            // user defined connection
            $this->db = $db;
        }
    }

    /**
     * Returns database object
     * @return DbMySql
     */
    public function getDb()
    {
        return $this->db;
    }

}
