<?php

namespace mamayadesu;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerChatEvent;

class healthy extends PluginBase implements Listener {

private $cfg;

    public function onEnable() {
        if(! file_exists($this->getDataFolder())) @mkdir($this->getDataFolder());
        $this->cfg = new Config($this->getDataFolder()."config.yml", Config::YAML);
        if(empty($this->cfg->get("symbol"))) {
            $this->cfg->set("symbol", "|");
            $this->cfg->save();
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $s = $this->cfg->get("symbol");
        $player = $event->getPlayer();
        $hp = $player->getHealth();
        $format = $event->getFormat();
        if($hp == 20) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 19) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s.$s.$s."§7".$s."§f]", $format);
        if($hp == 18) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s.$s.$s."§8".$s."§f]", $format);
        if($hp == 17) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s.$s."§7".$s."§8".$s."§f]", $format);
        if($hp == 16) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s.$s."§8".$s.$s."§f]", $format);
        if($hp == 15) $format = str_replace("{HEALTHY}", "§f[§2".$s.$s.$s.$s.$s.$s.$s."§7".$s."§8".$s.$s."§f]", $format);
        if($hp == 14) $format = str_replace("{HEALTHY}", "§f[§a".$s.$s.$s.$s.$s.$s.$s."§8".$s.$s.$s."§f]", $format);
        if($hp == 13) $format = str_replace("{HEALTHY}", "§f[§a".$s.$s.$s.$s.$s.$s."§7".$s."§8".$s.$s.$s."§f]", $format);
        if($hp == 12) $format = str_replace("{HEALTHY}", "§f[§a".$s.$s.$s.$s.$s.$s."§8".$s.$s.$s.$s."§f]", $format);
        if($hp == 11) $format = str_replace("{HEALTHY}", "§f[§a".$s.$s.$s.$s.$s."§7".$s."§8".$s.$s.$s.$s."§f]", $format);
        if($hp == 10) $format = str_replace("{HEALTHY}", "§f[§a".$s.$s.$s.$s.$s."§8".$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 9) $format = str_replace("{HEALTHY}", "§f[§e".$s.$s.$s.$s."§7".$s."§8".$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 8) $format = str_replace("{HEALTHY}", "§f[§e".$s.$s.$s.$s."§8".$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 7) $format = str_replace("{HEALTHY}", "§f[§6".$s.$s.$s."§7".$s."§8".$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 6) $format = str_replace("{HEALTHY}", "§f[§6".$s.$s.$s."§8".$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 5) $format = str_replace("{HEALTHY}", "§f[§6".$s.$s."§7".$s."§8".$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 4) $format = str_replace("{HEALTHY}", "§f[§c".$s.$s."§8".$s.$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 3) $format = str_replace("{HEALTHY}", "§f[§c".$s."§7".$s."§8".$s.$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 2) $format = str_replace("{HEALTHY}", "§f[§4".$s."§8".$s.$s.$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 1) $format = str_replace("{HEALTHY}", "§f[§8".$s.$s.$s.$s.$s.$s.$s.$s.$s.$s."§f]", $format);
        if($hp == 0) $format = str_replace("{HEALTHY}", "§f[§4DEATH§f]", $format);
        $event->setFormat($format);
    }
}
