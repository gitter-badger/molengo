<?php

namespace Molengo\Model;

use App;

trait UserModel
{

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

    public function getUserById($strId)
    {
        $arrReturn = array();

        $db = $this->getDb();
        $strSql = 'SELECT * FROM user WHERE id = {id};';
        $arrFields = array();
        $arrFields['id'] = $strId;
        $strSql = $db->prepare($strSql, $arrFields);
        $arrReturn = $db->queryRow($strSql);

        return $arrReturn;
    }

    public function login($arrParams = array())
    {
        $arrReturn = array();
        $arrReturn['status'] = 0;
        $arrReturn['text'] = '';

        $strUsername = $arrParams['username'];
        $strPassword = $arrParams['password'];

        // check username and password
        $arrUser = $this->getUserByLogin($strUsername, $strPassword);

        // $arrUser = array(
        //    'id' => 1,
        //    'acl' => 'admin',
        //    'locale' => 'de_DE'
        //);
        //App::log('debug', $arrUser);

        if (empty($arrUser)) {
            return $arrReturn;
        }

        // login ok
        $arrReturn['status'] = 1;

        // create new session id
        $sess = App::getSession();
        $sess->regenerate();

        // store user settings in session
        $this->setUser($arrUser);

        // load permissions
        $arrAcl = array();
        $arrAclValues = explode("\n", $arrUser['acl']);

        if (!empty($arrAclValues)) {
            foreach ($arrAclValues as $strValue) {
                if (!empty($strValue)) {
                    $arrAcl[$strValue] = 1;
                }
            }
        }
        $this->setAcl($arrAcl);

        // set locale
        if (isset($arrUser['locale'])) {
            $sess->set('locale', $arrUser['locale']);
        }

        return $arrReturn;
    }

    public function logout()
    {
        $this->setUser(null);
        $this->setAcl(null);
        App::getSession()->destroy();
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
        $strSessionId = App::getSession()->getId();
        $strTrueHash = sha1($strValue . $strSessionId . $strSecret);
        return $strTrueHash;
    }

    /**
     * Get current user information
     * @param string $strKey
     * @return mixed
     */
    public function getUser()
    {
        $mixReturn = App::getSession()->get('user');
        return $mixReturn;
    }

    /**
     * Set user
     * @param array $arrUser
     */
    public function setUser($arrUser)
    {
        App::getSession()->set('user', $arrUser);
    }

    /**
     * Get current user information
     * @param string $strKey
     * @return mixed
     */
    public function getUserInfo($strKey, $mixDefault = '')
    {
        $arrUser = App::getSession()->get('user');
        $mixReturn = isset($arrUser[$strKey]) ? $arrUser[$strKey] : $mixDefault;
        return $mixReturn;
    }

    /**
     * Set user info
     * @param string $strKey
     * @param type $mixValue
     */
    public function setUserInfo($strKey, $mixValue)
    {
        $arrUser = $this->getUser();
        $arrUser[$strKey] = $mixValue;
        $this->setUser($arrUser);
    }

    /**
     * Return all user permissions
     * @return string
     */
    public function getAcl()
    {
        $arrReturn = App::getSession()->get('acl');
        return $arrReturn;
    }

    /**
     * Check user permission
     * @param string $strAcl
     * @return boolean
     */
    public function acl($strAcl)
    {
        $arrAcl = $this->getAcl();
        $boolReturn = isset($arrAcl['admin']) || isset($arrAcl[$strAcl]);
        return $boolReturn;
    }

    /**
     * Set user permissions (accesskeys)
     * @param array $arrAcl
     */
    public function setAcl($arrAcl)
    {
        App::getSession()->set('acl', $arrAcl);
    }

    /**
     * Check login status
     * @param array $arrParams
     * @return boolean
     */
    public function checkLogin($arrParams = array())
    {
        $numId = $this->getUserInfo('id');
        $boolStatus = !empty($numId);
        $strRedirect = gv($arrParams, 'redirect');
        if (!$boolStatus && $strRedirect) {
            App::getResponse()->redirect($strRedirect);
            exit;
        }

        if (!$boolStatus) {
            header('HTTP/1.1 401 Unauthorized');
            echo 'Unauthorized access';
            exit;
        }

        return $boolStatus;
    }

    /**
     * Set locale into session
     *
     * @param string $strLocale e.g. de_DE
     * @param string $strDomain default = text_
     * @return bool
     * @todo Add $arrWhitelist to function
     */
    public function setLocale($strLocale, $strDomain = 'text_')
    {
        $session = App::getSession();

        if ($strLocale === 'auto') {
            $strSessionLocale = $session->get('locale');
            if (!empty($strSessionLocale)) {
                $strLocale = $strSessionLocale;
            }
        }

        $arrWhitelist = array();
        $arrWhitelist['de_DE'] = 1;
        $arrWhitelist['de_CH'] = 1;
        $arrWhitelist['fr_CH'] = 1;
        $arrWhitelist['it_CH'] = 1;
        $arrWhitelist['en_US'] = 1;

        if (!isset($arrWhitelist[$strLocale])) {
            $strLocale = 'de_DE';
        }

        // select locale handler class
        $translation = App::getTranslation();

        // change gettext locale
        $boolReturn = $translation->setLocale($strLocale, $strDomain);

        // store into session
        $session->set('locale', $strLocale);

        // set user language
        $this->setUserInfo('language', $translation->getLanguage());

        return $boolReturn;
    }

}
