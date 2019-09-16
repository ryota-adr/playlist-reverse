<?php

namespace App;
use Dotenv\Dotenv;

class Initializer
{
    private $root;

    public function __construct($dir)
    {
        $this->root = $dir;
    }

    public function init()
    {
        $this->loadDotenv();
        $this->setClientId();
        $this->setClientSecret();
        $this->setRedirect();
    }

    private function loadDotenv()
    {
        $this->dotenv = Dotenv::create($this->root);
        $this->dotenv->load();
    }

    private function setClientId()
    {
        define('CLIENT_ID', $_ENV['OAUTH2_CLIENT_ID']);
    }

    private function setClientSecret()
    {
        define('CLIENT_SECRET', $_ENV['OAUTH2_CLIENT_SECRET']);
    }

    private function setRedirect()
    {
        define('REDIRECT', $_ENV['REDIRECT']);
    }
}