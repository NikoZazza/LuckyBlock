<?php
/**
 *  _                _          ____  _            _
 * | |              | |        |  _ \| |          | |
 * | |    _   _  ___| | ___   _| |_) | | ___   ___| | __
 * | |   | | | |/ __| |/ / | | |  _ <| |/ _ \ / __| |/ /
 * | |___| |_| | (__|   <| |_| | |_) | | (_) | (__|   <
 * |______\__,_|\___|_|\_\\__, |____/|_|\___/ \___|_|\_\
 *                         __/ |
 *                        |___/
 * LuckyBlock plugin for PocketMine-MP server
 * Copyright (C) 2016 xionbig <https://github.com/xionbig/>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
namespace LuckyBlock;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Effect;
use pocketmine\item\Item;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Commands extends Command implements PluginIdentifiableCommand
{
    /** @var Main */
    private $luckyBlock;
    /** @var Config */
    private $setup;
    /** @var array */
    private $data;

    private $tag = TextFormat::GOLD . "[" . TextFormat::YELLOW . "LuckyBlock" . TextFormat::GOLD . "] " . TextFormat::WHITE;

    public function __construct(Main $luckyBlock, &$setup, &$data)
    {
        parent::__construct("luckyblock", "LuckyBlock is an plugin that allows you to create LuckyBlock!", "/lucky <command> <value>", ["luckyblock", "lucky", "lb"]);
        $this->luckyBlock = $luckyBlock;
        $this->data = $data;
        $this->setup = $setup;
    }

    public function execute(CommandSender $sender, $label, array $args)
    {
        $label = "/" . $label . " ";
        if (!$sender->hasPermission("luckyblock.command")) {
            $sender->sendMessage($this->tag . TextFormat::RED . "You do not have permission to use this command!");
            return;
        }
        if (!isset($args) || !isset($args[0]))
            $args[0] = "help";
        $cmds = "";
        foreach ($args as $var)
            $cmds = $cmds . "<" . $var . "> ";

        $args[0] = strtolower($args[0]);
        $sender->sendMessage($this->tag . "Usage: " . TextFormat::DARK_AQUA . $label . $cmds);

        switch ($args[0]) {
            case "?":
            case "h":
            case "help":
                $var = ["on <none|function>", "off <none|function>", "list", "potion <add|rmv|list|duration> <effect>", "block <item>", "explosion <min|max|info>", "drop <add|rmv|list|max>", "world <allow|deny|list>", "command", "mob"];
                $message = "";
                foreach ($var as $c)
                    $message .= $this->tag . TextFormat::WHITE . $label . $c . "\n";
                $message .= $this->tag . TextFormat::GRAY . "Developed by" . TextFormat::BOLD . " @xionbig";
                $sender->sendMessage($message);
                return;

            case "effect":
            case "potion":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " potion " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        if (Effect::getEffectByName($args[2]) instanceof Effect) {
                            if (!in_array($args[2], $this->data["potions"])) {
                                $arr = $this->setup->get("potions");
                                $arr[count($arr)] = $args[2];
                                $this->setup->set("potions", $arr);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The potion '" . $args[2] . "' has been added successfully.");
                            } else
                                $sender->sendMessage($this->tag . TextFormat::YELLOW . "The potion '" . $args[2] . "' is already present in the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::RED . "The name of the potion is not valid");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        if (in_array($args[2], $this->data["potions"])) {
                            $it = [];
                            foreach ($this->data["potions"] as $i) {
                                if ($i !== $args[2])
                                    $it = $args[2];
                            }
                            $this->setup->set("potions", $it);
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "The potion '" . $args[2] . "' has been successfully removed.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "The potion '" . $args[2] . "' was not found in the configuration.");
                        break;

                    case "list":
                        $list = $this->tag . "List of potions enabled: ";
                        foreach ($this->data["potions"] as $potion)
                            $list .= $potion . ", ";
                        $sender->sendMessage($list);
                        return;

                    case "duration":
                        if (!isset($args[2]) || empty($args[2]) || !is_numeric($args[2]) || $args[2] < 1) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("max_duration", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The maximum duration of potion is set to " . $args[2]);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data);
                return;
            case "item":
            case "drop":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " drop " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $item = $this->luckyBlock->getItem($args[2]);
                        if ($item->getId() != Item::AIR) {
                            if (!$this->luckyBlock->isExists($this->data["items_dropped"], $item)) {
                                $arr = $this->setup->get("items_dropped");
                                $arr[count($arr)] = $item->getId() . ":" . $item->getDamage();
                                $this->setup->set("items_dropped", $arr);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The item '" . $item->getName() . "' has been added successfully.");
                            } else
                                $sender->sendMessage($this->tag . TextFormat::YELLOW . "The item '" . $item->getName() . "' is already present in the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::RED . "The item is not valid.");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $item = $this->luckyBlock->getItem($args[2]);
                        if ($item->getId() != Item::AIR) {
                            if ($this->luckyBlock->isExists($this->data["items_dropped"], $item)) {
                                $it = [];
                                foreach ($this->data["items_dropped"] as $i) {
                                    if ($i->getId() !== $item->getId() && $i->getDamage() !== $item->getId())
                                        $it[] = $i->getId() . ":" . $i->getDamage();
                                }
                                $this->setup->set("items_dropped", $it);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The item '" . $item->getName() . "' has been successfully removed.");
                            } else
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The item '" . $item->getName() . "' was not found in the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::RED . "The item is not valid.");
                        break;
                    case "list":
                        $list = $this->tag . "List of items: ";
                        foreach ($this->data["items_dropped"] as $item)
                            $list .= $item->getName() . "(id=" . $item->getId() . " damage=" . $item->getDamage() . "); ";
                        $sender->sendMessage($list);
                        break;
                    case "max":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] <= 0) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("max_chest_item", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The maximum of the items generated inside the chest set to " . $args[2]);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;

            case "explosion":
                if (!isset($args[1]) || empty($args[1])) {
                    $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                    return;
                }
                switch ($args[1]) {
                    case "min":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_min", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Explosion set minimum at " . $args[2]);
                        break;
                    case "max":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_max", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Explosion set maximum at " . $args[2]);
                        break;
                    case "info":
                        $sender->sendMessage($this->tag . TextFormat::AQUA . "Explosion set minimum " . $this->data["explosion_min"] . " and maximum " . $this->data["explosion_max"]);
                        return;
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;
            case "block"://luckyblock
                if (!isset($args[1]) || empty($args[1])) {
                    $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                    return;
                }
                $item = $this->luckyBlock->getItem($args[1]);
                if ($item->getId() != Item::AIR) {
                    $this->setup->set("lucky_block", $item->getId() . ":" . $item->getDamage());
                    $sender->sendMessage($this->tag . TextFormat::GREEN . "Now the LuckyBlock is " . $item->getName());
                } else
                    $sender->sendMessage($this->tag . TextFormat::RED . "The item is not valid.");
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;

            case "world":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " world " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "allow":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        unset($args[0]);
                        unset($args[1]);
                        $level = "";
                        foreach ($args as $a)
                            $level .= $a . " ";
                        $level = trim($level);
                        $get = $this->data["level"];
                        $get[] = $level;

                        $this->setup->set("level", $get);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "You have allowed to use LuckyBlock in world '" . $level . "'");
                        break;
                    case "allowall":
                        $this->setup->set("level", []);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "You have allowed to use LuckyBlock in all worlds!");
                        break;
                    case "deny":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $args = array_slice($args, 2);
                        $level = "";
                        foreach ($args as $a)
                            $level .= $a . " ";
                        $level = trim($level);
                        $get = $this->data["level"];
                        $found = false;
                        foreach ($get as $c => $w) {
                            if (strtolower($w) == strtolower($level)) {
                                unset($get[$c]);
                                $found = true;
                            }
                        }
                        if ($found) {
                            $this->setup->set("level", $get);
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "The world '" . $level . "' has been removed from the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::RED . "The world '" . $level . "' does not exist in the configuration.");
                        break;
                    case "list":
                        if (count($this->data["level"]) == 0) {
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "Players can use LuckyBlock in all worlds!");
                            break;
                        }
                        $list = $this->tag . "List of worlds allowed: ";
                        foreach ($this->data["level"] as $level)
                            $list .= $level . "; ";
                        $sender->sendMessage($list);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;
            case "cmd":
            case "command":
            case "commands":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " command " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $command = "";
                        $arg = array_slice($args, 2);
                        foreach ($arg as $a)
                            $command .= " " . $a;
                        $arr = $this->setup->get("commands");
                        $command = trim(str_replace("/", "", $command));
                        $arr[count($arr)] = $command;
                        $this->setup->set("commands", $arr);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The command '/" . $command . "' has been added successfully!");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $command = "";
                        $arg = array_slice($args, 2);
                        foreach ($arg as $a)
                            $command .= " " . $a;
                        $command = trim(str_replace("/", "", $command));
                        $get = $this->data["commands"];
                        $found = false;
                        foreach ($get as $c => $w) {
                            if (trim(str_replace("/", "", $w)) == $command) {
                                unset($get[$c]);
                                $found = true;
                            }
                        }
                        if ($found) {
                            $this->setup->set("commands", $get);
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "The command '/" . $command . "' has been removed from the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::RED . "The command '/" . $command . "' does not exist in the configuration.");
                        break;
                    case "list":
                        if (count($this->data["commands"]) == 0) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "There aren't stored commands!");
                            break;
                        }
                        $sender->sendMessage($this->tag . "List of commands: ");
                        foreach ($this->data["commands"] as $c)
                            $sender->sendMessage("- /" . $c);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;
            case "mob":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " mob " . TextFormat::YELLOW . " <add|rmv|delay|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $mob = "";
                        $arg = array_slice($args, 2);
                        foreach ($arg as $a)
                            $mob .= " " . $a;
                        $arr = $this->setup->get("mob");
                        $mob = str_replace(" ", "", ucwords($mob));
                        if ($this->luckyBlock->isExistsEntity($mob)) {
                            $found = false;
                            foreach ($arr as $c => $w) {
                                if (str_replace(" ", "", $w) == $mob) {
                                    $found = true;
                                }
                            }
                            if ($found) {
                                $sender->sendMessage($this->tag . TextFormat::YELLOW . "The entity '" . $mob . "' has already been added.");
                                return;
                            } else {
                                $arr[count($arr)] = $mob;
                                $this->setup->set("mob", $arr);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The mob '" . $mob . "' has been added successfully!");
                            }
                        } else {
                            $sender->sendMessage($this->tag . TextFormat::RED . "The entity '" . $mob . "' isn't valid.");
                            return;
                        }
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $mob = "";
                        $arg = array_slice($args, 2);
                        foreach ($arg as $a)
                            $mob .= " " . $a;
                        $arr = $this->data["mob"];
                        $mob = str_replace(" ", "", ucwords($mob));
                        $found = false;
                        foreach ($arr as $c => $w) {
                            if (str_replace(" ", "", $w) == $mob) {
                                unset($arr[$c]);
                                $found = true;
                            }
                        }
                        if ($found) {
                            $sender->sendMessage($this->tag . TextFormat::GREEN . "The entity '" . $mob . "' has been removed successfully.");
                            $this->setup->set("mob", $arr);
                        } else {
                            $sender->sendMessage($this->tag . TextFormat::RED . "The mob '" . $mob . "' doesn't exist in the configuration!");
                            return;
                        }
                        break;
                    case "delay":
                        if (!isset($args[2]) || empty($args[2]) || !is_numeric($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("mob_explosion_delay", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The explosion delay of the mob is set to " . $args[2]);
                        break;
                    case "list":
                        if (count($this->data["mob"]) == 0) {
                            $sender->sendMessage($this->tag . TextFormat::RED . "There aren't stored mobs!");
                            break;
                        }
                        $list = $this->tag . "List of mobs: ";
                        foreach ($this->data["mob"] as $c)
                            $list .= $c . "; ";
                        $sender->sendMessage($list);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;
            case "on":
            case "true":
                if (!isset($args[1]) || empty($args[1])) {
                    $this->setup->set("status", "on");
                    $sender->sendMessage($this->tag . TextFormat::GREEN . "Plugin activated!");
                } else {
                    $arr = $this->data["functions"];
                    $found = false;
                    foreach ($arr as $c => $w) {
                        if (strtolower($c) == strtolower($args[1])) {
                            $arr[$c] = true;
                            $found = true;
                        }
                    }
                    if ($found) {
                        $this->setup->set("functions", $arr);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The function '" . $args[1] . "' has been enabled!");
                    } else {
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . "The function " . $args[1] . " was not found!");
                    }
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;

            case "off":
            case "false":
                if (!isset($args[1]) || empty($args[1])) {
                    $this->setup->set("status", "off");
                    $sender->sendMessage($this->tag . TextFormat::RED . "Plugin disabled!");
                } else {
                    $arr = $this->data["functions"];
                    $found = false;
                    foreach ($arr as $c => $w) {
                        if (strtolower($c) == strtolower($args[1])) {
                            $arr[$c] = false;
                            $found = true;
                        }
                    }
                    if ($found) {
                        $this->setup->set("functions", $arr);
                        $sender->sendMessage($this->tag . TextFormat::RED . "The function '" . $args[1] . "' has been disabled!");
                    } else {
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . "The function " . $args[1] . " was not found!");
                    }
                }
                $this->luckyBlock->reloadSetup($this->data, $this->setup);
                return;
            case "list":
                $sender->sendMessage($this->tag . "List of functions:");
                foreach ($this->data["functions"] as $f => $v) {
                    $text = $v == true ? TextFormat::GREEN . "enabled" : TextFormat::RED . "disabled";
                    $sender->sendMessage("- " . TextFormat::AQUA . $f . TextFormat::WHITE . " => " . $text);
                }
                return;
        }
        $sender->sendMessage($this->tag . TextFormat::RED . "The command does not exist!");
    }

    /**
     * @return Main
     */
    public function getPlugin(): Plugin
    {
        return $this->luckyBlock;
    }

}