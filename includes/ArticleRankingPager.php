<?php
/**
 * @file
 * @ingroup Pager
 */

namespace MediaWiki\Extension\ArticleRanking;

use DateTime;
use ExtensionRegistry;
use MediaWiki\Extension\ArticleContentArea\ArticleContentArea;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use TablePager;
use Title;

class ArticleRankingPager extends TablePager {
	/** @var array SQL conditions */
	public $mConds = [];
	/** @var array SQL options */
	public $mOptions = [];
	/** @var int The maximum number of entries to show */
	public $mLimit = 10000;
	/** @var int[] List of default entry limit options to be presented to clients */
	public $mLimitsShown = [ 1000, 5000, 10000, 20000 ];
	/** @var int The default entry limit choosen for clients */
	public $mDefaultLimit = 10000;
	/** @var string|null The article content area to filter by */
	protected $mContentAreaFilter = null;

	/**
	 * @param SpecialPage $form
	 * @param array $conds
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( $form, $conds, LinkRenderer $linkRenderer ) {
		$dbr = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getReplicaDatabase();
		if ( $conds[ 'target' ] ) {
			$title = Title::newFromText( $conds[ 'target' ] );
			// An invalid title matches no page, so the report is empty rather than a fatal
			$this->mConds[ 'ranking_page_id' ] = $title ? $title->getArticleID() : 0;
		}
		if ( !empty( $conds[ 'start' ] ) ) {
			$this->mConds[] = 'ranking_timestamp >= ' . $dbr->timestamp( new DateTime( $conds[ 'start' ] ) );
		}
		if ( !empty( $conds[ 'end' ] ) ) {
			$this->mConds[] = 'ranking_timestamp <= ' . $dbr->timestamp( new DateTime( $conds[ 'end' ] ) );
		}

		if ( isset( $conds[ 'content_area' ] ) && !empty( $conds[ 'content_area' ] ) ) {
			$this->mContentAreaFilter = $conds[ 'content_area' ];
		}

		if ( $conds['min_rankings'] > 1 && empty( $conds['target'] ) ) {
			$this->mOptions[ 'HAVING' ] = 'SUM(ABS(ranking_value)) >= ' . $conds[ 'min_rankings' ];
		}

		parent::__construct( $form->getContext(), $linkRenderer );

		// getLimitOffsetForUser() will limit us to 5,000, which is not good enough for our purposes
		// So if the limit requested is one of the presets, which we allow, we override it
		// Only the limit is taken from here: the offset is the "<total>|<page id>" string IndexPager
		// already read, and the integer offset this returns would truncate it
		$reqLimit = $this->getRequest()->getInt( 'limit' );
		[ $this->mLimit ] =
			$this->getRequest()->getLimitOffsetForUser( $this->getUser(), $this->mDefaultLimit, '' );
		$this->mLimit = ( $reqLimit > 5000 && in_array( $reqLimit, $this->mLimitsShown ) ) ? $reqLimit : $this->mLimit;
	}

	/** @inheritDoc */
	protected function getFieldNames() {
		static $headers = null;

		if ( $headers == [] ) {
			$headers = [
				'ranking_page_id' => 'articleranking-page-title',
				'sum_total' => 'articleranking-sum-total',
				'sum_negative' => 'articleranking-sum-negative',
				'sum_negative_percent' => 'articleranking-sum-negative-percent',
				'sum_positive' => 'articleranking-sum-positive',
				'sum_positive_percent' => 'articleranking-sum-positive-percent',
			];
			if ( ExtensionRegistry::getInstance()->isLoaded( 'ArticleContentArea' ) ) {
				$headers[ 'content_area' ] = 'articleranking-tableheader-content-area';
			}

			foreach ( $headers as $key => $val ) {
				$headers[$key] = $this->msg( $val )->text();
			}
		}

		return $headers;
	}

	/** @inheritDoc */
	protected function getTableClass() {
		// Add wikitable for style, sortable for JS-based sorting on-page
		return 'mw-datatable wikitable sortable';
	}

	/** @inheritDoc */
	public function getQueryInfo() {
		$db = $this->getDatabase();
		$positiveVotesCond = $db->conditional( [ 'ranking_value > 0' ], 'ranking_value', '0' );
		$negativeVotesCond = $db->conditional( [ 'ranking_value < 0' ], '-ranking_value', '0' );

		$this->mOptions[ 'GROUP BY' ] = 'ranking_page_id';

		// We use sums here for b/c - older rows might have values larger than 1 or smaller than -1
		$query = [
			'tables' => [ 'article_rankings2' ],
			'fields' => [
				'ranking_page_id',
				'sum_negative' => "SUM($negativeVotesCond)",
				'sum_positive' => "SUM($positiveVotesCond)",
				'sum_total' => 'SUM(ABS(ranking_value))'
			],
			'conds' => $this->mConds,
			'options' => $this->mOptions
		];

		// If Extension:ArticleContentArea is available, use it
		if ( \ExtensionRegistry::getInstance()->isLoaded( 'ArticleContentArea' ) ) {
			$query = array_merge_recursive(
				$query, ArticleContentArea::getJoin( $this->mContentAreaFilter, 'ranking_page_id' )
			);
		}

		return $query;
	}

	/** @inheritDoc */
	public function getIndexField() {
		// Totals are not unique, so the page id (the GROUP BY key) breaks ties; without it,
		// paging skips or repeats the pages that share a total at a page boundary
		return [ [ 'sum_total', 'ranking_page_id' ] ];
	}

	/**
	 * The sort key is an aggregate, which WHERE cannot reference, so the offset condition
	 * IndexPager would put in WHERE goes in HAVING instead, applied to the grouped rows.
	 *
	 * @inheritDoc
	 */
	protected function buildQueryInfo( $offset, $limit, $order ) {
		$info = parent::buildQueryInfo( '', $limit, $order );
		if ( !$offset ) {
			return $info;
		}

		[ , $fields, , , $options ] = $info;
		$columns = (array)$this->mIndexField;
		// As in IndexPager, a shorter offset (e.g. an old link with a total only) uses the leading columns
		$offsets = explode( '|', $offset, count( $columns ) );
		$conds = [];
		foreach ( array_slice( $columns, 0, count( $offsets ) ) as $i => $column ) {
			// HAVING gets the aggregate expression, not its alias, and integers so that the
			// comparison is numeric
			$conds[ $fields[ $column ] ?? $column ] = (int)$offsets[ $i ];
		}
		if ( $order === self::QUERY_ASCENDING ) {
			$operator = $this->mIncludeOffset ? '>=' : '>';
		} else {
			$operator = $this->mIncludeOffset ? '<=' : '<';
		}

		$having = isset( $options[ 'HAVING' ] ) ? (array)$options[ 'HAVING' ] : [];
		$having[] = $this->getDatabase()->buildComparison( $operator, $conds );
		$options[ 'HAVING' ] = $having;
		$info[4] = $options;

		return $info;
	}

	/** @inheritDoc */
	public function getDefaultSort() {
		return '';
	}

	/** @inheritDoc */
	protected function getDefaultDirections() {
		return self::DIR_DESCENDING;
	}

	/** @inheritDoc */
	protected function isFieldSortable( $field ) {
		// no index for sorting exists
		return false;
	}

	/** @inheritDoc */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;
		switch ( $name ) {
			case 'ranking_page_id':
				$title = Title::newFromID( $value );
				return $title ? $this->getLinkRenderer()->makeKnownLink( $title ) : '';
			case 'sum_positive_percent':
				$percent = round( $row->sum_positive / $row->sum_total * 100 );
				return ( $percent . '%' );
			case 'sum_negative_percent':
				$percent = round( $row->sum_negative / $row->sum_total * 100 );
				return ( $percent . '%' );
			default:
				return $value;
		}
	}
}
