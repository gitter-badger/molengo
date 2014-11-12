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
 * Class for reading MO files (poedit)
 * Thanks to: nbachiyski
 *
 * @version 2014.03.07
 */
class MoFileReader
{

    protected $endian = 'little';
    protected $file_pos = 0;
    protected $file = '';
    protected $is_overloaded = false;

    public function __construct()
    {
        $this->is_overloaded = ((ini_get("mbstring.func_overload") & 2) != 0) && function_exists('mb_substr');
        $this->file_pos = 0;
    }

    /**
     * Fills up with the entries from MO file $filename
     *
     * @param string $filename MO file to load
     */
    public function load($filename)
    {
        $this->openMoFile($filename);
        if (!$this->isResource()) {
            return false;
        }
        $boolReturn = $this->importMoFile();
        return $boolReturn;
    }

    protected function openMoFile($filename)
    {
        $this->file = fopen($filename, 'rb');
    }

    protected function getByteOrder($magic)
    {
        // The magic is 0x950412de
        // bug in PHP 5.0.2, see:
        // https://savannah.nongnu.org/bugs/?func=detailitem&item_id=10565
        $magic_little = (int) - 1794895138;
        $magic_little_64 = (int) 2500072158;
        // 0xde120495
        $magic_big = ((int) - 569244523) & 0xFFFFFFFF;
        if ($magic_little == $magic || $magic_little_64 == $magic) {
            return 'little';
        } else if ($magic_big == $magic) {
            return 'big';
        } else {
            return false;
        }
    }

    protected function importMoFile()
    {
        $endian_string = $this->getByteOrder($this->readInt32());
        if (false === $endian_string) {
            return false;
        }
        $this->setEndian($endian_string);

        $endian = ('big' == $endian_string) ? 'N' : 'V';

        $header = $this->read(24);
        if ($this->strlen($header) != 24) {
            return false;
        }

        // parse header
        $header = unpack("{$endian}revision/{$endian}total/{$endian}originals_lenghts_addr/{$endian}translations_lenghts_addr/{$endian}hash_length/{$endian}hash_addr", $header);
        if (!is_array($header)) {
            return false;
        }

        extract($header);

        // support revision 0 of MO format specs, only
        if ($revision != 0) {
            return false;
        }

        // seek to data blocks
        $this->seekto($originals_lenghts_addr);

        // read originals' indices
        $originals_lengths_length = $translations_lenghts_addr - $originals_lenghts_addr;
        if ($originals_lengths_length != $total * 8) {
            return false;
        }

        $originals = $this->read($originals_lengths_length);
        if ($this->strlen($originals) != $originals_lengths_length) {
            return false;
        }

        // read translations' indices
        $translations_lenghts_length = $hash_addr - $translations_lenghts_addr;
        if ($translations_lenghts_length != $total * 8) {
            return false;
        }

        $translations = $this->read($translations_lenghts_length);
        if ($this->strlen($translations) != $translations_lenghts_length) {
            return false;
        }

        // transform raw data into set of indices
        $originals = $this->str_split($originals, 8);
        $translations = $this->str_split($translations, 8);

        // skip hash table
        $strings_addr = $hash_addr + $hash_length * 4;

        $this->seekto($strings_addr);

        $strings = $this->readAll();
        $this->close();

        for ($i = 0; $i < $total; $i++) {
            $o = unpack("{$endian}length/{$endian}pos", $originals[$i]);
            $t = unpack("{$endian}length/{$endian}pos", $translations[$i]);
            if (!$o || !$t) {
                return false;
            }

            // adjust offset due to reading strings to separate space before
            $o['pos'] -= $strings_addr;
            $t['pos'] -= $strings_addr;

            $original = $this->substr($strings, $o['pos'], $o['length']);
            $translation = $this->substr($strings, $t['pos'], $t['length']);

            if ($original !== '') {
                $entry = $this->createEntry($original, $translation);

                if (!isset($entry['translations'][0])) {
                    continue;
                }

                $k = $entry['source'];
                $v = $entry['translations'][0];
                $this->entries[$k] = $v;
            }
        }
        return true;
    }

    public function getEntries()
    {
        return $this->entries;
    }

    public function setEntries(array $arrEntries)
    {
        $this->entries = $arrEntries;
    }

    /**
     * Build a Translation Entry from original string and translation strings,
     * found in a MO file
     *
     * @param string $original original string to translate from MO file. Might contain
     * 	0x04 as context separator or 0x00 as singular/plural separator
     * @param string $translation translation string from MO file. Might contain
     * 	0x00 as a plural translations separator
     */
    protected function createEntry($original, $translation)
    {
        $arrEntry = array();
        // look for context
        $parts = explode(chr(4), $original);
        if (isset($parts[1])) {
            $original = $parts[1];
            $arrEntry['context'] = $parts[0];
        }
        // look for plural original
        //$parts = explode(chr(0), $original);
        //$arrEntry['source_singular'] = $parts[0];
        //if (isset($parts[1])) {
        //    $arrEntry['is_plural'] = true;
        //    $arrEntry['source_plural'] = $parts[1];
        //}
        // plural translations are also separated by \0
        $arrEntry['translations'] = explode(chr(0), $translation);
        $arrEntry['source'] = $original;

        return $arrEntry;
    }

    public function translate($strMsgId)
    {
        $strReturn = isset($this->entries[$strMsgId]) ? $this->entries[$strMsgId] : $strMsgId;
        return $strReturn;
    }

    //--------------------------------------------------------------------------
    // Mo file reader
    //--------------------------------------------------------------------------

    protected function read($bytes)
    {
        return fread($this->file, $bytes);
    }

    protected function seekto($pos)
    {
        if (-1 == fseek($this->file, $pos, SEEK_SET)) {
            return false;
        }
        $this->file_pos = $pos;
        return true;
    }

    protected function isResource()
    {
        return is_resource($this->file);
    }

    protected function feof()
    {
        return feof($this->file);
    }

    protected function close()
    {
        return fclose($this->file);
    }

    protected function readAll()
    {
        $all = '';
        while (!$this->feof()) {
            $all .= $this->read(4096);
        }
        return $all;
    }

    /**
     * Sets the endianness of the file.
     *
     * @param $endian string 'big' or 'little'
     */
    protected function setEndian($endian)
    {
        $this->endian = $endian;
    }

    /**
     * Reads a 32bit Integer from the Stream
     *
     * @return mixed The integer, corresponding to the next 32 bits from
     * 	the stream of false if there are not enough bytes or on error
     */
    protected function readInt32()
    {
        $bytes = $this->read(4);
        if (4 != $this->strlen($bytes)) {
            return false;
        }
        $endian_letter = ('big' == $this->endian) ? 'N' : 'V';
        $int = unpack($endian_letter, $bytes);
        return array_shift($int);
    }

    protected function substr($string, $start, $length)
    {
        if ($this->is_overloaded) {
            return mb_substr($string, $start, $length, 'ascii');
        } else {
            return substr($string, $start, $length);
        }
    }

    protected function strlen($string)
    {
        if ($this->is_overloaded) {
            return mb_strlen($string, 'ascii');
        } else {
            return strlen($string);
        }
    }

    protected function str_split($string, $chunk_size)
    {
        if (!function_exists('str_split')) {
            $length = $this->strlen($string);
            $out = array();
            for ($i = 0; $i < $length; $i += $chunk_size) {
                $out[] = $this->substr($string, $i, $chunk_size);
            }
            return $out;
        } else {
            return str_split($string, $chunk_size);
        }
    }

    protected function pos()
    {
        return $this->file_pos;
    }
}
