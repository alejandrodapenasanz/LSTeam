<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\GetGamesRepository;

// DecoratorGamesAPI es la classe base decoradora, que usa el mismo interfaz que los otras. 
// Y que tiene como objetivo definir el interfaz del objeto envolvente,  contiene un campo para almacenar el 
//objeto envuelto y la función en sí misma

class DecoratorGamesAPI implements GetGamesRepository{

protected $decoratee;

public function __construct( GetGamesAPI $decoratee )
{
    $this->decoratee = $decoratee;
}

public function GetDeals(): array {

    return $this->decoratee->GetDeals();
}

}
