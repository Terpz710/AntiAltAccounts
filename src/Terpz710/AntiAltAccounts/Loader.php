<?php

declare(strict_types=1);

namespace Terpz710\AntiAltAccounts;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase implements Listener {

    public function onLoad(): void{
        $this->getLogger()->info("AntiAltAccounts has been enabled!");
    }

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void{
        $this->getLogger()->info("AntiAltAccounts has been disabled!");
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $ip = $player->getNetworkSession()->getIp();
        $playerName = $player->getName();
        
        if ($this->isAltIP($playerName, $ip)) {
            $player->kick(TF::RED . "Your account has been §cbanned§f for being having alt!");
            $this->getServer()->getIPBans()->addBan($ip, "Alt Account Detected", null, $playerName);
            return;
        }
        $this->savePlayerIP($playerName, $ip);
    }

    private function isAltIP(string $playerName, string $ip): bool {
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        return $data->exists($playerName) && $data->get($playerName) === $ip;
    }

    private function savePlayerIP(string $playerName, string $ip) {
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        $data->set($playerName, $ip);
        $data->save();
    }
}