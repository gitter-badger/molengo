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
 * Session Handler
 * @version 2014.05.22
 */
class Session
{

    /**
     * Start new or resume existing session
     *
     * @param string $strName session name
     * @return bool true if a session was successfully started
     */
    public function start($strName = null)
    {
        $boolReturn = false;

        if (!isset($_SESSION)) {
            if ($strName !== null) {
                session_name($strName);
            }
            $boolReturn = session_start();
        }
        return $boolReturn;
    }

    /**
     * Start session and generate new session id and cookie
     *
     * @param string $strName
     * @return string session id
     */
    public function regenerate($strName = null)
    {
        $strSessionId = session_id();
        if (empty($strSessionId)) {
            $this->start($strName);
        }

        $boolStatus = session_regenerate_id(true);
        $strSessionId = session_id();

        return $strSessionId;
    }

    /**
     * Returns current session id
     *
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * Get session parameter value
     *
     * @param string $strKey
     * @param mixed $strDefault
     * @return mixed
     */
    public function get($strKey = null, $strDefault = null)
    {
        if ($strKey === null) {
            return $_SESSION;
        }
        $mixReturn = $strDefault;
        if (isset($_SESSION[$strKey])) {
            $mixReturn = $_SESSION[$strKey];
        }
        return $mixReturn;
    }

    /**
     * Set session value
     *
     * @param string $strKey
     * @param mix $mixValue
     */
    public function set($strKey, $mixValue)
    {
        $_SESSION[$strKey] = $mixValue;
    }

    /**
     * Clear all session values
     */
    public function clear()
    {
        $_SESSION = array();
    }

    /**
     * Destroy current Session and Cookie
     *
     * @param string $strName session name
     * @return bool
     */
    public function destroy($strName = null)
    {
        // make sure session is startet
        $this->start($strName);

        // clear session variables
        $this->clear();

        // delete session-cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            // expire = 0, the cookie will expire when the browser closes
            setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        // destroy session
        $boolReturn = session_destroy();
        return $boolReturn;
    }
}
