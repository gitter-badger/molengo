<?php

namespace Molengo\Model;

use App;

trait UserModel
{

    // Whitelist with locals
    protected $arrLocals = array(
        'en_US' => 1,
        'de_DE' => 1,
        'de_CH' => 1,
        'fr_CH' => 1
    );
    // default locale
    protected $strDefaultLocale = 'de_DE';

    /**
     * Returns user by username and password
     *
     * @param string $strUsername
     * @param string $strPassword
     * @return array|null
     */
    public function getUserByLogin($strUsername, $strPassword)
    {
        $arrReturn = array();

        $db = $this->getDb();

        $strSql = 'SELECT *
            FROM user
            WHERE username={username}
            AND enabled = 1
            LIMIT 1;';

        $arrInput = array();
        $arrInput['username'] = $strUsername;

        $strSql = $db->prepare($strSql, $arrInput);
        $arrReturn = $db->queryRow($strSql);

        $strHash = $arrReturn['password'];
        $boolStatus = $this->verifyHash($strPassword, $strHash);
        if (!$boolStatus) {
            $arrReturn = null;
        }
        return $arrReturn;
    }

    /**
     * Returns user by user id
     *
     * @param int $numId
     * @return array
     */
    public function getUserById($numId)
    {
        $arrReturn = array();

        $db = $this->getDb();
        $strSql = 'SELECT * FROM user WHERE id = {id};';
        $arrFields = array();
        $arrFields['id'] = $numId;
        $strSql = $db->prepare($strSql, $arrFields);
        $arrReturn = $db->queryRow($strSql);

        return $arrReturn;
    }

    /**
     * Login user with username and password
     *
     * @param array $arrParams
     * @return bool
     */
    public function login($arrParams = array())
    {
        $boolReturn = false;
        $strUsername = $arrParams['username'];
        $strPassword = $arrParams['password'];

        // check username and password
        $arrUser = $this->getUserByLogin($strUsername, $strPassword);

        if (empty($arrUser)) {
            return false;
        }

        // login ok
        $boolReturn = true;

        // create new session id
        $this->session->regenerate();

        // store user settings in session
        $this->set('user.id', $arrUser['id']);
        $this->set('user.role', $arrUser['role']);
        $this->setLocale($arrUser['locale']);

        return $boolReturn;
    }

    /**
     * Logout user session
     *
     * @return void
     */
    public function logout()
    {
        $this->set('user.id', null);
        $this->set('user.role', null);
        $this->set('user.locale', null);
        $this->session->destroy();
    }

    /**
     * Returns secure password hash
     *
     * @param string $strPassword
     * @param int $numAlgo
     * @param array $arrOptions
     * @return string
     */
    public function createHash($strPassword, $numAlgo = 1, $arrOptions = array())
    {
        if (function_exists('password_hash')) {
            // php >= 5.5
            $hash = password_hash($strPassword, $numAlgo, $arrOptions);
        } else {
            $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
            $salt = base64_encode($salt);
            $salt = str_replace('+', '.', $salt);
            $hash = crypt($strPassword, '$2y$10$' . $salt . '$');
        }
        return $hash;
    }

    /**
     * Returns true if password and hash is valid
     *
     * @param string $strPassword
     * @param string $strHash
     * @return bool
     */
    public function verifyHash($strPassword, $strHash)
    {
        if (function_exists('password_verify')) {
            // php >= 5.5
            $boolReturn = password_verify($strPassword, $strHash);
        } else {
            $strHash2 = crypt($strPassword, $strHash);
            $boolReturn = $strHash == $strHash2;
        }
        return $boolReturn;
    }

    /**
     * Check if token is correct for this string
     *
     * @param string $strValue
     * @param string $strToken
     * @return boolean
     */
    public function checkToken($strValue, $strToken)
    {
        $strTrueHash = $this->getToken($strValue);
        $boolReturn = ($strToken === $strTrueHash);
        return $boolReturn;
    }

    /**
     * Generate Hash-Token from string
     *
     * @param string $strValue
     * @param string $strSecret
     * @return string
     */
    public function getToken($strValue, $strSecret = null)
    {
        if ($strSecret === null) {
            $strSecret = App::get('app.secret');
        }
        // create real key for value
        $strSessionId = $this->session->getId();
        $strTrueHash = sha1($strValue . $strSessionId . $strSecret);
        return $strTrueHash;
    }

    /**
     * Set user info
     *
     * @param array $arrUser
     */
    public function set($strKey, $mixValue)
    {
        $this->session->set($strKey, $mixValue);
    }

    /**
     * Get current user information
     *
     * @param string $strKey
     * @return mixed
     */
    public function get($strKey, $mixDefault = '')
    {
        $mixReturn = $this->session->get($strKey, $mixDefault);
        return $mixReturn;
    }

    /**
     * Check user permission
     *
     * @param string|array $mixRole (e.g. 'ROLE_ADMIN' or 'ROLE_USER')
     * or array('ROLE_ADMIN', 'ROLE_USER')
     * @return boolean
     */
    public function is($mixRole)
    {
        // current user role
        $strUserRole = $this->get('user.role');

        // full access for admin
        if ($strUserRole === 'ROLE_ADMIN') {
            return true;
        }
        if ($mixRole === $strUserRole) {
            return true;
        }
        if (is_array($mixRole) && in_array($strUserRole, $mixRole)) {
            return true;
        }
        return false;
    }

    /**
     * Check if user is authenticated (logged in)
     *
     * @return boolean
     */
    public function isAuth()
    {
        $numId = $this->get('user.id');
        $boolStatus = !empty($numId);
        return $boolStatus;
    }

    /**
     * Change user session locale
     *
     * @param string $strLocale e.g. de_DE
     * @param string $strDomain default = text_
     * @return bool
     */
    public function setLocale($strLocale, $strDomain = 'text_')
    {
        if ($strLocale === 'auto') {
            $strSessionLocale = $this->get('user.locale');
            if (!empty($strSessionLocale)) {
                $strLocale = $strSessionLocale;
            }
        }

        // check whitelist
        if (!isset($this->arrLocals[$strLocale])) {
            $strLocale = $this->strDefaultLocale;
        }

        // select locale handler class
        $translation = App::getTranslation();

        // change gettext locale
        $boolReturn = $translation->setLocale($strLocale, $strDomain);
        //$strTest = __('en');
        // store into session
        $this->set('user.locale', $strLocale);

        // set user language
        $this->set('user.language', $translation->getLanguage());

        return $boolReturn;
    }

}
