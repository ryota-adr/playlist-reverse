<?php
require_once('vendor/autoload.php');
use App\Initializer;
use App\Playlist;

session_start();

$initializer = new Initializer(__DIR__);
$initializer->init();


$playlist = new Playlist('', youtubeFactory(CLIENT_ID, CLIENT_SECRET, SCOPE, REDIRECT));

$playlist->outputHtml();
?>