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
 * GNU General Public License for more details. *
 */
namespace LuckyBlock;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Effect;
use pocketmine\item\Item;
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
        parent::__construct("luckyblock", "LuckyBlock is an plugin that allows you to create LuckyBlock!", "/lucky <command> <value>", ["luckyblock", "lucky"]);
        $this->luckyBlock = $luckyBlock;
        $this->data = $data;
        $this->setup = $setup;
    }

    public function execute(CommandSender $sender, $label, array $args)
    {
        $label = "/" . $label . " ";
        if (!$sender->hasPermission("luckyblock.command")) {
            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "You do not have permission to use this command!");
            return;
        }
        if (!isset($args) || !isset($args[0]))
            $args[0] = "help";
        $cmds = "";
        foreach ($args as $var)
            $cmds = $cmds . "<" . $var . "> ";

        $args[0] = strtolower($args[0]);
        $sender->sendMessage($this->tag . TextFormat::DARK_AQUA . "Usage: " . $label . $cmds);

        switch ($args[0]) {
            case "?":
            case "h":
            case "help":
                $var = ["on", "off", "potion <add|rmv|list|duration> <effect>", "block <item>", "prison <item>", "explosion <min|max|info>", "chest <add|rmv|list|max>", "drop <add|rmv|list>", "world <allow|deny|list>", "money <min|max|info>"];
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
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " drop " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The name of the potion is not valid");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("max_duration", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The maximum duration of potion is set to " . $args[2]);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data);
                return;

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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                        break;
                    case "list":
                        $list = $this->tag . "List of items dropped: ";
                        foreach ($this->data["items_dropped"] as $item)
                            $list .= $item->getName() . "(id=" . $item->getId() . " damage=" . $item->getDamage() . "); ";
                        $sender->sendMessage($list);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data);
                return;

            case "chest":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " chest " . TextFormat::YELLOW . " <add|rmv|list>");
                        return;
                    case "add":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $item = $this->luckyBlock->getItem($args[2]);
                        if ($item->getId() != Item::AIR) {
                            if (!$this->luckyBlock->isExists($this->data["items_chest"], $item)) {
                                $arr = $this->setup->get("items_chest");
                                $arr[count($arr)] = $item->getId() . ":" . $item->getDamage();
                                $this->setup->set("items_chest", $arr);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The item '" . $item->getName() . "' has been added successfully.");
                            } else
                                $sender->sendMessage($this->tag . TextFormat::YELLOW . "The item '" . $item->getName() . "' is already present in the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if (!isset($args[2]) || empty($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $item = $this->luckyBlock->getItem($args[2]);
                        if ($item->getId() != Item::AIR) {
                            if ($this->luckyBlock->isExists($this->data["items_chest"], $item)) {
                                $it = [];
                                foreach ($this->data["items_chest"] as $i) {
                                    if ($i->getId() !== $item->getId() && $i->getDamage() !== $item->getId())
                                        $it[] = $i->getId() . ":" . $i->getDamage();
                                }
                                $this->setup->set("items_chest", $it);
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "The item '" . $item->getName() . "' has been successfully removed.");
                            } else
                                $sender->sendMessage($this->tag . TextFormat::GREEN . "the item '" . $item->getName() . "' was not found in the configuration.");
                        } else
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                        break;
                    case "list":
                        $list = $this->tag . "List of items of the chest: ";
                        foreach ($this->data["items_chest"] as $item)
                            $list .= $item->getName() . "(id=" . $item->getId() . " damage=" . $item->getDamage() . "); ";
                        $sender->sendMessage($list);
                        break;
                    case "max":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] <= 0) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("max_chest_item", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "The maximum of the items generated inside the chest set to " . $args[2]);
                        break;
                }
                $this->luckyBlock->reloadSetup($this->data);
                return;
            case "explosion":
                if (!isset($args[1]) || empty($args[1])) {
                    $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                    return;
                }
                switch ($args[1]) {
                    case "min":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_min", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Explosion set minimum at " . $args[2]);
                        break;
                    case "max":
                        if (!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_max", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Explosion set maximum at " . $args[2]);
                        break;
                    case "info":
                        $sender->sendMessage($this->tag . TextFormat::AQUA . "Explosion set minimum " . $this->data["explosion_min"] . " and maximum " . $this->data["explosion_max"]);
                        return;
                }
                $this->luckyBlock->reloadSetup($this->data);
                return;
            case "block"://luckyblock
                if (!isset($args[1]) || empty($args[1])) {
                    $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                    return;
                }
                $item = $this->luckyBlock->getItem($args[1]);
                if ($item->getId() != Item::AIR) {
                    $this->setup->set("lucky_block", $item->getId() . ":" . $item->getDamage());
                    $sender->sendMessage($this->tag . TextFormat::GREEN . "Now the LuckyBlock is " . $item->getName());
                } else
                    $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                $this->luckyBlock->reloadSetup($this->data);
                return;
            case "prison":
                if (!isset($args[1]) || empty($args[1])) {
                    $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                    return;
                }
                $item = $this->luckyBlock->getItem($args[1]);
                if ($item->getId() != Item::AIR) {
                    $this->setup->set("prison_block", $item->getId() . ":" . $item->getDamage());
                    $sender->sendMessage($this->tag . TextFormat::GREEN . "Now the prison block is " . $item->getName());
                } else
                    $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The item is not valid.");
                $this->luckyBlock->reloadSetup($this->data);
                return;
            case "money":
                if (!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag . TextFormat::YELLOW . $label . TextFormat::WHITE . " money " . TextFormat::YELLOW . " <min|max|info>");
                        return;
                    case "min":
                        if (!isset($args[2]) || !is_numeric($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("money_min", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Setting the value of the minimum range of the money generated...");
                        break;
                    case "max":
                        if (!isset($args[2]) || !is_numeric($args[2])) {
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        $this->setup->set("money_max", $args[2]);
                        $sender->sendMessage($this->tag . TextFormat::GREEN . "Setting the value of the maximum range of the money generated...");
                        break;
                    case "info":
                        $sender->sendMessage($this->tag . TextFormat::AQUA . "The range of the values generated for the money is included from " . $this->data["money_min"] . " to " . $this->data["money_max"]);
                        return;
                }
                $this->luckyBlock->reloadSetup($this->data);
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Invalid parameters.");
                            return;
                        }
                        unset($args[0]);
                        unset($args[1]);
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
                            $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The world '" . $level . "' does not exist in the configuration.");
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
                $this->luckyBlock->reloadSetup($this->data);
                return;

            case "on":
            case "true":
                $this->setup->set("status", "on");
                $this->luckyBlock->reloadSetup($this->data);
                $sender->sendMessage($this->tag . TextFormat::DARK_GREEN . "Plugin activated!");
                return;

            case "off":
            case "false":
                $this->setup->set("status", "off");
                $this->luckyBlock->reloadSetup($this->data);
                $sender->sendMessage($this->tag . TextFormat::DARK_RED . "Plugin disabled!");
                return;
        }
        $sender->sendMessage($this->tag . TextFormat::DARK_RED . "The command does not exist!");
    }

    /**
     * @return Main
     */
    public function getPlugin()
    {
        return $this->luckyBlock;
    }


}