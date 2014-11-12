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

class TranslationBase
{

    /**
     * Locale / Language
     * @var array
     */
    protected $arrLocale = array();

    /**
     * Set application locale
     *
     * @param string $strLocale e.g. en_US| de_DE | fr_FR | it_IT
     * @param string $strDomain default = text_
     * @return boolean
     */
    public function setLocale($strLocale, $strDomain = 'text_')
    {
        return false;
    }

    public function translate($strMessage, array $arrContext = array())
    {
        return '';
    }

    /**
     * Get current language settings
     *
     * @return type
     */
    public function getLocale($strKey = 'locale')
    {
        $strReturn = null;

        if (isset($this->arrLocale[$strKey])) {
            $strReturn = $this->arrLocale[$strKey];
        }

        return $strReturn;
    }

    /**
     * Returns language from current locale
     *
     * @return string (e.g. de, fr, it, en)
     */
    public function getLanguage()
    {
        $strReturn = $this->getLocale('language');
        return $strReturn;
    }
}
