<?php

function youtubeFactory($clientId, $clinetSecret, $scope, $redirect)
{
    $client = new Google_Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clinetSecret);
    $client->setScopes($scope);
    $client->setRedirectUri($redirect);
    
    $youtube = new Google_Service_YouTube($client);
    
    return $youtube;
}