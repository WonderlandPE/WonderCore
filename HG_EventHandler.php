<?php
namespace SimpleHungerGames;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
class EventHandler implements Listener{
    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }
    public function onPreLogin(PlayerPreLoginEvent $event){
        if($this->plugin->ingame == true) {
            $event->getPlayer()->close("Match running.");
        }
    }
    public function onJoin(PlayerJoinEvent $event){
        $spawn = $this->plugin->getNextSpawn();
        $event->getPlayer()->teleport($spawn);
        $this->plugin->players = $this->plugin->players + 1;
        $event->setJoinMessage("[HG] ".$event->getPlayer()->getName()." joined the match!");
        if(!$this->plugin->points->exists($event->getPlayer()->getName())){
            $this->plugin->points->set($event->getPlayer()->getName(), array("kills" => 0, "deaths" => 0));
        }
    }
    public function onQuit(PlayerQuitEvent $event){
        $this->plugin->players = $this->plugin->players - 1;
        $event->setQuitMessage("[HG] ".$event->getPlayer()->getName()." left the match!");
        if($this->plugin->players <= 1){
            $this->plugin->getServer()->broadcastMessage("[HG] Game ended!");
            $this->plugin->getServer()->shutdown();
        }
    }
    public function onDeath(PlayerDeathEvent $event){
        $this->plugin->players = $this->plugin->players - 1;
        $name = $event->getEntity()->getName();
        $d = $this->plugin->points->getNested("$name.kills") + 1;
        $this->plugin->points->setNested("$name.kills", $d);
        $killer = $event->getEntity()->getLastDamageCause()->getCause()->getDamager();
        if($killer instanceof Player){
            $name = $killer->getName();
            $k = $this->plugin->points->getNested("$name.kills") + 1;
            $this->plugin->points->setNested("$name.kills", $k);
        }
        $event->getEntity()->kick("Death");
        $event->setDeathMessage("[HG] ".$event->getEntity()->getName()." died!\nThere are ".$this->plugin->players." left.");
        if($this->plugin->players <= 1){
            $this->plugin->getServer()->broadcastMessage("[HG] Game ended!");
            $this->plugin->getServer()->shutdown();
        }
    }
    public function onChat(PlayerChatEvent $event){
        if($this->plugin->prefs->get("chat_format") == true){
            $event->setFormat("[k:".$this->plugin->points->get($event->getPlayer()->getName())["kills"]."] [d:".$this->plugin->points->get($event->getPlayer()->getName())["deaths"]."] ".$event->getPlayer()->getName().": ".$event->getMessage());
        }
    }
    public function onBlockPlace(BlockPlaceEvent $event){
        if(!$event->getPlayer()->isOp()) $event->setCancelled();
    }
    public function onBlockBreak(BlockBreakEvent $event){
        if(!$event->getPlayer()->isOp()) $event->setCancelled();
    }
}
