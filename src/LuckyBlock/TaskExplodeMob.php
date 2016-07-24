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

use pocketmine\entity\Entity;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class TaskExplodeMob extends PluginTask
{
    /** @var Entity */
    private $entity;
    /** @var int */
    private $radius;

    public function __construct(Plugin $owner, Entity $entity, $radius = 3)
    {
        parent::__construct($owner);
        $this->entity = $entity;
        $this->radius = $radius;
    }

    public function onRun($currentTick)
    {
        $explosion = new Explosion(new Position($this->entity->getX(), $this->entity->getY(), $this->entity->getZ(), $this->entity->getLevel()), $this->radius);
        if (!$this->entity->isAlive())
            return;
        $this->entity->close();
        if ($explosion->explodeA())
            $explosion->explodeB();
    }
}
