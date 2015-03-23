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
 * WebApp (Service Locator)
 *
 * @version: 15.03.20.0
 */
class WebApp
{

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = array();

    /**
     * Database
     *
     * @var \Molengo\DbMySql
     */
    protected $db = null;

    /**
     * View template
     *
     * @var \Molengo\HtmlTemplate
     */
    protected $view = null;

    /**
     * Url router
     *
     * @var \Molengo\SmartUrl
     */
    protected $router = null;

    /**
     * Session
     *
     * @var \Molengo\Session
     */
    protected $session = null;

    /**
     * Request
     *
     * @var \Molengo\Request
     */
    protected $request = null;

    /**
     * Response
     *
     * @var \Molengo\Response
     */
    protected $response = null;

    /**
     * Cache
     *
     * @var \Molengo\CacheFile
     */
    protected $cache = null;

    /**
     * Text
     *
     * @var \Molengo\TranslationBase
     */
    protected $translation = null;

    /**
     * User
     *
     * @var \Model\AppModel
     */
    protected $user = null;

    /**
     * Static instance ob App
     *
     * @var \Molengo\WebApp
     */
    protected static $instance;

    /**
     * Returns WebApp static instance
     *
     * @return \App
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Main
     *
     * @return void
     */
    public static function main()
    {
        $app = static::getInstance();
        $app->init();
        $app->run();
        $app->close();
    }

    /**
     * Init configuration
     *
     * @return void
     * @throws Exception
     */
    public function config()
    {

    }

    /**
     * Init all objects
     *
     * @return void
     */
    public function init()
    {
        // load config
        $this->config();

        // error reporting and handler
        $this->initErrorHandling();

        // session
        $this->initSession();

        // user session
        $this->initUser();

        // url mapping
        $this->initRouter();

        // view templates
        $this->initView();
    }

    /**
     * Start application
     *
     * @return void
     */
    public function run()
    {
        $this->getRouter()->run();
    }

    /**
     * Close app
     *
     * @return void
     */
    public function close()
    {

    }

    /**
     * Return session object
     *
     * @return Session
     */
    public function getSession()
    {
        if ($this->session === null) {
            $this->session = new \Molengo\Session();
        }
        return $this->session;
    }

    /**
     * Init Session
     *
     * @return void
     */
    protected function initSession()
    {
        // start session
        $session = $this->getSession();
        $session->start($this->get('session.name'));
    }

    /**
     * Init user session
     *
     * @return void
     */
    public function initUser()
    {
        $user = $this->getUser();
        // init language
        $user->setLocale('auto');
    }

    /**
     * Return user object
     *
     * @return \Model\UserModel
     */
    public function getUser()
    {
        if ($this->user === null) {
            $this->user = new \Model\UserModel();
        }
        return $this->user;
    }

    /**
     * Return translation object
     *
     * @return TranslationGettext
     */
    public function getTranslation()
    {
        if ($this->translation === null) {
            // since php 5.5.x gettext under windows is not working (#66265)
            $boolWin = strtolower(substr(PHP_OS, 0, 3)) === 'win';
            $boolVersion = version_compare(PHP_VERSION, '5.5.0', '>=');
            if ($boolWin && $boolVersion) {
                $this->translation = new \Molengo\TranslationMoFile();
            } else {
                $this->translation = new \Molengo\TranslationGettext();
            }
        }
        return $this->translation;
    }

    /**
     * Return html template object
     *
     * @return HtmlTemplate
     */
    public function getView()
    {
        if ($this->view === null) {
            $this->view = new \Molengo\HtmlTemplate();
        }
        return $this->view;
    }

    /**
     * Init HTML Template
     *
     * @return void
     */
    protected function initView()
    {
        $view = $this->getView();
        $view->setTemplateDir(G_VIEW_DIR);
        $view->setCacheDir(G_VIEW_CACHE_DIR);
        $view->setCacheMode($this->get('cache.mode', 1));
        $view->setMinMode($this->get('cache.min', 1));
    }

    /**
     * Return router object
     *
     * @return SmartUrl
     */
    public function getRouter()
    {
        if ($this->router === null) {
            $this->router = new \Molengo\SmartUrl();
        }
        return $this->router;
    }

    /**
     * Init Router and define url mappings
     *
     * @return void
     */
    protected function initRouter()
    {
        $router = $this->getRouter();
        $router->addIndexRule();
        if (G_DEBUG) {
            $router->addXdebugRule();
        }
        $router->addAssetRule();
        $router->addControllerRule();
    }

    /**
     * Return request object
     *
     * @return Request
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = new \Molengo\Request();
        }
        return $this->request;
    }

    /**
     * Return Response object
     *
     * @return Response
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->response = new \Molengo\Response();
        }
        return $this->response;
    }

    /**
     * Sendmail
     *
     * @param array $arrEmail
     * @return boolean|string  true = ok else error message as string
     */
    public function sendMail($arrEmail)
    {
        // smtp or mail
        $arrEmail['type'] = gv($arrEmail, 'type', $this->get('smtp.type', 'smtp'));
        // debugging: 1 = errors and messages, 2 = messages only
        $arrEmail['debug'] = gv($arrEmail, 'debug', $this->get('smtp.debug', 0));
        $arrEmail['charset'] = gv($arrEmail, 'charset', $this->get('smtp.charset', 'UTF-8'));
        $arrEmail['smtpauth'] = gv($arrEmail, 'smtpauth', $this->get('smtp.smtpauth', true));
        $arrEmail['authtype'] = gv($arrEmail, 'authtype', $this->get('smtp.authtype', 'LOGIN'));
        // secure transfer enabled REQUIRED for GMail:  'ssl' or 'tls'
        $arrEmail['secure'] = gv($arrEmail, 'secure', $this->get('smtp.secure', false));
        $arrEmail['host'] = gv($arrEmail, 'host', $this->get('smtp.host', '127.0.0.1'));
        $arrEmail['helo'] = gv($arrEmail, 'helo', $this->get('smtp.helo', ''));
        $arrEmail['port'] = gv($arrEmail, 'port', $this->get('smtp.port', '25'));
        $arrEmail['username'] = gv($arrEmail, 'username', $this->get('smtp.username', ''));
        $arrEmail['password'] = gv($arrEmail, 'password', $this->get('smtp.password', ''));
        $arrEmail['from'] = gv($arrEmail, 'from', $this->get('smtp.from', ''));
        $arrEmail['from_name'] = gv($arrEmail, 'from_name', $this->get('smtp.from_name', ''));
        $arrEmail['to'] = gv($arrEmail, 'to', $this->get('smtp.to', ''));
        $arrEmail['bcc'] = gv($arrEmail, 'bcc', $this->get('smtp.bcc', ''));

        $mixReturn = send_mail($arrEmail);

        return $mixReturn;
    }

    /**
     * Write logs in a file
     *
     * @param string $strLevel
     * emergency, alert, critical, error, warning, notice, info, debug
     * @param string $strMessage
     * @param array $arrContext
     * @param string $strFilename (optional)
     * @throws Exception
     */
    public function log($strLevel, $mixMessage, array $arrContext = array())
    {
        logmsg($strLevel, $mixMessage, $arrContext);
    }

    /**
     * Return database object
     *
     * @return DbMySql
     * @throws Exception
     */
    public function getDb()
    {
        if ($this->db === null) {
            // open database connection
            $this->db = new DbMySql();
            if (!$this->db->connect($this->get('db.dsn'))) {
                throw new Exception('Database connection failed!');
            }
        }
        return $this->db;
    }

    /**
     * Set config
     *
     * @param string $strKey
     * @param mixed $mixValue
     * @return void
     */
    public function set($strKey, $mixValue)
    {
        $this->config[$strKey] = $mixValue;
    }

    /**
     * Get config
     *
     * @param string $strKey
     * @param mixed $mixDefault
     * @return mixed
     */
    public function get($strKey, $mixDefault = '')
    {
        if (isset($this->config[$strKey])) {
            return $this->config[$strKey];
        } else {
            return $mixDefault;
        }
    }

    /**
     * Init error handler
     *
     * @return void
     */
    protected function initErrorHandling()
    {
        // error reporting
        if (G_DEBUG) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
            ini_set('display_errors', '0');
        }

        // global error handler

        set_error_handler(array($this, 'handleError'), error_reporting());
        set_exception_handler(array($this, 'handleException'));
        register_shutdown_function(array($this, 'handleShutdown'));
    }

    /**
     * Exception handler for exceptions which are not within a
     * try / catch block. Terminates the current script.
     *
     * @param ErrorException $error
     * @return boolean
     */
    public function handleException($error)
    {
        return $this->handleErrorException($error);
    }

    /**
     * Error handler for any kind of PHP errors (except fatal errors)
     *
     * @param mixed $numCode
     * @param string $strMessage
     * @param string $strFilename
     * @param mixed $numLine
     * @return boolean
     */
    public function handleError($numCode, $strMessage, $strFilename, $numLine)
    {
        $error = new \ErrorException($strMessage, $numCode, 0, $strFilename, $numLine);
        $this->handleErrorException($error);
        // true = continue script
        // false = exit script and set $php_errormsg
        return false;
    }

    /**
     * Handler for Fatal errors
     * e.g. Maximum execution time of x second exceeded
     *
     * @return void
     */
    public function handleShutdown()
    {
        $arrError = error_get_last();
        if (empty($arrError)) {
            return;
        }
        $numType = $arrError['type'];
        if ($numType & error_reporting()) {
            $strMessage = 'Shutdown Error: ' . error_type_text($numType) . ' ' . $arrError['message'];
            $strFile = $arrError['file'];
            $numLine = $arrError['line'];
            $error = new \ErrorException($strMessage, $numType, 0, $strFile, $numLine);
            $this->handleErrorException($error);
        }
    }

    /**
     * Error Handler ErrorException object
     * @param ErrorException $error
     */
    protected function handleErrorException($error)
    {
        $numCode = $error->getCode();
        $strCodeText = error_type_text($numCode);
        $strFile = $error->getFile();
        $numLine = $error->getLine();
        $strMessage = $error->getMessage();
        $strTrace = $error->getTraceAsString();

        $strError = sprintf("Error: [%s] %s in %s on line %s.", $strCodeText, $strMessage, $strFile, $numLine);
        $strError .= sprintf("\nBacktrace:\n%s", $strTrace);
        // verbose logging
        //$strError .= sprintf("\nGLOBALS dump:\n%s", dump_var($GLOBALS));
        // file logging
        $this->log('error', $strError);

        // check display error parameter
        $mixDisplayErrors = strtolower(trim(ini_get('display_errors')));
        if ($mixDisplayErrors == '1' || $mixDisplayErrors == 'on') {
            echo $strError;
        }
    }

}
