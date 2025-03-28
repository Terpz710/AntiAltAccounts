<?php

declare(strict_types=1);

namespace Terpz710\AntiAltAccounts;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\utils\Config;

use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Embed;

use DaPigGuy\libPiggyUpdateChecker\libPiggyUpdateChecker;

use DateTime;

final class Loader extends PluginBase implements Listener {

    private Webhook $webhook;

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();

        $webhookUrl = $this->getConfig()->get("discord-webhook-url");
        
        if($webhookUrl !== null && filter_var($webhookUrl, FILTER_VALIDATE_URL)){
            $this->webhook = new Webhook($webhookUrl);
        }

        libPiggyUpdateChecker::init($this);
    }

    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $ip = hash('sha256', $player->getNetworkSession()->getIp());

        $this->saveIP($ip, $player->getName());

        if ($this->isAltIP($ip, $player->getName())) {
            $banOrKick = $this->getConfig()->get("Ban-Or-Kick", "ban");
            $banMessage = $this->getConfig()->get("Ban-Message");
            
            if ($banOrKick === "ban") {
                $banReason = $this->getConfig()->get("Ban-Reason", "Alt Account Detected");
                $banDuration = $this->getConfig()->get("Ban-Duration", "NULL");

                $banExpiry = null;
                if ($banDuration !== "NULL") {
                    try {
                        $banExpiry = (new DateTime())->modify("+$banDuration");
                    } catch (\Exception $e) {
                        $this->getLogger()->error("Invalid ban duration format: $banDuration");
                        return;
                    }
                }

                $player->kick($banMessage);
                $this->getServer()->getNameBans()->addBan($ip, $banReason, $banExpiry, $player->getName());

                $this->sendDiscordAlert($player->getName(), $ip, "Banned", $banReason);
            } else {
                $player->kick($banMessage);
                $this->sendDiscordAlert($player->getName(), $ip, "Kicked", "Alt Account Detected");
            }
        }
    }

    private function saveIP(string $ip, string $playerName) : void{
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        if (!$data->exists($ip)) {
            $data->set($ip, $playerName);
            $data->save();
        }
    }

    private function isAltIP(string $ip, string $playerName) : bool{
        $data = new Config($this->getDataFolder() . "ip_data.json", Config::JSON);
        return $data->exists($ip) !== false && $data->get($ip) !== $playerName;
    }

    private function sendDiscordAlert(string $playerName, string $ip, string $action, string $reason): void {
        if(isset($this->webhook) && $this->webhook->isValid()){
            $message = new Message();
            $message->setUsername("AntiAltAccounts Bot");

            $embed = new Embed();
            $embed->setTitle("Alt Account Detected");
            $embed->setDescription("**Player:** $playerName\n**Action:** $action\n**Reason:** $reason");
            $embed->setColor(0xFF0000);
            $embed->setTimestamp(new DateTime());

            $message->addEmbed($embed);
            $this->webhook->send($message);
        }
    }
}
