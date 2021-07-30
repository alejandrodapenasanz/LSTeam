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
use Psr\Http\Message\UploadedWallet;
use Ramsey\Uuid\Uuid;

final class WalletController
{

    private Twig $twig;
    private UserRepository $userRepository;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function addWalletAction(Request $request, Response $response): Response
    {
        
        if ($_SESSION['logged']) {
            
            $data = $request->getParsedBody();
            $picture = $this->showPicture();
            $user = $this->userRepository->getUser($_SESSION['id']);
            $oldmoney = $user->money();
            $information = $this->validate($data);
            
            if (count($information) <= 0) {

                $floatOldmoney = (float)$oldmoney;
                $floatNewMoney = (float)$data['newMoney'];
                $addedMoney = $floatOldmoney + $floatNewMoney;
                $addedMoney = sprintf("%.2f", $addedMoney);
                $this->userRepository->addWalletMoney($_SESSION['id'], $addedMoney);
                $user = $this->userRepository->getUser($_SESSION['id']);
                $information['addedMoneyWallet'] = '[WALLET] Your money has been added to your wallet.';

            }

            return $this->twig->render(
                $response,
                'wallet.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'information' => $information,
                    'money' => $user->money(),
                ]
            );

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'sessionStatus' => $_SESSION['logged'],
                   'information' => $information,
                   'picture' => $picture,
                ]
            );

        }
    }
    
    public function showWalletAction(Request $request, Response $response): Response
    {

        $picture = $this->showPicture();
        
        if ($_SESSION['logged']) {

            $user = $this->userRepository->getUser($_SESSION['id']);

            return $this->twig->render(
                $response,
                'wallet.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'money' => $user->money(),
                ]
            );

        } else {

            $information['authentication'] = '[ERROR] user must be logged first.';

            return $this->twig->render(
                $response,
                'login.twig',
                [
                   'sessionStatus' => $_SESSION['logged'],
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

    //Validate function to check add wallet values are numbers and greater than 0
    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['newMoney'])) {
            $errors['notCorrect'] = '[ERROR] Value is not correct.';
            $errors['empty'] = '[ERROR] The money field cannot be empty.';
        }
          
        if (!is_numeric($data['newMoney']) || $data['newMoney'] <= 0) {
            $errors['notCorrect'] = '[ERROR] Value is not correct.';
            $errors['notNumeric'] = '[ERROR] The value field has to be a positive number.';
        }  
        return $errors;
    }

}