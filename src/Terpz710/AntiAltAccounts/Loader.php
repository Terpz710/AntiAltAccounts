<?php

declare(strict_types=1);

namespace Terpz710\AntiAltAccounts;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use DateTime;

class Loader extends PluginBase implements Listener {

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $ip = $player->getNetworkSession()->getIp();

        $this->saveIP($ip, $player->getName());

        if ($this->isAltIP($ip, $player->getName())) {
            $banOrKick = $this->getConfig()->get("Ban-Or-Kick", "ban");
            $banMessage = $this->getConfig()->get("Ban-Message");
            
            if ($banOrKick === "ban") {
                $banReason = $this->getConfig()->get("Ban-Reason", "Alt Account Detected");
                $banDuration = $this->getConfig()->get("Ban-Duration", "NULL");

                if ($banDuration === "NULL") {
                    $banExpiry = null;
                } else {
                    $banExpiry = (new DateTime())->modify("+$banDuration")->getTimestamp();
                }

                $player->kick($banMessage);
                $this->getServer()->getNameBans()->addBan($ip, $banReason, $banExpiry, $player->getName());
            } else {//Add discord logs
                $player->kick($banMessage);
            }
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
