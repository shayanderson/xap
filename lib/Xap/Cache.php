<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5+
 *
 * @package Xap
 * @version 0.0.5
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/xap>
 */
namespace Xap;

/**
 * Xap Cache class
 *
 * @author Shay Anderson 08.14 <http://www.shayanderson.com/contact>
 */
class Cache
{
	/**
	 * Expire time
	 *
	 * @var string
	 */
	private static $__expire = '-30 seconds';

	/**
	 * Global expire time
	 *
	 * @var string
	 */
	private static $__expire_global = '-30 seconds';

	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	private static $__key_prefix;

	/**
	 * Cache directory path
	 *
	 * @var string
	 */
	private static $__path;

	/**
	 * Format expire time
	 *
	 * @param mixed $expire (int ex: 20 (for 20 seconds), or string ex: '20 seconds')
	 * @return string
	 */
	private static function __formatExpire($expire)
	{
		return '-' . ( is_int($expire) ? $expire . ' seconds' : $expire );
	}

	/**
	 * Reset expire time and cache key prefix
	 *
	 * @return void
	 */
	private static function __reset()
	{
		self::$__expire = self::$__expire_global;
		self::$__key_prefix = null;
	}

	/**
	 * Flush all cache files in cache path directory
	 *
	 * @return void
	 */
	public static function flush()
	{
		array_map('unlink', glob(self::$__path . '*')); // flush all cache files
	}

	/**
	 * Expire time formatted string getter
	 *
	 * @return string
	 */
	public static function getExpire()
	{
		return ltrim(self::$__expire, '-');
	}

	/**
	 * Global expire time formatted string getter
	 *
	 * @return string
	 */
	public static function getExpireGlobal()
	{
		return ltrim(self::$__expire_global, '-');
	}

	/**
	 * Cache key getter
	 *
	 * @param int $connection_id (for multiple connection handling)
	 * @param string $query
	 * @param mixed $query_params (array|null)
	 * @return string
	 */
	public static function getKey(&$connection_id, &$query, &$query_params)
	{
		return ( self::$__key_prefix !== null ? self::$__key_prefix . '-' : '' )
			. sha1($connection_id . $query . ( is_array($query_params) ? implode('', $query_params) : null ));
	}

	/**
	 * Cache path getter
	 *
	 * @return string
	 */
	public static function getPath()
	{
		return self::$__path;
	}

	/**
	 * Valid cache exists flag getter
	 *
	 * @param string $key
	 * @return boolean
	 */
	public static function has($key)
	{
		if(is_readable(self::$__path . $key))
		{
			if(@filemtime(self::$__path . $key) < strtotime(self::$__expire)) // expire cache
			{
				self::__reset();
				return false;
			}

			self::__reset();
			return true;
		}

		self::__reset();
		return false;
	}

	/**
	 * Cache getter
	 *
	 * @param string $key
	 * @return string
	 */
	public static function read($key)
	{
		return @unserialize(base64_decode(file_get_contents(self::$__path . $key)));
	}

	/**
	 * Cache key prefix setter
	 *
	 * @param string $key_prefix (\w characters only, all others are stripped)
	 * @return void
	 */
	public static function setCacheKeyPrefix($key_prefix)
	{
		self::$__key_prefix = preg_replace('/[^\w]+/', '', $key_prefix);
	}

	/**
	 * Expire time setter
	 *
	 * @param mixed $expire (int ex: 20 (for 20 seconds), or string ex: '20 seconds')
	 * @return void
	 */
	public static function setExpire($expire)
	{
		self::$__expire = self::__formatExpire($expire);
	}

	/**
	 * Global expire time setter
	 *
	 * @param mixed $expire (int ex: 20 (for 20 seconds), or string ex: '20 seconds')
	 * @return void
	 */
	public static function setExpireGlobal($expire)
	{
		self::$__expire_global = self::__formatExpire($expire);
		self::__reset();
	}

	/**
	 * Cache directory path setter
	 *
	 * @param string $cache_path
	 * @return void
	 */
	public static function setPath($cache_path)
	{
		self::$__path = rtrim($cache_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Cache setter
	 *
	 * @param string $key
	 * @param mixed $data (array|object)
	 * @return array
	 * @throws \Exception (when cache file write fails)
	 */
	public static function &write($key, $data)
	{
		if(@file_put_contents(self::$__path . $key, base64_encode(serialize($data)), LOCK_EX) === false)
		{
			throw new \Exception('Failed to write cache file \'' . self::$__path . $key . '\'');
		}

		return $data;
	}
}