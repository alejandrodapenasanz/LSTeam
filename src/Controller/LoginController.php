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
use function Couchbase\defaultDecoder;

final class LoginController
{

    private Twig $twig;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function showLoginFormAction(Request $request, Response $response): Response
    {

        $picture = $this->showPicture();

        return $this->twig->render(
            $response,
            'login.twig',
            [
                'sessionStatus' => $_SESSION['logged'],
                'picture' => $picture,
            ]);

    }

    public function loginAction(Request $request, Response $response): Response
    {
        // This method decodes the received json
        $data = $request->getParsedBody();
        $comprobation = -1;
        $information = $this->validate($data);

        //Check if there is any error
        if (count($information) > 0) {

            $information['login_result'] = '[LOGIN] failed';

        } else {
            $date = new DateTime();
            $birthday = $date->format('Y-m-d H:i:s');
            //Create a sample user with the username/email and password fields filled
            $user = new User(
                $data['username'],
                $data['username'],
                md5($data['password']),
                md5($data['password']),
                $birthday,
                "666333111",
                'deactived',
                '0',
                'default',
                '',
                '',
                ''
            );

            //Check if the user exists and is actived
            $comprobation = $this->userRepository->login($user);

            if ($comprobation === 0) $information['login_result'] = '[LOGIN] success.';
            if ($comprobation === 1) $information['login_result'] = '[LOGIN] user exists but deactived.';
            if ($comprobation === 2) $information['login_result'] = '[LOGIN] incorrect password.';
            if ($comprobation === 3) $information['login_result'] = '[LOGIN] user does not exist.';

        }

        //Redirect in case of success
        if ($comprobation === 0) {

            $_SESSION['logged'] = true;
            $_SESSION['id'] = $this->userRepository->getID($user);
            $picture = $this->showPicture();

            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://www.cheapshark.com/api/1.0/',
            ]);
            $responseGuzzle = $client->request('GET', 'deals');
            $games = json_decode($responseGuzzle->getBody()->getContents());

            return $this->twig->render(
                $response,
                'store.twig',
                [
                   'sessionStatus' => $_SESSION['logged'],
                   'picture' => $picture,
                   'games' => $games,
                ]
            );
            
        } else {

            //Inform about errors in case of fail
            $_SESSION['id'] = 0;
            $picture = $this->showPicture();

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'information' => $information,
                   'sessionStatus' => $_SESSION['logged'],
                   'picture' => $picture,
                ]
            );

        }
        
    }

    public function logoutAction(Request $request, Response $response): Response
    {

        $_SESSION['logged'] = false;
        $_SESSION['id'] = 0;
    
    }

    private function validate(array $data): array
    {
        $information = [];
        $errors = [];
        $username = $data['username'];
        $password = $data['password'];
        $errors['username_empty'] = false;
        $errors['password_empty'] = false;

        //Check if username contains something
        if (empty($username)) $errors['username_empty'] = true;
            
        //Check if password contains something
        if (empty($data['password'])) $errors['password_empty'] = true;

        if ($errors['username_empty']) $information['username_empty'] = '[ERROR] The username or email cannot be empty';
        if ($errors['password_empty']) $information['password_empty'] = '[ERROR] The password cannot be empty';

        return $information;
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