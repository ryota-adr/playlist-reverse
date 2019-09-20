<?php

namespace App;

class PlaylistSorter
{
    const maxResults = 50;
    const maxItems = 200;

    private $title;
    private $description;
    private $originalPlaylistId;
    private $videos = [];
    private $newPlaylistIds = [];
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
            
            header('Location: ' . REDIRECT);
            exit();
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
                $this->setVideos(
                    $this->chunkVideos(
                        $this->sortVideos(
                            $this->makeVideoInfoCollection($this->originalPlaylistId)
                        ), static::maxItems
                    )
                );

                $this->newPlaylistIds = $this->insertNewPlaylist(
                    $this->title, $this->description);
                
                $this->insertVideos($this->newPlaylistIds, $this->videos);

                $count = count($this->newPlaylistIds);
                if ($count === 1) {
                    $playlistId = $this->newPlaylistIds[0];
                    $this->html =<<<END
<div class="h5 text-center">
    <a href="https://www.youtube.com/playlist?list=$playlistId">
        新しいプレイリスト
    </a>
</div>
END;
                } else {
                    $atags = '';
                    for($i = 0; $i < $count; $i++) {
                        $atags .= '<a href="https://www.youtube.com/playlist?list=' . 
                            $this->newPlaylistIds[$i] . '">' . ($i + 1) . ' </a>';
                    }

                    $this->html =<<<END
<div class="h5 text-center">
    <span>新しいプレイリスト </span>$atags
</div>
END;
                }
            } catch (\Google_Service_Exception $e) {
                $this->html = $this->errorMessage($e);
            } catch (\Google_Exception $e) {
                $this->html = sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));
            }

            session_destroy();
        } else {
            $state = mt_rand();
            $this->client->setState($state);
            $_SESSION['state'] = $state;

            $authUrl = $this->client->createAuthUrl();
            $this->html = <<<END
<div class="h5 text-center"><a href="$authUrl">ログイン</a>が必要です。</div>
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
                'id' => $snippet->getResourceId()->getVideoId()
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

    private function chunkVideos($videos, $size)
    {
        return array_chunk($videos, $size);
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

    private function insertNewPlaylist($title, $description)
    {
        $count = count($this->videos);
        $newPlaylistIds = [];

        for($i = 0; $i < $count; $i++) {
            $playlistSnippet = new \Google_Service_YouTube_PlaylistSnippet();
            $playlistSnippet->setTitle($title);
            $playlistSnippet->setDescription($description);

            $playlistStatus = new \Google_Service_YouTube_PlaylistStatus();
            $playlistStatus->setPrivacyStatus($this->privacyStatus);

            $youTubePlaylist = new \Google_Service_YouTube_Playlist();
            $youTubePlaylist->setSnippet($playlistSnippet);
            $youTubePlaylist->setStatus($playlistStatus);

            array_push($newPlaylistIds, $this->youtube->playlists->insert('snippet,status', $youTubePlaylist, [])['id']);
        }

        return $newPlaylistIds;
    }

    public function insertVideos($playlistIds, $videos)
    {
        $count = count($playlistIds);
        for($i = 0; $i < $count; $i++) {
            foreach($videos[$i] as $video) {
                $resourceId = new \Google_Service_YouTube_ResourceId();
                $resourceId->setVideoId($video['id']);
                $resourceId->setKind('youtube#video');

                $playlistItemSnippet = new \Google_Service_YouTube_PlaylistItemSnippet();
                $playlistItemSnippet->setTitle($count > 1 ? 
                    $video['title'] . '-' . ($i + 1) :
                    $video['title']);
                $playlistItemSnippet->setPlaylistId($playlistIds[$i]);
                $playlistItemSnippet->setResourceId($resourceId);

                $playlistItem = new \Google_Service_YouTube_PlaylistItem();
                $playlistItem->setSnippet($playlistItemSnippet);
                $playlistItemResponse = $this->youtube->playlistItems->insert(
                'snippet,contentDetails', $playlistItem, []);
            }
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

        if (APP_ENV === 'local') {
            $message = $e->getMessage();
        } else if ($error['code'] === 404 && $error['errors'][0]['reason'] === 'playlistNotFound') {
            $message = $this->originalPlaylistId . ' URLかIDが間違っています。';
        } else if ($error['code'] === 403 && $error['errors'][0]['reason'] === 'exceededRateLimit') {
            $message = 'API制限です。時間をおいて利用してください。';
        }

        return "<div class=\"h4\">$message</div>";
    }
}