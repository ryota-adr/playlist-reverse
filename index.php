<?php
require_once('vendor/autoload.php');
require_once('google-api-php-client-2.4.0/vendor/autoload.php');

use App\Initializer;
use App\PlaylistSorter;

session_start();

$initializer = new Initializer(__DIR__);
$initializer->init();

if (isset($_GET['playlistId']) && isset($_GET['sortMode']) && isset($_GET['privacyStatus'])) {
    $_SESSION['playlistId'] = $_GET['playlistId'];
    $_SESSION['sortMode'] = $_GET['sortMode'];
    $_SESSION['privacyStatus'] = $_GET['privacyStatus'];
}

if (isset($_SESSION['playlistId'])) {
    $playlistSorter = new PlaylistSorter($_SESSION['playlistId'], $_SESSION['sortMode'], $_SESSION['privacyStatus'], youtubeFactory(CLIENT_ID, CLIENT_SECRET, SCOPE, REDIRECT));
    $playlistSorter->work();

    $htmlBody = $playlistSorter->getHtml();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Playlist Sorter</title>
    <link href="<?php echo (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . APP_HOST; ?>/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-nav text-center">
                <a href="/" class="navbar-brand h3">
                    Playlist Sorter
                </a>
            </div>
        </div>
    </nav>
    <main class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="mb-3">
                        <form action="<?php echo (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . APP_HOST; ?>" method="GET">
                            <div class="">
                                <div class="form-group">
                                    <input type="text" name="playlistId" class="form-control" placeholder="Youtube Playlist or Channel URL" value="<?php
                                    if (isset($_GET['playlistId'])) {
                                        $playlistId = $_GET['playlistId'];
                                    } else if (isset($_SESSION['playlistId'])) {
                                        $playlistId = $_SESSION['playlistId'];
                                    } else {
                                        $playlistId = '';
                                    }
                                    echo $playlistId;
                                    ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4 pr-0">
                                    <div class="form-group">
                                        <select name="sortMode" class="form-control">   
                                            <option value="popular">
                                                人気順
                                            </option>
                                            <option value="newest">
                                                新しい順
                                            </option>
                                            <option value="oldest" selected>
                                                古い順
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4 pr-0">
                                    <div class="form-group">
                                        <select name="privacyStatus" class="form-control">
                                            <option value="public">
                                                公開
                                            </option>
                                            <option value="unlisted">
                                                限定公開
                                            </option>
                                            <option value="private" selected>
                                                非公開
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <button type="submit" class="btn btn-primary form-control">
                                        作成
                                    </button>
                                </div>
                            </div>
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