<?php

namespace MediaWiki\Extension\ArticleRanking\Tests\Integration;

use MediaWiki\Extension\ArticleRanking\Captcha;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use MWHttpRequest;
use ReflectionMethod;
use Status;

/**
 * @covers \MediaWiki\Extension\ArticleRanking\Captcha
 * @group ArticleRanking
 */
class CaptchaTest extends MediaWikiIntegrationTestCase {

	/** cData this fork sends; overridden in the teen fork's copy of this test. */
	private const CDATA = 'articleranking';

	/** @var string|false MW_ENVIRONMENT before the test */
	private $savedEnvironment;

	protected function setUp(): void {
		parent::setUp();
		$this->savedEnvironment = getenv( 'MW_ENVIRONMENT' );
	}

	protected function tearDown(): void {
		putenv( $this->savedEnvironment === false
			? 'MW_ENVIRONMENT'
			: 'MW_ENVIRONMENT=' . $this->savedEnvironment );
		parent::tearDown();
	}

	private function enableCaptcha( string $secret = 'se' ): void {
		$this->setMwGlobals( 'wgArticleRankingCaptcha', [ 'siteKey' => 'sk', 'secret' => $secret ] );
		$this->overrideConfigValue( 'Server', 'https://kolzchut.org.il' );
	}

	/** What siteverify answers for Cloudflare's always-pass test secret. */
	private function stubTestSecretSiteverify(): void {
		$this->stubSiteverify( json_encode( [
			'success' => true,
			'hostname' => 'example.com',
			'error-codes' => [],
		] ) );
	}

	/**
	 * Stub the Turnstile siteverify HTTP call with a canned response.
	 *
	 * @param string $body Response body returned by getContent()
	 * @param bool $httpOk Whether the request Status is OK (false = transport error)
	 */
	private function stubSiteverify( string $body, bool $httpOk = true ): void {
		$req = $this->createMock( MWHttpRequest::class );
		$req->method( 'execute' )
			->willReturn( $httpOk ? Status::newGood() : Status::newFatal( 'http-error' ) );
		$req->method( 'getContent' )->willReturn( $body );

		$factory = $this->createMock( HttpRequestFactory::class );
		$factory->method( 'create' )->willReturn( $req );
		$this->setService( 'HttpRequestFactory', $factory );
	}

	public function testDisabledCaptchaFailsOpen(): void {
		$this->setMwGlobals( 'wgArticleRankingCaptcha', [ 'siteKey' => '', 'secret' => '' ] );
		// No siteverify call happens when disabled; any token is accepted.
		$this->assertTrue( Captcha::verifyToken( 'irrelevant' ) );
	}

	public function testValidTokenPasses(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( json_encode( [
			'success' => true,
			'cdata' => self::CDATA,
			'hostname' => 'kolzchut.org.il',
		] ) );
		$this->assertTrue( Captcha::verifyToken( 'good-token' ) );
	}

	public function testMissingHostnameStillPasses(): void {
		// The hostname check applies only when siteverify reports one.
		$this->enableCaptcha();
		$this->stubSiteverify( json_encode( [
			'success' => true,
			'cdata' => self::CDATA,
		] ) );
		$this->assertTrue( Captcha::verifyToken( 'good-token' ) );
	}

	public function testWrongCdataFails(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( json_encode( [
			'success' => true,
			'cdata' => 'some-other-service',
			'hostname' => 'kolzchut.org.il',
		] ) );
		$this->assertFalse( Captcha::verifyToken( 'foreign-token' ) );
	}

	public function testWrongHostnameFails(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( json_encode( [
			'success' => true,
			'cdata' => self::CDATA,
			'hostname' => 'evil.example.com',
		] ) );
		$this->assertFalse( Captcha::verifyToken( 'replayed-token' ) );
	}

	public function testTestSecretPassesInDevelopment(): void {
		putenv( 'MW_ENVIRONMENT=development' );
		$this->enableCaptcha( Captcha::TEST_SECRET );
		$this->stubTestSecretSiteverify();
		$this->assertTrue( Captcha::verifyToken( 'XXXX.DUMMY.TOKEN.XXXX' ) );
	}

	public function testTestSecretFailsOutsideDevelopment(): void {
		foreach ( [ 'MW_ENVIRONMENT=staging', 'MW_ENVIRONMENT=production', 'MW_ENVIRONMENT' ] as $env ) {
			putenv( $env );
			$this->enableCaptcha( Captcha::TEST_SECRET );
			$this->stubTestSecretSiteverify();
			$this->assertFalse( Captcha::verifyToken( 'XXXX.DUMMY.TOKEN.XXXX' ), $env );
		}
	}

	public function testRealSecretKeepsChecksInDevelopment(): void {
		// Only the test secret skips the cData and hostname checks.
		putenv( 'MW_ENVIRONMENT=development' );
		$this->enableCaptcha();
		$this->stubTestSecretSiteverify();
		$this->assertFalse( Captcha::verifyToken( 'token' ) );
	}

	public function testUnsuccessfulTestSecretFailsInDevelopment(): void {
		putenv( 'MW_ENVIRONMENT=development' );
		$this->enableCaptcha( Captcha::TEST_SECRET );
		$this->stubSiteverify( json_encode( [
			'success' => false,
			'error-codes' => [ 'invalid-input-response' ],
		] ) );
		$this->assertFalse( Captcha::verifyToken( 'bad-token' ) );
	}

	public function testUnsuccessfulVerificationFails(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( json_encode( [
			'success' => false,
			'error-codes' => [ 'invalid-input-response' ],
		] ) );
		$this->assertFalse( Captcha::verifyToken( 'bad-token' ) );
	}

	public function testTransportErrorFails(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( '', false );
		$this->assertFalse( Captcha::verifyToken( 'token' ) );
	}

	public function testMalformedJsonFails(): void {
		$this->enableCaptcha();
		$this->stubSiteverify( 'this is not json' );
		$this->assertFalse( Captcha::verifyToken( 'token' ) );
	}

	/**
	 * Regression guard for the two-arg bug: verifyToken() must accept exactly
	 * one parameter. A second argument (as ApiVoteMessage once passed) sent the
	 * secret as the token and dropped the real one — latent only because the
	 * captcha was disabled.
	 */
	public function testVerifyTokenAcceptsSingleArgument(): void {
		$method = new ReflectionMethod( Captcha::class, 'verifyToken' );
		$this->assertSame(
			1,
			$method->getNumberOfParameters(),
			'Captcha::verifyToken() must take exactly one argument (the token).'
		);
	}
}
