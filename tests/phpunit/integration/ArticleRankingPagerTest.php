<?php

namespace MediaWiki\Extension\ArticleRanking\Tests\Integration;

use MediaWiki\Extension\ArticleRanking\ArticleRankingPager;
use MediaWiki\Extension\ArticleRanking\SpecialArticleRankingReport;
use MediaWiki\Request\FauxRequest;
use MediaWikiIntegrationTestCase;
use RequestContext;

/**
 * @covers \MediaWiki\Extension\ArticleRanking\ArticleRankingPager
 * @group ArticleRanking
 * @group Database
 */
class ArticleRankingPagerTest extends MediaWikiIntegrationTestCase {

	private function newPager( string $target, array $query = [] ): ArticleRankingPager {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $query ) );
		$special = new SpecialArticleRankingReport();
		$special->setContext( $context );

		return new ArticleRankingPager(
			$special,
			[ 'target' => $target, 'start' => null, 'end' => null, 'min_rankings' => 0 ],
			$this->getServiceContainer()->getLinkRenderer()
		);
	}

	/**
	 * @param int[] $totals Page id => number of single votes
	 */
	private function insertVotes( array $totals ): void {
		$rows = [];
		foreach ( $totals as $pageId => $count ) {
			for ( $i = 0; $i < $count; $i++ ) {
				$rows[] = [
					'ranking_page_id' => $pageId,
					'ranking_value' => $i % 2 ? -1 : 1,
					'ranking_timestamp' => $this->getDb()->timestamp(),
				];
			}
		}
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'article_rankings2' )
			->rows( $rows )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * The page ids of one page of the report, in the order it displays them
	 *
	 * @param ArticleRankingPager $pager
	 * @return int[]
	 */
	private function shownPageIds( ArticleRankingPager $pager ): array {
		$pager->doQuery();
		$ids = [];
		foreach ( $pager->getResult() as $row ) {
			$ids[] = (int)$row->ranking_page_id;
		}
		// The query fetches one row past the page to tell whether a "next" link is needed
		$ids = array_slice( $ids, 0, $pager->getLimit() );
		return $pager->mIsBackwards ? array_reverse( $ids ) : $ids;
	}

	/**
	 * Three pages share a total, so a page boundary falls among them
	 */
	private function insertTiedVotes(): void {
		$this->insertVotes( [ 9001 => 5, 9002 => 3, 9003 => 3, 9004 => 3, 9005 => 1 ] );
	}

	public function testOffsetPagingWalksTheWholeReportOnce(): void {
		$this->insertTiedVotes();

		$pages = [];
		$query = [ 'limit' => 2 ];
		do {
			$pager = $this->newPager( '', $query );
			$pages[] = $this->shownPageIds( $pager );
			$next = $pager->getPagingQueries()['next'];
			$query = $next ? [ 'limit' => 2, 'offset' => $next['offset'] ] : null;
		} while ( $query && count( $pages ) < 10 );

		$this->assertSame( [ [ 9001, 9004 ], [ 9003, 9002 ], [ 9005 ] ], $pages );
	}

	public function testPrevLinkReturnsThePreviousPage(): void {
		$this->insertTiedVotes();

		// The last page, then the link back from it
		$last = $this->newPager( '', [ 'limit' => 2, 'offset' => '3|9002' ] );
		$this->assertSame( [ 9005 ], $this->shownPageIds( $last ) );
		$prev = $last->getPagingQueries()['prev'];

		$pager = $this->newPager( '', [ 'limit' => 2, 'dir' => 'prev', 'offset' => $prev['offset'] ] );

		$this->assertSame( [ 9003, 9002 ], $this->shownPageIds( $pager ) );
	}

	public function testOffsetPagingOnATargetedReport(): void {
		$page = $this->getExistingTestPage( 'ArticleRankingPagerTest' );
		$this->insertVotes( [ $page->getId() => 4, 9001 => 7 ] );
		$target = $page->getTitle()->getPrefixedText();

		// Any offset on a targeted report used to be a DBQueryError (kz-mediawiki-main#203).
		// The report runs from the highest total down, so an offset shows the totals below it.
		$this->assertSame(
			[ $page->getId() ],
			$this->shownPageIds( $this->newPager( $target, [ 'offset' => '100' ] ) )
		);
		$this->assertSame( [], $this->shownPageIds( $this->newPager( $target, [ 'offset' => '4' ] ) ) );
		$this->assertSame(
			[ $page->getId() ],
			$this->shownPageIds( $this->newPager( $target, [ 'dir' => 'prev', 'offset' => '1' ] ) )
		);
	}

	public function testInvalidTargetMatchesNoPage(): void {
		// '<' is never legal in a title, so Title::newFromText() returns null
		$pager = $this->newPager( '<invalid>' );

		$this->assertSame( 0, $pager->mConds['ranking_page_id'] );
		$this->assertSame( 0, $pager->getNumRows() );
	}

	public function testExistingTargetFiltersByItsPageId(): void {
		$page = $this->getExistingTestPage( 'ArticleRankingPagerTest' );

		$pager = $this->newPager( $page->getTitle()->getPrefixedText() );

		$this->assertSame( $page->getId(), $pager->mConds['ranking_page_id'] );
	}
}
