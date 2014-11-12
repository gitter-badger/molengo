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
 * Xml Utils
 */
class XmlUtil
{

    /**
     * XML beautifier
     * @param string $strXml
     * @return string pretty xml string
     */
    public function formatXmlString($strXml)
    {
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->loadXML($strXml);
        $strReturn = $xml->saveXML();
        return $strReturn;
    }

    /**
     * File XML Beautifier
     *
     * @param type $strFilename
     * @param type $strFilenameDestination
     * @return boolean
     */
    public function formatXmlFile($strFilename, $strFilenameDestination = null)
    {
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->load($strFilename);
        if ($strFilenameDestination === null) {
            $strFilenameDestination = $strFilename;
        }
        $boolReturn = ($xml->save($strFilenameDestination) !== false);
        return $boolReturn;
    }

    /**
     * Validate XML-File against XSD-File (Schema)
     *
     * @param string $strXmlFile
     * @param string $strXsdFile
     * @return array if not valid an array with errors
     */
    public function validateXmlFile($strXmlFile, $strXsdFile)
    {
        $arrReturn = array();

        // Enable user error handling
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = new \DOMDocument();
        $xml->load($strXmlFile);
        if (!$xml->schemaValidate($strXsdFile)) {
            // not valid
            $arrXml = explode("\n", file_get_contents($strXmlFile));
            $i = 0;
            $arrErrors = libxml_get_errors();
            foreach ($arrErrors as $error) {
                $arrReturn[$i]['level'] = $error->level;
                $arrReturn[$i]['message'] = trim($error->message);
                $arrReturn[$i]['file'] = str_replace('file:///', '', $error->file);
                $arrReturn[$i]['line'] = $error->line;
                $arrReturn[$i]['content'] = '';
                if (isset($arrXml[$error->line - 1])) {
                    $arrReturn[$i]['content'] = trim($arrXml[$error->line - 1]);
                }
                $arrReturn[$i]['code'] = $error->code;
                $arrReturn[$i]['column'] = $error->column;
                $i++;
            }
        }
        libxml_clear_errors();
        return $arrReturn;
    }

    /**
     * Convert an Array to XML
     *
     * Author : Lalit Patel
     * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
     *
     * @param string $strRootName - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @param array $arrOptions
     * @return \DomDocument
     */
    public function convertArrayToXml($strRootName, &$arr = array(), $arrOptions = array())
    {
        // overwrite default options
        $arrOptions = array_merge(array(
            'version' => '1.0',
            'encoding' => 'UTF-8',
            'format_output' => true), $arrOptions);

        $xml = new \DomDocument($arrOptions['version'], $arrOptions['encoding']);
        $xml->formatOutput = $arrOptions['format_output'];

        $xml->appendChild($this->arrayToXml($xml, $strRootName, $arr));

        return $xml;
    }

    /**
     * Convert an Array to XML node
     *
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return \DOMNode
     */
    protected function &arrayToXml($xml, $strNodeName, $arr = array())
    {
        if (isset($arr['@comment'])) {
            $node = $xml->createDocumentFragment();
        } else {
            $node = $xml->createElement($strNodeName);
        }

        if (is_array($arr)) {
            // get the attributes first.;
            if (isset($arr['@attributes'])) {
                foreach ($arr['@attributes'] as $key => $value) {
                    if (!$this->isValidTagName($key)) {
                        throw new \Exception('[Array2XML] Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $strNodeName);
                    }
                    $node->setAttribute($key, $this->boolToStr($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if (isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode($this->boolToStr($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if (isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection($this->boolToStr($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            } else if (isset($arr['@comment'])) {
                $strComment = $this->boolToStr($arr['@comment']);

                // To prevent a parser error when the comment string would
                // contain the character sequence "--". This will insert a Soft
                // Hyphen in between the two hyphens which will not cause the parser to error out.
                $strComment = str_replace('--', '-' . chr(194) . chr(173) . '-', $strComment);

                $node->appendChild($xml->createComment($strComment));
                //remove the key from the array once done.
                unset($arr['@comment']);
                //return from recursion, as a note with comment cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if (is_array($arr)) {
            // recurse to get the node for that key
            foreach ($arr as $key => $value) {
                if (!$this->isValidTagName($key)) {
                    throw new \Exception('[Array2XML] Illegal character in tag name. tag: ' . $key . ' in node: ' . $strNodeName);
                }
                if (is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach ($value as $k => $v) {
                        $node->appendChild($this->arrayToXml($xml, $key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild($this->arrayToXml($xml, $key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if (!is_array($arr)) {
            $node->appendChild($xml->createTextNode($this->boolToStr($arr)));
        }

        return $node;
    }

    /**
     * Get string representation of boolean value
     *
     * @param bool $v
     * @return string
     */
    protected function boolToStr($v)
    {
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }

    /**
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     *
     * @param string $tag
     * @return boolean
     */
    protected function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}
