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
 * TempFile
 *
 * @version: 2014.08.27
 */
class TempFile
{

    protected $strTempDir = '';
    protected $arrKeys = array();

    public function __construct()
    {
        if (defined('G_TMP_DIR')) {
            $this->strTempDir = G_TMP_DIR;
        }
    }

    /**
     * Set temp dir
     *
     * @param string $strTempDir
     * @return void
     * @throws \Exception
     */
    protected function setTempDir($strTempDir)
    {
        if (!is_writable($this->strTempDir)) {
            throw new Exception('Cant write to {dir}!', array('dir' => $strTempDir));
        }
        $this->strTempDir = $strTempDir;
    }

    /**
     * Create new file and return file key (id)
     *
     * @param string $strFileName [optional]
     * @param string $strContent [optional]
     * @return string key
     */
    public function create($strFileName = null, $strContent = null)
    {
        // create new key
        $strKey = sha1(uuid());

        // create data file
        $strDataFile = $this->getDataFileName($strKey);
        $this->touchFile($strDataFile);
        $this->write($strKey, $strContent);

        // create info file
        $arrInfo = array(
            'filename' => $strFileName,
            'datafile' => $strDataFile
        );

        $strInfoFile = $this->getInfoFileName($strKey);
        $this->touchFile($strInfoFile);
        $this->setInfo($strKey, $arrInfo);

        $this->arrKeys[] = $strKey;

        return $strKey;
    }

    /**
     * Append content to file
     *
     * @param string $strKey
     * @param string $strContent
     * @return integer|false returns the number of bytes that
     * were written to the file, or false on failure.
     */
    public function write($strKey, $strContent)
    {
        $mixReturn = false;
        $strDataFile = $this->getDataFileName($strKey);
        if (file_exists($strDataFile)) {
            $mixReturn = file_put_contents($strDataFile, $strContent, FILE_APPEND);
        }
        return $mixReturn;
    }

    /**
     * Reads entire file into a string
     *
     * @param string $strKey
     * @param integer $numOffset [optional]
     * @param integer $numMaxLength [optional]
     * @return string|false data or false on failure
     */
    public function read($strKey, $numOffset = null, $numMaxLength = null)
    {
        $strDataFile = $this->getDataFileName($strKey);

        if (!file_exists($strDataFile)) {
            return false;
        }

        if ($numOffset === null) {
            return file_get_contents($strDataFile);
        } else {
            return file_get_contents($strDataFile, false, null, $numOffset, $numMaxLength);
        }
    }

    /**
     * Delete file by key
     *
     * @param string $strKey
     */
    public function delete($strKey)
    {
        $strDataFileName = $this->getDataFileName($strKey);
        if (file_exists($strDataFileName)) {
            unlink($strDataFileName);
        }
        $strInfoFileName = $this->getInfoFileName($strKey);
        if (file_exists($strInfoFileName)) {
            unlink($strInfoFileName);
        }
    }

    /**
     * Get file info by key
     *
     * @param string $strKey
     * @return array
     */
    public function getInfo($strKey)
    {
        $arrReturn = array();
        $strFileName = $this->getInfoFileName($strKey);
        if (file_exists($strFileName)) {
            $strInfo = file_get_contents($strFileName);
            $arrReturn = decode_json($strInfo);
        }
        return $arrReturn;
    }

    /**
     * Returns filename by key
     *
     * @param string $strKey
     * @return string
     */
    public function getDataFileName($strKey)
    {
        $strFilePath = sprintf("%s/%s.data", $this->strTempDir, $strKey);
        return $strFilePath;
    }

    /**
     * Returns info filename by key
     *
     * @param string $strKey
     * @return string
     */
    protected function getInfoFileName($strKey)
    {
        $strFileName = sprintf("%s/%s.info", $this->strTempDir, $strKey);
        return $strFileName;
    }

    /**
     * Set file informations
     *
     * @param string $strKey
     * @param array $arrInfo
     * @return boolean
     */
    public function setInfo($strKey, $arrInfo)
    {
        $boolReturn = false;
        $strFileName = $this->getInfoFileName($strKey);
        if (file_exists($strFileName)) {
            $boolReturn = (file_put_contents($strFileName, encode_json($arrInfo)) !== false);
        }
        return $boolReturn;
    }

    /**
     * Send file to browser
     *
     * @param string $strKey
     * @param bool $boolDownload true = attachment, false = inline
     * @param bool $boolClean
     */
    public function send($strKey, $boolDownload = true, $boolClean = true)
    {
        $arrInfo = $this->getInfo($strKey);
        $strDataFileName = $this->getDataFileName($strKey);

        // For security I take only the 40 first chars.
        $strKey = substr($strKey, 0, 40);

        // To make sure no hex or bin is pass in the only 40 chars that are allowed.
        if (preg_match('/^([a-zA-Z0-9]{40})$/s', $strKey)) {
            // Output the file directly
            $res = new Response();
            $res->sendFile($strDataFileName, $arrInfo['filename'], null, $boolDownload);

            // delete data and info file
            if ($boolClean == true) {
                $this->delete($strKey);
            }
        }
    }

    /**
     * Create file
     *
     * @param string $strFilename
     * @param int $numFileTime
     */
    protected function touchFile($strFilename, $numFileTime = null)
    {
        if ($numFileTime === null) {
            touch($strFilename);
        } else {
            touch($strFilename, $numFileTime);
        }
        umask(0);
        chmod($strFilename, 0777);
        umask(0);
    }

    /**
     * Cleanup tempfiles
     */
    public function clean()
    {
        if (!empty($this->arrKeys)) {
            foreach ($this->arrKeys as $strKey) {
                $this->delete($strKey);
            }
        }
        $this->arrKeys = array();
    }
}
