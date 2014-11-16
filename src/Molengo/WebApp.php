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
 * @version: 14.11.16.0
 */
class WebApp
{

    /**
     * Configuration
     *
     * @var array
     */
    protected static $config = array();

    /**
     * Database
     *
     * @var DbMySql
     */
    protected static $db = null;

    /**
     * Template
     *
     * @var HtmlTemplate
     */
    protected static $tpl = null;

    /**
     * Url router
     *
     * @var SmartUrl
     */
    protected static $router = null;

    /**
     * Session
     *
     * @var Session
     */
    protected static $session = null;

    /**
     * Request
     *
     * @var Request
     */
    protected static $request = null;

    /**
     * Response
     *
     * @var Response
     */
    protected static $response = null;

    /**
     * Cache
     *
     * @var CacheFile
     */
    protected static $cache = null;

    /**
     * Text
     *
     * @var TranslationBase
     */
    protected static $translation = null;

    /**
     * User
     *
     * @var CacheFile
     */
    protected static $user = null;

    /**
     * Init configuration
     *
     * @return void
     * @throws Exception
     */
    public static function init()
    {
        return true;
    }

    /**
     * Init all objects
     *
     * @return void
     */
    protected static function initAll()
    {
        // error reporting and handler
        static::initErrorHandling();

        // session
        static::initSession();

        // user session
        static::initUser();

        // cache
        static::initCache();

        // url mapping
        static::initRouter();

        // html templates
        static::initTemplate();
    }

    /**
     * Start application
     *
     * @return void
     */
    public static function run()
    {
        static::getRouter()->run();
    }

    /**
     * Close app
     *
     * @return void
     */
    public static function close()
    {

    }

    /**
     * Return session object
     *
     * @return Session
     */
    public static function getSession()
    {
        if (static::$session === null) {
            static::$session = new Session();
        }
        return static::$session;
    }

    /**
     * Init Session
     *
     * @return void
     */
    protected static function initSession()
    {
        // start session
        $session = static::getSession();
        $session->start(static::get('session.name'));
    }

    /**
     * Init user session
     *
     * @return void
     */
    public static function initUser()
    {
        $user = static::getUser();
        // init language
        $user->setLocale('auto');
    }

    /**
     * Return user object
     *
     * @return \Model\UserModel
     */
    public static function getUser()
    {
        if (static::$user === null) {
            static::$user = new \Model\UserModel();
        }
        return static::$user;
    }

    /**
     * Return translation object
     *
     * @return TranslationGettext
     */
    public static function getTranslation()
    {
        if (static::$translation === null) {
            // since php 5.5.x gettext under windows is not working (#66265)
            $boolWin = strtolower(substr(PHP_OS, 0, 3)) === 'win';
            $boolVersion = version_compare(PHP_VERSION, '5.5.0', '>=');
            if ($boolWin && $boolVersion) {
                static::$translation = new TranslationMoFile();
            } else {
                static::$translation = new TranslationGettext();
            }
        }
        return static::$translation;
    }

    /**
     * Return html template object
     *
     * @return HtmlTemplate
     */
    public static function getTemplate()
    {
        if (static::$tpl === null) {
            static::$tpl = new HtmlTemplate();
        }
        return static::$tpl;
    }

    /**
     * Init HTML Template
     *
     * @return void
     */
    protected static function initTemplate()
    {
        $tpl = static::getTemplate();
        $tpl->setTemplateDir(G_VIEW_DIR);

        // cache
        $cache = static::getCache();
        $tpl->setCache($cache);
    }

    /**
     * CacheFile
     *
     * @return CacheFile
     */
    public static function getCache()
    {
        if (static::$cache === null) {
            static::$cache = new CacheFile();
        }
        return static::$cache;
    }

    /**
     * Init cache
     *
     * @return void
     */
    protected static function initCache()
    {
        $cache = static::getCache();
        $cache->setCacheDir(G_CACHE_DIR);
        $cache->setCacheMode(static::get('cache.mode', 1));
        $cache->setMinMode(static::get('cache.min', 1));
    }

    /**
     * Return router object
     *
     * @return SmartUrl
     */
    public static function getRouter()
    {
        if (static::$router === null) {
            static::$router = new SmartUrl();
        }
        return static::$router;
    }

    /**
     * Init Router and define url mappings
     *
     * @return void
     */
    protected static function initRouter()
    {
        $router = static::getRouter();
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
    public static function getRequest()
    {
        if (static::$request === null) {
            static::$request = new Request();
        }
        return static::$request;
    }

    /**
     * Return Response object
     *
     * @return Response
     */
    public static function getResponse()
    {
        if (static::$response === null) {
            static::$response = new Response();
        }
        return static::$response;
    }

    /**
     * Sendmail
     *
     * @param array $arrEmail
     * @return boolean|string  true = ok else error message as string
     */
    public static function sendMail($arrEmail)
    {
        // smtp or mail
        $arrEmail['type'] = gv($arrEmail, 'type', static::get('smtp.type', 'smtp'));
        // debugging: 1 = errors and messages, 2 = messages only
        $arrEmail['debug'] = gv($arrEmail, 'debug', static::get('smtp.debug', 0));
        $arrEmail['charset'] = gv($arrEmail, 'charset', static::get('smtp.charset', 'UTF-8'));
        $arrEmail['smtpauth'] = gv($arrEmail, 'smtpauth', static::get('smtp.smtpauth', true));
        $arrEmail['authtype'] = gv($arrEmail, 'authtype', static::get('smtp.authtype', 'LOGIN'));
        // secure transfer enabled REQUIRED for GMail:  'ssl' or 'tls'
        $arrEmail['secure'] = gv($arrEmail, 'secure', static::get('smtp.secure', false));
        $arrEmail['host'] = gv($arrEmail, 'host', static::get('smtp.host', '127.0.0.1'));
        $arrEmail['helo'] = gv($arrEmail, 'helo', static::get('smtp.helo', ''));
        $arrEmail['port'] = gv($arrEmail, 'port', static::get('smtp.port', '25'));
        $arrEmail['username'] = gv($arrEmail, 'username', static::get('smtp.username', ''));
        $arrEmail['password'] = gv($arrEmail, 'password', static::get('smtp.password', ''));
        $arrEmail['from'] = gv($arrEmail, 'from', static::get('smtp.from', ''));
        $arrEmail['from_name'] = gv($arrEmail, 'from_name', static::get('smtp.from_name', ''));
        $arrEmail['to'] = gv($arrEmail, 'to', static::get('smtp.to', ''));
        $arrEmail['bcc'] = gv($arrEmail, 'bcc', static::get('smtp.bcc', ''));

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
    public static function log($strLevel, $mixMessage, array $arrContext = array())
    {
        logmsg($strLevel, $mixMessage, $arrContext);
    }

    /**
     * Return database object
     *
     * @return DbMySql
     * @throws Exception
     */
    public static function getDb()
    {
        if (static::$db === null) {
            // open database connection
            static::$db = new DbMySql();
            if (!static::$db->connect(static::get('db.dsn'))) {
                throw new Exception('Database connection failed!');
            }
        }
        return static::$db;
    }

    /**
     * Set config
     *
     * @param string $strKey
     * @param mixed $mixValue
     * @return void
     */
    public static function set($strKey, $mixValue)
    {
        static::$config[$strKey] = $mixValue;
    }

    /**
     * Get config
     *
     * @param string $strKey
     * @param mixed $mixDefault
     * @return mixed
     */
    public static function get($strKey, $mixDefault = '')
    {
        if (isset(static::$config[$strKey])) {
            return static::$config[$strKey];
        } else {
            return $mixDefault;
        }
    }

    /**
     * Init error handler
     *
     * @return void
     */
    protected static function initErrorHandling()
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
        set_error_handler('\Molengo\WebApp::handleError', error_reporting());
        set_exception_handler('\Molengo\WebApp::handleException');
        register_shutdown_function('\Molengo\WebApp::handleShutdown');
    }

    /**
     * Exception handler for exceptions which are not within a
     * try / catch block. Terminates the current script.
     *
     * @param ErrorException $error
     * @return boolean
     */
    public static function handleException($error)
    {
        return static::handleErrorException($error);
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
    public static function handleError($numCode, $strMessage, $strFilename, $numLine)
    {
        $error = new \ErrorException($strMessage, $numCode, 0, $strFilename, $numLine);
        static::handleErrorException($error);
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
    public static function handleShutdown()
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
            static::handleErrorException($error);
        }
    }

    /**
     * Error Handler ErrorException object
     * @param ErrorException $error
     */
    protected static function handleErrorException($error)
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
        static::log('error', $strError);

        // check display error parameter
        $mixDisplayErrors = strtolower(trim(ini_get('display_errors')));
        if ($mixDisplayErrors == '1' || $mixDisplayErrors == 'on') {
            echo $strError;
        }
    }
}
