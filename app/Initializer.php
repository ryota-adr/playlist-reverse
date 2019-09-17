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
        $this->defineConstants();
    }

    private function loadDotenv()
    {
        $this->dotenv = Dotenv::create($this->root);
        $this->dotenv->load();
    }

    private function defineConstants()
    {
        define('API_KEY', $_ENV['API_KEY']);
        define('CLIENT_ID', $_ENV['OAUTH2_CLIENT_ID']);
        define('CLIENT_SECRET', $_ENV['OAUTH2_CLIENT_SECRET']);
        define('SCOPE', $_ENV['SCOPE']);
        define('REDIRECT', $_ENV['REDIRECT']);
    }
}