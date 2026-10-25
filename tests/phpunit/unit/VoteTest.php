<?php

namespace MediaWiki\Extension\ArticleRanking\Tests\Unit;

use InvalidArgumentException;
use MediaWiki\Extension\ArticleRanking\Vote;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ArticleRanking\Vote::computeTotals
 * @group ArticleRanking
 */
class VoteTest extends MediaWikiUnitTestCase {

	public static function provideComputeTotals(): array {
		return [
			'zero votes' => [ 0, 0, 0, 0.0 ],
			'up-votes only' => [ 7, 0, 7, 100.0 ],
			'down-votes only' => [ 0, 4, 4, 0.0 ],
			// The two pages that reported 109% and -120% on dev (kz-mediawiki-main#181)
			'mixed, mostly up' => [ 25, 2, 27, 2500 / 27 ],
			'mixed, mostly down' => [ 6, 11, 17, 600 / 17 ],
			'mixed, even' => [ 3, 3, 6, 50.0 ],
		];
	}

	/**
	 * @dataProvider provideComputeTotals
	 */
	public function testComputeTotals(
		int $positive, int $negative, int $expectedTotal, float $expectedRank
	): void {
		$totals = Vote::computeTotals( $positive, $negative );

		$this->assertSame( $positive, $totals['positive_votes'] );
		$this->assertSame( $negative, $totals['negative_votes'] );
		$this->assertSame( $expectedTotal, $totals['total_votes'] );
		$this->assertIsFloat( $totals['rank'] );
		$this->assertEqualsWithDelta( $expectedRank, $totals['rank'], 0.0001 );
		$this->assertGreaterThanOrEqual( 0, $totals['rank'] );
		$this->assertLessThanOrEqual( 100, $totals['rank'] );
	}

	public function testComputeTotalsRejectsNegativeCounts(): void {
		$this->expectException( InvalidArgumentException::class );
		Vote::computeTotals( 3, -2 );
	}
}
