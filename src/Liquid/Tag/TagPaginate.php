<?php

/**
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 *
 * Paginates a given collection
 *
 * @author Ryan Marshall (ryan@syngency.com)
 *
 * @example
 * {% paginate blog.articles by 5 %} {% for article in blog.articles %} {% endpaginate %}
 */

class LiquidTagPaginate extends LiquidBlock
{
	/**
	 * @var array The collection to paginate
	 */
	private $collectionName;

	/**
	 * @var array The collection object
	 */
	private $collection;
	
	/**
	 *
	 * @var int The size of the collection
	 */
	private $collectionSize;

	/**
	 * @var int The number of items to paginate by
	 */
	private $numberItems;
	
	/**
	 * @var int The current page
	 */
	private $currentPage;
	
	/**
	 * @var int Total pages
	 */
	private $totalPages;

	
	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @param LiquidFileSystem $fileSystem
	 * @return ForLiquidTag
	 */
	public function __construct($markup, &$tokens, &$fileSystem) {
		parent::__construct($markup, $tokens, $fileSystem);

		$syntax = new LiquidRegexp('/(' . LIQUID_ALLOWED_VARIABLE_CHARS . '+)\s+by\s+(\w+)/');

		if ($syntax->match($markup)) {
			$this->collectionName = $syntax->matches[1];
			$this->numberItems = $syntax->matches[2];
			$this->currentPage = ( is_numeric($_GET['page']) ) ? $_GET['page'] : 1;
			$this->currentOffset = ($this->currentPage - 1) * $this->numberItems;
			$this->extractAttributes($markup);
		} else {
			throw new LiquidException("Syntax Error - Valid syntax: paginate [collection] by [items]");
		}
	}

	/**
	 * Renders the tag
	 *
	 * @param LiquidContext $context
	 */
	public function render(&$context) {
		$this->collection = $context->get($this->collectionName);
		$this->collectionSize = count($this->collection);
		$this->totalPages = ceil($this->collectionSize / $this->numberItems);
		$paginated_collection =  array_slice($this->collection,$this->currentOffset,$this->numberItems);
		
		// Sets the collection if it's a key of another collection (ie search.results, collection.products, blog.articles)
		$segments = explode('.',$this->collectionName);
		if ( count($segments) == 2 ) {
			$context->set($segments[0], [$segments[1] => $paginated_collection]);
		} else {
			$context->set($this->collectionName, $paginated_collection);
		}
		
		$paginate = [
			'page_size' => $this->numberItems,
			'current_page' => $this->currentPage,
			'current_offset' => $this->currentOffset,
			'pages' => $this->totalPages,
			'items' => $this->collectionSize,
			'previous' => false,
			'next' => false
		];
		
		if ( $this->currentPage != 1 ) {
			$paginate['previous'] = [
				'title' => '&laquo; Previous',
				'url' => $this->current_url() . '?page=' . ( $this->currentPage - 1 )
			];
		}
		
		if ( $this->currentPage != $this->totalPages ) {
			$paginate['next'] = [
				'title' => 'Next &raquo;',
				'url' => $this->current_url() . '?page=' . ( $this->currentPage + 1 )
			];
		}

		$context->set('paginate',$paginate);
		return parent::render($context);
	}
	
	/**
	 * Returns the current page URL
	 */
	public function current_url() {
		$scheme = 'http';
		if ( $_SERVER['HTTPS'] == 'on' ) $scheme .= 's';
		$full_url = $scheme . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$parsed_url = parse_url($full_url);
		$current_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
		return $current_url;
	}
}
