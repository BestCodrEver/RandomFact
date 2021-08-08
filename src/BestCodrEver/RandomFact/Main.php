<?php

/*
  ____            _    _____          _      ______              
 |  _ \          | |  / ____|        | |    |  ____|             
 | |_) | ___  ___| |_| |     ___   __| |_ __| |____   _____ _ __ 
 |  _ < / _ \/ __| __| |    / _ \ / _` | '__|  __\ \ / / _ \ '__|
 | |_) |  __/\__ \ |_| |___| (_) | (_| | |  | |___\ V /  __/ |   
 |____/ \___||___/\__|\_____\___/ \__,_|_|  |______\_/ \___|_|   

This plugin was made by BestCodrEver.
Discord: FaithlessMC#7013

Special thanks to Javier Leon9966#0001 for telling me how to use async tasks.
*/

namespace BestCodrEver\RandomFact;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;

class Main extends PluginBase implements Listener
{
    public $config;
    public function onEnable()
    {
        $this->getServer()
            ->getPluginManager()
            ->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (int $currentTick): void {
                $this->getServer()
                    ->getAsyncPool()
                    ->submitTask(
                        new class($this->config) extends AsyncTask {
                            public function __construct(Config $config)
                            {
                                $this->storeLocal($config);
                            }
                            public function onRun(): void
                            {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_URL, 'https://useless-facts.sameerkumar.website/api');
                                $factInJson = curl_exec($ch);
                                $fact = json_decode($factInJson);
                                $this->setResult($fact);
                            }
                            public function onCompletion(Server $server): void
                            {
                                $fact = $this->getResult();
                                if ($fact === null) {
                                    return;
                                }

                                $tags = [
                                    "{fact}",
                                    "{reset}",
                                    "{bold}",
                                    "{italic}",
                                    "{obfuscated}",
                                    "{strike}",
                                    "{under}",
                                    "{aqua}",
                                    "{black}",
                                    "{blue}",
                                    "{darkaqua}",
                                    "{darkblue}",
                                    "{darkgrey}",
                                    "{darkgreen}",
                                    "{darkpurple}",
                                    "{darkred}",
                                    "{gold}",
                                    "{grey}",
                                    "{green}",
                                    "{lightpurple}",
                                    "{red}",
                                    "{white}",
                                    "{yellow}",
                                ];

                                $replacements = ["{$fact->data}", "§r", "§l", "§o", "§k", "§m", "§n", "§b", "§0", "§9", "§3", "§1", "§8", "§2", "§5", "§4", "§6", "§7", "§a", "§d", "§c", "§f", "§e"];
                                $config = $this->fetchLocal();
                                $formattedFact = str_replace($tags, $replacements, $config->get("fact-msg"));

                                foreach ($server->getLevelByName($config->get("world"))->getPlayers() as $p) {
                                    $p->sendMessage($formattedFact);
                                }
                            }
                        }
                    );
            }),
            $this->config->get("fact-delay")
        );
    }
}