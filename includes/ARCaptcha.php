<?php
namespace MediaWiki\Extension\ArticleRanking;

class ARCaptcha {

	public static function verifyToken( $secret, $token ) {

		$data = [
			'secret'   => $secret,
			'response' => $token
		];

		$data = http_build_query( $data );

		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
		curl_setopt( $curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify' );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

		$result = curl_exec( $curl );
		$result = json_decode( $result );

		return $result->success === true;

	}

}
