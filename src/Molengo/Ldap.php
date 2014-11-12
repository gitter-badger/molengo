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
 * LDAP
 *
 * @author odan
 * @copyright (c) 2014, molengo
 * @license MIT
 */
class Ldap
{

    /**
     * Returns LDAP user informations as array
     *
     * @param array $arrParams
     * @return array
     */
    public function getUser(array $arrParams)
    {
        $arrReturn = array();

        $strHost = $arrParams['host'];
        $strDomain = $arrParams['domain'];
        $strBaseDn = $arrParams['basedn'];
        $strUsername = $arrParams['username'];
        $strPassword = $arrParams['password'];

        // empty username or password is not allowed
        if (empty($strUsername) || empty($strPassword)) {
            return $arrReturn;
        }

        // debug flag
        $boolDebug = false;
        if (isset($arrParams['debug'])) {
            $boolDebug = $arrParams['debug'];
        }

        // set debugging
        if ($boolDebug) {
            ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        $strAdServer = "ldap://" . $strHost;
        $ldap = ldap_connect($strAdServer);
        if (!$ldap) {
            $strError = ldap_error($ldap);
            //$this->log($strError);
            return $arrReturn;
        }
        $strBindRdn = $strDomain . "\\" . $strUsername;

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, 3);

        // check username and password
        $bind = @ldap_bind($ldap, $strBindRdn, $strPassword);

        if (!$bind) {
            $strError = ldap_error($ldap);
            //$this->log($strError);
            return $arrReturn;
        }

        // load user
        $filter = "(sAMAccountName=$strUsername)";
        $result = ldap_search($ldap, $strBaseDn, $filter);
        ldap_sort($ldap, $result, "sn");
        $arrEntries = ldap_get_entries($ldap, $result);

        if (!empty($arrEntries[0])) {
            $arrReturn = $arrEntries[0];
        }
        ldap_close($ldap);

        return $arrReturn;
    }
}
