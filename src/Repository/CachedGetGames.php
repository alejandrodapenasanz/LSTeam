<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\GetGamesRepository;


class CachedGetGames extends DecoratorGamesAPI{

    private const FILE_DIR ="../cache/deals.txt";

   
    public function GetDeals():array
    {

        if (file_exists(self::FILE_DIR)){
            return json_decode(file_get_contents(self::FILE_DIR));
        } else {
        
            return $this->NewFileDeals();
        }
    }
    private function NewFileDeals(): array {

        $file = fopen(self::FILE_DIR,"w");

//aquí es donde llamamos al objeto envuelto

        $deals = $this->decoratee->GetDeals();

        if ($file == false)return $deals;

        //debemos convertir del array al fichero
        $deals_encoded = json_encode($deals);
        file_put_contents(self::FILE_DIR, $deals_encoded);
        
        return $deals;
    }

   
}
