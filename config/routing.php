<?php
declare(strict_types=1);

use SallePW\SlimApp\Middleware\StartSessionMiddleware;
use SallePW\SlimApp\Controller\RegisterController;
use SallePW\SlimApp\Controller\HomeController;
use SallePW\SlimApp\Controller\LoginController;
use SallePW\SlimApp\Controller\ProfileController;
use SallePW\SlimApp\Controller\WalletController;
use SallePW\SlimApp\Controller\StoreController;
use SallePW\SlimApp\Controller\WishListController;
use SallePW\SlimApp\Controller\LSteamFriendsController;

//MIDDLEWARE
$app->add(StartSessionMiddleware::class);

//HOME
$app->get('/', HomeController::class . ':apply')->setName('home');

//REGISTER
$app->get('/register', RegisterController::class . ":showRegisterFormAction")->setName('register');

$app->post('/register', RegisterController::class . ":registerAction")->setName('register');

$app->get('/activate', RegisterController::class . ":sendRegisterComplete")->setName('register');

//LOGIN
$app->get('/login', LoginController::class . ":showLoginFormAction")->setName('login');

$app->post('/login', LoginController::class . ":loginAction")->setName('login');

$app->post('/logout', LoginController::class . ":logoutAction")->setName('logout');

//PROFILE
$app->get('/profile', ProfileController::class . ":showProfileAction")->setName('profile');

$app->post('/profile', ProfileController::class . ":uploadFileAction")->setName('profile');

$app->get('/profile/changePassword', ProfileController::class . ":showChangePasswordAction")->setName('profile/changePassword');

$app->post('/profile/changePassword', ProfileController::class . ":changePasswordAction")->setName('profile/changePassword');

//WALLET
$app->get('/user/wallet', WalletController::class . ":showWalletAction")->setName('wallet');

$app->post('/user/wallet', WalletController::class . ":addWalletAction")->setName('wallet');

//STORE
$app->get('/store', StoreController::class . ":showStoreAction")->setName('store');

$app->post('/store/buy/{gameID}', StoreController::class . ":buyGame")->setName('buy');

$app->get('/user/myGames', StoreController::class . ":showMyGames")->setName('store');

//WISHLIST
$app->get('/user/wishlist', WishListController::class . ":showWishList")->setName('wishlist');

$app->get('/user/wishlist/{gameId}', WishListController::class . ":showWishList")->setName('wishlistGameID');

$app->post('/user/wishlist/{gameId}', WishListController::class . ":saveGameID")->setName('wishlistPost');

$app->delete('/user/wishlist/{gameId}', WishListController::class . ":deleteFromWishlist")->setName('wishlistDelete');

//LSTEAM FRIENDS
$app->get('/user/friends', LSteamFriendsController::class . ":showLSteamFriends")->setName('friends');

$app->get('/user/friendRequests', LSteamFriendsController::class . ":showFriendRequests")->setName('friendsRequest');

$app->get('/user/friendRequests/send', LSteamFriendsController::class . ":showSendRequestForm")->setName('sendRequest');

$app->post('/user/friendRequests/send', LSteamFriendsController::class . ":sendRequestAction")->setName('sendRequest');

$app->post('/user/friendRequests/accept/{requestId}', LSteamFriendsController::class . ":acceptFriendAction")->setName('acceptRequest');