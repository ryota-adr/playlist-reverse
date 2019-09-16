<?php
require_once('vendor/autoload.php');
use App\Initializer;

session_start();

$initializer = new Initializer(__DIR__);
$initializer->init();

$client = new Google_Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$client->setRedirectUri(REDIRECT);

$youtube = new Google_Service_YouTube($client);