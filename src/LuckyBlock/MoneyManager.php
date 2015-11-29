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

use pocketmine\plugin\Plugin;
class MoneyManager{
    /** @var Plugin */
    private $PocketMoney = false, $EconomyS = false, $MassiveEconomy = false;
    /** @var bool */
    private $loaded = false;

    public function __construct(Main $main){
        $pluginManager = $main->getServer()->getPluginManager();
        if($pluginManager->getPlugin("PocketMoney") instanceof Plugin){
            $version = explode(".", $pluginManager->getPlugin("PocketMoney")->getDescription()->getVersion());
            if($version[0] >= 4){
                $this->loaded = true;
                $this->PocketMoney = $pluginManager->getPlugin("PocketMoney");
            }
        }elseif($pluginManager->getPlugin("EconomyAPI") instanceof Plugin){
            $version = explode(".", $pluginManager->getPlugin("EconomyAPI")->getDescription()->getVersion());
            if($version[0] >= 2) {
                $this->loaded = true;
                $this->EconomyS = $pluginManager->getPlugin("EconomyAPI");
            }
        }elseif($pluginManager->getPlugin("MassiveEconomy") instanceof Plugin) {
            $this->loaded = true;
            $this->MassiveEconomy = $pluginManager->getPlugin("MassiveEconomy");
        }
        else{
            $this->loaded = false;
            $main->getLogger()->critical("Install the plugin PocketMoney or EconomyS or MassiveEconomy for a better experience.");
        }
    }

    public function getValue(){
        if($this->PocketMoney) return "pm";
        if($this->EconomyS) return "$";
        if($this->MassiveEconomy) return $this->MassiveEconomy->getMoneySymbol();
        return "?";
    }

    public function getMoney($player){
        if($this->PocketMoney) return $this->PocketMoney->getMoney($player);
        if($this->EconomyS) return $this->EconomyS->myMoney($player);
        if($this->MassiveEconomy) return $this->MassiveEconomy->getMoney($player);
        return 0;
    }

    public function addMoney($player, $value){
        if($this->PocketMoney) $this->PocketMoney->setMoney($player, $this->getMoney($player) + $value);
        elseif($this->EconomyS) $this->EconomyS->setMoney($player, $this->getMoney($player) + $value);
        elseif($this->MassiveEconomy) $this->MassiveEconomy->setMoney($player, $this->getMoney($player) + $value);
        return false;
    }

    public function isExists($player){
        if($this->PocketMoney) return $this->PocketMoney->isRegistered($player);
        elseif($this->EconomyS) return $this->EconomyS->accountExists($player);
        elseif($this->MassiveEconomy) return $this->MassiveEconomy->isPlayerRegistered($player);
        return false;
    }

    public function isLoaded(){
        return $this->loaded;
    }
}