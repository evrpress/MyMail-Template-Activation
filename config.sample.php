<?php

//your secret app key
define('SECRET_APP_KEY', '');

//your client id
define('CLIENT_ID', '');

//set only if redirection fails
//define('REDIRECT_URL', 'http://example.com');


//set to true to output some info
define('DEBUG', false);


//set a random seed to hash custom download URIs
define('SEED', '9k0PaV2JWU4HKbze');


//add you items here
$items = array(

	999999 => array(
		'name' => 'Template name' //optional
		'description' => 'This is the Description',
		'author' => 'the author',
		'author_profile' => 'https://example.com',
		'version' => '1.0',
	),

);
