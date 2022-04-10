<?php

namespace MediaWiki\Extension\ArticleRanking;

use SpecialPage;

class SpecialArticleRankingReport extends SpecialPage {
	protected $opts;
	protected $pager;

	public function __construct( $name = 'ArticleRanking' ) {
		parent::__construct( $name, 'articleranking-view-report' );
	}

	public function execute( $sub ) {
		parent::execute( $sub );

		$out = $this->getOutput();
		$this->opts = [];
		$request = $this->getRequest();

		$this->opts['target']  = $par ?? $request->getVal( 'target' );
		$this->opts['limit'] = $request->getInt( 'limit' );

		$skip = $request->getText( 'offset' ) || $request->getText( 'dir' ) == 'prev';
		# Offset overrides date selection
		if ( !$skip ) {
			$this->opts['start'] = $request->getVal( 'start' );
			$this->opts['end'] = $request->getVal( 'end' );
		}

		$this->opts['min_rankings'] = $request->getInt( 'min_rankings' );

		$this->pager = new ArticleRankingPager( $this, $this->opts, $this->getLinkRenderer() );
		$out->addHTML( $this->getForm( $this->opts ) );
		$out->addHTML( $this->pager->getFullOutput()->getText() );
	}

	/**
	 * Generates the filtering form.
	 * @param array $pagerOptions with keys limit, target, start, end
	 * @return string HTML fragment
	 */
	protected function getForm( array $pagerOptions ) {
		$fields = [];
		$target = $this->opts['target'] ?? null;
		$fields['target'] = [
			'type' => 'title',
			'default' => $target ?
				str_replace( '_', ' ', $target ) : '' ,
			'label' => $this->msg( 'articleranking-filter-title' )->text(),
			'name' => 'target',
			'id' => 'mw-target-title',
			'size' => 40,
			'required' => false,
			'autofocus' => !$target,
		];
		$fields['start'] = [
			'type' => 'date',
			'default' => '',
			'id' => 'mw-date-start',
			'label' => $this->msg( 'date-range-from' )->text(),
			'name' => 'start',
			//'section' => 'articleranking-date',
		];
		$fields['end'] = [
			'type' => 'date',
			'default' => '',
			'id' => 'mw-date-end',
			'label' => $this->msg( 'date-range-to' )->text(),
			'name' => 'end',
			//'section' => 'articleranking-date',
		];
		$fields['min_rankings'] = [
			'type' => 'int',
			'min' => 2,
			'default' => '20',
			'id' => 'mw-min-rankings',
			'name' => 'min_rankings',
			'label' => $this->msg( 'articleranking-filter-min-ranknigs' )->text()
		];
		$fields['limit'] = [
			'type' => 'limitselect',
			'label-message' => 'table_pager_limit_label',
			'options' => $this->pager->getLimitSelectList(),
			'name' => 'limit',
			'default' => $this->pager->getLimit(),
		];

		$htmlForm = \HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$htmlForm
			->setMethod( 'get' )
			// When offset is defined, the user is paging through results,
			// so we hide the form by default to allow users to focus on browsing
			// rather than defining search parameters
			->setCollapsibleOptions(
				( $pagerOptions['target'] ?? null ) ||
				( $pagerOptions['start'] ?? null ) ||
				( $pagerOptions['end'] ?? null )
			)
			->setWrapperLegend( $this->msg( 'articleranking-filter-legend' )->text() );

		$htmlForm->loadData();

		return $htmlForm->getHTML( false );
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'other';
	}

}
