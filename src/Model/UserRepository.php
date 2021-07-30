<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

interface UserRepository
{
    public function save(User $user): void;
    public function login(User $user): int;
    public function userExists(string $username): int;
    public function register(User $user): int;
    public function addMoney(int $id): void;
    public function getID(User $user): int;
    public function getUserID(string $username): int;
    public function checkDeactived(int $id) : int;
    public function getEmail(int $id): string;
    public function addPicture(int $id, string $image): void;
    public function addPhone(int $id, string $phone): void;
    public function addWalletMoney(int $id, string $addedMoney): void;
    public function changePassword(int $id, string $password): void;
    public function buyGame(int $id, string $gameID): void;
    public function getMyGames(int $id): array;
    public function addWish(int $id, string $gameID): void;
    public function deleteWish(int $id, string $gameID): void;
    public function getMyWishes(int $id): array;
    public function sendFriendRequest(int $idSender, int $idReceiver, string $senderUsername, string $receiverUsername, string $uuid, string $date, string $statusSender, string $statusReceiver): void;
    public function getMyFriendsRequest(int $id): array;
    public function acceptFriendRequest(int $senderID, string $requestID): int;
    public function notFriend(int $id, string $usernameReceiver): int;
}