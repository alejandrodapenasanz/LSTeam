<?php


namespace SallePW\SlimApp\Model;


class Game
{
    public string $title;
    public float $normalPrice;
    public string $thumb;
    public string $gameID;

    /**
     * @return string
     */
    public function getGameID(): string
    {
        return $this->gameID;
    }

    /**
     * @param string $gameID
     */
    public function setGameID(string $gameID): void
    {
        $this->gameID = $gameID;
    }

    /**
     * @return int
     */



    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getNormalPrice(): int
    {
        return $this->normalPrice;
    }

    /**
     * @return string
     */
    public function getThumb(): string
    {
        return $this->thumb;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param int $normalPrice
     */
    public function setNormalPrice(int $normalPrice): void
    {
        $this->normalPrice = $normalPrice;
    }

    /**
     * @param string $thumb
     */
    public function setThumb(string $thumb): void
    {
        $this->thumb = $thumb;
    }

}