<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/lgpl.txt GNU/LGPL
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public
 *   License as published by the Free Software Foundation; either
 *   version 3 of the License, or (at your option) any later version.
 *
 ***************************************************************************/


/*
 * Partial iconv implementation in pure PHP
 *
 * Not implemented:

iconv                     - Convert string to requested character encoding
iconv_mime_decode_headers - Decodes multiple MIME header fields at once
iconv_mime_decode         - Decodes a MIME header field


 * Implemented:
 
iconv_get_encoding - Retrieve internal configuration variables of iconv extension
iconv_set_encoding - Set current setting for character encoding conversion
iconv_mime_encode  - Composes a MIME header field
ob_iconv_handler   - Convert character encoding as output buffer handler
iconv_strlen       - Returns the character count of string
iconv_strpos       - Finds position of first occurrence of a needle within a haystack
iconv_strrpos      - Finds the last occurrence of a needle within a haystack
iconv_substr       - Cut out part of a string

 *
 */


define('ICONV_MIME_DECODE_STRICT',            1);
define('ICONV_MIME_DECODE_CONTINUE_ON_ERROR', 2);


function iconv($in_charset, $out_charset, $str) {return utf8_iconv::iconv($in_charset, $out_charset, $str);}
function iconv_mime_decode_headers($encoded_headers, $mode = 2, $charset = INF) {return utf8_iconv::mime_decode_headers($encoded_headers, $mode, $charset);}
function iconv_mime_decode($encoded_headers, $mode = 2, $charset = INF)         {return utf8_iconv::mime_decode        ($encoded_headers, $mode, $charset);}
function iconv_get_encoding($type = 'all')   {return utf8_iconv::get_encoding($type);}
function iconv_set_encoding($type, $charset) {return utf8_iconv::set_encoding($type, $charset);}
function iconv_mime_encode($field_name, $field_value, $pref = INF) {return utf8_iconv::mime_encode($field_name, $field_value, $pref);}
function ob_iconv_handler($buffer, $mode)  {return utf8_iconv::ob_handler($buffer, $mode);}
function iconv_strlen($s, $encoding = INF) {return utf8_iconv::strlen($s, $encoding = INF);}
function iconv_strpos ($haystack, $needle, $offset = 0, $encoding = INF) {return utf8_iconv::strpos ($haystack, $needle, $offset, $encoding);}
function iconv_strrpos($haystack, $needle,              $encoding = INF) {return utf8_iconv::strrpos($haystack, $needle,          $encoding);}
function iconv_substr($s, $start, $length = PHP_INT_MAX, $encoding = INF) {return utf8_iconv::substr($s, $start, $length, $encoding);}


class utf8_iconv
{
	const

	ERROR_ILLEGAL_CHARACTER = 'utf8_iconv::iconv(): Detected an illegal character in input string',
	ERROR_WRONG_CHARSET     = 'utf8_iconv::iconv(): Wrong charset, conversion from `%s\' to `%s\' is not allowed';


	static protected

	$input_encoding = 'UTF-8',
	$output_encoding = 'UTF-8',
	$internal_encoding = 'UTF-8',

	$translit_map = array(),
	$convert_map = array();


	static function iconv($in_charset, $out_charset, $str)
	{
		if ('' === $str) return '';


		// Prepare for //IGNORE and //TRANSLIT

		$TRANSLIT = $IGNORE = '';

		$out_charset = strtr(strtolower($out_charset), '_', '-');
		$in_charset  = strtr(strtolower($in_charset ), '_', '-');

		if ('//translit' === substr($out_charset, -10))
		{
			$TRANSLIT = '//TRANSLIT';
			$out_charset = substr($out_charset, 0, -10);
		}

		if ('//ignore' === substr($out_charset, -8))
		{
			$IGNORE = '//IGNORE';
			$out_charset = substr($out_charset, 0, -8);
		}

		'//translit' === substr($in_charset, -10) && $in_charset = substr($in_charset, 0, -10);
		'//ignore'   === substr($in_charset,  -8) && $in_charset = substr($in_charset, 0,  -8);


		// Load charset maps

		if (   ('utf-8' !==  $in_charset && !self::loadMap('from.',  $in_charset,  $in_map)
		    || ('utf-8' !== $out_charset && !self::loadMap(  'to.', $out_charset, $out_map) )
		{
			trigger_error(sprintf(self::ERROR_WRONG_CHARSET, $in_charset, $out_charset));
			return false;
		}


		if ('utf-8' !== $in_charset)
		{
			// Convert input to UTF-8

			ob_start();

			$str = 2 === count($in_map)
				? call_user_func_array($in_map, array(&$str, $IGNORE, $in_charset))
				: self::map_to_utf8($in_map, $str, $IGNORE);

			$str = false === $str && ob_end_clean() || 1 ? false : ob_get_clean();
		}
		else if ('utf-8' === $out_charset || 2 === count($out_map))
		{
			// UTF-8 validation

			$str = self::utf8_to_utf8($str, $IGNORE);
		}

		if ('utf-8' !== $out_charset && false !== $str)
		{
			// Convert output to UTF-8

			ob_start();

			$str = 2 === count($out_map)
				? call_user_func_array($out_map, array(&$str, $IGNORE, $TRANSLIT, $out_charset))
				: self::map_from_utf8($out_map, $str, $IGNORE, $TRANSLIT);

			$str = false === $str && ob_end_clean() || 1 ? false : ob_get_clean();
		}

		return $str;
	}

	static function mime_decode_headers($encoded_headers, $mode = ICONV_MIME_DECODE_CONTINUE_ON_ERROR, $charset = INF)
	{
		INF === $charset && $charset = self::$internal_encoding;

		trigger_error('utf8_iconv::mime_decode_headers() not implemented'); // TODO

		return false;
	}

	static function mime_decode($encoded_headers, $mode = ICONV_MIME_DECODE_CONTINUE_ON_ERROR, $charset = INF)
	{
		INF === $charset && $charset = self::$internal_encoding;

		trigger_error('utf8_iconv::mime_decode() not implemented'); // TODO

		return false;
	}

	static function get_encoding($type = 'all')
	{
		switch ($type)
		{
		case 'input_encoding'   : return self::$input_encoding;
		case 'output_encoding'  : return self::$output_encoding;
		case 'internal_encoding': return self::$internal_encoding;
		}

		return array(
			'input_encoding'    => self::$input_encoding,
			'output_encoding'   => self::$output_encoding,
			'internal_encoding' => self::$internal_encoding
		);
	}

	static function set_encoding($type, $charset)
	{
		switch ($type)
		{
		case 'input_encoding'   : self::$input_encoding    = $charset; break;
		case 'output_encoding'  : self::$output_encoding   = $charset; break;
		case 'internal_encoding': self::$internal_encoding = $charset; break;

		default: return false;
		}

		return true;
	}

	static function mime_encode($field_name, $field_value, $pref = INF)
	{
		is_array($pref) || $pref = array();

		$pref += array(
			'scheme'           => 'B',
			'input-charset'    => self::$internal_encoding,
			'output-charset'   => self::$internal_encoding,
			'line-length'      => 76,
			'line-break-chars' => "\r\n"
		);

		preg_match('/[\x80-\xFF]/', $field_name) && $field_name = '';

		$scheme = strtoupper(substr($pref['scheme'], 0, 1));
		$in  = strtoupper($pref['input-charset']);
		$out = strtoupper($pref['output-charset']);

		if ('UTF-8' !== $in && false === $field_value = iconv($in, 'UTF-8', $field_value)) return false;

		preg_match_all('/./us', $field_value, $chars);

		$chars = isset($chars[0]) ? $chars[0] : array();

		$line_break  = (int) $pref['line-length'];
		$line_start  = "=?{$pref['output-charset']}?{$scheme}?";
		$line_length = strlen($field_name) + 2 + strlen($line_start) + 2;
		$line_offset = strlen($line_start) + 3;
		$line_data   = '';

		$field_value = array();

		$Q = 'Q' === $scheme;

		foreach ($chars as &$c)
		{
			if ('UTF-8' !== $out && false === $c = iconv('UTF-8', $out, $c)) return false;

			$o = $Q
				? $c = preg_replace_callback(
					'/[=_\?\x00-\x1F\x80-\xFF]/',
					array(__CLASS__, 'qp_byte_callback'),
					$c
				)
				: base64_encode($line_data . $c);

			if ($line_length + strlen($o) > $line_break)
			{
				$Q || $line_data = base64_encode($line_data);
				$field_value[] = $line_start . $line_data . '?=';
				$line_length = $line_offset;
				$line_data = '';
			}

			$line_data .= $c;
			$Q && $line_length += strlen($c);
		}

		if ('' !== $line_data)
		{
			$Q || $line_data = base64_encode($line_data);
			$field_value[] = $line_start . $line_data . '?=';
		}

		return $field_name . ': ' . implode($pref['line-break-chars'] . ' ', $field_value);
	}

	static function ob_handler($buffer, $mode)
	{
		return self::iconv(self::$internal_encoding, self::$output_encoding, $buffer);
	}

	static function strlen($s, $encoding = INF)
	{
		return INF === $encoding
			? mb_strlen($s)
			: mb_strlen($s, $encoding);
	}

	static function strpos ($haystack, $needle, $offset = 0, $encoding = INF)
	{
		return INF === $encoding
			? mb_strpos($haystack, $needle, $offset)
			: mb_strpos($haystack, $needle, $offset, $encoding);
	}

	static function strrpos($haystack, $needle, $encoding = INF)
	{
		return INF === $encoding
			? mb_strrpos($haystack, $needle)
			: mb_strrpos($haystack, $needle, $encoding);
	}

	static function substr($s, $start, $length = PHP_INT_MAX, $encoding = INF)
	{
		return INF === $encoding
			? mb_substr($s, $start, $length)
			: mb_substr($s, $start, $length, $encoding);
	}


	protected static function loadMap($type, $charset, &$map)
	{
		$map =& self::$convert_map[$type . $charset];

		if (INF === $map)
		{
			$map = resolvePath('data/utf8/charset/' . $type . $charset . '.ser');
			if (false === $map) return false;
			$map = unserialize($map);
		}

		return true;
	}

	protected static function utf8_to_utf8(&$str, $IGNORE)
	{
		static $utf_len_mask = array("\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4);

		$valid = preg_match('//u', $str);

		if (!$valid && !$IGNORE)
		{
			trigger_error(self::ERROR_ILLEGAL_CHARACTER);
			return false;
		}

		ob_start();

		$i = 0;
		$len = strlen($str);

		while ($i < $len)
		{
			if ($str[$i] < "\x80") echo $str[$i++];
			else
			{
				$utf_len = $s[$i] & "\xF0";
				$utf_len = isset($utf_len_mask[$utf_len]) ? $utf_len_mask[$utf_len] : 1;
				$utf_chr = substr($str, $i, $utf_len);
				$i += $utf_len;

				if (1 === $utf_len || !($valid || preg_match('//u', $utf_chr)))
				{
					if ($IGNORE) continue;

					trigger_error(self::ERROR_ILLEGAL_CHARACTER);
					return false;
				}

				echo $utf_chr;
			}
		}

		$str = ob_get_clean();
	}

	protected static function map_to_utf8(&$map, &$str, $IGNORE)
	{
		trigger_error('utf8_iconv::map_to_utf8() not implemented');  // TODO

		return false;
	}

	protected static function map_from_utf8(&$map, &$str, $IGNORE, $TRANSLIT)
	{
		static $utf_len_mask = array("\xC0" => 2, "\xD0" => 2, "\xE0" => 3, "\xF0" => 4);

		$TRANSLIT
			&& self::$translit_map
			|| self::$translit_map = unserialize(file_get_contents(resolvePath('data/utf8/translit.ser')));

		$i = 0;
		$len = strlen($str);

		while ($i < $len)
		{
			if ($str[$i] < "\x80") $utf_chr = $str[$i++];
			else
			{
				$utf_len = $s[$i] & "\xF0";

				if (isset($utf_len_mask[$utf_len])) $utf_len = $utf_len_mask[$utf_len];
				else if ($IGNORE)
				{
					++$i;
					continue;
				}
				else
				{
					trigger_error(self::ERROR_ILLEGAL_CHARACTER);
					return false;
				}

				$utf_chr = substr($str, $i, $utf_len);
				$i += $utf_len;
			}

			do
			{
				if (isset($map[$utf_chr]))
				{
					echo $map[$utf_chr];
					continue 2;
				}

				if ($TRANSLIT && isset($translit_map[$utf_chr]))
				{
					$utf_chr = $translit_map[$utf_chr];
					continue;
				}
			}
			while (0);

			if (!$IGNORE)
			{
				trigger_error(self::ERROR_ILLEGAL_CHARACTER);
				return false;
			}
		}

		return true;
	}

	protected static function qp_byte_callback($m)
	{
		return '=' . strtoupper(dechex(ord($m[0])));
	}
}