<?php

namespace ojy\npc\event;

use pocketmine\entity\Entity;
use pocketmine\event\Event;
use pocketmine\Player;

class PlayerUseItemOnEntityEvent extends Event
{

    /** @var Player */
    protected $player;

    /** @var Entity */
    protected $entity;

    public function __construct(Player $player, Entity $entity)
    {
        $this->player = $player;
        $this->entity = $entity;
    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }
}