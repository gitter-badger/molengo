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
 * Locale with Gettext extension
 */
class TranslationGettext extends TranslationBase
{

    /**
     * Set application locale
     *
     * @param string $strLocale e.g. en_US| de_DE | fr_FR | it_IT
     * @param string $strDomain default = text_
     * @return boolean
     */
    public function setLocale($strLocale, $strDomain = 'text_')
    {
        $boolReturn = false;

        // mo filename: text_de_DE.mo
        $strTextDomain = $strDomain . $strLocale;

        // for windows
        putenv('LC_ALL=' . $strLocale);

        $mixStatus = setlocale(LC_ALL, $strLocale);

        // folder for translation files
        if (defined('G_APP_DIR')) {
            $strDir = G_APP_DIR . '/Locale';
        } else {
            throw new \Exception('G_APP_DIR is not defined');
        }
        bindtextdomain($strTextDomain, $strDir);

        // text encoding
        $strCodeset = 'UTF-8';
        bind_textdomain_codeset($strTextDomain, $strCodeset);

        // load text from file ./locale/de_CH/LC_MESSAGES/text_en_US.mo
        textdomain($strTextDomain);

        // mo filename
        $strMoFile = $strDir . '/' . $strLocale . '/LC_MESSAGES/' . $strTextDomain . '.mo';

        // save configuration
        $this->arrLocale = array();
        $this->arrLocale['locale'] = $strLocale;
        $this->arrLocale['language'] = substr($strLocale, 0, 2);
        $this->arrLocale['domain'] = $strDomain;
        $this->arrLocale['mofile'] = $strMoFile;
        $this->arrLocale['codeset'] = $strCodeset;

        // check if locale is implemented on your platform
        $boolReturn = ($mixStatus !== false);
        return $boolReturn;
    }

    /**
     * Returns translated text
     *
     * @param string $strMessage
     * @param array $arrContext
     * @return string
     */
    public function translate($strMessage, array $arrContext = array())
    {
        if (!isset($strMessage)) {
            return '';
        }

        // encode source to utf-8
        if (!mb_check_encoding($strMessage, 'UTF-8')) {
            $strMessage = mb_convert_encoding($strMessage, 'UTF-8');
        }

        // translate
        $strMessage = gettext($strMessage);

        // placeholder
        if (!empty($arrContext)) {
            $strMessage = interpolate($strMessage, $arrContext);
        }

        // encode translation to utf-8
        if (!mb_check_encoding($strMessage, 'UTF-8')) {
            $strMessage = mb_convert_encoding($strMessage, 'UTF-8');
        }

        return $strMessage;
    }

}
