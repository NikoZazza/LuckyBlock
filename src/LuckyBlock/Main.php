<?php
/**
 * LuckyBlock Copyright (C) 2015 xionbig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * @author xionbig
 * @version 2.0.0
 * @link https://github.com/xionbig/LuckyBlock
 * @link https://forums.pocketmine.net/plugins/luckyblock.1437
 */
namespace LuckyBlock;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Enum;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\Block;
use pocketmine\level\Explosion;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\String;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\block\Sapling;

class Main extends PluginBase implements Listener{ 
    /** @var string */
    private $tag = TextFormat::YELLOW."[LuckyBlock] ".TextFormat::WHITE;
    /** @var Config */
    private $setup, $message;
    /** @var array */
    private $data = [
        "lucky_block" => 19,
        "status" => "on",
        "level" => [],
        "explosion_min" => 1,
        "explosion_max" => 3,
        "prison_block" => 49,
        "money_min" => 0,
        "money_max" => 1,
        "max_chest_item" => 4,
        "items_chest" => [],
        "items_dropped" => []
    ];
    /** @var MoneyManager */
    private $moneyManager;

    public function onEnable(){
        $dataResources = $this->getDataFolder()."/resources/";
        if(!file_exists($this->getDataFolder())) 
            @mkdir($this->getDataFolder(), 0755, true);
        if(!file_exists($dataResources)) 
            @mkdir($dataResources, 0755, true);
        
        $this->setup = new Config($dataResources. "config.yml", Config::YAML, $this->data);
        $this->setup->save();

        $this->message = new Config($dataResources. "message.yml", Config::YAML, [
            "tree" => "Tree spammed",
            "explosion" => "BOOOM!!!",
            "drop" => "Lucky",
            "sign" => "It's your problem!",
            "signText" => "It's your problem!",
            "prison" => "OPS...",
            "unlucky" => "Try again maybe you will be more lucky",
            "spawn" => "Muahahahahha",
            "money" => "You just won %MONEY%",
            "chest" => "You are very lucky!",
            "not_allowed" => "You are not authorized to use the plugin",
            ]
        );
        $this->message->save();
        $this->reloadSetup();

        $this->moneyManager = new MoneyManager($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if(strtolower($command->getLabel()) != "luckyblock" && strtolower($command->getLabel()) != "lb")
            return;
        $cmd = "/".strtolower($command->getLabel())." ";
        if(!$sender->hasPermission("luckyblock.command")){
            $sender->sendMessage($this->tag.TextFormat::DARK_RED."You do not have permission to use this command!");
            return;
        }
        if(!isset($args) || !isset($args[0]))
            $args[0] = "help";
        $cmds = "";
        foreach($args as $var)
            $cmds = $cmds. "<".$var."> ";

        $args[0] = strtolower($args[0]);
        $sender->sendMessage($this->tag.TextFormat::DARK_AQUA."Usage: ".$cmd.$cmds);

        switch($args[0]) {
            case "?":
            case "h":
            case "help":
                $var = ["on", "off", "block <item>", "prison <item>", "explosion <min|max|info>", "chest <add|rmv|list|max>", "drop <add|rmv|list>", "world <allow|deny|list>", "money <min|max|info>"];
                $message = "";
                foreach ($var as $c)
                    $message .= $this->tag . TextFormat::AQUA . $cmd . $c . "\n";
                $sender->sendMessage($message);
                return;

            case "drop":
                if(!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag.TextFormat::YELLOW.$cmd.TextFormat::WHITE." drop ".TextFormat::YELLOW." <add|rmv|list>");
                        return;
                    case "add":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $item = $this->getItem($args[2]);
                        if($item->getId() != Item::AIR){
                            if(!$this->isExists($this->data["items_dropped"], $item)){
                                $arr = $this->setup->get("items_dropped");
                                $arr[count($arr)] = $item->getId().":".$item->getDamage();
                                $this->setup->set("items_dropped", $arr);
                                $sender->sendMessage($this->tag.TextFormat::GREEN."The item '".$item->getName()."' has been added successfully.");
                            }else
                                $sender->sendMessage($this->tag.TextFormat::YELLOW."The item '".$item->getName()."' is already present in the configuration.");
                        }else
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $item = $this->getItem($args[2]);
                        if($item->getId() != Item::AIR) {
                            if ($this->isExists($this->data["items_dropped"], $item)) {
                                $it = [];
                                foreach($this->data["items_dropped"] as $i){
                                    if($i->getId() !== $item->getId() && $i->getDamage() !== $item->getId())
                                        $it = $i->getId().":".$i->getDamage();
                                }
                                $this->setup->set("items_dropped", $it);
                                $sender->sendMessage($this->tag.TextFormat::GREEN."The item '".$item->getName()."' has been successfully removed.");
                            }else
                                $sender->sendMessage($this->tag.TextFormat::GREEN."the item '".$item->getName()."' was not found in the configuration.");
                        }else
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                        break;
                    case "list":
                        $list = $this->tag."List of items dropped: ";
                        foreach($this->data["items_dropped"] as $item)
                            $list .= $item->getName()."(id=".$item->getId()." damage=".$item->getDamage()."); ";
                        $sender->sendMessage($list);
                        break;
                }
                $this->reloadSetup();
                return;

            case "chest":
                if(!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag.TextFormat::YELLOW.$cmd.TextFormat::WHITE." chest ".TextFormat::YELLOW." <add|rmv|list>");
                        return;
                    case "add":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $item = $this->getItem($args[2]);
                        if($item->getId() != Item::AIR){
                            if(!$this->isExists($this->data["items_chest"], $item)){
                                $arr = $this->setup->get("items_chest");
                                $arr[count($arr)] = $item->getId().":".$item->getDamage();
                                $this->setup->set("items_chest", $arr);
                                $sender->sendMessage($this->tag.TextFormat::GREEN."The item '".$item->getName()."' has been added successfully.");
                            }else
                                $sender->sendMessage($this->tag.TextFormat::YELLOW."The item '".$item->getName()."' is already present in the configuration.");
                        }else
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                        break;
                    case "rmw":
                    case "rmv":
                    case "remove":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $item = $this->getItem($args[2]);
                        if($item->getId() != Item::AIR) {
                            if ($this->isExists($this->data["items_chest"], $item)) {
                                $it = [];
                                foreach($this->data["items_chest"] as $i){
                                    if($i->getId() !== $item->getId() && $i->getDamage() !== $item->getId())
                                        $it = $i->getId().":".$i->getDamage();
                                }
                                $this->setup->set("items_chest", $it);
                                $sender->sendMessage($this->tag.TextFormat::GREEN."The item '".$item->getName()."' has been successfully removed.");
                            }else
                                $sender->sendMessage($this->tag.TextFormat::GREEN."the item '".$item->getName()."' was not found in the configuration.");
                        }else
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                        break;
                    case "list":
                        $list = $this->tag."List of items of the chest: ";
                        foreach($this->data["items_chest"] as $item)
                            $list .= $item->getName()."(id=".$item->getId()." damage=".$item->getDamage()."); ";
                        $sender->sendMessage($list);
                        break;
                    case "max":
                        if(!isset($args[2]) || !is_numeric($args[2]) || $args[2] <= 0){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $this->setup->set("max_chest_item", $args[2]);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."The maximum of the items generated inside the chest set to ".$args[2]);
                        break;
                }
                $this->reloadSetup();
                return;
            case "explosion":
                if(!isset($args[1]) || empty($args[1])){
                    $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                    return;
                }
                switch($args[1]){
                    case "min":
                        if(!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_min", $args[2]);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."Explosion set minimum at ".$args[2]);
                        break;
                    case "max":
                        if(!isset($args[2]) || !is_numeric($args[2]) || $args[2] < 0){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $this->setup->set("explosion_max", $args[2]);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."Explosion set maximum at ".$args[2]);
                        break;
                    case "info":
                        $sender->sendMessage($this->tag.TextFormat::ACQUA."Explosion set minimum ".$this->data["explosion_min"]." and maximum ".$this->data["explosion_max"]);
                        return;
                }
                $this->reloadSetup();
                return;
            case "block"://luckyblock
                if(!isset($args[1]) || empty($args[1])){
                    $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                    return;
                }
                $item = $this->getItem($args[1]);
                if($item->getId() != Item::AIR){
                    $this->setup->set("lucky_block", $item->getId().":".$item->getDamage());
                    $sender->sendMessage($this->tag.TextFormat::GREEN."Now the LuckyBlock is ".$item->getName());
                }else
                    $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                $this->reloadSetup();
                return;
            case "prison":
                if(!isset($args[1]) || empty($args[1])){
                    $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                    return;
                }
                $item = $this->getItem($args[1]);
                if($item->getId() != Item::AIR){
                    $this->setup->set("prison_block", $item->getId().":".$item->getDamage());
                    $sender->sendMessage($this->tag.TextFormat::GREEN."Now the prison block is ".$item->getName());
                }else
                    $sender->sendMessage($this->tag.TextFormat::DARK_RED."The item is not valid.");
                $this->reloadSetup();
                return;
            case "money":
                if(!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag.TextFormat::YELLOW.$cmd.TextFormat::WHITE." money ".TextFormat::YELLOW." <min|max|info>");
                        return;
                    case "min":
                        if(!isset($args[2]) || !is_numeric($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $this->setup->set("money_min", $args[2]);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."Setting the value of the minimum range of the money generated...");
                        break;
                    case "max":
                        if(!isset($args[2]) || !is_numeric($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        $this->setup->set("money_max", $args[2]);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."Setting the value of the maximum range of the money generated...");
                        break;
                    case "info":
                        $sender->sendMessage($this->tag.TextFormat::AQUA."The range of the values generated for the money is included from ".$this->data["money_min"]." to ".$this->data["money_max"]);
                        return;
                }
                $this->reloadSetup();
                return;

            case "world":
                if(!isset($args[1]) || empty($args[1]))
                    $args[1] = "help";
                switch ($args[1]) {
                    case "?":
                    case "h":
                    case "help":
                        $sender->sendMessage($this->tag.TextFormat::YELLOW.$cmd.TextFormat::WHITE." world ".TextFormat::YELLOW." <add|rmv|list>");
                        return;
                    case "allow":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        unset($args[0]);
                        unset($args[1]);
                        $level = "";
                        foreach($args as $a)
                            $level .= $a." ";
                        $level = trim($level);
                        $get = $this->data["level"];
                        $get[] = $level;

                        $this->setup->set("level", $get);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."You have allowed to use LuckyBlock in world '".$level."'");
                        break;
                    case "allowall":
                        $this->setup->set("level", []);
                        $sender->sendMessage($this->tag.TextFormat::GREEN."You have allowed to use LuckyBlock in all worlds!");
                        break;
                    case "deny":
                        if(!isset($args[2]) || empty($args[2])){
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."Invalid parameters.");
                            return;
                        }
                        unset($args[0]);
                        unset($args[1]);
                        $level = "";
                        foreach($args as $a)
                            $level .= $a." ";
                        $level = trim($level);
                        $get = $this->data["level"];
                        $found = false;
                        foreach($get as $c => $w) {
                            if (strtolower($w) == strtolower($level)){
                                unset($get[$c]);
                                $found = true;
                            }
                        }
                        if($found){
                            $this->setup->set("level", $get);
                            $sender->sendMessage($this->tag.TextFormat::GREEN."The world '".$level."' has been removed from the configuration.");
                        }else
                            $sender->sendMessage($this->tag.TextFormat::DARK_RED."The world '".$level."' does not exist in the configuration.");
                        break;
                    case "list":
                        if(count($this->data["level"]) == 0){
                            $sender->sendMessage($this->tag.TextFormat::GREEN."Players can use LuckyBlock in all worlds!");
                            break;
                        }
                        $list = $this->tag."List of worlds allowed: ";
                        foreach($this->data["level"] as $level)
                            $list .= $level."; ";
                        $sender->sendMessage($list);
                        break;
                }
                $this->reloadSetup();
                return;

            case "on":
            case "true":
                $this->setup->set("status", "on");
                $this->reloadSetup();
                $sender->sendMessage($this->tag.TextFormat::DARK_GREEN."Plugin activated!");
                return;

            case "off":
            case "false":
                $this->setup->set("status", "off");
                $this->reloadSetup();
                $sender->sendMessage($this->tag.TextFormat::DARK_RED."Plugin disabled!");
                return;
        }
        $sender->sendMessage($this->tag.TextFormat::DARK_RED."The command does not exist!");
    }

    private function reloadSetup(){
        $this->setup->save();
        if(!is_numeric($this->setup->get("max_chest_item")) || $this->setup->get("max_chest_item") < 1)
            $this->data["max_chest_item"] = 4;
        else
            $this->data["max_chest_item"] = $this->setup->get("max_chest_item");
        $this->data["level"] = $this->setup->get("level");

        if(!is_numeric($this->setup->get("explosion_min")) || !is_numeric($this->setup->get("explosion_max"))){
            $this->data["explosion_min"] = 1;
            $this->data["explosion_max"] = 3;
        }else{
            if($this->setup->get("explosion_min") >= 0 && $this->setup->get("explosion_max") >= 0){
                $this->data["explosion_min"] = $this->setup->get("explosion_min");
                $this->data["explosion_max"] = $this->setup->get("explosion_max");
            }
        }

        $this->data["lucky_block"] = $this->getItem($this->setup->get("lucky_block"))->getId();
        if($this->data["lucky_block"] === Block::AIR)
            $this->data["lucky_block"] = Block::SPONGE;

        $this->data["prison_block"] = $this->getItem($this->setup->get("prison_block"))->getId();
        if($this->data["prison_block"] === Block::AIR)
            $this->data["prison_block"] = Block::OBSIDIAN;

        $this->data["money_min"] = $this->setup->get("money_min");
        $this->data["money_max"] = $this->setup->get("money_max");
        if($this->data["money_min"] > $this->data["money_max"]){
            $this->data["money_max"] = $this->data["money_min"];
            $this->data["money_min"] = $this->setup->get("money_max");
        }

        foreach(["dropped", "chest"] as $type) {
            foreach ($this->setup->get("items_".$type) as $string) {
                $item = $this->getItem($string);
                if ($item->getId() !== Item::AIR && !$this->isExists($this->data["items_".$type], $item))
                    $this->data["items_".$type][] = $item;
            }
        }
    }

    private function getItem($string){
        $e = explode(":", $string);
        $id = $e[0];
        if(is_numeric($id)) {
            $damage = 0;
            if (count($e) > 1) {
                $damage = $e[1];
            }
            return new Item($id, $damage, 1, 1);
        }else{
            $item = Item::fromString($id);
            if($item->getId() !== Item::AIR){
                $item->setCount(1);
                return $item;
            }
        }
        return new Item(0);
    }

    private function isExists(array $arr, Item $item){
        foreach($arr as $it){
            if($it instanceof Item){
                if($it->getId() == $item->getId() && $it->getDamage() == $item->getDamage())
                    return true;
            }
        }
        return false;
    }

    private function isAllowedWorld(Level $level){
        if($this->data["status"] !== "on")
            return false;

        $level = strtolower($level->getName());
        $get = $this->data["level"];
        if(count($get) <= 0)
            return true;
        else{
            foreach($get as $l){
                if(strtolower(trim($l)) === $level)
                    return true;
            }
        }
        return false;
    }

    private function p_rand($min, $max){
        $generated = range($min, $max);
        shuffle($generated);
        return $generated[mt_rand(0, count($generated)-1)];
    }

    /** Thanks to @dxm_hippie for this code */
    public function itemLoop(Player $player, Position $pos){
        if($this->p_rand(1, 2) === 2){
            $player->getLevel()->dropItem($pos, $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"])-1)]);
            $this->itemLoop2($player, $pos);
        }
    }

    public function itemLoop2(Player $player, Position $pos){
        if($this->p_rand(1, 3) === 2){
            for($i = 1; $i <= 3; $i++)
                $player->getLevel()->dropItem($pos, $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"])-1)]);
            $this->itemLoop($player, $pos);
        }
    }
    /** END CODE */

    public function blockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        if($block->getId() === $this->data["lucky_block"] && $this->isAllowedWorld($block->getLevel())){
            $player = $event->getPlayer();
            if(!$player->hasPermission("luckyblock.use")){
                $player->sendMessage($this->tag.$this->message->get("not_allowed"));
                return;
            }
            $event->setCancelled();
            $player->getLevel()->setBlock($block, new Block(Block::AIR), false, true);
            switch($this->p_rand(1, 9)){
                case 1:
                    switch($this->p_rand(1, 4)){
                        case 1:
                            $type = Sapling::OAK;
                            break;
                        case 2:
                            $type = Sapling::BIRCH;
                            break;
                        case 3:
                            $type = Sapling::SPRUCE;
                            break;
                        case 4:
                            $type = Sapling::JUNGLE;
                            break;
                    }
                    if($player->getLevel()->setBlock($block, new Sapling($type), true, true)){
                        $player->getLevel()->getBlock($block)->onActivate(new Item(Item::DYE, 15), $player);
                        $player->sendMessage($this->tag.$this->message->get("tree"));
                    }
                    break;
                    
                case 2:
                    $explosion = new Explosion($block, mt_rand($this->data["explosion_min"], $this->data["explosion_max"]));

                    if($explosion->explodeA())
                        $explosion->explodeB();
                    $player->sendMessage($this->tag.$this->message->get("explosion"));
                    break;
                    
                case 3:
                    if(count($this->data["items_dropped"]) === 0) {
                        $player->sendMessage($this->tag.$this->message->get("unlucky"));
                        break;
                    }
                    $player->getLevel()->dropItem($block, $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"])-1)]);
                    $player->sendMessage($this->tag.$this->message->get("drop"));
                    break;
                    
                case 4:
                    $player->getLevel()->setBlock($block, new Block(Block::BEDROCK));
                    $p = new Position($block->x, $block->y + 1, $block->z, $block->level);
                    if($player->getLevel()->getBlock($p)->getId() != Block::AIR)
                        break;
                    $player->getLevel()->setBlock($p, Block::get(Item::SIGN_POST), true, false);
                    
                    new Sign($player->getLevel()->getChunk($block->x >> 4, $block->z >> 4, true), new Compound(false, array(
                        new Int("x", $block->x),
                        new Int("y", $block->y + 1),
                        new Int("z", $block->z),
                        new String("id", Tile::SIGN),
                        new String("Text1", $this->tag),
                        new String("Text2", $this->message->get("signText")),
                        )));   
                    $player->sendMessage($this->tag.$this->message->get("sign"));
                    break;       
                
                case 5:
                    $pos = $event->getPlayer();
                    $pos->x = round($pos->x) + 0.5;
                    $pos->y = round($pos->y);
                    $pos->z = round($pos->z) + 0.5;
                    $player->teleport($pos, $player->getYaw(), $player->getPitch());
                    
                    foreach([Block::AIR, $this->data["prison_block"]] as $block){
                        $player->getLevel()->setBlock(new Position($pos->x, $pos->y-1, $pos->z, $pos->getLevel()), new Block($block));
                        for($x = $pos->x - 1; $x <= $pos->x + 1; $x++) {
                            for($z = $pos->z - 1; $z <= $pos->z + 1; $z++){
                                if(!($x === $pos->x && $z === $pos->z)){
                                    for($y = $pos->y; $y <= $pos->y + 2; $y++)
                                        $player->getLevel()->setBlock(new Position($x, $y, $z, $pos->getLevel()), new Block($block));
                                }
                            }
                        }
                        $player->getLevel()->updateAround($pos);
                    }
                    $player->sendMessage($this->tag.$this->message->get("prison"));
                    break;

                case 6:
                    if(count($this->data["items_chest"]) === 0) {
                        $player->sendMessage($this->tag.$this->message->get("unlucky"));
                        break;
                    }
                    $player->getLevel()->setBlock($block, new Block(Block::CHEST), true, true);
                    $nbt = new Compound("", [
                        new Enum("Items", []),
                        new String("id", Tile::CHEST),
                        new Int("x", $block->x),
                        new Int("y", $block->y),
                        new Int("z", $block->z)
                    ]);
                    $nbt->Items->setTagType(NBT::TAG_Compound);
                    $tile = Tile::createTile("Chest", $block->getLevel()->getChunk($block->x >> 4, $block->z >> 4), $nbt);
                    if($tile instanceof Chest) {
                        for ($i = 0; $i <= mt_rand(1, $this->data["max_chest_item"]); $i++)
                            $tile->getInventory()->setItem($i, $this->data["items_chest"][mt_rand(0, count($this->data["items_chest"]) - 1)]);
                        $player->sendMessage($this->tag . $this->message->get("chest"));
                    }
                    break;

                case 7:
                    if(!$this->moneyManager->isLoaded() || ($this->data["money_min"] == $this->data["money_max"] && $this->data["money_min"] == 0)){
                        $player->sendMessage($this->tag.$this->message->get("unlucky"));
                        break;
                    }
                    if($this->moneyManager->isLoaded()){
                        $m = mt_rand($this->data["money_min"], $this->data["money_max"]);

                        $this->moneyManager->addMoney($player->getName(), $m);
                        $player->sendMessage($this->tag.str_replace("%MONEY%", $m." ".$this->moneyManager->getValue(), $this->message->get("money")));
                    }else
                        $player->sendMessage($this->tag.$this->message->get("unlucky"));
                    break;

                case 8:
                    $player->teleport($player->getLevel()->getSpawnLocation(), $player->getYaw(), $player->getPitch());
                    $player->sendMessage($this->tag.$this->message->get("spawn"));
                    break;

                case 9:
                    $player->sendMessage($this->tag.$this->message->get("unlucky"));
                    break;
            }
            $player->getLevel()->save();            
        }    
    }
} 
   