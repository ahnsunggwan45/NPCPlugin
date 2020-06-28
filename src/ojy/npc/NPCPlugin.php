<?php

/*
 * //// B1 //// 2020-04-16
 * - NPC
 */

namespace ojy\npc;

use ojy\npc\cmd\NPCCommand;
use ojy\npc\event\PlayerUseItemOnEntityEvent;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class NPCPlugin extends PluginBase implements Listener
{

    /** @var self|null */
    public static $instance = null;

    public function onLoad()
    {
        self::$instance = $this;
        Entity::registerEntity(NPC::class, true, ['NPC']);
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        foreach ([
                     NPCCommand::class
                 ] as $c)
            Server::getInstance()->getCommandMap()->register('NPCPlugin', new $c);
    }

    public static function message(CommandSender $sender, string $message)
    {
        $sender->sendMessage('§l§6[!] §r§f' . $message);
    }

    public static $queue = [];

    public function onUseItemOnEntity(PlayerUseItemOnEntityEvent $event)
    {
        $npc = $event->getEntity();
        if (!$npc instanceof NPC) return;
        $player = $event->getPlayer();
        if (isset(self::$queue[$player->getName()])) {
            $data = self::$queue[$player->getName()];
            $type = $data['type'];
            if ($type === 'id') {
                self::message($player, 'Entity ID: ' . $npc->getId());
                unset(self::$queue[$player->getName()]);
            }
            return;
        }
        $data = $npc->getData();
        $cmd = $data->getCommand();
        if ($cmd !== "" && $cmd !== 'x')
            Server::getInstance()->dispatchCommand($player, $cmd);
        $message = str_replace("(줄바꿈)", "\n", $npc->getData()->getMessage());
        if ($message !== "" && $message !== 'x') {
            static $q = [];
            if (!isset($q[$npc->getId()]) || $q[$npc->getId()] < microtime(true)) {
                for ($i = 0; $i < mb_strlen($message, 'utf-8'); $i++) {
                    $nowStr = mb_substr($message, $i, 1);
                    if ($nowStr === "\\") continue;
                    $nametag = mb_substr($message, 0, 1 + $i) . "\n\n{$npc->getData()->getName()}";
                    $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($nametag, $npc): void {
                        $npc->setNameTag($nametag);
                    }), $i);
                }
                $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($npc): void {
                    $npc->setNameTag($npc->getData()->getName());
                }), mb_strlen($message, 'utf-8') + 30);

                $expectedTime = microtime(true) + mb_strlen($message, 'utf-8') / 20 + 3 / 2;
                $q[$npc->getId()] = $expectedTime;
            }
        }
        //InstanceTagPlugin::appearTagToPlayer($player, $npc->level, $npc->add(0, 2.5), $message);
    }

    public function handleReceivePacket(DataPacketReceiveEvent $event)
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();

        if ($packet instanceof InventoryTransactionPacket) {
            if ($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {

                $entityId = $packet->trData->entityRuntimeId;
                $entity = Server::getInstance()->findEntity($entityId, $player->level);

                if ($entity !== null)
                    (new PlayerUseItemOnEntityEvent($player, $entity))->call();

            }
        }
    }


    /*public function onDamage(EntityDamageEvent $event)
    {
        if (!$event instanceof EntityDamageByEntityEvent) return;
        $entity = $event->getEntity();
        if (!$entity instanceof NPC) return;
        $damager = $event->getDamager();
        if (!$damager instanceof Player) return;
        $entity->lookAt($damager);
        $event->setCancelled();
        if (isset(self::$queue[$damager->getName()])) {
            $data = self::$queue[$damager->getName()];
            $type = $data['type'];
            if ($type === 'id') {
                self::message($damager, '엔티티 ID: ' . $entity->getId());
                unset(self::$queue[$damager->getName()]);
            }
            return;
        }
        $data = $entity->getData();
        $cmd = $data->getCommand();
        if ($cmd !== "" && $cmd !== 'x')
            Server::getInstance()->dispatchCommand($damager, $cmd);
        $message = $data->getMessage();
        if ($message !== "" && $message !== 'x')
            $damager->sendMessage($message);
    }*/

    public static function createNPC(Player $player, string $name)
    {
        $nbt = Entity::createBaseNBT($player->getPosition(), new Vector3(), $player->yaw, $player->pitch);
        $skin = $player->getSkin();
        $nbt->setTag(new CompoundTag("Skin", [
            new StringTag("Name", $skin->getSkinId()),
            new ByteArrayTag("Data", $skin->getSkinData()),
            new ByteArrayTag("CapeData", $skin->getCapeData()),
            new StringTag("GeometryName", $skin->getGeometryName()),
            new ByteArrayTag("GeometryData", $skin->getGeometryData())
        ]));
        $inventoryTag = new ListTag("Inventory", [], NBT::TAG_Compound);

        $slotCount = $player->getInventory()->getSize() + $player->getInventory()->getHotbarSize();
        for ($slot = $player->getInventory()->getHotbarSize(); $slot < $slotCount; ++$slot) {
            $item = $player->getInventory()->getItem($slot - 9);
            if (!$item->isNull()) {
                $inventoryTag->push($item->nbtSerialize($slot));
            }
        }

        for ($slot = 100; $slot < 104; ++$slot) {
            $item = $player->getArmorInventory()->getItem($slot - 100);
            if (!$item->isNull()) {
                $inventoryTag->push($item->nbtSerialize($slot));
            }
        }

        $nbt->setTag($inventoryTag);

        $nbt->setInt("SelectedInventorySlot", $player->getInventory()->getHeldItemIndex());
        $nbt->setByte('Invulnerable', 1);
        $nbt->setString('NPCData', (new NPCData($name))->serialize());
        $npc = new NPC($player->level, $nbt);
        $npc->setNameTag(str_replace("(줄바꿈)", "\n", $name));
        $npc->setNameTagAlwaysVisible();
        $npc->setImmobile();
        $npc->spawnToAll();
    }
}