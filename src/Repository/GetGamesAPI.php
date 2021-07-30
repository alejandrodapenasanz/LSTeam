<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use PDO;
use DateTime;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\GetGamesRepository;
use GuzzleHttp\Client;



// GetGamesAPI es  la class concreta que implementa GetGamesRepository

 class GetGamesAPI implements GetGamesRepository{

    public function __construct()
    {}

    public function GetDeals():array {

    $client = new Client([
        // Base URI is used with relative requests
        'base_uri' => 'https://www.cheapshark.com/api/1.0/',
    ]);
    $responseGuzzle = $client->request('GET', 'deals');
    $games = json_decode($responseGuzzle->getBody()->getContents());
    return $games;
    }
}



