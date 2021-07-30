<?php
declare(strict_types=1);

use DI\Container;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use SallePW\SlimApp\Controller\HomeController;
use SallePW\SlimApp\Controller\RegisterController;
use SallePW\SlimApp\Controller\LoginController;
use SallePW\SlimApp\Controller\ProfileController;
use SallePW\SlimApp\Controller\WalletController;
use SallePW\SlimApp\Controller\StoreController;
use SallePW\SlimApp\Controller\WishListController;
use SallePW\SlimApp\Controller\LSteamFriendsController;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\GetGamesRepository;
use SallePW\SlimApp\Repository\MySQLUserRepository;
use SallePW\SlimApp\Repository\PDOSingleton;
use SallePW\SlimApp\Repository\GetGamesAPI;
use SallePW\SlimApp\Repository\CachedGetGames;
use SallePW\SlimApp\Repository\DecoratorGamesAPI;


$container = new Container();

//View key for container
$container->set(
    'view',
    function () {
        return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
    }
);

//DB key for container
$container->set('db', function () {
    return PDOSingleton::getInstance(
        $_ENV['MYSQL_ROOT_USER'],
        $_ENV['MYSQL_ROOT_PASSWORD'],
        $_ENV['MYSQL_HOST'],
        $_ENV['MYSQL_PORT'],
        $_ENV['MYSQL_DATABASE']
    );
});

//Linking HomeController with View key
$container->set(
    HomeController::class,
    function (Container $c) {
        $controller = new HomeController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking UserRepository with DB key
$container->set(
    UserRepository::class,
    function (Container $container) {
        return new MySQLUserRepository($container->get('db'));
    }
);

$container->set(
    GetGamesRepository::class,
    function (Container $container) {
        return new CachedGetGames($container->get(GetGamesAPI::class));
    }
);

$container->set(
    GetGamesAPI::class,
    function (Container $container) {
        return new GetGamesAPI();
    }
);



//Linking RegisterController with View and DB key
$container->set(
    RegisterController::class,
    function (Container $c) {
        $controller = new RegisterController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking LoginController with View and DB key
$container->set(
    LoginController::class,
    function (Container $c) {
        $controller = new LoginController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking ProfileController with View and DB key
$container->set(
    ProfileController::class,
    function (Container $c) {
        $controller = new ProfileController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking WalletController with View and DB key
$container->set(
    WalletController::class,
    function (Container $c) {
        $controller = new WalletController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking StoreController with View and DB key
$container->set(
    StoreController::class,
    function (Container $c) {
    $controller = new StoreController($c->get("view"), $c->get(UserRepository::class), $c->get(GetGamesRepository::class));
        return $controller;
    }
);

//Linking WishListController with View and DB key
$container->set(
    WishListController::class,
    function (Container $c) {
        $controller = new WishListController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }
);

//Linking LSteamFriendsController with View and DB key
$container->set(
    LSteamFriendsController::class,
    function (Container $c) {
        $controller = new LSteamFriendsController($c->get("view"), $c->get(UserRepository::class));
        return $controller;
    }

);
