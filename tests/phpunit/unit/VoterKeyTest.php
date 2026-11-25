<?php

namespace MediaWiki\Extension\ArticleRanking\Tests\Unit;

use MediaWiki\Extension\ArticleRanking\Vote;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\ArticleRanking\Vote::getAnonVoterKey
 * @covers \MediaWiki\Extension\ArticleRanking\Vote::getVoterNetwork
 * @group ArticleRanking
 */
class VoterKeyTest extends MediaWikiUnitTestCase {

	private const SECRET = 'test-secret';

	private static function key( string $ip ): string {
		return Vote::getAnonVoterKey( $ip, self::SECRET );
	}

	public function testIPv4IsHashedAsIs(): void {
		$this->assertSame( '192.0.2.10', Vote::getVoterNetwork( '192.0.2.10' ) );
		$this->assertSame(
			'ip:' . hash_hmac( 'sha256', '192.0.2.10', self::SECRET ),
			self::key( '192.0.2.10' ),
			'IPv4 keys must match the keys already stored for existing votes'
		);
		$this->assertSame( self::key( '192.0.2.10' ), self::key( '192.0.2.10' ) );
	}

	public function testIPv4NeighboursDiffer(): void {
		$this->assertNotSame( self::key( '192.0.2.10' ), self::key( '192.0.2.11' ) );
	}

	public static function provideSameIPv6Network(): array {
		return [
			'different interface ids' => [ '2001:db8:1:2::1', '2001:db8:1:2:aaaa:bbbb:cccc:dddd' ],
			'compressed vs. expanded, mixed case' => [
				'2001:db8:1:2::1', '2001:DB8:1:2:0:0:0:FFFF'
			],
			'last address of the /64' => [ '2001:db8:1:2::', '2001:db8:1:2:ffff:ffff:ffff:ffff' ],
		];
	}

	/**
	 * @dataProvider provideSameIPv6Network
	 */
	public function testIPv6InOneSlash64ShareAKey( string $a, string $b ): void {
		$this->assertSame( self::key( $a ), self::key( $b ) );
	}

	public static function provideDifferentIPv6Network(): array {
		return [
			'adjacent /64s' => [ '2001:db8:1:2::1', '2001:db8:1:3::1' ],
			'different /48s' => [ '2001:db8:1:2::1', '2001:db8:2:2::1' ],
		];
	}

	/**
	 * @dataProvider provideDifferentIPv6Network
	 */
	public function testIPv6InDifferentSlash64sDiffer( string $a, string $b ): void {
		$this->assertNotSame( self::key( $a ), self::key( $b ) );
	}

	public function testIPv6NetworkIsTheSlash64(): void {
		$this->assertSame(
			Vote::getVoterNetwork( '2001:db8:1:2::' ),
			Vote::getVoterNetwork( '2001:db8:1:2:dead:beef:0:1' )
		);
		$this->assertStringEndsWith( '/64', Vote::getVoterNetwork( '2001:db8:1:2::1' ) );
	}

	public function testKeyDependsOnTheSecret(): void {
		$this->assertNotSame(
			Vote::getAnonVoterKey( '192.0.2.10', 'one' ),
			Vote::getAnonVoterKey( '192.0.2.10', 'two' )
		);
	}
}
