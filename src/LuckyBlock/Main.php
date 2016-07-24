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

use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\Block;
use pocketmine\level\Explosion;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\block\Sapling;

class Main extends PluginBase implements Listener
{
    /** @var string */
    private $tag = TextFormat::GOLD . "[" . TextFormat::YELLOW . "LuckyBlock" . TextFormat::GOLD . "] " . TextFormat::WHITE;
    /** @var Config */
    public $setup;
    /** @var Config */
    private $message;
    /** @var array */
    public $data = [
        "lucky_block" => 19,
        "status" => "on",
        "level" => [],
        "explosion_min" => 1,
        "explosion_max" => 3,
        "max_chest_item" => 4,
        "max_duration" => 20,
        "potions" => [],
        "items_dropped" => [],
        "functions" => ["spawnTree" => true, "explosion" => true, "dropItem" => true, "bedrock" => true, "prison" => true, "chest" => true, "teleport" => true, "potion" => true, "mob" => true, "execCmd" => true, "lightning" => true],
        "commands" => [],
        "mob" => [],
        "mob_explosion_delay" => 1
    ];

    public function onEnable()
    {
        $dataResources = $this->getDataFolder() . "/resources/";
        if (!file_exists($this->getDataFolder()))
            @mkdir($this->getDataFolder(), 0755, true);
        if (!file_exists($dataResources))
            @mkdir($dataResources, 0755, true);

        $this->setup = new Config($dataResources . "config.yml", Config::YAML, $this->data);
        $this->setup->save();

        $this->message = new Config($dataResources . "message.yml", Config::YAML, [
                "tree" => "Tree spammed",
                "explosion" => "BOOOM!!!",
                "drop" => "Lucky",
                "sign" => "It's your problem!",
                "signText" => "It's your problem!",
                "prison" => "OPS...",
                "unlucky" => "Try again maybe you will be more lucky",
                "spawn" => "Muahahahahha",
                "chest" => "You are very lucky!",
                "effect" => "Don't worry about a thing",
                "not_allowed" => "You are not authorized to use the plugin",
                "command" => "IDK",
                "mob" => "Surprise",
                "lightning" => "WTF"
            ]
        );
        $this->message->save();
        $this->reloadSetup();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("luckyblock", new Commands($this, $this->setup, $this->data));
    }

    public function reloadSetup(&$data = false, &$setup = false)
    {
        $this->setup->save();
        $this->data["status"] = $this->setup->get("status");

        if (!is_numeric($this->setup->get("max_chest_item")) || $this->setup->get("max_chest_item") < 1)
            $this->data["max_chest_item"] = 4;
        else
            $this->data["max_chest_item"] = $this->setup->get("max_chest_item");
        $this->data["level"] = $this->setup->get("level");

        if (!is_numeric($this->setup->get("explosion_min")) || !is_numeric($this->setup->get("explosion_max"))) {
            $this->data["explosion_min"] = 1;
            $this->data["explosion_max"] = 3;
        } else {
            if ($this->setup->get("explosion_min") >= 0 && $this->setup->get("explosion_max") >= 0) {
                $this->data["explosion_min"] = $this->setup->get("explosion_min");
                $this->data["explosion_max"] = $this->setup->get("explosion_max");
            }
        }

        $this->data["lucky_block"] = $this->getItem($this->setup->get("lucky_block"))->getId();
        if ($this->data["lucky_block"] === Block::AIR)
            $this->data["lucky_block"] = Block::SPONGE;

        if (is_numeric($this->setup->get("max_duration")) && $this->setup->get("max_duration") > 0)
            $this->data["max_duration"] = $this->setup->get("max_duration");

        foreach ($this->setup->get("potions") as $string) {
            if (Effect::getEffectByName($string) instanceof Effect)
                $this->data["potions"][] = $string;
        }
        $this->data["items_dropped"] = [];
        foreach ($this->setup->get("items_dropped") as $string) {
            $item = $this->getItem($string);
            if ($item->getId() !== Item::AIR && !$this->isExists($this->data["items_dropped"], $item))
                $this->data["items_dropped"][] = $item;
        }
        $fun = $this->setup->get("functions");
        foreach ($this->data["functions"] as $f => $v) {
            if (isset($fun[$f]))
                $this->data["functions"][$f] = $fun[$f];
        }
        if (is_numeric($this->setup->get("mob_explosion_delay")) && $this->setup->get("mob_explosion_delay") > 0)
            $this->data["mob_explosion_delay"] = $this->setup->get("mob_explosion_delay");
        $this->data["mob"] = [];
        foreach ($this->setup->get("mob") as $m) {
            if (!$this->isExistsEntity($m))
                $this->getLogger()->critical("The mob '" . $m . "' doesn't exists!");
            else if (!in_array($m, $this->data["mob"]))
                $this->data["mob"][] = $m;
        }
        $this->data["commands"] = $this->setup->get("commands");
        $data = $this->data;
        $setup = $this->setup;
    }

    public function getItem($string): Item
    {
        $e = explode(":", $string);
        $id = $e[0];
        if (is_numeric($id)) {
            $damage = 0;
            if (count($e) > 1) {
                $damage = $e[1];
            }
            return new Item($id, $damage, 1, 1);
        } else {
            $item = Item::fromString($id);
            if ($item->getId() !== Item::AIR) {
                $item->setCount(1);
                return $item;
            }
        }
        return new Item(0);
    }

    public function isExists(array $arr, Item $item): bool
    {
        foreach ($arr as $it) {
            if ($it instanceof Item) {
                if ($it->getId() == $item->getId() && $it->getDamage() == $item->getDamage())
                    return true;
            }
        }
        return false;
    }

    public function isExistsEntity($name): bool
    {
        $nbt = new CompoundTag("", [
            new ListTag("Pos", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0),
            ]),
            new ListTag("Rotation", [
                new FloatTag("", 0),
                new FloatTag("", 0),
            ])
        ]);
        $name = str_replace(" ", "", ucwords($name));
        $entity = Entity::createEntity($name, $this->getServer()->getDefaultLevel()->getChunk(0, 0, true), $nbt);
        if (!($entity instanceof Entity))
            return false;
        $entity->close();
        return true;
    }

    public function isAllowedWorld(Level $level): bool
    {
        if ($this->data["status"] !== "on")
            return false;

        $level = strtolower($level->getName());
        $get = $this->data["level"];
        if (count($get) <= 0)
            return true;
        else {
            foreach ($get as $l) {
                if (strtolower(trim($l)) === $level)
                    return true;
            }
        }
        return false;
    }

    /** Thanks to @dxm_hippie for this code */
    private function itemLoop(Player $player, Position $pos): bool
    {
        if (mt_rand(1, 2) === 2) {
            if (count($this->data["items_dropped"]) === 0)
                $item = $this->randItem();
            else
                $item = $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"]) - 1)];
            $player->getLevel()->dropItem($pos, $item);
            $this->itemLoop2($player, $pos);
            return true;
        }
        return false;
    }

    private function itemLoop2(Player $player, Position $pos)
    {
        if (mt_rand(1, 3) === 2) {
            for ($i = 1; $i <= 3; $i++) {
                if (count($this->data["items_dropped"]) === 0)
                    $item = $this->randItem();
                else
                    $item = $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"]) - 1)];
                $player->getLevel()->dropItem($pos, $item);
            }
            $this->itemLoop($player, $pos);
        }
    }

    /** END CODE */

    private function randItem(): Item
    {
        $o = mt_rand(1, Item::$list->getSize());
        $i = Item::$list[$o];
        while (is_null($i) && count(explode("item", $i)) > 0) {
            $o = mt_rand(1, Item::$list->getSize());
            $i = Item::$list[$o];
        }
        return new Item($o);
    }

    public function blockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        if ($block->getId() === $this->data["lucky_block"] && $this->isAllowedWorld($block->getLevel())) {
            $player = $event->getPlayer();
            if (!$player->hasPermission("luckyblock.use")) {
                $player->sendMessage($this->tag . $this->message->get("not_allowed"));
                return;
            }
            $event->setCancelled();
            $player->getLevel()->setBlock($block, new Block(Block::AIR), false, true);
            $rand = new Random();
            switch ($rand->nextRange(1, 12)) {
                case 1:
                    if (!isset($this->data["functions"]["spawnTree"]) || $this->data["functions"]["spawnTree"]) {
                        $type = Sapling::OAK;
                        switch ($rand->nextRange(0, 3)) {
                            case 1:
                                $type = Sapling::BIRCH;
                                break;
                            case 2:
                                $type = Sapling::SPRUCE;
                                break;
                            case 3:
                                $type = Sapling::JUNGLE;
                                break;
                        }
                        if ($player->getLevel()->setBlock($block, new Sapling($type), true, true)) {
                            $player->getLevel()->getBlock($block)->onActivate(new Item(Item::DYE, 15), $player);
                            $player->sendMessage($this->tag . $this->message->get("tree"));
                        }
                        break;
                    }
                case 2:
                    if (!isset($this->data["functions"]["explosion"]) || $this->data["functions"]["explosion"]) {
                        $explosion = new Explosion($block, mt_rand($this->data["explosion_min"], $this->data["explosion_max"]));
                        if ($explosion->explodeA())
                            $explosion->explodeB();
                        $player->sendMessage($this->tag . $this->message->get("explosion"));
                        break;
                    }

                case 3:
                    if (!isset($this->data["functions"]["dropItem"]) || $this->data["functions"]["dropItem"]) {
                        if (mt_rand(0, 1)) {
                            if ($this->itemLoop($player, $block))
                                break;
                        }
                        if (count($this->data["items_dropped"]) === 0)
                            $item = $this->randItem();
                        else
                            $item = $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"]) - 1)];
                        $player->getLevel()->dropItem($block, $item);
                        $player->sendMessage($this->tag . $this->message->get("drop"));
                        break;
                    }
                case 4:
                    if (!isset($this->data["functions"]["bedrock"]) || $this->data["functions"]["bedrock"]) {
                        $player->getLevel()->setBlock($block, new Block(Block::BEDROCK));
                        $p = new Position($block->x, $block->y + 1, $block->z, $block->level);
                        if ($player->getLevel()->getBlock($p)->getId() != Block::AIR)
                            break;
                        $block->getLevel()->setBlock($p, Block::get(Item::SIGN_POST));

                        $sign = new Sign($player->getLevel()->getChunk($block->x >> 4, $block->z >> 4), new CompoundTag(false, array(
                            new IntTag("x", (int)$block->x),
                            new IntTag("y", (int)$block->y + 1),
                            new IntTag("z", (int)$block->z),
                            new StringTag("Text1", $this->tag),
                            new StringTag("Text2", $this->message->get("signText"))
                        )));
                        $sign->spawnToAll();
                        $player->sendMessage($this->tag . $this->message->get("sign"));
                        break;
                    }
                case 5:
                    if (!isset($this->data["functions"]["prison"]) || $this->data["functions"]["prison"]) {
                        $pos = $event->getPlayer();
                        $pos->x = round($pos->x) + 0.5;
                        $pos->y = round($pos->y);
                        $pos->z = round($pos->z) + 0.5;
                        $player->teleport($pos, $player->getYaw(), $player->getPitch());
                        $arr = [];
                        switch ($rand->nextRange(1, 5)) {
                            case 1:
                                $player->getLevel()->setBlock(new Position($pos->x, $pos->y - 1, $pos->z, $pos->getLevel()), new Block(Block::OBSIDIAN));
                                for ($x = $pos->x - 1; $x <= $pos->x + 1; $x++) {
                                    for ($z = $pos->z - 1; $z <= $pos->z + 1; $z++) {
                                        if (!($x === $pos->x && $z === $pos->z)) {
                                            for ($y = $pos->y; $y <= $pos->y + 2; $y++)
                                                $player->getLevel()->setBlock(new Position($x, $y, $z, $pos->getLevel()), new Block(Block::OBSIDIAN));
                                        }
                                    }
                                }
                                $player->getLevel()->updateAround($pos);
                                $player->sendMessage($this->tag . $this->message->get("prison"));
                                break;
                            case 2:
                                $player->getLevel()->setBlock(new Position($pos->x, $pos->y - 1, $pos->z, $pos->getLevel()), new Block(Block::STILL_LAVA));
                                $player->getLevel()->setBlock(new Position($pos->x, $pos->y - 2, $pos->z, $pos->getLevel()), new Block(Block::GLASS));
                                for ($x = $pos->x - 1; $x <= $pos->x + 1; $x++) {
                                    for ($z = $pos->z - 1; $z <= $pos->z + 1; $z++) {
                                        if (!($x === $pos->x && $z === $pos->z)) {
                                            for ($y = $pos->y; $y <= $pos->y + 2; $y++)
                                                $player->getLevel()->setBlock(new Position($x, $y, $z, $pos->getLevel()), new Block(Block::IRON_BAR));
                                        }
                                    }
                                }
                                $player->getLevel()->updateAround($pos);
                                $player->sendMessage($this->tag . $this->message->get("prison"));
                                break;

                            case 3:
                                $player->getLevel()->setBlock(new Position($pos->x, $pos->y - 1, $pos->z, $pos->getLevel()), new Block(Block::SANDSTONE));
                                for ($x = $pos->x - 1; $x <= $pos->x + 1; $x++) {
                                    for ($z = $pos->z - 1; $z <= $pos->z + 1; $z++) {
                                        if (!($x === $pos->x && $z === $pos->z)) {
                                            for ($y = $pos->y; $y <= $pos->y + 2; $y++)
                                                $player->getLevel()->setBlock(new Position($x, $y, $z, $pos->getLevel()), new Block(Block::IRON_BAR));
                                        }
                                    }
                                }
                                break;

                            case 4:
                                $arr = [
                                    ["x" => -1, "y" => -1, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => -1, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => -1, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => -1, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => -1, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => -1, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => -1, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => -1, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => -1, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 0, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 0, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 0, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 0, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 0, "z" => 0, "block" => Block::STILL_WATER],
                                    ["x" => 0, "y" => 0, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 0, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 0, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 0, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 0, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 1, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 1, "z" => 0, "block" => Block::GLASS],
                                    ["x" => -1, "y" => 1, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 1, "z" => -1, "block" => Block::GLASS],
                                    ["x" => 0, "y" => 1, "z" => 0, "block" => Block::STILL_WATER],
                                    ["x" => 0, "y" => 1, "z" => 1, "block" => Block::GLASS],
                                    ["x" => 1, "y" => 1, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 1, "z" => 0, "block" => Block::GLASS],
                                    ["x" => 1, "y" => 1, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 2, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 2, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => -1, "y" => 2, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 2, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 2, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => 0, "y" => 2, "z" => 1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 2, "z" => -1, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 2, "z" => 0, "block" => Block::OBSIDIAN],
                                    ["x" => 1, "y" => 2, "z" => 1, "block" => Block::OBSIDIAN],
                                ];
                                break;
                            case 5:
                                $arr = [
                                    ["x" => -1, "y" => 0, "z" => -1, "block" => Block::STILL_LAVA],
                                    ["x" => -1, "y" => 0, "z" => 0, "block" => Block::STILL_LAVA],
                                    ["x" => -1, "y" => 0, "z" => 1, "block" => Block::STILL_LAVA],
                                    ["x" => 0, "y" => 0, "z" => -1, "block" => Block::STILL_LAVA],
                                    ["x" => 0, "y" => 0, "z" => 0, "block" => Block::STILL_LAVA],
                                    ["x" => 0, "y" => 0, "z" => 1, "block" => Block::STILL_LAVA],
                                    ["x" => 1, "y" => 0, "z" => -1, "block" => Block::STILL_LAVA],
                                    ["x" => 1, "y" => 0, "z" => 0, "block" => Block::STILL_LAVA],
                                    ["x" => 1, "y" => 0, "z" => 1, "block" => Block::STILL_LAVA],
                                    ["x" => -1, "y" => 1, "z" => -1, "block" => Block::COBWEB],
                                    ["x" => -1, "y" => 1, "z" => 0, "block" => Block::COBWEB],
                                    ["x" => -1, "y" => 1, "z" => 1, "block" => Block::COBWEB],
                                    ["x" => 0, "y" => 1, "z" => -1, "block" => Block::COBWEB],
                                    ["x" => 0, "y" => 1, "z" => 0, "block" => Block::COBWEB],
                                    ["x" => 0, "y" => 1, "z" => 1, "block" => Block::COBWEB],
                                    ["x" => 1, "y" => 1, "z" => -1, "block" => Block::COBWEB],
                                    ["x" => 1, "y" => 1, "z" => 0, "block" => Block::COBWEB],
                                    ["x" => 1, "y" => 1, "z" => 1, "block" => Block::COBWEB],
                                ];
                                break;
                        }
                        $pos = $player->getPosition();
                        foreach ($arr as $i => $c) {
                            $player->getLevel()->setBlock($pos->add($c["x"], $c["y"], $c["z"]), Block::get($c["block"]), true, true);
                        }
                        break;
                    }
                case 6:
                    if (!isset($this->data["functions"]["chest"]) || $this->data["functions"]["chest"]) {
                        $player->getLevel()->setBlock($block, new Block(Block::CHEST), true, true);
                        $nbt = new CompoundTag("", [
                            new ListTag("Items", []),
                            new StringTag("id", Tile::CHEST),
                            new IntTag("x", $block->x),
                            new IntTag("y", $block->y),
                            new IntTag("z", $block->z)
                        ]);
                        $nbt->Items->setTagType(NBT::TAG_Compound);
                        $tile = Tile::createTile("Chest", $block->getLevel()->getChunk($block->x >> 4, $block->z >> 4), $nbt);
                        if ($tile instanceof Chest) {
                            for ($i = 0; $i <= mt_rand(1, $this->data["max_chest_item"]); $i++) {
                                if (count($this->data["items_dropped"]) === 0)
                                    $item = $this->randItem();
                                else
                                    $item = $this->data["items_dropped"][mt_rand(0, count($this->data["items_dropped"]) - 1)];
                                $tile->getInventory()->setItem($i, $item);
                            }
                            $player->sendMessage($this->tag . $this->message->get("chest"));
                        }
                        break;
                    }

                case 7:
                    if (!isset($this->data["functions"]["teleport"]) || $this->data["functions"]["teleport"]) {

                        $player->teleport($player->getLevel()->getSpawnLocation(), $player->getYaw(), $player->getPitch());
                        $player->sendMessage($this->tag . $this->message->get("spawn"));
                        break;
                    }
                case 8:
                    if (!isset($this->data["functions"]["potion"]) || $this->data["functions"]["potion"]) {
                        if (count($this->data["potions"])) {
                            $effect = Effect::getEffectByName($this->data["potions"][$rand->nextRange(0, count($this->data["potions"]) - 1)]);
                            $effect->setDuration($rand->nextRange(20, $this->data["max_duration"] * 20));
                            $player->addEffect($effect);
                            $player->sendMessage($this->tag . $this->message->get("effect"));

                        } else
                            $player->sendMessage($this->tag . $this->message->get("unlucky"));
                        break;
                    }

                case 9: //exec command
                    if (!isset($this->data["functions"]["execCmd"]) || $this->data["functions"]["execCmd"]) {
                        if (count($this->data["commands"])) {
                            $cmd = $this->data["commands"][$rand->nextRange(0, count($this->data["commands"]) - 1)];
                            $cmd = str_replace(["%PLAYER%", "%X%", "%Y%", "%Z%", "%WORLD%", "%IP%", "%XP%"], [$player->getName(), $player->getX(), $player->getY(), $player->getZ(), $player->getLevel()->getName(), $player->getAddress(), $player->getXpLevel()], $cmd);
                            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                            $player->sendMessage($this->tag . $this->message->get("command"));
                            break;
                        }
                    }
                case 10://mob
                    if (!isset($this->data["functions"]["mob"]) || $this->data["functions"]["mob"]) {

                        if (count($this->data["mob"])) {
                            $mob = $this->data["mob"][$rand->nextRange(0, count($this->data["mob"]) - 1)];
                            if ($this->isExistsEntity($mob)) {
                                $nbt = new CompoundTag("", [
                                    new ListTag("Pos", [
                                        new DoubleTag("", $block->getX()),
                                        new DoubleTag("", $block->getY()),
                                        new DoubleTag("", $block->getZ()),
                                    ]),
                                    new ListTag("Rotation", [
                                        new FloatTag("", $player->getYaw()),
                                        new FloatTag("", $player->getPitch()),
                                    ]),
                                    new StringTag("CustomName", $this->tag)
                                ]);
                                $entity = Entity::createEntity($mob, $player->getLevel()->getChunk($block->getX() >> 4, $block->getZ() >> 4), $nbt);
                                if ($entity instanceof Entity) {
                                    $entity->spawnToAll();
                                    $this->getServer()->getScheduler()->scheduleDelayedTask(new TaskExplodeMob($this, $entity, mt_rand($this->data["explosion_min"], $this->data["explosion_max"])), 20 * mt_rand(1, $this->data["mob_explosion_delay"]));
                                    $player->sendMessage($this->tag . $this->message->get("mob"));
                                    break;
                                }
                            }
                        }
                    }

                case 11:
                    if (!isset($this->data["functions"]["lightning"]) || $this->data["functions"]["lightning"]) {

                        $player->getLevel()->spawnLightning($player);
                        $player->sendMessage($this->tag . $this->message->get("lightning"));
                        break;
                    }
                case 12:
                    $player->sendMessage($this->tag . $this->message->get("unlucky"));
                    break;
            }
            $player->getLevel()->save();
        }
    }
} 
   
