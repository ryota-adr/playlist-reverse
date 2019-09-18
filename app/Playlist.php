<?php

namespace App;

class Playlist
{
    const maxResults = 50;

    private $title;
    private $description;
    private $id;
    private $videos = [];

    private $client;
    private $youtube;

    private $html;
    
    public function __construct($playlistId, \Google_Service_YouTube $youtube)
    {
        $this->setPlaylistId($playlistId);
        $this->youtube = $youtube;
        $this->client = $this->youtube->getClient();

        $this->setAccessToken();

        $this->generate();
        $this->setVideos($playlistId);
    }

    private function setPlaylistId($playlistId)
    {
        $first2str = substr($playlistId, 0, 2);
        if ($first2str === 'PL') {
            $this->id = $playlistId;
        } else if ($first2str === 'UC') {
            $this->id = 'UU' . substr($playlistId, 2);
        }
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

    private function generate()
    {
        if ($this->client->getAccessToken()) {
            try {
                $this->setVideos($this->sortVideos($this->makeVideoInfoCollection()));
                dump($this->getVideos());
                session_destroy();
            } catch (\Google_Service_Exception $e) {
                $this->html = $this->errorMessage($e);
            } catch (\Google_Exception $e) {
                $this->html = sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
            }
        } else {
            $state = mt_rand();
            $this->client->setState($state);
            $_SESSION['state'] = $state;

            $authUrl = $this->client->createAuthUrl();
            $this->html = <<<END
<div><a href="$authUrl">ログイン</a>が必要です。</div>
END;
        }
    }

    private function makeVideoInfoCollection()
    {
        $playlistId = $this->id;
        $videos = [];

        if ($playlistId === null) {
            return [];
        }

        $playlistItemResponse = $this->youtube->playlistItems->listPlaylistItems(
            'snippet', [
            'playlistId' => $playlistId,
            'maxResults' => static::maxResults,
        ]);

        foreach($playlistItemResponse->getItems() as $item) {
            $snippet = $item->getSnippet();
            array_push($videos, [
                'publishedAt' => $snippet->publishedAt,
                'title' => $snippet->title,
                'videoId' => $snippet->getResourceId()->getVideoId()
            ]);
        }

        while($playlistItemResponse->nextPageToken) {
            $playlistItemResponse = $this->youtube->playlistItems->listPlaylistItems(
                'snippet', [
                    'playlistId' => $playlistId,
                    'maxResults' => static::maxResults,
                    'pageToken' => $playlistItemResponse->nextPageToken,
            ]);

            foreach($playlistItemResponse->getItems() as $item) {
                $snippet = $item->getSnippet();
                array_push($videos, [
                    'publishedAt' => $snippet->publishedAt,
                    'title' => $snippet->title,
                    'videoId' => $snippet->getResourceId()->getVideoId()
                ]);
            }
        }

        return $videos;
    }

    public function getVideos()
    {
        return $this->videos;
    }

    private function setVideos($videos)
    {
        $this->videos = $videos;
    }

    private function sortVideos($videos)
    {
        if (empty($videos)) {
            return [];
        }

        $publishedAts = array_column($videos, 'publishedAt');

        array_multisort($videos, $publishedAts);

        return $videos;
    }

    public function insert()
    {
        foreach($this->videos as $video) {
            
        }
    }

    public function getHtml()
    {
        if ($this->html) {
            return $this->html;
        }
    }

    private function errorMessage($e)
    {
        $error = json_decode($e->getMessage(), true)['error'];

        if ($error['code'] === 404 && $error['errors'][0]['reason'] === 'playlistNotFound') {
            $message = 'プレイリストIDかチャンネルIDが間違っています。';
        } else {
            $message = 'エラー';
        }

        return "<div class=\"h4\">$message</div>";
    }
}