<?php
require_once('defines.php');
require_once('vendor/autoload.php');
require_once('google-api-php-client-2.4.0/vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use App\PlaylistSorter;

class PlaylistSorterTest extends TestCase
{
    public function testCheckSettings()
    {
        $sortMode = 'popular';
        $privacyStatus = 'public';
        $playlistSorter = new PlaylistSorter('testPlaylistId', $sortMode, $privacyStatus, youtubeFactory(CLIENT_ID, CLIENT_SECRET, SCOPE, REDIRECT));

        $this->assertSame($playlistSorter->getSortMode(), $sortMode);
        $this->assertSame($playlistSorter->getPrivacyStatus(), $privacyStatus);
    }
}