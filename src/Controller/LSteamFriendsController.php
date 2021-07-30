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
use Ramsey\Uuid\Uuid;

final class LSteamFriendsController
{
    private Twig $twig;
    private Messages $flash;

    public function __construct(Twig $twig, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->userRepository = $userRepository;
    }

    public function showLSteamFriends(Request $request, Response $response)
    {

        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            $acceptedFriends = [];
            $acceptedDates = [];

            $user = $this->userRepository->getUser($_SESSION['id']);

            $friends = $this->userRepository->getMyFriendsRequest($_SESSION['id']);

            foreach ($friends as $key => $friend) {

                if (!strcmp($friend['status'], 'accepted')) {

                    $acceptedFriends[] = $friend['username'];
                    $acceptedDates[] = $friend['accept_date'];

                }

            }

            return $this->twig->render(
                $response,
                'lsteamfriends.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'acceptedFriends' => $acceptedFriends,
                    'acceptedDates' => $acceptedDates,
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

    public function showFriendRequests(Request $request, Response $response)
    {

        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            $pendingFriends = [];
            $requestedFriends = [];
            $requestedIDs = [];

            $user = $this->userRepository->getUser($_SESSION['id']);

            $friends = $this->userRepository->getMyFriendsRequest($_SESSION['id']);

            foreach ($friends as $key => $friend) {

                if (!strcmp($friend['status'], 'pending')) {

                    $pendingFriends[] = $friend['username'];

                }

                if (!strcmp($friend['status'], 'requested')) {

                    $requestedFriends[] = $friend['username'];
                    $requestedIDs[] = $friend['uniqueID'];

                }

            }

            return $this->twig->render(
                $response,
                'lsteamrequests.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'pendingFriends' => $pendingFriends,
                    'requestedFriends' => $requestedFriends,
                    'requestedIDs' => $requestedIDs,
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

    public function showSendRequestForm(Request $request, Response $response)
    {

        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            return $this->twig->render(
                $response,
                'lsteamsend.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
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

    public function sendRequestAction(Request $request, Response $response)
    {

        $picture = $this->showPicture();

        if ($_SESSION['logged']) {

            // This method decodes the received json
            $data = $request->getParsedBody();
            $information = $this->validate($data);

            //Check if there is any error
            if (count($information) > 0) {

                $information['send_request'] = '[SEND REQUEST] failed.';

            } else {

                $senderUser = $this->userRepository->getUser($_SESSION['id']);
                $senderUsername = $senderUser->username();
                $idSender = $_SESSION['id'];
                $idReceiver = $this->userRepository->getUserID($data['username']);
                $uuid = Uuid::uuid4();
                $date = new DateTime();
                $date = $date->format('Y-m-d H:i:s');
                $information['send_request'] = '[SEND REQUEST] you have sent a friend request to ' . $data['username'] . ' successfully.';
                $this->userRepository->sendFriendRequest($idSender, $idReceiver, $senderUsername, $data['username'], strval($uuid), $date, 'pending', 'requested');

            }

            return $this->twig->render(
                $response,
                'lsteamsend.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'information' => $information,
                    'picture' => $picture,
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

    public function acceptFriendAction(Request $request, Response $response)
    {

        $information = [];
        $picture = $this->showPicture();
        $requestID = $request->getAttribute('requestId');

        if ($_SESSION['logged']) {

            if ($this->userRepository->acceptFriendRequest($_SESSION['id'], $requestID)) {

                $information[] = '[ERROR] you can not accept a friend request that not belong to your user.';

            }

            $acceptedFriends = [];
            $acceptedDates = [];

            $friends = $this->userRepository->getMyFriendsRequest($_SESSION['id']);

            foreach ($friends as $key => $friend) {

                if (!strcmp($friend['status'], 'accepted')) {

                    $acceptedFriends[] = $friend['username'];
                    $acceptedDates[] = $friend['accept_date'];

                }

            }

            return $this->twig->render(
                $response,
                'lsteamfriends.twig',
                [
                    'sessionStatus' => $_SESSION['logged'],
                    'picture' => $picture,
                    'acceptedFriends' => $acceptedFriends,
                    'acceptedDates' => $acceptedDates,
                    'information' => $information,
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

    private function validate(array $data): array
    {
        $information = [];
        $errors = [];
        $username = $data['username'];
        $errors['username_empty'] = false;
        $errors['same_user'] = false;
        $errors['deactived_user'] = false;
        $errors['requested_or_accepted_user'] = false;
        $senderUser = $this->userRepository->getUser($_SESSION['id']);
        $senderUsername = $senderUser->username();

        //Check if username contains something
        if (empty($username)) $errors['username_empty'] = true;
        if (!empty($username) && !strcmp($username, $senderUsername)) $errors['same_user'] = true;
        if (!empty($username) && $this->userRepository->userExists($username)) $errors['deactived_user'] = true;
        if (!empty($username) && $this->userRepository->notFriend($_SESSION['id'], $username)) $errors['requested_or_accepted_user'] = true;

        if ($errors['username_empty']) $information['username_empty'] = '[ERROR] The username cannot be empty.';
        if ($errors['same_user']) $information['same_user'] = '[ERROR] you can not send a friend request to yourself.';
        if ($errors['deactived_user']) $information['deactived_user'] = '[ERROR] user does not exist or his/her account is deactived.';
        if ($errors['requested_or_accepted_user']) $information['requested_or_accepted_user'] = '[ERROR] user who you want to be friend of is already a friend or a requested one.';

        return $information;
    }

}