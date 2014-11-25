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
/**
 * Utils (only functions)
 * Version: 14.11.25.0
 */

/**
 * Returns HTML encoded string
 *
 * @param string $str
 * @return string
 */
function gh($str)
{
    // skip empty strings
    if ($str === null || $str === '') {
        return '';
    }

    // convert to utf-8
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8');
    }

    // convert special chars to numeric entity
    $str = preg_replace_callback('/[^a-z0-9A-Z ]/u', function($match) {
        return mb_encode_numericentity($match[0], array(0x0, 0xffff, 0, 0xffff), 'UTF-8');
    }, $str);

    return $str;
}

/**
 * Returns HTML encoded string. Newlines are converted to HTML <br> tag.
 *
 * @param string $str
 * @return string
 */
function ghbr($str)
{
    if ($str === null || $str === '') {
        return '';
    }

    $str_return = '';
    $arr = explode("\n", $str);

    if (!empty($arr) && is_array($arr)) {
        foreach ($arr as $str_key => $str_row) {
            $arr[$str_key] = gh($str_row);
        }
        $str_return = implode('<br>', $arr);
    }

    return $str_return;
}

/**
 * Print Html and nl2br encoded string
 *
 * @param string $str
 */
function whbr($str)
{
    echo ghbr($str);
}

/**
 * Write html encoded string
 *
 * @param string $str
 */
function wh($str)
{
    echo gh($str);
}

/**
 * URL Encoding: Write URL encoded string
 *
 * @param string $str
 */
function wu($str)
{
    echo urlencode($str);
}

/**
 * URL Encoding
 *
 * @param string $str
 */
function gu($str)
{
    return urlencode($str);
}

/**
 * HTML Attribute Encoding
 *
 * @param string $str string to encode
 */
function ga($str)
{
    return htmlspecialchars($str);
}

/**
 * HTML Attribute Encoding: Write attribute encoded string
 *
 * @param string $str string to encode and print
 */
function wa($str)
{
    echo htmlspecialchars($str);
}

/**
 * Return Array element value (get value)
 *
 * @param array $arr
 * @param string $strKey
 * @param mixed $mixDefault
 * @return mixed
 */
function gv(&$arr, $strKey, $mixDefault = '')
{
    if (!isset($arr[$strKey])) {
        return $mixDefault;
    }
    return $arr[$strKey];
}

/**
 * Get array value
 *
 * <code>
 * $array = array();
 * $array['key1']['key2']['key3']['key4'] = 'hello';
 * echo av($array, ['key1', 'key2', 'key3', 'key4'], 'default');
 * </code>
 *
 * @param array $arr
 * @param array $arr_keys
 * @param mixed $mix_default
 * @return mixed
 */
function av(&$arr, $arr_keys, $mix_default = '')
{
    if (empty($arr)) {
        return $mix_default;
    }
    $mix_return = $mix_default;
    foreach ($arr_keys as $index) {
        if (isset($arr[$index])) {
            array_shift($arr_keys);
            if (empty($arr_keys)) {
                $mix_return = $arr[$index];
            } else {
                $mix_return = av($arr[$index], $arr_keys, $mix_default);
            }
        } else {
            break;
        }
    }
    return $mix_return;
}

/**
 * Returns true if the variable is blank.
 * When you need to accept these as valid, non-empty values:
 *
 * - 0 (0 as an integer)
 * - 0.0 (0 as a float)
 * - "0" (0 as a string)
 *
 * @param mix $mixValue
 * @return boolean
 */
function blank($mixValue)
{
    return empty($mixValue) && !is_numeric($mixValue);
}

/**
 * Validate E-Mail address
 *
 * @param string $str_email
 * @return bool
 */
function is_email($str_email = null)
{
    return filter_var($str_email, FILTER_VALIDATE_EMAIL);
}

/**
 * Returns a trimmed array
 *
 * @param array $array
 * @return array
 */
function trim_array($array)
{
    if (is_array($array)) {
        foreach ($array as $key => $val) {
            $array[$key] = trim_array($val);
        }
        return $array;
    } else {
        return trim($array);
    }
}

/**
 * Returns a truncated string
 *
 * @param string $str
 * @param int $num_length
 * @param string $str_append
 * @return string
 */
function truncate($str, $num_length = 255, $str_append = '')
{
    $num_textlen = strlen($str);
    if ($num_textlen > $num_length) {
        $num_appendlen = strlen($str_append);
        $str = substr($str, 0, $num_textlen - $num_appendlen) . $str_append;
    }
    return $str;
}

/**
 * Returns a random string
 *
 * @param int $num_length Password Length: (4 - 64 chars)
 * @param bool $bool_lowercase  Include Letters: (e.g. abcdef)
 * @param bool $bool_uppercase Include Mixed Case: (e.g. AbcDEf)
 * @param bool $bool_numbers  Include Numbers: (e.g. a9b8c7d)
 * @param bool $bool_punctuation Include Punctuation: (e.g. a!b*c_d)
 * @return string
 */
function random_string($num_length, $bool_lowercase = true, $bool_uppercase = true, $bool_numbers = true, $bool_punctuation = false)
{
    $str_lowercase = "abcdefghijklmnopqrstuvwxyz";
    $str_uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $str_number = "1234567890";
    $str_punctuation = "!$%&/()=?+#'-_:@{}*.<>\"";
    $str_chars = "";
    $str_return = "";

    if ($bool_lowercase == true) {
        $str_chars .= $str_lowercase;
    }
    if ($bool_uppercase == true) {
        $str_chars .= $str_uppercase;
    }
    if ($bool_numbers == true) {
        $str_chars .= $str_number;
    }
    if ($bool_punctuation == true) {
        $str_chars .= $str_punctuation;
    }

    $str_chars_len = strlen($str_chars);
    for ($i = 0; $i < $num_length; $i++) {
        $num_pos = mt_rand(0, $str_chars_len - 1);
        $str_return .= substr($str_chars, $num_pos, 1);
    }
    return $str_return;
}

/**
 * Returns a random UUID
 *
 * @return string
 */
function uuid()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * PSR-3: Interpolates context values into the message placeholders.
 *
 * The message MAY contain placeholders which implementors MAY replace
 * with values from the context array.
 *
 * Placeholder names MUST correspond to keys in the context array.
 *
 * Placeholder names MUST be delimited with
 * a single opening brace { and a single closing brace }.
 *
 * There MUST NOT be any whitespace between the delimiters
 * and the placeholder name.
 *
 * Placeholder names SHOULD be composed only of the characters A-Z, a-z, 0-9,
 * underscore _, and period .. The use of other characters is reserved for
 * future modifications of the placeholders specification.
 *
 * Details:
 * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 *
 * @example
 *
 * // a message with brace-delimited placeholder names
 * $message = "User {username} created";
 *
 * // a context array of placeholder names => replacement values
 * $context = array('username' => 'bolivar');
 *
 * // echoes "Username bolivar created"
 * echo interpolate($message, $context);
 *
 */
function interpolate($str_message, array $arr_context = array())
{
    // build a replacement array with braces around the context keys
    $arr_replace = array();
    foreach ($arr_context as $key => $val) {
        $arr_replace['{' . $key . '}'] = $val;
    }

    // interpolate replacement values into the message and return
    return strtr($str_message, $arr_replace);
}

/**
 * Shortcut for interpolate
 *
 * @param string $str_message
 * @param array $arr_context
 * @return string
 */
function i($str_message, array $arr_context = array())
{
    return interpolate($str_message, $arr_context);
}

//------------------------------------------------------------------------------
// Date-Time
//------------------------------------------------------------------------------

/**
 * Returns the current date and time in IS0-8601 format
 * Format: Y-m-d H:i:s
 *
 * @return string
 */
function now()
{
    return date('Y-m-d H:i:s');
}

/**
 * Converts any date/time format (default is d.m.Y)
 *
 * @param string $str_time
 * @param string $str_format
 * @param mixed $mix_default
 * @return string or $mix_default
 *
 * <code>
 * echo format_time('2011-03-28 15:14:30') -> '28.03.1982'
 * echo format_time('2011-03-28 15:10:5', 'd.m.Y H:i:s') -> '28.03.1982 15:10:05'
 * echo format_time('2011-3-22 23:01:45', 'H:i:s') -> '23:01:45'
 * echo format_time('2014-14-31', 'H:i:s', 'not valid') -> 'not valid'
 * </code>
 */
function format_time($str_time, $str_format = 'd.m.Y', $mix_default = '')
{
    if (empty($str_time) || $str_time === '0000-00-00 00:00:00' || $str_time === '0000-00-00') {
        return $mix_default;
    }
    $num_time = strtotime($str_time);
    if ($num_time === false) {
        return $mix_default;
    }
    $str_return = date($str_format, $num_time);
    return $str_return;
}

/**
 * Validate date format via ereg. Delimeter can be a . (point)
 * Returns true if the date given is valid; otherwise returns false.
 *
 * <code>
 * $valid = is_date('28.03.1982');
 * </code>
 *
 * @param string $str_date format: dd.mm.yyyy  e.g. 30.12.2002
 * @param int $num_min_year mininum year
 * @param int $num_max_year maximum year
 * @return boolean
 */
function is_date($str_date, $num_min_year = 1, $num_max_year = 32767)
{
    $str_format = "/^([0-9]{2})[.]([0-9]{2})[.]([0-9]{4})$/";
    $arr_matches = array();
    if (preg_match($str_format, $str_date, $arr_matches)) {
        if ($arr_matches[3] >= $num_min_year && $arr_matches[3] <= $num_max_year) {
            if (checkdate($arr_matches[2], $arr_matches[1], $arr_matches[3])) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Validate time format
 *
 * <code>
 * $valid = is_time('15:10:00');
 * </code>
 *
 * @param string $str_time format: hh:mm:ss
 * @return array|bool
 */
function is_time($str_time)
{
    $str_format = "/^([0-9]{2})[:]([0-9]{2})[:]([0-9]{2})$/";
    $arr_matches = array();
    if (preg_match($str_format, $str_time, $arr_matches)) {
        // with seconds
        if ($arr_matches[1] >= 0 && $arr_matches[1] <= 23 &&
                $arr_matches[2] >= 0 && $arr_matches[2] <= 59 &&
                $arr_matches[3] >= 0 && $arr_matches[3] <= 59) {
            return true;
        }
    }
    return false;
}

//------------------------------------------------------------------------------
// Encoding
//------------------------------------------------------------------------------

/**
 * Returns a UTF-8 encoded string or array
 *
 * @param mixed $mix
 * @return mixed
 */
function encode_utf8($mix)
{
    if ($mix === null || $mix === '') {
        return $mix;
    }
    if (is_array($mix)) {
        foreach ($mix as $str_key => $str_val) {
            $mix[$str_key] = encode_utf8($str_val);
        }
        return $mix;
    } else {
        if (!mb_check_encoding($mix, 'UTF-8')) {
            return mb_convert_encoding($mix, 'UTF-8');
        } else {
            return $mix;
        }
    }
}

/**
 * Returns a ISO-8859-1 encoded string or array
 *
 * @param mixed $mix
 * @return mixed
 */
function encode_iso($mix)
{
    if ($mix === null || $mix === '') {
        return $mix;
    }
    if (is_array($mix)) {
        foreach ($mix as $str_key => $str_val) {
            $mix[$str_key] = encode_iso($str_val);
        }
        return $mix;
    } else {
        if (mb_check_encoding($mix, 'UTF-8')) {
            return mb_convert_encoding($mix, 'ISO-8859-1', 'auto');
        } else {
            return $mix;
        }
    }
}

/**
 * Returns json string from value
 *
 * @param mixed $mixValue
 * @param int $numOptions
 * @return string
 */
function encode_json($mixValue, $numOptions = 0)
{
    $str = json_encode(encode_utf8($mixValue), $numOptions);
    return $str;
}

/**
 * Returns array from json string
 *
 * @param string $str_json
 * @return array
 */
function decode_json($str_json)
{
    $arr = json_decode($str_json, true);
    return $arr;
}

/**
 * Takes a string as input and creates a human-friendly URL string.
 * This is useful if, for example, you have a blog in which you'd like to
 * use the title of your entries in the URL. Example:
 *
 * $title = "What's wrong with CSS?";
 * $url_title = url_title($title);
 * Produces: Whats-wrong-with-CSS
 *
 * @param string $str
 * @return string
 */
function url_title($str)
{
    if ($str === null || $str === '') {
        return '';
    }

    $arr_normal = char_map_utf8_array();

    // replace all silly chars
    $str = preg_replace_callback('/[^a-z0-9A-Z]/u', function($match) use ($arr_normal) {
        $num_code = uord($match[0]);
        if (isset($arr_normal[$num_code])) {
            return $arr_normal[$num_code];
        }
        return '-';
    }, $str);

    // replace 2 or more contiguous occurrences
    // of any minus character with a single minus
    $str = trim(preg_replace('/\-{2,}/', '-', $str), '-');
    return $str;
}

/**
 * Returns a mapping array (from utf-8 charcode to normal char)
 * @return array
 */
function char_map_utf8_array()
{
    return array(
        228 => 'a',
        246 => 'o',
        252 => 'u',
        196 => 'A',
        214 => 'O',
        220 => 'U',
        223 => 'sz',
        233 => 'e',
        232 => 'e',
        234 => 'e',
        235 => 'e',
        231 => 'c',
        219 => 'U',
        338 => 'O',
        339 => 'O',
        206 => 'I',
        202 => 'E',
        200 => 'E',
        201 => 'E',
        233 => 'e',
        199 => 'C',
        231 => 'c',
        194 => 'A',
        226 => 'a',
        192 => 'A',
        224 => 'a'
    );
}

/**
 * Return a specific character (utf-8)
 * @param int $num_code
 * @return string
 */
function uchr($num_code)
{
    return mb_convert_encoding(pack('n', $num_code), 'UTF-8', 'UTF-16BE');
}

/**
 * Return an UTF-8 value of character
 * @param string $str_char
 * @return num
 */
function uord($str_char)
{
    $k = mb_convert_encoding($str_char, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}

//------------------------------------------------------------------------------
// SMTP
//------------------------------------------------------------------------------

/**
 * Sendmail
 *
 * @param array $arrEmail
 * @param array $arrConfig default values and smtp parameter configuration
 * @return boolean|string  true = ok else error message as string
 *
 * <code>
 * $arrEmail = array();
 * $arrEmail['host'] = 'mail.gmx.net';
 * $arrEmail['username'] = 'mail@gmx.net';
 * $arrEmail['password'] = 'secret';
 *
 * $arrEmail['from'] = 'webmaster@mail.com';
 * $arrEmail['to'] = 'test@mail.com';
 * $arrEmail['subject'] = 'Test';
 * $arrEmail['body_text'] = "Testmail\nNext row";
 * $mixMailStatus = send_mail($arrEmail);
 *
 * if ($mixMailStatus === true) {
 *    echo 'sendmail ok';
 * } else {
 *    echo 'sendmail error: ' . $mixMailStatus;
 * }
 * </code>
 */
function send_mail($arrEmail, $arrConfig = null)
{
    $strError = '';

    if ($arrConfig !== null) {
        $arrEmail = array_merge($arrConfig, $arrEmail);
    }

    // smtp or mail
    $arrEmail['type'] = gv($arrEmail, 'type', 'smtp');
    // debugging: 1 = errors and messages, 2 = messages and data
    $arrEmail['debug'] = gv($arrEmail, 'debug', 0);
    $arrEmail['charset'] = gv($arrEmail, 'charset', 'UTF-8');
    $arrEmail['smtpauth'] = gv($arrEmail, 'smtpauth', true);
    $arrEmail['authtype'] = gv($arrEmail, 'authtype', 'LOGIN');
    // secure transfer enabled REQUIRED for GMail:  'ssl' or 'tls'
    $arrEmail['secure'] = gv($arrEmail, 'secure', false);
    $arrEmail['host'] = gv($arrEmail, 'host', '127.0.0.1');
    $arrEmail['helo'] = gv($arrEmail, 'helo', '');
    $arrEmail['port'] = gv($arrEmail, 'port', '25');
    $arrEmail['username'] = gv($arrEmail, 'username', '');
    $arrEmail['password'] = gv($arrEmail, 'password', '');

    $mail = new PHPMailer();

    // default is smtp
    $mail->IsSMTP();
    if ($arrEmail['type'] == 'mail') {
        // sendmail
        $mail->IsMail();
    }

    $mail->SMTPDebug = $arrEmail['debug'];
    $mail->CharSet = $arrEmail['charset'];
    $mail->SMTPAuth = $arrEmail['smtpauth'];
    $mail->AuthType = $arrEmail['authtype'];
    $mail->SMTPSecure = $arrEmail['secure'];
    $mail->Host = $arrEmail['host'];
    $mail->Hostname = $mail->Host;
    $mail->Helo = $arrEmail['helo'];
    $mail->Port = $arrEmail['port'];
    $mail->Username = $arrEmail['username'];
    $mail->Password = $arrEmail['password'];

    if (isset($arrEmail['priority'])) {
        $mail->Priority = $arrEmail['priority'];
    }

    if (isset($arrEmail['wordwrap'])) {
        $mail->WordWrap = $arrEmail['wordwrap'];
    }

    if (!isset($arrEmail['from_name'])) {
        $arrEmail['from_name'] = '';
    }

    $mail->SetFrom($arrEmail['from'], $arrEmail['from_name']);
    $mail->Subject = $arrEmail['subject'];

    if (isset($arrEmail['body_html'])) {
        $mail->MsgHTML($arrEmail['body_html']);

        if (isset($arrEmail['body_text'])) {
            $mail->AltBody = $arrEmail['body_text'];
        }
    } else {
        $mail->Body = $arrEmail['body_text'];
    }

    if (isset($arrEmail['to'])) {
        if (is_array($arrEmail['to'])) {
            foreach ($arrEmail['to'] as $strAddress) {
                $mail->AddAddress($strAddress);
            }
        } else {
            $mail->AddAddress($arrEmail['to']);
        }
    }

    if (isset($arrEmail['cc'])) {
        if (is_array($arrEmail['cc'])) {
            foreach ($arrEmail['cc'] as $strAddress) {
                $mail->AddCC($strAddress);
            }
        } else {
            $mail->AddCC($arrEmail['cc']);
        }
    }

    if (isset($arrEmail['bcc'])) {
        if (is_array($arrEmail['bcc'])) {
            foreach ($arrEmail['bcc'] as $strAddress) {
                $mail->AddBCC($strAddress);
            }
        } else {
            $mail->AddBCC($arrEmail['bcc']);
        }
    }

    if (isset($arrEmail['reply-to'])) {
        if (is_array($arrEmail['reply-to'])) {
            foreach ($arrEmail['reply-to'] as $strAddress) {
                $mail->AddReplyTo($strAddress);
            }
        } else {
            $mail->AddReplyTo($arrEmail['reply-to']);
        }
    }

    if (isset($arrEmail['attachment']) && is_array($arrEmail['attachment'])) {
        foreach ($arrEmail['attachment'] as $strFilename) {
            $mail->AddAttachment($strFilename);
        }
    }

    // send mail
    if (!$mail->Send()) {
        $strError = 'Mail error: ' . $mail->ErrorInfo;
        return $strError;
    } else {
        return true;
    }
}

//------------------------------------------------------------------------------
// Debugging
//------------------------------------------------------------------------------

/**
 * Write logs in a file
 *
 * @param string $strLevel
 * emergency, alert, critical, error, warning, notice, info, debug
 * @param string $strMessage
 * @param array $arrContext
 * @param string $strFilename (optional)
 * @throws Exception
 */
function logmsg($strLevel, $mixMessage, array $arrContext = array())
{
    $strLevel = strtolower($strLevel);

    // log to file
    if (!defined('G_LOG_DIR')) {
        throw new Exception('G_LOG_DIR is not defined');
    }

    if (is_string($mixMessage)) {
        if (!empty($arrContext)) {
            $mixMessage = interpolate($mixMessage, $arrContext);
        }
        $mixMessage = date('Y-m-d H:i:s') . ';' . $mixMessage . "\r\n";
    } else {
        $mixMessage = date('Y-m-d H:i:s') . ';' . var_export($mixMessage, true) . "\r\n";
    }

    if (!file_exists(G_LOG_DIR)) {
        mkdir(G_LOG_DIR, 0755, true);
    }

    $strFilename = date('Y-m-d') . '_' . $strLevel . '.txt';
    $strFilename = G_LOG_DIR . '/' . basename($strFilename);
    file_put_contents($strFilename, $mixMessage, FILE_APPEND);
}

/**
 * Prints human-readable information about a variable
 *
 * @param mixed $obj
 * @param string $str_name
 * @param bool $bool_exit
 */
function printr($obj, $str_name = '', $bool_exit = false)
{
    if ($str_name > '') {
        print('\'' . $str_name . '\' : ');
    }
    echo('<pre>');
    echo var_dump($obj, true);
    echo('</pre>');

    if ($bool_exit == true) {
        exit;
    }
}

/**
 * A smart alternative to PHP's var_dump and var_export function.
 * var_export does not handle circular references
 *
 * @link
 * http://www.leaseweblabs.com/2013/10/smart-alternative-phps-var_dump-function/
 *
 * @param mixed $variable
 * @param int $strlen
 * @param int $width
 * @param int $depth
 * @param int $i
 * @param mixed $objects
 * @return string
 */
function dump_var($variable, $strlen = 100, $width = 25, $depth = 10, $i = 0, &$objects = array())
{
    //$search = array("\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v");
    //$replace = array('\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v');

    $string = '';

    switch (gettype($variable)) {
        case 'boolean': $string.= $variable ? 'true' : 'false';
            break;
        case 'integer': $string.= $variable;
            break;
        case 'double': $string.= $variable;
            break;
        case 'resource': $string.= '[resource]';
            break;
        case 'NULL': $string.= "null";
            break;
        case 'unknown type': $string.= '???';
            break;
        case 'string':
            $len = strlen($variable);
            //$variable = str_replace($search, $replace, substr($variable, 0, $strlen), $count);
            //$variable = substr($variable, 0, $strlen);
            //if ($len < $strlen)
            //    $string.= '"' . $variable . '"';
            //else
            //$string.= 'string(' . $len . '): "' . $variable . '"...';
            $string.= 'string(' . $len . '): "' . $variable . '"';
            break;
        case 'array':
            $len = count($variable);
            if ($i == $depth) {
                $string.= 'array(' . $len . ') {...}';
            } elseif (!$len) {
                $string.= 'array(0) {}';
            } else {
                $keys = array_keys($variable);
                $spaces = str_repeat(' ', $i * 2);
                $string.= "array($len)\n" . $spaces . '{';
                $count = 0;
                foreach ($keys as $key) {
                    if ($count == $width) {
                        $string.= "\n" . $spaces . "  ...";
                        break;
                    }
                    $string.= "\n" . $spaces . "  [$key] => ";
                    $string.= dump_var($variable[$key], $strlen, $width, $depth, $i + 1, $objects);
                    $count++;
                }
                $string.="\n" . $spaces . '}';
            }
            break;
        case 'object':
            $id = array_search($variable, $objects, true);
            if ($id !== false) {
                $string .= get_class($variable) . '#' . ($id + 1) . ' {...}';
            } elseif ($i == $depth) {
                $string .= get_class($variable) . ' {...}';
            } else {
                $id = array_push($objects, $variable);
                $array = (array) $variable;
                $spaces = str_repeat(' ', $i * 2);
                $string.= get_class($variable) . "#$id\n" . $spaces . '{';
                $properties = array_keys($array);
                foreach ($properties as $property) {
                    $name = str_replace("\0", ':', trim($property));
                    $string.= "\n" . $spaces . "  [$name] => ";
                    $string.= dump_var($array[$property], $strlen, $width, $depth, $i + 1, $objects);
                }
                $string.= "\n" . $spaces . '}';
            }
            break;
    }

    if ($i > 0) {
        return $string;
    }

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

    do {
        $caller = array_shift($backtrace);
    } while ($caller && !isset($caller['file']));

    if ($caller) {
        $string = $caller['file'] . ':' . $caller['line'] . "\n" . $string;
    }

    return $string;
}

/**
 * Returns string by error type
 *
 * @param int $num_type
 * @return string
 */
function error_type_text($num_type)
{
    switch ($num_type) {
        case E_ERROR: // 1
            return 'E_ERROR';
        case E_WARNING: // 2
            return 'E_WARNING';
        case E_PARSE: // 4
            return 'E_PARSE';
        case E_NOTICE: // 8
            return 'E_NOTICE';
        case E_CORE_ERROR: // 16
            return 'E_CORE_ERROR';
        case E_CORE_WARNING: // 32
            return 'E_CORE_WARNING';
        case E_COMPILE_ERROR: // 64
            return 'E_COMPILE_ERROR';
        case E_CORE_WARNING: // 128
            return 'E_CORE_WARNING';
        case E_USER_ERROR: // 256
            return 'E_USER_ERROR';
        case E_USER_WARNING: // 512
            return 'E_USER_WARNING';
        case E_USER_NOTICE: // 1024
            return 'E_USER_NOTICE';
        case E_STRICT: // 2048
            return 'E_STRICT';
        case E_RECOVERABLE_ERROR: // 4096
            return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED: // 8192
            return 'E_DEPRECATED';
        case E_USER_DEPRECATED: // 16384
            return 'E_USER_DEPRECATED';
    }
    return $num_type;
}

/**
 * Text translation (I18n)
 *
 * @param string $strMessage
 * @param array $arrContext
 * @return string
 *
 * <code>
 * echo __('Hello');
 * echo __('There are {number} persons logged', array('number' => 7));
 * </code>
 */
function __($strMessage, array $arrContext = array())
{
    return \App::getTranslation()->translate($strMessage, $arrContext);
}
