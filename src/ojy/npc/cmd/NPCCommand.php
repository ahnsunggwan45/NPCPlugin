<?php

namespace ojy\npc\cmd;

use ojy\npc\NPC;
use ojy\npc\NPCPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\Server;

class NPCCommand extends Command
{

    public function __construct()
    {
        parent::__construct('npc', 'npc 명령어입니다.', '/npc [ id|command|message|name|spawn|remove|scale ]', []);
        $this->setPermission(Permission::DEFAULT_OP);
    }

    public static function message(CommandSender $sender, string $message)
    {
        $sender->sendMessage('§l§6[!] §r§f' . $message);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender->hasPermission($this->getPermission())) {
            if (isset($args[0])) {
                switch ($args[0]) {
                    case 'scale':
                    case 'size':
                        if (isset($args[2])) {
                            $id = intval($args[1]);
                            $scale = floatval($args[2]);
                            if ($scale < 0.4)
                                $scale = 0.4;
                            $npc = Server::getInstance()->findEntity($id);
                            if ($npc instanceof NPC) {
                                $npc->setScale($scale);
                            } else {
                                self::message($sender, "NPC with ID {$id} not found.");
                            }
                        } else {
                            self::message($sender, '/npc scale <id> <scale>');
                        }
                        break;
                    case 'remove':
                    case 'delete':
                        if (isset($args[1])) {
                            $id = intval($args[1]);
                            $npc = Server::getInstance()->findEntity($id);
                            if ($npc instanceof NPC) {
                                $npc->getInventory()->clearAll();
                                $npc->getArmorInventory()->clearAll();
                                $npc->kill();
                            } else {
                                self::message($sender, "NPC with ID {$id} not found.");
                            }
                        } else {
                            self::message($sender, '/npc remove <id>');
                        }
                        break;
                    case 'create':
                    case 'spawn':
                        if ($sender instanceof Player) {
                            if (isset($args[1])) {
                                unset($args[0]);
                                $name = implode(' ', $args);
                                NPCPlugin::createNPC($sender, $name);
                            } else {
                                self::message($sender, '/npc spawn <name>');
                            }
                        } else {
                            self::message($sender, 'Please execute in the in-game.');
                        }
                        break;
                    case 'id':
                        if ($sender instanceof Player) {
                            NPCPlugin::$queue[$sender->getName()] = ['type' => 'id'];
                            self::message($sender, 'Touch NPC to view entity ID.');
                        } else {
                            self::message($sender, 'Please execute in the in-game.');
                        }
                        break;
                    case 'command':
                    case 'cmd':
                        if (isset($args[2])) {
                            $id = intval($args[1]);
                            unset($args[0], $args[1]);
                            $cmd = implode(' ', $args);
                            $npc = Server::getInstance()->findEntity($id);
                            if ($npc instanceof NPC) {
                                $data = $npc->getData();
                                $data->setCommand($cmd);
                                $npc->setData($data);
                                self::message($sender, 'successfully set up the command : ' . $cmd);
                            } else {
                                self::message($sender, "NPC with ID {$id} not found.");
                            }
                        } else {
                            self::message($sender, '/npc command <id> <명령어>');
                        }
                        break;
                    case 'message':
                    case 'msg':
                        if (isset($args[2])) {
                            $id = intval($args[1]);
                            unset($args[0], $args[1]);
                            $message = implode(' ', $args);
                            $npc = Server::getInstance()->findEntity($id);
                            if ($npc instanceof NPC) {
                                $data = $npc->getData();
                                $data->setMessage($message);
                                $npc->setData($data);
                                self::message($sender, 'successfully set up the message : ' . $message);
                            } else {
                                self::message($sender, "NPC with ID {$id} not found.");
                            }
                        } else {
                            self::message($sender, '/npc message <id> <명령어>');
                        }
                        break;
                    default:
                        self::message($sender, $this->getUsage());
                        break;
                }
            } else {
                self::message($sender, $this->getUsage());
            }
        } else {
            self::message($sender, 'Permission denied.');
        }
    }
}