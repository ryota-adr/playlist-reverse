<?php
require_once('vendor/autoload.php');
use App\Initializer;
use App\Playlist;

session_start();

if (isset($_GET['playlistId'])) {
    $_SESSION['playlistId'] = $_GET['playlistId'];
}

if (isset($_SESSION['playlistId'])) {
    $initializer = new Initializer(__DIR__);
    $initializer->init();

    $playlist = new Playlist($_SESSION['playlistId'], youtubeFactory(CLIENT_ID, CLIENT_SECRET, SCOPE, REDIRECT));

    $htmlBody = $playlist->getHtml();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog-Laravel5.8</title>
    <link href="/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-nav text-center">
                <a href="/" class="navbar-brand">
                    Playlist Sorter
                </a>
            </div>
        </div>
    </nav>
    <main class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="mb-3">
                        <form action="/" method="GET">
                            <input type="text" name="playlistId" class="form-control" placeholder="Playlist ID" value="<?php
                            if (isset($_GET['playlistId'])) {
                                $playlistId = $_GET['playlistId'];
                            } else if (isset($_SESSION['playlistId'])) {
                                $playlistId = $_SESSION['playlistId'];
                            } else {
                                $playlistId = '';
                            }
                            echo $playlistId;
                            ?>">
                        </form>
                    </div>
                    <div>
                        <?php
                        if (isset($htmlBody)) { echo $htmlBody; }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>