<?php
namespace SimpleHungerGames;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\Config;
class Main extends PluginBase{
    /**@var Config*/
    public $prefs;
    public $ingame = false;
    public $players = 0;
    public $totalminutes;
    public $minute;
    public $spawns = 0;
    /**@var Config*/
    public $points;
    const DEV = "luca28pet";
    const VER = "1.0beta";
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        @mkdir($this->getDataFolder());
        $this->prefs = new Config($this->getDataFolder()."prferences.yml", Config::YAML, array
            (
                "world" => "worldname",
                "players" => 16,
                "spawn_locs" => array(
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                    array(1,2,3),
                ),
                "minplayers" => 4,
                "waiting_time" => 5,
                "game_time" => 7,
                "deathmatch_time" => 3,
                "chat_format" => true,
                "chest_items" => array(
                    array(252, 0, 1),
                    array(222, 0, 1)
                ),
                "show_top_stats" => 5
            )
        );
        $this->prefs->save();
        $this->points = new Config($this->getDataFolder()."points.yml", Config::YAML);
        $this->totalminutes = $this->prefs->get("waiting_time") + $this->prefs->get("game_time") + $this->prefs->get("deathmatch_time");
        $this->minute = $this->prefs->get("waiting_time") + $this->prefs->get("game_time") + $this->prefs->get("deathmatch_time");
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "schedule"]), 1200); //1200 = 1 tick * 20 ticks per second * 60 seconds
        $this->getServer()->setDefaultLevel($this->getServer()->getLevelByName($this->prefs->get("world")));
        $this->refillChests();
    }
    public function onDisable(){
        $this->prefs->save();
        $this->points->save();
    }
    public function schedule(){
        $this->minute--;
        if($this->minute <= $this->totalminutes and $this->minute > ($this->totalminutes - $this->prefs->get("waiting_time"))) {
            $this->getServer()->broadcastMessage("[HG] Match will start in " . $this->totalminutes - $this->minute);
        }elseif($this->minute == ($this->totalminutes - $this->prefs->get("waiting_time"))){
            if($this->players >= $this->prefs->get("minplayers")){
                $this->getServer()->broadcastMessage("[HG] Game starts NOW!!!");
                $this->ingame = true;
            }else{
                $this->getServer()->broadcastMessage("[HG] There are not enough players to begin the match.");
                $this->minute = $this->totalminutes;
            }
        }elseif($this->minute < ($this->totalminutes - $this->prefs->get("waiting_time")) and $this->minute > ($this->totalminutes - $this->prefs->get("waiting_time") - $this->prefs->get("game_time"))){
            $timetodm = $this->totalminutes - $this->minute + $this->prefs->get('deathmatch_time');
            $this->getServer()->broadcastMessage("[HG] DeathMatch starts in ".$timetodm." minutes.");
        }elseif($this->minute == ($this->totalminutes - $this->prefs->get("waiting_time") - $this->prefs->get("game_time"))){
            $this->getServer()->broadcastMessage("[HG] DeathMatch starts NOW!");
            $this->getServer()->broadcastMessage("[HG] Chest has been refilled!");
            $this->spawns = 0;
            foreach($this->getServer()->getOnlinePlayers() as $p){
                $p->teleport($this->getNextSpawn());
            }
            $this->refillChests();
        }elseif($this->minute < ($this->totalminutes - $this->prefs->get("waiting_time") - $this->prefs->get("game_time")) and $this->minute > 0){
            $timeleft = $this->totalminutes - $this->prefs->get("waiting_time") - $this->prefs->get("game_time") - $this->prefs->get("deathmatch_time") + $this->minute;
            $this->getServer()->broadcastMessage("[HG] ".$timeleft." minutes left");
        }elseif($this->minute == 0){
            $this->getServer()->broadcastMessage("[HG] Game ended!");
            $this->getServer()->shutdown();
        }
    }
    public function getNextSpawn(){
        $this->spawns++;
        $x = $this->prefs->get('spawn_locs')[$this->spawns][0];
        $y = $this->prefs->get('spawn_locs')[$this->spawns][1];
        $z = $this->prefs->get('spawn_locs')[$this->spawns][2];
        return (new Vector3($x, $y, $z));
    }
    public function refillChests(){
        foreach($this->getServer()->getLevelByName($this->prefs->get("world"))->getTiles() as $t){
            if($t instanceof Chest){
                if($t->isPaired()){
                    $inv = $t->getInventory();
                }else{
                    $inv = $t->getRealInventory();
                }
                $inv->clearAll();
                foreach($this->prefs->get('chest_items') as $i){
                    $inv->addItem(Item::get($i[0], $i[1], $i[2]));
                }
            }
        }
    }
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        if($command->getName() == "hg"){
            if(!isset($args[0])){
                $sender->sendMessage("[HG] HungerGames plugin by ".self::DEV.", version ".self::VER);
                $sender->sendMessage("[HG] Type /hg help for a list of commands");
                return true;
            }
            $subCommand = array_shift($args);
            switch($subCommand){
                case "help":
                    $sender->sendMessage("[HG] Commands for hg:");
                    $sender->sendMessage("[HG] /hg stat [player]: see a player stats");
                    return true;
                break;
                case "stat":
                    if(!isset($args[0])){
                        if(!$this->points->exists($sender->getName())){
                            $sender->sendMessage("[HG] Stats not found");
                            return true;
                        }
                        $name = $sender->getName();
                        $kills = $this->points->getNested("$name.kills");
                        $deaths = $this->points->getNested("$name.deaths");
                        $sender->sendMessage("[HG] Your stats:");
                        $sender->sendMessage("[HG] Kills: ".$kills);
                        $sender->sendMessage("[HG] Deaths: ".$deaths);
                        return true;
                    }
                    $name = $args[0];
                    if(!$this->points->exists($name)){
                        $sender->sendMessage("[HG] Stats not found");
                        return true;
                    }
                    $kills = $this->points->getNested("$name.kills");
                    $deaths = $this->points->getNested("$name.deaths");
                    $sender->sendMessage("[HG] ".$name." stats:");
                    $sender->sendMessage("[HG] Kills: ".$kills);
                    $sender->sendMessage("[HG] Deaths: ".$deaths);
                    return true;
                break;
            }
        }
        return true;
    }
}
