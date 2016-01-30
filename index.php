<?php

if (!file_exists('config.php')) die('Copy the config.sample.php to config.php');
require 'config.php';

if ( defined('REDIRECT_URL') ) {
	$redirect_uri = REDIRECT_URL;
} else {
	//try to guess the redirect urL
	$redirect_uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') ."://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/';
}


//load the API (thanks Dave!)
require 'envato-api.class.php';

$api = new envato_api_basic();

$api->set_client_id( CLIENT_ID );
$api->set_client_secret( SECRET_APP_KEY );
$api->set_redirect_url( $redirect_uri );


//list items
if ( isset($_GET['items']) ) {

	$return = array();

	foreach($_GET['items'] as $slug => $item_id){

		if ( isset($items[$item_id]))

			$return[] = $items[$item_id];

	}

	@header( 'Content-type: application/json' );
	echo json_encode($return);
	exit;




//redirect to Envato authorization page
} elseif ( isset($_GET['auth']) ) {

	@session_start();

	$_SESSION['returnto'] = $_GET['returnto'];
	$_SESSION['item_id'] = $_GET['item_id'];
	$_SESSION['slug'] = $_GET['slug'];
	$_SESSION['auth'] = $_GET['auth'];
	$_SESSION['time'] = time();

	if(!empty($_SESSION['returnto']) && !empty($_SESSION['returnto']) && !empty($_SESSION['returnto'])){
		$url = $api->get_authorization_url();
	}else{
		$url = $api->get_authorization_url();
	}

	header('Location: '.$url);
	exit;




//return from the Envato authorization page
} elseif ( isset( $_GET['code'] ) ){

	@session_start();

	$response = $api->get_authentication( $_GET['code'] );

	if ( isset($response['error']) ) {

		$url = buildquery($_SESSION['returnto'], array(
			'mymail_error' => urlencode($response['error_description']),
			'mymail_nonce' => $_SESSION['auth'],
		));


	} else if ( isset($response['refresh_token']) && isset($response['access_token']) ) {

		$api->set_personal_token($response['access_token']);

		$params = buildquery(array(
			'item_id' => (int) $_SESSION['item_id'],
			'shorten_url' => 'true',
		));

		$response = $api->get('v3/market/buyer/download'.$params, false, false);

		$item_id = (int) $_SESSION['item_id'];
		$slug = $_SESSION['slug'];

		if ( isset($response['error']) ) {

			$error_description = $response['description'];

			$url = buildquery($_SESSION['returnto'], array(
				'mymail_error' => urlencode($error_description),
				'mymail_slug' => $slug,
				'mymail_nonce' => $_SESSION['auth'],
			));

		} else if ( isset($response['download_url']) ) {

			$download_url = $response['download_url'];

			if(isset($items[$item_id]['file_path']) && file_exists($items[$item_id]['file_path'])){

				//expire every 30 minutes
				$time = floor(time()/86400*(2*24));
				$download_url = buildquery($redirect_uri, array(
					'item_id' => $item_id,
					'download' => md5(SEED.$item_id.$time),
				));

			}

			$url = buildquery($_SESSION['returnto'], array(
				'mymail_download_url' => urlencode($download_url),
				'mymail_slug' => $slug,
				'mymail_nonce' => $_SESSION['auth'],
			));

		}



	}

	header('Location: '.$url);
	exit;



//return from the Envato authorization page
} elseif ( isset( $_GET['error'] ) ){

	@session_start();

	$url = buildquery($_SESSION['returnto'], array(
		'mymail_error' => urlencode($_GET['error_description']),
		'mymail_nonce' => $_SESSION['auth'],
	));

	header('Location: '.$url);
	exit;


//custom file path in use
} elseif ( isset($_GET['download'] ) ) {

	$time = floor(time()/86400*(2*24));
	$item_id = (int) $_GET['item_id'];
	$hash = md5(SEED.$item_id.$time);

	if($_GET['download'] == $hash ){

		$path = $items[$item_id]['file_path'];

		header('Content-Description: File Transfer');
		header('Content-Type: application/zip');
		header('Content-Length: ' .(string)(filesize($path)) );
		header('Content-Disposition: attachment; filename='.$hash.'.zip');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		ob_clean();
		flush();
		readfile($path);

	}

	exit;




//if debug is enabled
} elseif ( defined('DEBUG') && DEBUG ){

	echo '<pre>Current Path: '.getcwd().'</pre>';
	echo '<pre>Secret App Key: '.SECRET_APP_KEY.'</pre>';
	echo '<pre>Client ID: '.CLIENT_ID.'</pre>';
	echo '<pre>Redirect URL: '.$redirect_uri.'</pre>';
	echo '<pre>Items: '."\n".htmlspecialchars(print_r($items, true)).'</pre>';

}


function buildquery( $url, $args = array() ) {
	if(is_array($url)){
		$args = $url;
		$url = '';
	}

	$connector = strpos($url, '?') !== false ? '&' : '?';

	return (string) $url . $connector .http_build_query((array) $args, null, '&');
}

