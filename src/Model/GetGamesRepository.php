<?php

declare(strict_types=1);

namespace SallePW\SlimApp\Model;

// The base Component interface defines operations that can be altered by decorators

interface GetGamesRepository{

    public function GetDeals(): array;
}



