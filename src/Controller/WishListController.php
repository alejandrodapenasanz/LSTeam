<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateTime;
use Exception;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;
use SallePW\SlimApp\Model\Game;
final class WishListController
{
    private Twig $twig;
    private Messages $flash;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function saveGameID(Request $request, Response $response){
        $actual_link = "$_SERVER[REQUEST_URI]";
        $urlArray = explode("/",$actual_link);
        $gameID = $urlArray[3]; //Agafem el ID del joc
        //userID -> $userDB['id']
        $myGames = $this->userRepository->getMyWishes($_SESSION['id']);
        $saveGame = true;
        //Comprovamos que no tengamos el juego ya añadido en la Wishlist
        foreach ($myGames as $i){
            if($i == $gameID){
               $saveGame = false;
               break;
            }
        }
        if($saveGame){
            $saveGame = false;
            $this->userRepository->addWish($_SESSION['id'], $gameID );
        }

        return $response->withHeader('Location','/user/wishlist')->withStatus(200);

    }
    public function deleteFromWishlist(Request $request, Response $response)
    {
        $picture = $this->showPicture();
        $gameID =$requestID = $request->getAttribute('gameId');
        $this->userRepository->deleteWish($_SESSION['id'],$gameID);
        //redirect

    }

    public function showWishList(Request $request, Response $response)
    {
        $numJuegos=0;
        $picture = $this->showPicture();
        $myGames = $this->userRepository->getMyWishes($_SESSION['id']);

        $gameList = array();
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://www.cheapshark.com/api/1.0/',
        ]);
        $responseGuzzle = $client->request('GET', 'deals');
        $games = json_decode($responseGuzzle->getBody()->getContents());
        foreach ($myGames as $i){
            foreach ($games as $z){
                if($i == $z->gameID){ //Per cada id de joc que te guardat l'usuari li creem el objecte joc
                    $game = new Game();
                    $game->title =  $z->title;
                    $game->normalPrice =  (float)$z->normalPrice;
                    $game->thumb = $z->thumb;
                    if(strcmp("NULL", $z->gameID) != 0){
                        $numJuegos = $numJuegos +1;
                    }
                    $game->gameID= $z->gameID;
                    array_push($gameList,$game);
                    break;
                }
            }
        }

        return $this->twig->render(
            $response,
            'wishlist.twig',
            [
                'sessionStatus' => $_SESSION['logged'],
                'myGames' => $gameList,
                'picture' => $picture,
                'numJuegos' => $numJuegos,
            ]
        );
    }

    private function showPicture(): string 
    {

        $picture = '';

        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);

            //Checking actual picture
            if (!strcmp('default', $user->picture())) {
                $picture = 'assets/img/default_user.png';
            } else {
                $picture = 'uploads/' . $user->picture();
            }

        }

        return $picture;

    }
}