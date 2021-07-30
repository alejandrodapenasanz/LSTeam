<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

use Cassandra\Date;
use DateTime;

final class User
{
    private int $id;
    private string $username;
    private string $email;
    private string $password;
    private string $repeatPassword;
    private string $birthday;
    private string $phone;
    private string $status;
    private string $money;
    private string $picture;
    private string $buyList;
    private string $wishList;
    private string $friends;

    public function __construct(
        string $username,
        string $email,
        string $password,
        string $repeatPassword,
        string $birthday,
        string $phone,
        string $status,
        string $money,
        string $picture,
        string $buyList,
        string $wishList,
        string $friends
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->repeatPassword = $repeatPassword;
        $this->birthday = $birthday;
        $this->phone = $phone;
        $this->status = $status;
        $this->money  = $money;
        $this->picture = $picture;
        $this->buyList = $buyList;
        $this->wishList = $wishList;
        $this->friends = $friends;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function username(): string
    {
        return $this->username;
    }
    
    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function repeatPassword(): string
    {
        return $this->repeatPassword;
    }

    public function birthday(): string
    {
        return $this->birthday;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function money(): string
    {
        return $this->money;
        
    }

    public function picture(): string
    {
        return $this->picture;
    }
}