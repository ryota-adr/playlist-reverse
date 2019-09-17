<?php

namespace App;

class Playlist
{
    const maxResults = 50;

    private $title;
    private $description;
    private $id;
    private $videoIds = [];

    private $client;
    private $youtube;

    private $html;
    
    public function __construct($playlistId, \Google_Service_YouTube $youtube)
    {
        $this->youtube = $youtube;
        $this->client = $this->youtube->getClient();

        $this->setAccessToken();

        $this->setVideoIds($playlistId);
    }

    private function setAccessToken()
    {
        if (isset($_GET['code'])) {
            if (strval($_SESSION['state']) !== strval($_GET['state'])) {
                die('The session state did not match.');
            }

            $this->client->authenticate($_GET['code']);
            $_SESSION['token'] = $this->client->getAccessToken();
            if (headers_sent()) {
                header('Location: ' . REDIRECT);
            }
        }

        if (isset($_SESSION['token'])) {
            $this->client->setAccessToken($_SESSION['token']);
        }
    }

    public function getVideoIds()
    {
        return $this->videoIds;
    }

    private function setVideoIds($playlistId)
    {
        if ($this->client->getAccessToken()) {
            try {
                $playlistItemResponse = $this->youtube->playlistItems->listPlaylistItems(
                    'snippet', [
                    'playlistId' => $playlistId,
                    'maxResults' => static::maxResults,
                ]);

                foreach($playlistItemResponse->getItems() as $item) {
                    array_push($this->videoIds, $item->getSnippet()->getResourceId()->getVideoId());
                }

                while($playlistItemResponse->nextPageToken) {
                    $playlistItemResponse = $this->youtube->playlistItems->listPlaylistItems(
                        'snippet', [
                            'playlistId' => $playlistId,
                            'maxResults' => static::maxResults,
                            'pageToken' => $playlistItemResponse->nextPageToken,
                    ]);

                    foreach($playlistItemResponse->getItems() as $item) {
                        array_push(
                            $this->videoIds,
                            $item->getSnippet()->getResourceId()->getVideoId()
                        );
                    }
                }
            } catch (Google_Service_Exception $e) {
                $this->html = sprintf('<p>A service error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
            } catch (Google_Exception $e) {
                $this->html = sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
            }
        } else {
            $state = mt_rand();
            $this->client->setState($state);
            $_SESSION['state'] = $state;

            $authUrl = $this->client->createAuthUrl();
            $this->html = <<<END
<h3>Authorization Required</h3>
<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
        }
    }

    public function insert()
    {
        foreach($this->videoIds as $videoId) {
            
        }
    }

    public function outputHtml()
    {
        if ($this->html) {
            echo $this->html;
        }
    }
}