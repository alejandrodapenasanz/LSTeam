<?php
declare(strict_types=1);

/* 
CREATE TABLE `user` (
  `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `repeatPassword` VARCHAR(255) NOT NULL DEFAULT '',
  `birthday` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(255) NOT NULL DEFAULT '',
  `status` VARCHAR(255) NOT NULL DEFAULT '',
  `money` VARCHAR(255) NOT NULL DEFAULT '',
  `picture` VARCHAR(255) NOT NULL DEFAULT '',
  `buyList` JSON NOT NULL,
  `wishList` JSON NOT NULL,
  `friends` JSON NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

namespace SallePW\SlimApp\Repository;

use PDO;
use DateTime;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;

final class MySQLUserRepository implements UserRepository
{
    private const DATE_FORMAT = 'd-m-Y';

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }

    public function save(User $user): void
    {
        $query = <<<'QUERY'
        INSERT INTO user(username, email, password, repeatPassword, birthday, phone, status, money, picture, buyList, wishList, friends)
        VALUES(:username, :email, :password, :repeatPassword, :birthday, :phone, :status, :money, :picture, :buyList, :wishList, :friends)
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $username = $user->username();
        $email = $user->email();
        $password = $user->password();
        $repeatPassword = $user->repeatPassword();
        $birthday = $user->birthday();
        $phone = $user->phone();
        $status = 'deactived';
        $money = $user->money();
        $picture = $user->picture();
        $buyList =   '{
                    "start": "root"
                    }';
        $wishList =   '{
                    "start": "root"
                    }';
        $friends =   '{
                    "start": {"status": "root", "uniqueID": "root", "username": "root", "accept_date": "root"}
                    }';

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('repeatPassword', $repeatPassword, PDO::PARAM_STR);
        $statement->bindParam('birthday', $birthday, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('status', $status, PDO::PARAM_STR);
        $statement->bindParam('money', $money, PDO::PARAM_STR);
        $statement->bindParam('picture', $picture, PDO::PARAM_STR);
        $statement->bindParam('buyList', $buyList, PDO::FETCH_ASSOC);
        $statement->bindParam('wishList', $wishList, PDO::FETCH_ASSOC);
        $statement->bindParam('friends', $friends, PDO::FETCH_ASSOC);

        $statement->execute();
    }

    public function login(User $user): int
    { 

        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            //Username or email matches
            if (!strcmp($userDB['username'], $user->username()) || !strcmp($userDB['email'], $user->email())){

                //Password matches
                if (!strcmp($userDB['password'], $user->password())) {

                    //Actived
                    if (!strcmp($userDB['status'], 'actived')) {

                        return 0;

                    } else {

                        //Deactived
                        return 1;

                    }

                } else {

                    //Password doesnt match
                    return 2;

                }

            }
        
        }

        //Username or email doesnt exist
        return 3;

    }

    public function userExists(string $username): int
    { 

        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            //Username or email matches
            if (!strcmp($userDB['username'], $username) && !strcmp($userDB['status'], 'actived')) {

                return 0;

            }
        
        }

        return 1;

    }

    public function register(User $user): int
    {
        $email = $user->email();
        $user = $user->username();
        $nEmail = $this->database->connection()->query("SELECT COUNT(email) AS email_count FROM user WHERE email LIKE '$email'")->fetchColumn();
        if ($nEmail>=1){
            return 0; //Email already used
        }
        $nUser = $this->database->connection()->query("SELECT COUNT(username) AS username_count FROM user WHERE username LIKE '$user'")->fetchColumn();
        if ($nUser>=1){
            return 1; //Username already used
        }
        return 2; //No error-> Username and mail do not already exist

    }

    public function addMoney(int $id): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $this->database->connection()->query("UPDATE user SET money = '50.00' WHERE id = '$id'");
                $this->database->connection()->query("UPDATE user SET status = 'actived' WHERE id = '$id'");
            }
        
        }
    }

    public function getMoney(int $id): int
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                return $userDB['money'];
            }
        }
    }
    
    public function getID(User $user): int
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['username'] == $user->username()) {
                return (int)$userDB['id'];
            }
    
        }

        return 0;

    }

    public function checkDeactived(int $id) : int {

        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                
                //Actived
                if (!strcmp($userDB['status'], 'deactived')) {

                    return 0;

                }

            }
    
        }

        return 1;

    }

    public function getEmail(int $id): string
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                return $userDB['email'];
            }
    
        }

        return 'null';

    }

    public function getUser(int $id): User
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $user = new User(
                    $userDB['username'],
                    $userDB['email'],
                    $userDB['password'],
                    $userDB['repeatPassword'],
                    $userDB['birthday'],
                    $userDB['phone'],
                    $userDB['status'],
                    $userDB['money'],
                    $userDB['picture'],
                    $userDB['buyList'],
                    $userDB['wishList'],
                    $userDB['friends']
                );
            }
    
        }

        return $user;

    }

    public function getUserID(string $username): int
    {
        
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['username'] == $username) {

                return (int)$userDB['id'];
            
            }
    
        }

        return 0;

    }

    public function addPicture(int $id, string $image): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $this->database->connection()->query("UPDATE user SET picture = '$image' WHERE id = '$id'");
            }
        
        }
    }

    public function addPhone(int $id, string $phone): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $this->database->connection()->query("UPDATE user SET phone = '$phone' WHERE id = '$id'");
            }
        
        }
    }

    public function changePassword(int $id, string $password): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $this->database->connection()->query("UPDATE user SET password = '$password' WHERE id = '$id'");
                $this->database->connection()->query("UPDATE user SET repeatPassword = '$password' WHERE id = '$id'");
            }
        
        }
    }

    public function addWalletMoney(int $id, string $addedMoney): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();
        
        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {
                $this->database->connection()->query("UPDATE user SET money = '$addedMoney' WHERE id = '$id'");
            }
        
        }
    }

    public function buyGame(int $id, string $gameID): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                $actualGamesAssoc = json_decode($userDB['buyList'], true);
                $actualGamesQuantity = count($actualGamesAssoc);
                $actualGamesArray = explode('}', $userDB['buyList']);
                $actualGamesString = $actualGamesArray[0] . ', "gameid' . $actualGamesQuantity . '": "' . $gameID . '"}';

                $this->database->connection()->query("UPDATE user SET buyList = '$actualGamesString' WHERE id = '$id'");
            }
        
        }
    }

    public function getMyGames(int $id): array
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                return json_decode($userDB['buyList'], true);
                
            }
        
        }
    }

    public function addWish(int $id, string $gameID): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                $actualWishesAssoc = json_decode($userDB['wishList'], true);
                $actualWishesQuantity = count($actualWishesAssoc);
                $actualWishesArray = explode('}', $userDB['wishList']);
                $actualWishesString = $actualWishesArray[0] . ', "gameid' . $actualWishesQuantity . '": "' . $gameID . '"}';

                $this->database->connection()->query("UPDATE user SET wishList = '$actualWishesString' WHERE id = '$id'");
            }
        
        }
    }

    public function getMyWishes(int $id): array
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                return json_decode($userDB['wishList'], true);
                
            }
        
        }
    }

    public function sendFriendRequest(int $idSender, int $idReceiver, string $senderUsername, string $receiverUsername, string $uuid, string $date, string $statusSender, string $statusReceiver): void
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            //SENDER
            if ($userDB['id'] == $idSender) {

                $actualFriendsAssoc = json_decode($userDB['friends'], true);
                $actualFriendsQuantity = count($actualFriendsAssoc);
                $actualFriendsArray = explode("}", $userDB['friends']);
                $actualFriendsString = str_replace('}}', '}', $userDB['friends']);
                $actualFriendsString = $actualFriendsString . ', "friend' . $actualFriendsQuantity . '": {"status": "' . $statusSender . '", "uniqueID": "' . $uuid . '", "username": "' . $receiverUsername . '", "accept_date": "' . $date . '"}}';
                
                $this->database->connection()->query("UPDATE user SET friends = '$actualFriendsString' WHERE id = '$idSender'");
                
            }

            //RECEIVER
            if ($userDB['id'] == $idReceiver) {

                $actualFriendsAssoc = json_decode($userDB['friends'], true);
                $actualFriendsQuantity = count($actualFriendsAssoc);
                $actualFriendsArray = explode("}", $userDB['friends']);
                $actualFriendsString = str_replace('}}', '}', $userDB['friends']);
                $actualFriendsString = $actualFriendsString . ', "friend' . $actualFriendsQuantity . '": {"status": "' . $statusReceiver . '", "uniqueID": "' . $uuid . '", "username": "' . $senderUsername . '", "accept_date": "' . $date . '"}}';
                
                $this->database->connection()->query("UPDATE user SET friends = '$actualFriendsString' WHERE id = '$idReceiver'");

            }
        
        }

    }

    public function getMyFriendsRequest(int $id): array
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                return json_decode($userDB['friends'], true);
                
            }
        
        }
    }

    public function acceptFriendRequest(int $senderID, string $requestID): int
    {

        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();
        $senderUser = $this->getUser($senderID);
        $receiverID = 'null';
        $currentDate = new DateTime();
        $currentDate = $currentDate->format('Y-m-d H:i:s');
        $uniqueIDFound = 1;

        //LOGGED USER UPDATING
        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $senderID) {

                $friends = $this->getMyFriendsRequest($senderID);

                foreach ($friends as $key => $friend) {

                    if (!strcmp($friend['uniqueID'], $requestID)) {

                        $toBeEdited = '"status": "requested", "uniqueID": "' . $requestID . '", "username": "' . $friend['username'] . '", "accept_date": "' . $friend['accept_date'] . '"';
                        $toBeWritten = '"status": "accepted", "uniqueID": "' . $requestID . '", "username": "' . $friend['username'] . '", "accept_date": "' . $currentDate . '"';
                        $newFriendsString = str_replace($toBeEdited, $toBeWritten, $userDB['friends']);

                        $this->database->connection()->query("UPDATE user SET friends = '$newFriendsString' WHERE id = '$senderID'");

                        $receiverID = $this->getUserID($friend['username']);

                        $uniqueIDFound = 0;
                        
                    }
    
                }
                
            }
        
        }

        $uniqueIDFound = 1;

        //FOREIGN USER WHO IS BEING ACCEPTED AS FRIEND
        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $receiverID) {

                $friends = $this->getMyFriendsRequest($receiverID);

                foreach ($friends as $key => $friend) {

                    if (!strcmp($friend['uniqueID'], $requestID)) {

                        $toBeEdited = '"status": "pending", "uniqueID": "' . $requestID . '", "username": "' . $senderUser->username() . '", "accept_date": "' . $friend['accept_date'] . '"';
                        $toBeWritten = '"status": "accepted", "uniqueID": "' . $requestID . '", "username": "' . $senderUser->username() . '", "accept_date": "' . $currentDate . '"';
                        $newFriendsString = str_replace($toBeEdited, $toBeWritten, $userDB['friends']);

                        $this->database->connection()->query("UPDATE user SET friends = '$newFriendsString' WHERE id = '$receiverID'");

                        $uniqueIDFound = 0;
                        
                    }

                }
                
            }

        }

        return $uniqueIDFound;

    }

    public function notFriend(int $id, string $usernameReceiver): int
    {
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                $friends = $this->getMyFriendsRequest($id);

                foreach ($friends as $key => $friend) {

                    if (!strcmp($friend['username'], $usernameReceiver) && (!strcmp($friend['status'], 'accepted') || !strcmp($friend['status'], 'pending') || !strcmp($friend['status'], 'requested'))) {
    
                        return 1;
    
                    }
    
                }
                
            }
        
        }

        return 0;

    }
    public function deleteWish(int $id, string $gameID): void{
        $statement = $this->database->connection()->query('SELECT * FROM user ORDER BY id ASC');
        $users = $statement->fetchAll();

        foreach ($users as $key => $userDB) {

            if ($userDB['id'] == $id) {

                $actualWishesAssoc = json_decode($userDB['wishList'], true);
                $actualWishesQuantity = count($actualWishesAssoc);
                $actualWishesArray = explode('}', $userDB['wishList']);
                $gameString = $actualWishesArray[0] . ', "gameid' . $actualWishesQuantity . '": "' . $gameID . '"}';
                //$gameString = implode(",",$actualWishesArray);
                $gameString =str_replace($gameID, "NULL", $gameString);
            }

        }
        $this->database->connection()->query("UPDATE user SET wishList = '$gameString' WHERE id = '$id'");//Guardo actualWishes pero toco y modificio wishGamelis!!!-> guardar el array wishgamelist, que por algo lo toco

    }
}