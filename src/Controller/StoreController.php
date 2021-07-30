<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateTime;
use Exception;
use SallePW\SlimApp\Model\Game;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Model\GetGamesRepository;
use SallePW\SlimApp\Repository\GetGamesAPI;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Client;
use function Couchbase\defaultDecoder;

final class StoreController
{

    private Twig $twig;
    private UserRepository $userRepository;
    private GetGamesRepository $getGames;

    public function __construct(Twig $twig, UserRepository $userRepository, GetGamesRepository $getGames)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
        $this->getGames = $getGames;
   }

    public function buyGame(Request $request, Response $response): Response
    {

        $buyMessage = [];

        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);
            $picture = $this->showPicture();
            $gameID = $request->getAttribute("gameID");
            $userMoney = $user->money();
            $wishGames =  $this->userRepository->getMyWishes($_SESSION['id']);
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://www.cheapshark.com/api/1.0/',
            ]);
            $responseGuzzle = $client->request('GET', 'deals');
            $games = json_decode($responseGuzzle->getBody()->getContents());
            foreach ($games as $i){
                if($gameID == $i->gameID){//Find the game
                    if($userMoney >= $i->normalPrice){
                        $this->userRepository->buyGame($_SESSION['id'], $gameID);
                        $addMoneyWallet = $userMoney - $i->normalPrice;
                        $this->userRepository->addWalletMoney($_SESSION['id'], strval($addMoneyWallet) );
                        $buyMessage['message'] = '[Succes] New game added!';

                        //Si el joc esta a la wishlist el borrem de la wishlist
                        foreach ($wishGames as $i){
                            if($i == $gameID){
                                $this->userRepository->deleteWish($_SESSION['id'],$gameID); //Borrar joc BBDD wishlist
                            }
                        }
                    }else{
                        $buyMessage['message'] = '[ERROR] Not enough credit in your wallet';
                    }
                }

            }

            $gameList = array();
            $myGames = $this->userRepository->getMyGames($_SESSION['id']); //ID dels jocs que ha comprat
            foreach ($myGames as $i){
                foreach ($games as $z){
                    if($i == $z->gameID){ //Per cada id de joc que te guardat l'usuari li creem el objecte joc
                        $game = new Game();
                        $game->title =  $z->title;
                        $game->normalPrice =  (float)$z->normalPrice;
                        $game->thumb = $z->thumb;
                        array_push($gameList,$game);
                    }
                }
            }


            return $this->twig->render(
                $response,
                'buy.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'myGames' => $gameList,
                    'information' => $buyMessage,
                ]
            );

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'buy.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'information' => $information,
                    'picture' => $picture,
                ]
            );

        }
        
    }

    public function showStoreAction(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $picture = $this->showPicture();

        if ($_SESSION['logged']) {
           
                    
            $games = $this->getGames->GetDeals();
                       
            
            /*$client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://www.cheapshark.com/api/1.0/',
            ]);
            $responseGuzzle = $client->request('GET', 'deals');
            $games = json_decode($responseGuzzle->getBody()->getContents());
            //$a= gettype($games);
            //var_dump($a);
            //comprobado es un array*/

            return $this->twig->render(
                $response,
                'store.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'games' => $games,

                ]);

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                    'information' => $information,
                    'picture' => $picture,
                ]
            );

        }
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

    public function showMyGames(Request $request, Response $response): Response
    {

        $picture = $this->showPicture();
        
        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);
            $myGames = $this->userRepository->getMyGames($_SESSION['id']);

            return $this->twig->render(
                $response,
                'buy.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'myGames' => $myGames,
                ]
            );

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'buy.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'information' => $information,
                    'picture' => $picture,
                ]
            );

        }
        
    }

}