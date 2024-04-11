<?php

declare(strict_types=1);

namespace Terpz710\AntiAltAccounts;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase implements Listener {

    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $ip = $player->getNetworkSession()->getIp();

        $this->saveIP($ip, $player->getName());

        if ($this->isAltIP($ip, $player->getName())) {
            $player->kick($this->getConfig()->get("Ban-Message"));
            $this->getServer()->getNameBans()->addBan($ip, "Alt Account Detected", NULL, $player->getName());
        }
    }

    private function saveIP(string $ip, string $playerName): void {
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        if (!$data->exists($ip)) {
            $data->set($ip, $playerName);
            $data->save();
        }
    }

    private function isAltIP(string $ip, string $playerName): bool {
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        return $data->exists($ip) !== false && $data->get($ip) !== $playerName;
    }
}
