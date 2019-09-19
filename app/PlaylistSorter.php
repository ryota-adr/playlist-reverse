<?php

namespace App;

class PlaylistSorter
{
    const maxResults = 50;
    const maxitems = 200;

    private $title;
    private $description;
    private $originalPlaylistId;
    private $videos = [];
    private $newPlaylist;
    private $sortMode;
    private $privacyStatus;
    private $client;
    private $youtube;

    private $html;
    
    public function __construct($urlOrOriginalPlaylistId, $sortMode, $privacyStatus, \Google_Service_YouTube $youtube)
    {
        $this->setOriginalPlaylistId($this->url2PlaylistId($urlOrOriginalPlaylistId));
        $this->sortMode = $sortMode;
        $this->privacyStatus = $privacyStatus;
        $this->youtube = $youtube;
        $this->client = $this->youtube->getClient();

        $this->setAccessToken();
    }

    private function isYoutubeChannelOrPlaylistUrl($urlOrOriginalPlaylistId) {
        preg_match('/https:\/\/www\.youtube\.com\/(channel|playlist)/', $urlOrOriginalPlaylistId, $match);

        return $match ? true : false;
    }

    private function url2PlaylistId($urlOrOriginalPlaylistId) {
        if ($this->isYoutubeChannelOrPlaylistUrl($urlOrOriginalPlaylistId)) {
            $id = preg_replace(
                '/https:\/\/www\.youtube\.com\/(channel\/|playlist\?list=)/',
                '',
                $urlOrOriginalPlaylistId
            );
        } else {
            $id = $urlOrOriginalPlaylistId;
        }

        if ((substr($id, 0, 2)) === 'UC') {
            return 'UU' . substr($id, 2);
        } else {
            return $id;
        }
    }

    private function setOriginalPlaylistId($originalPlaylistId)
    {
        $first2str = substr($originalPlaylistId, 0, 2);
        if ($first2str === 'PL') {
            $this->originalPlaylistId = $originalPlaylistId;
        } else if ($first2str === 'UC') {
            $this->originalPlaylistId = 'UU' . substr($originalPlaylistId, 2);
        }
    }

    private function getPlaylistSnippet($playlistId)
    {
        return $this->youtube->playlists->listPlaylists('snippet', ['id' => $playlistId])->getItems()[0]->getSnippet();
    }

    private function setTitle($title)
    {
        $this->title = $title;
    }

    private function setDescription($description)
    {
        $this->description = $description;
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

    public function work()
    {
        if ($this->client->getAccessToken()) {
            try {
                $playlistSnippet = $this->getPlaylistSnippet($this->originalPlaylistId);
                $this->setTitle($playlistSnippet->title);
                $this->setDescription($playlistSnippet->description);
                $this->setVideos($this->sortVideos($this->makeVideoInfoCollection($this->originalPlaylistId)));
                $this->makeNewPlaylist($this->title, $this->description);

                session_destroy();
            } catch (\Google_Service_Exception $e) {
                $this->html = $this->invalidId($e);
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

    private function makeVideoInfoCollection($playlistId)
    {
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

        if ($this->sortMode === 'oldest') {
            $publishedAts = array_column($videos, 'publishedAt');

            array_multisort($videos, $publishedAts);
        }

        return $videos;
    }

    private function makeNewPlaylist($title, $description)
    {
        $playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
        $playlistSnippet->setTitle($title);
        $playlistSnippet->setDescription($description);
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

    private function invalidId($e)
    {
        $error = json_decode($e->getMessage(), true)['error'];

        if ($error['code'] === 404 && $error['errors'][0]['reason'] === 'playlistNotFound') {
            $message = $this->originalPlaylistId . ' URLかIDが間違っています。';
        } else {
            $message = 'エラー';
        }

        return "<div class=\"h4\">$message</div>";
    }
}