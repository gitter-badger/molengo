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

class TranslationMoFile extends TranslationBase
{

    /** @var MoFileReader */
    protected $mo;

    /**
     * Set Application Locale
     * 
     * @param string $strLocale (e.g. de_DE, fr_FR, it_IT, en_US)
     * @param string $strDomain default = text_
     * @return boolean
     */
    public function setLocale($strLocale, $strDomain = 'text_')
    {
        $boolReturn = false;

        // mo filename: text_de_DE.mo
        $strTextDomain = $strDomain . $strLocale;

        // folder for translation files
        if (defined('G_ROOT_DIR')) {
            $strDir = G_ROOT_DIR . '/locale';
        } else {
            throw new \Exception('G_ROOT_DIR is not defined');
        }

        // text encoding
        $strCodeset = 'UTF-8';

        // mo filename
        $strMoFile = $strDir . '/' . $strLocale . '/LC_MESSAGES/' . $strTextDomain . '.mo';

        $this->mo = null;
        if (file_exists($strMoFile)) {
            // load mo file
            $mo = new MoFileReader();
            $boolReturn = $mo->load($strMoFile);
            $this->mo = $mo;
        } else {
            logmsg('warning', 'File not found: ' . $strMoFile);
        }

        // save configuration
        $this->arrLocale = array();
        $this->arrLocale['locale'] = $strLocale;
        $this->arrLocale['language'] = substr($strLocale, 0, 2);
        $this->arrLocale['domain'] = $strDomain;
        $this->arrLocale['mofile'] = $strMoFile;
        $this->arrLocale['codeset'] = $strCodeset;

        // check if locale is implemented on your platform
        return $boolReturn;
    }

    public function translate($strMessage, array $arrReplace = array())
    {
        if (!isset($strMessage)) {
            return '';
        }

        // encode source to utf-8
        if (!mb_check_encoding($strMessage, 'UTF-8')) {
            $strMessage = mb_convert_encoding($strMessage, 'UTF-8');
        }

        // translate
        if ($this->mo !== null) {
            $strMessage = $this->mo->translate($strMessage);
        }

        // placeholder
        if (!empty($arrReplace)) {
            $strMessage = interpolate($strMessage, $arrReplace);
        }

        // encode translation to utf-8
        if (!mb_check_encoding($strMessage, 'UTF-8')) {
            $strMessage = mb_convert_encoding($strMessage, 'UTF-8');
        }

        return $strMessage;
    }
}
