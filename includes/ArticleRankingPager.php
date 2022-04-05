<?php
/**
 * @file
 * @ingroup Pager
 */

namespace MediaWiki\Extension\ArticleRanking;

use DateTime;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use TablePager;

class ArticleRankingPager extends TablePager {
	public $mConds = [];
	public $mOptions = [];

	/**
	 * @param SpecialPage $form
	 * @param array $conds
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( $form, $conds, LinkRenderer $linkRenderer ) {
		$dbr = wfGetDB( DB_REPLICA );
		if ( $conds[ 'target' ] ) {
			$pageId = \Title::newFromText( $conds[ 'target' ] )->getArticleID();
			$this->mConds[ 'ranking_page_id' ] = $pageId;
		}
		// @todo add conditions for start/end (timestamp)
		if ( $conds[ 'start' ] ) {
			$this->mConds[] = 'ranking_timestamp >= ' . $dbr->timestamp( new DateTime( $conds[ 'start' ] ) );
		}
		if ( $conds[ 'end' ] ) {
			$this->mConds[] = 'ranking_timestamp <= ' . $dbr->timestamp( new DateTime( $conds[ 'end' ] ) );
		}

		if ( $conds['min_rankings'] > 1 && empty( $conds['target'] ) ) {
			$this->mOptions[ 'HAVING' ] = 'SUM(ABS(ranking_value)) >= ' . $conds[ 'min_rankings' ];
		}

		parent::__construct( $form->getContext(), $linkRenderer );
	}

	/* @inheritDoc */
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
			foreach ( $headers as $key => $val ) {
				$headers[$key] = $this->msg( $val )->text();
			}
		}

		return $headers;
	}

	/**
	 * @inheritDoc
	 */
	protected function getTableClass() {
		return 'mw-datatable wikitable';
	}

	public function getQueryInfo() {
		$db = $this->getDatabase();
		$positiveVotesCond = $db->conditional( [ 'ranking_value > 0' ], 'ranking_value', '0' );
		$negativeVotesCond = $db->conditional( [ 'ranking_value < 0' ], '-ranking_value', '0' );

		$this->mOptions[ 'GROUP BY' ] = 'ranking_page_id';

		// We use sums here for b/c - older rows might have values larger than 1 or smaller than -1
		return [
			'tables' => [ 'article_rankings2' ],
			'fields' => [
				'ranking_page_id',
				'sum_negative' => "SUM($negativeVotesCond)",
				'sum_positive' => "SUM($positiveVotesCond)",
				'sum_total' => "SUM($negativeVotesCond) + SUM($positiveVotesCond)"
			],
			'conds' => $this->mConds,
			'options' => $this->mOptions
		];
	}

	public function getIndexField() {
		return 'ranking_page_id';
	}

	public function getDefaultSort() {
		return 'ranking_page_id';
	}

	protected function isFieldSortable( $field ) {
		// no index for sorting exists
		return false;
	}

	public function formatValue( $name, $value ) {
		$sum_total = $this->mCurrentRow->sum_negative + $this->mCurrentRow->sum_positive;

		// TODO: Implement formatValue() method.
		switch ( $name ) {
			case 'ranking_page_id':
				$title = \Title::newFromID( $value );
				return $this->getLinkRenderer()->makeKnownLink( $title );
			case 'sum_positive_percent':
				$percent = round( $this->mCurrentRow->sum_positive / $sum_total * 100 );
				return ( $percent . '%' );
			case 'sum_negative_percent':
				$percent = round( $this->mCurrentRow->sum_negative / $sum_total * 100 );
				return ( $percent . '%' );
			default:
				return $value;
		}
	}
}
