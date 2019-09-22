<?php
require_once('defines.php');
require_once('vendor/autoload.php');
require_once('google-api-php-client-2.4.0/vendor/autoload.php');

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    function testMakeYoutubeServiceInstance()
    {
        $youtube = youtubeFactory(CLIENT_ID, CLIENT_SECRET, SCOPE, REDIRECT);
        $client = $youtube->getClient();

        $this->assertSame(get_class($youtube), 'Google_Service_YouTube');
        $this->assertSame($client->getClientId(), CLIENT_ID);
        $this->assertSame($client->getClientSecret(), CLIENT_SECRET);
        $this->assertSame($client->getScopes()[0], SCOPE);
        $this->assertSame($client->getRedirectUri(), REDIRECT);
    }
}