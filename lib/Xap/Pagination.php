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
	 * Previous page range active HTML wrapper
	 *
	 * @var string
	 */
	public static $conf_html_prev_page_range_active = '{$number}';

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
	 * Page number filter
	 *
	 * @var callable
	 */
	public static $conf_page_num_filter;

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
	 * Rows exist flag
	 *
	 * @var boolean
	 */
	public $has_rows = false;

	/**
	 * Pagination HTML
	 *
	 * @var string
	 */
	public $pagination;

	/**
	 * Original pagination data
	 *
	 * @var \stdClass
	 */
	public $pagination_data;

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
	 * @param string $append_uri
	 * @param boolean $use_page_range
	 * @param int $page_range_count
	 * @param string $uri_first (optional override auto URI first, ex: '/item/view')
	 */
	public function __construct($xap_data, $append_uri = null, $use_page_range = false,
		$page_range_count = 0, $uri_first = null)
	{
		if(isset($xap_data['pagination'], $xap_data['rows']))
		{
			$this->rows = &$xap_data['rows'];
			$this->pagination_data = &$xap_data['pagination'];

			if(count($this->rows) > 0)
			{
				$this->has_rows = true;
			}

			$this->pagination = '';

			if($uri_first === null)
			{
				$uri_first = $this->__getAutoUri(); // use auto first URI
			}

			// only set HTML if pagination controls need to exist
			if($this->pagination_data->next > 0 || $this->pagination_data->prev > 0)
			{
				$this->pagination = self::$conf_html_wrapper_before;

				if($this->pagination_data->prev > 0)
				{
					if($this->pagination_data->prev === 1) // first
					{
						$this->pagination .= str_replace('{$uri}', $uri_first . $append_uri,
							self::$conf_html_prev);
					}
					else // all other
					{
						$this->pagination .= str_replace('{$uri}',
							$this->__getAutoUri($this->pagination_data->prev) . $append_uri,
							self::$conf_html_prev);
					}

					// previous page range
					if((self::$conf_prev_page_range || $use_page_range)
						&& $this->pagination_data->prev > 1)
					{
						foreach(array_slice(range(1, $this->pagination_data->prev),
							(int)$page_range_count > 0 ? -(int)$page_range_count
								: -(int)self::$conf_prev_page_range_count) as $v)
						{
							$this->pagination .= str_replace('{$uri}',
								$this->__getAutoUri($v == 1 ? null : $v) . $append_uri,
								str_replace('{$number}', $v, self::$conf_html_prev_page_range));
						}

						// add active page
						$this->pagination .= str_replace('{$number}', $this->pagination_data->page,
							self::$conf_html_prev_page_range_active);
					}
				}

				if($this->pagination_data->next > 0)
				{
					$this->pagination .= str_replace('{$uri}',
							$this->__getAutoUri($this->pagination_data->next) . $append_uri,
						self::$conf_html_next);
				}

				$this->pagination .= self::$conf_html_wrapper_after;
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
					if(self::$conf_page_num_filter !== null)
					{
						$f = &self::$conf_page_num_filter;
						$page_num = $f($page_num);
					}

					$qs[self::$conf_page_get_var] = $page_num;
				}

				if(count($qs) > 0)
				{
					$url['path'] .= '?' . http_build_query($qs); // add query string
				}
			}
			else if($page_num > 0) // add page num
			{
				if(self::$conf_page_num_filter !== null)
				{
					$f = &self::$conf_page_num_filter;
					$page_num = $f($page_num);
				}

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
		return $this->pagination;
	}
}