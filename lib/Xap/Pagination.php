<?php
/**
 * Xap - MySQL Rapid Development Engine for PHP 5.5+
 *
 * @package Xap
 * @version 0.0.9
 * @copyright 2016 Shay Anderson <http://www.shayanderson.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @link <https://github.com/shayanderson/xap>
 */
namespace Xap;

/**
 * Pagination helper class
 *
 * @author Shay Anderson
 */
class Pagination
{
	/**
	 * Next HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_next = '<a href="{$uri}">Next &raquo;</a>';

	/**
	 * Previous HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_prev = '<a href="{$uri}">&laquo; Previous</a>';

	/**
	 * Previous page range HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_prev_page_range = '<a href="{$uri}">{$number}</a>';

	/**
	 * Append HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_wrapper_after;

	/**
	 * Prepend HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_wrapper_before;

	/**
	 * GET var name for current page number
	 *
	 * @var string
	 */
	public static $conf_page_get_var = 'pg';

	/**
	 * Use previous page range flag
	 *
	 * @var boolean
	 */
	public static $conf_prev_page_range = false;

	/**
	 * Previous page range max page numbers to display
	 *
	 * @var int
	 */
	public static $conf_prev_page_range_count = 5;

	/**
	 * Pagination HTML
	 *
	 * @var string
	 */
	public $html;

	/**
	 * Pagination object
	 *
	 * @var \stdClass
	 */
	public $pagination;

	/**
	 * Row data
	 *
	 * @var \stdClass (or array)
	 */
	public $rows;

	/**
	 * Init
	 *
	 * @param array $xap_data (['pagination' => [...], 'rows' => [...]])
	 * @param string $uri_first (optional override auto URI first, ex: '/item/view')
	 */
	public function __construct($xap_data, $uri_first = null)
	{
		if(isset($xap_data['pagination'], $xap_data['rows']))
		{
			$this->rows = &$xap_data['rows'];
			$this->pagination = &$xap_data['pagination'];

			$this->html = '';

			if($uri_first === null)
			{
				$uri_first = $this->__getAutoUri(); // use auto first URI
			}

			// only set HTML if pagination controls need to exist
			if($this->pagination->next > 0 || $this->pagination->prev > 0)
			{
				$this->html = self::$conf_html_wrapper_before;

				if($this->pagination->prev > 0)
				{
					if($this->pagination->prev === 1) // first
					{
						$this->html .= str_replace('{$uri}', $uri_first, self::$conf_html_prev);
					}
					else // all other
					{
						$this->html .= str_replace('{$uri}',
							$this->__getAutoUri($this->pagination->prev), self::$conf_html_prev);
					}

					// previous page range
					if(self::$conf_prev_page_range && $this->pagination->prev > 1)
					{
						foreach(array_slice(range(1, $this->pagination->prev),
							-self::$conf_prev_page_range_count) as $v)
						{
							$this->html .= str_replace('{$uri}',
								$this->__getAutoUri($v == 1 ? null : $v),
								str_replace('{$number}', $v, self::$conf_html_prev_page_range));
						}
					}
				}

				if($this->pagination->next > 0)
				{
					$this->html .= str_replace('{$uri}',
							$this->__getAutoUri($this->pagination->next), self::$conf_html_next);
				}

				$this->html .= self::$conf_html_wrapper_after;
			}
		}
	}

	/**
	 * Get URI without pagination page number
	 *
	 * @param mixed $page_num
	 * @return string
	 */
	private function __getAutoUri($page_num = null)
	{
		$page_num = (int)$page_num;
		$url = parse_url(@$_SERVER['REQUEST_URI']);

		if(isset($url['path']))
		{
			if(isset($url['query'])) // handle query string
			{
				parse_str($url['query'], $qs); // make array

				if(isset($qs[self::$conf_page_get_var]))
				{
					unset($qs[self::$conf_page_get_var]); // rm page num
				}

				if($page_num > 0) // add page num
				{
					$qs[self::$conf_page_get_var] = $page_num;
				}

				if(count($qs) > 0)
				{
					$url['path'] .= '?' . http_build_query($qs); // add query string
				}
			}
			else if($page_num > 0) // add page num
			{
				$url['path'] .= '?' .http_build_query([self::$conf_page_get_var => $page_num]);
			}

			return $url['path'];

		}

		return '';
	}

	/**
	 * Pagination HTML printer
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->html;
	}
}