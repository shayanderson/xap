<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5.0+
 *
 * @package Xap
 * @version 0.0.3
 * @copyright 2014 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/xap>
 */
namespace Xap;

/**
 * Xap Decorator class
 *
 * @author Shay Anderson 07.14 <http://www.shayanderson.com/contact>
 */
class Decorator implements \IteratorAggregate
{
	/**
	 * Data
	 *
	 * @var array
	 */
	private $__data = [];

	/**
	 * Decorated data
	 *
	 * @var array
	 */
	private $__decorated = [];

	/**
	 * Data is scalar flag
	 *
	 * @var boolean
	 */
	private $__is_scalar = false;

	/**
	 * Init data and decorate data
	 *
	 * @param mixed $data
	 * @param string $decorator
	 */
	public function __construct($data, $decorator)
	{
		if(is_scalar($data))
		{
			$this->__data[] = $data;
			$this->__is_scalar = true;
		}
		else if(is_array($data))
		{
			$i = 0;
			if(!is_scalar(current($data))) // multidimensional array
			{
				foreach($data as $k => $v)
				{
					if(is_array($v))
					{
						$this->__data[$i] = $v;
					}
					else if(is_object($v))
					{
						$this->__data[$i] = (array)$v;
					}
					self::__validateArray($this->__data[$i]);
					$i++;
				}
			}
			else if(!empty($data)) // array
			{
				$this->__data[0] = $data;
				self::__validateArray($this->__data[0]);
			}
		}
		else if(is_object($data))
		{
			$this->__data[0] = (array)$data;
			self::__validateArray($this->__data[0]);
		}

		$this->__decorate($decorator);
		unset($data); // cleanup
	}

	/**
	 * Decorated data printer
	 *
	 * @return string
	 */
	public function __toString()
	{
		return implode('', $this->__decorated);
	}

	/**
	 * Decorate data
	 *
	 * @param string $decorator
	 * @return void
	 */
	private function __decorate($decorator)
	{
		$i = 0;
		foreach($this->__data as $v)
		{
			if($this->__is_scalar)
			{
				if(strpos($decorator, '?:') !== false) // switch decorator
				{
					$this->__decorated[$i] = self::__decorateSwitch($v, $decorator);
				}
				else // {$*} pattern
				{
					$this->__decorated[$i] = preg_replace('/\{\$([\w]+)?\}/', $v, $decorator); // match: '{$*}'
				}
			}
			else // array
			{
				$this->__decorated[$i] = $decorator;
				preg_replace_callback('/\{\$([\w]+)(\:[^\?]+\?\:[^\}]+)?\}/', // match: '{$x:y?:z}'
				function($m) use(&$v, &$i)
				{
					if(isset($v[$m[1]]) || array_key_exists($m[1], $v)) // column in pattern
					{
						if(count($m) === 2) // {$x}
						{
							$this->__decorated[$i] = str_replace($m[0], $v[$m[1]], $this->__decorated[$i]);
						}
						else if(count($m) === 3) // {$x:y?:z}
						{
							$switch = false;

							if(ctype_digit($v[$m[1]]))
							{
								if((int)$v[$m[1]] > 0)
								{
									$switch = true;
								}
							}
							else if(strlen($v[$m[1]]) > 0)
							{
								$switch = true;
							}

							$this->__decorated[$i] = str_replace($m[0], trim($switch
								? substr($m[2], 1, strpos($m[2], '?:') - 1) : substr($m[2], strpos($m[2], '?:') + 2)),
								$this->__decorated[$i]);
						}
					}

				}, $decorator);
			}
			$i++;
		}
	}

	/**
	 * Decorate data based on switch logic (x?:y)
	 *
	 * @param mixed $value
	 * @param string $decorator
	 * @return string
	 */
	private static function __decorateSwitch($value, $decorator)
	{
		$switch = false;

		if(is_bool($value) && $value)
		{
			$switch = true;
		}
		else if(is_int($value) && $value > 0)
		{
			$switch = true;
		}

		if(preg_match('/^([^\{]+\{)?([^\?]+)\?\:([^\}]+)(\}[^\}]+)?$/', $decorator, $m)) // match: '(*{)(x)?:(y)(}*)'
		{
			if(count($m) === 5) // *{x?:y}*
			{
				$value = substr($m[1], 0, strlen($m[1]) - 1) . trim( $switch ? $m[2] : $m[3] ) . substr($m[4], 1);
			}
			else if(count($m) === 4) // x?:y
			{
				$value = trim( $switch ? $m[2] : $m[3] );
			}
		}

		return $value;
	}

	/**
	 * Validate data array - ensure not multidimensional array data
	 *
	 * @param array $arr
	 * @return void
	 * @throws \Exception (when invalid data array - multidimensional array)
	 */
	private static function __validateArray(array $arr)
	{
		foreach($arr as $v)
		{
			if(is_array($v) || is_object($v)) // multidimensional array
			{
				throw new \Exception('Failed to decorate, invalid array depth');
			}
		}
	}

	/**
	 * Data getter
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->__data;
	}

	/**
	 * Get decorated data as array
	 *
	 * @return array
	 */
	public function getDecorated()
	{
		return $this->__decorated;
	}

	/**
	 * Iterator getter
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->__decorated);
	}
}