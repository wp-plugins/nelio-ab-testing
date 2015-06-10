<?php

// ******************************************************************
// A couple of constants inherited from the plugin
define( 'NELIOAB_BACKEND_NAME', 'nelioabtesting' );
define( 'NELIOAB_BACKEND_DOMAIN', NELIOAB_BACKEND_NAME . '.appspot.com' );



// ******************************************************************
// If we're not processing a POST request...
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
	// Silence is gold
	die();
}


// ******************************************************************
// Let's extract the information from the request
$url = false;
$data = false;

if ( isset( $_POST['originalRequestUrl'] ) ) {
	$url = $_POST['originalRequestUrl'];
	$url = preg_replace( '/^\/\//', '', $url );
}
else {
	// Silence is gold
	return;
}

if ( isset( $_POST['data'] ) ) {
	$data = $_POST['data'];
}
else {
	// Silence is gold
	return;
}



// ******************************************************************
// Trying to send data using cURL
$was_data_sent = false;




// ******************************************************************
// Trying to send data using cURL
if ( !$was_data_sent && function_exists( 'curl_version' ) ) {
	//open connection
	$ch = curl_init();

	if ( $ch ) {
		//set the url, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, substr_count( $data, '=' ) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		if ( isset( $_SERVER['HTTP_REFERER'] ) )
			curl_setopt( $ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER'] );
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) )
			curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );

		//execute post
		$result = curl_exec( $ch );

		//close connection
		curl_close( $ch );

		$was_data_sent = true;
	}
}




// ******************************************************************
// If that failed, let's try to send data using sockets
if ( !$was_data_sent && function_exists( 'fsockopen' ) ) {
	$server = NELIOAB_BACKEND_DOMAIN;
	$action = '/v6/rn';

	$fp = fsockopen( $server );

	if ( $fp ) {
		fwrite( $fp, "POST http://$url HTTP/1.1\r\n" );
		fwrite( $fp, "Host: " . $_SERVER['SERVER_HOST'] . "\r\n" );
		fwrite( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
		fwrite( $fp, "Content-Length: " . strlen( $data ) . "\r\n" );
		if ( isset( $_SERVER['HTTP_REFERER'] ) )
			fwrite( $fp, "Referer: " . $_SERVER['HTTP_REFERER'] . "\r\n" );
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) )
			fwrite( $fp, "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n" );
		fwrite( $fp, "Connection: close\r\n" );
		fwrite( $fp, "\r\n" );
		fwrite( $fp, $data );
		while ( !feof ($fp ) )
			echo fgets( $fp, 128 );
		fclose( $fp );
		$was_data_sent = true;
	}
}

