<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5+
 *
 * @package Xap
 * @copyright 2016 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <https://github.com/shayanderson/xap/blob/master/LICENSE>
 * @link <https://github.com/shayanderson/xap>
 */
namespace Xap;

/**
 * Decorate class - decorate strings and data
 *
 * @author Shay Anderson <http://www.shayanderson.com/contact>
 */
class Decorate
{
	/**
	 * Placeholder pattern for '{$(var)?(:callable_filter|:TestYes?:TestNo)?}'
	 */
	const PATTERN_VAR_PLACEHOLDER = '/{\$([\w]+)?(?:\:([^}]+))?}/i';

	/**
	 * Placeholder strings
	 */
	const
		PLACEHOLDER_ARRAY_KEY = '{$:key}',
		PLACEHOLDER_TEST_VALUE_SEP = '?:';

	/**
	 * Decorate array data
	 *
	 * @param array $data
	 * @param string $decorator
	 * @param array $filters
	 * @param int $key
	 * @return string
	 */
	public static function __decorateArray(array &$data, &$decorator, &$filters, &$key = 0)
	{
		$str = $decorator;

		preg_replace_callback(self::PATTERN_VAR_PLACEHOLDER, function($m) use(&$data, &$str,
			&$filters)
		{
			if(isset($m[1]) || array_key_exists(1, $m))
			{
				if(empty($m[1])) // callable filter no key
				{
					if(isset($filters[$m[2]]) && is_callable($filters[$m[2]]))
					{
						$str = str_replace($m[0], $filters[$m[2]]($data), $str);
					}
				}
				else if((isset($data[$m[1]]) || array_key_exists($m[1], $data))
					&& (is_scalar($data[$m[1]]) || $data[$m[1]] === null))
				{
					if(isset($m[2])) // callable filter with key or test value
					{
						if(strpos($m[2], self::PLACEHOLDER_TEST_VALUE_SEP) !== false) // test value
						{
							$str = str_replace($m[0], self::test($data[$m[1]], $m[2]), $str);
						}
						// apply callable filter
						else if(isset($filters[$m[2]]) && is_callable($filters[$m[2]]))
						{
							$str = str_replace($m[0], $filters[$m[2]]($data[$m[1]]), $str);
						}
					}
					else // no callable filter
					{
						$str = str_replace($m[0], $data[$m[1]], $str);
					}
				}
			}
		}, $str);

		return str_replace(self::PLACEHOLDER_ARRAY_KEY, $key, $str); // add array key value
	}

	/**
	 * Decorate data (array, multidimensional array, object, or array of objects)
	 *
	 * @param mixed $data (array|object, ex: [['id' => a, 'name' => b], ['id' => x, 'name' => y]])
	 * @param string $decorator (ex: 'ID: {$id}, Name: {$name}<br />')
	 * @param array $filters (optional, ex: ['filter_name' => function(array $data) { ... }])
	 * @return string
	 */
	public static function data($data, $decorator, $filters = null)
	{
		$str = '';

		if(is_object($data))
		{
			$data = (array)$data;
		}

		foreach($data as $k => $v)
		{
			if(is_object($v))
			{
				$v = (array)$v;
			}

			if(is_array($v)) // multidimensional array
			{
				$str .= self::__decorateArray($v, $decorator, $filters, $k);
			}
			else if(is_scalar($v)) // single array
			{
				$str = self::__decorateArray($data, $decorator, $filters);
			}
		}

		return $str;
	}

	/**
	 * Decorate string
	 *
	 * @param string $string (ex: 'my string')
	 * @param string $decorator (ex: '<b>{$str}</b>', or shorthand: '<b>{$}</b>')
	 * @param array $filters (optional, ex: ['filter_name' => function($v) { ... }])
	 * @return string
	 */
	public static function string($string, $decorator, $filters = null)
	{
		if(is_scalar($string))
		{
			preg_replace_callback(self::PATTERN_VAR_PLACEHOLDER, function($m) use(&$string,
				&$decorator, &$filters)
			{
				if(isset($m[2])) // callable filter
				{
					// apply callable filter
					if(isset($filters[$m[2]]) && is_callable($filters[$m[2]]))
					{
						$decorator = str_replace($m[0], $filters[$m[2]]($string), $decorator);
					}
				}
				else // no callable filter
				{
					$decorator = str_replace($m[0], $string, $decorator);
				}
			}, $decorator);
		}

		return $decorator;
	}

	/**
	 * Test value for non-empty value (using empty() function)
	 *
	 * @param string $value (ex: '1')
	 * @param string $decorator (ex: 'Yes ?: No')
	 * @return string
	 */
	public static function test($value, $decorator)
	{
		if(($pos = strpos($decorator, self::PLACEHOLDER_TEST_VALUE_SEP)) !== false)
		{
			if(!empty($value))
			{
				return trim(substr($decorator, 0, $pos));
			}
			else
			{
				return trim(substr($decorator, $pos + 2));
			}
		}

		return $decorator;
	}
}