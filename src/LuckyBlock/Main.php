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
 * @version 1.0.0
 * @link https://github.com/xionbig/LuckyBlock
 */
namespace LuckyBlock;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
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
    /** @var Item[] */
    private $item = [];
    /** @var string */
    private $tag = TextFormat::YELLOW."[LuckyBlock] ".TextFormat::WHITE;
    /** @var Config */
    private $setup, $message;

    public function onEnable(){
        $dataResources = $this->getDataFolder()."/resources/";
        if(!file_exists($this->getDataFolder())) 
            @mkdir($this->getDataFolder(), 0755, true);
        if(!file_exists($dataResources)) 
            @mkdir($dataResources, 0755, true);
        
        $this->setup = new Config($dataResources. "config.yml", Config::YAML, [
                "item" => [],
                "explosion" => 3,
                "status" => "on",
                "level" => []]);
        $this->setup->save();

        $this->message = new Config($dataResources. "message.yml", Config::YAML, [
            "tree" => "Tree spammed",
            "explosion" => "BOOOM!!!",
            "drop" => "Lucky",
            "sign" => "It's your problem!",
            "signText" => "It's your problem!",
            "prison" => "OPS...",
            "unlucky" => "Try again maybe you will be more lucky"]);
        $this->message->save();

        if(!is_numeric($this->setup->get("explosion")) || $this->setup->get("explosion") <= 0){
            $this->getServer()->getLogger()->error("The field 'explosion' is invalid. LuckyBlock disabled!");
            $this->getServer()->getPluginManager()->disablePlugin($this->getServer()->getPluginManager()->getPlugin("LuckyBlock"));
            return;
        }

        foreach($this->setup->get("item") as $id){
            $e = explode(":", $id);
            $id = $e[0];
            $damage = 0;
            if(count($e) > 1){
                $damage = $e[1];
            }
            $this->item[] = ["id" => $id, "damage" => $damage];
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if(strtolower($command->getName()) !== "luckyblock"){
            return;
        }
        if(!($sender instanceof ConsoleCommandSender)){
            $sender->sendMessage($this->tag."You must run this command from the console");
            return;
        }
        if(!isset($args) || !isset($args[0]))
            $args[0] = "help";

        $cmds = "";
        foreach($args as $var){
            $cmds = $cmds. " <".$var.">";
        }

        $args[0] = strtolower($args[0]);
        $sender->sendMessage($this->tag.TextFormat::DARK_AQUA."Usage: /luckyblock".$cmds);

        switch($args[0]){
            case "?":
            case "h":
            case "help":
                $var = ["on = enable plugin", "off = disable plugin"];

                $message = "";
                foreach ($var as $c)
                    $message .= $this->tag.TextFormat::AQUA."/luckyblock ".$c."\n";
                $sender->sendMessage($message);
                return;

            case "on":
            case "true":
                $this->setup->set("status", "on");
                $this->setup->save();
                $sender->sendMessage($this->tag.TextFormat::DARK_GREEN."Plugin activated!");
                return;

            case "off":
            case "false":
                $this->setup->set("status", "off");
                $this->setup->save();
                $sender->sendMessage($this->tag.TextFormat::DARK_GREEN."Plugin disabled!");
                return;
        }
        $sender->sendMessage($this->tag.TextFormat::DARK_RED."The command does not exist!");
    }

    private function isAllowedWorld(Level $level){
        if($this->setup->get("status") !== "on") {
            return false;
        }
        $level = strtolower($level->getName());
        $get = $this->setup->get("level");
        if(empty($get) || !$get || count($get) === 0){
            return true;
        }else{
            $e = explode(",", $get);
            if(count($e) > 1){
                foreach($e as $l){
                    if(strtolower(trim($l)) == $level)
                        return true;
                }
                return false;
            }else{
                return $level == strtolower(trim($get));
            }
        }
    }

    public function blockBreak(BlockBreakEvent $event){
        $block = $event->getBlock();
        $player = $event->getPlayer();
        
        if($block->getId() === Block::SPONGE && $this->isAllowedWorld($block->getLevel())){
            $event->setCancelled();
            
            $player->getLevel()->setBlock($block, new Block(Block::AIR), false, true);
            switch(mt_rand(1, 6)){
                case 1:
                    switch(mt_rand(1, 4)){
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
                    $ex = new Explosion($block, $this->setup->get("explosion"));
                    if($ex->explodeA())
                        $ex->explodeB();
                    $player->sendMessage($this->tag.$this->message->get("explosion"));
                    break;
                    
                case 3:
                    if(count($this->item) > 0){
                        $r = mt_rand(0, count($this->item)-1);
                        $item = $this->item[$r];
                        $player->getLevel()->dropItem($block, new Item($item["id"], $item["damage"], 1));
                        break;               
                    }
                    $player->sendMessage($this->tag.$this->message->get("drop"));
                    break;
                    
                case 4:
                    $player->getLevel()->setBlock($block, new Block(Block::BEDROCK), false, true);
                    $p = new Position($block->x, $block->y + 1, $block->z, $block->level);
                    if($player->getLevel()->getBlock($p)->getId() != Block::AIR)
                        break;
                    $player->getLevel()->setBlock($p, Block::get(Item::SIGN_POST), true, true);
                    
                    new Sign($player->getLevel()->getChunk($block->x >> 4, $block->z >> 4, true), new Compound(false, array(
                        new Int("x", $block->x),
                        new Int("y", $block->y + 1),
                        new Int("z", $block->z),
                        new String("id", Tile::SIGN),
                        new String("Text1", TextFormat::YELLOW."[LuckyBlock]"),
                        new String("Text2", $this->message->get("signText")),
                        )));   
                    $player->sendMessage($this->tag.$this->message->get("sign"));
                    break;       
                
                case 5:
                    $pos = $event->getPlayer();
                    $pos->x = round($pos->x)+0.5;
                    $pos->y = round($pos->y);
                    $pos->z = round($pos->z)+0.5;       
                    $player->teleport($pos);
                    
                    foreach([Block::AIR, Block::OBSIDIAN] as $block){
                        $player->getLevel()->setBlock(new Position($pos->x, $pos->y-1, $pos->z, $pos->getLevel()), new Block($block));
                        for($y = $pos->y; $y <= $pos->y + 2; $y++){
                            $player->getLevel()->setBlock(new Position($pos->x - 1, $y, $pos->z, $pos->getLevel()), new Block($block));
                            $player->getLevel()->setBlock(new Position($pos->x + 1, $y, $pos->z, $pos->getLevel()), new Block($block));
                            $player->getLevel()->setBlock(new Position($pos->x, $y, $pos->z - 1, $pos->getLevel()), new Block($block));
                            $player->getLevel()->setBlock(new Position($pos->x, $y, $pos->z + 1, $pos->getLevel()), new Block($block));
                        }     
                        $player->getLevel()->updateAround($pos);
                    }
                    $player->sendMessage($this->tag.$this->tag.$this->message->get("prison"));
                    break;
                    
                case 6:
                    $player->sendMessage($this->tag.$this->tag.$this->message->get("unlucky"));
                    break;
            }
            $player->getLevel()->save();            
        }    
    }
} 
   