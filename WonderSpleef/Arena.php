<?php

namespace SimpleSpleef\Arena;

use pocketmine\block\Block;
use pocketmine\block\Snow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemBlock;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use SimpleSpleef\Main;

class Arena implements Listener{

    //Name of the arena
    private $arena_name;

    //Array of players that are in the arena
    public $players = array();

    //Arena spawn
    private $spawn;

    //The main plugin
    private $plugin;

    //All broken snow blocks
    private $broken = array();

    //Arena enabled
    public $enabled = false;

    //Arena active? (game running)
    public $active = false;

    //Second
    public $second = 60;

    //Deepest floor
    private $floor = 0;

    /*
     * Create a new arena
     */
    public function __construct($name, $spawn, Main $main)
    {
        $this->setName($name);
        $this->setSpawn($spawn);
        $this->plugin = $main;
        $this->second = $this->plugin->getConfig()->get("wait");
    }

    /*
     * Resets the arena
     */
    public function resetArena()
    {
        foreach($this->players as $p)
        {
            if($p instanceof Player)
            {
                $p->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD."You have won!");
                $this->removePlayer($p);
                if($this->plugin->getConfig()->get("display") == true)
                {
                    $this->plugin->getServer()->broadcastMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD.$p->getDisplayName()." has won spleef in Arena ".$this->getArenaName());
                }
            }
        }
        $this->enabled = false;
        foreach($this->broken as $block)
        {
            if($block instanceof Position)
            {
                $level = $block->getLevel();
                $x = $block->getX();
                $y = $block->getY();
                $z = $block->getZ();
                $level->setBlock(new Vector3($x, $y, $z), Block::get(Block::SNOW_BLOCK));
            }
        }
        $this->second = $this->plugin->getConfig()->get("wait");
        $this->broken = array();
        $this->active = false;
        $this->enabled = true;
    }

    /*
     * Sets floor
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;
    }

    /*
     * Gets floor
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /*
     * Add a player to the arena
     * Returns: void
     */
    public function addPlayer(Player $player)
    {
        if($this->enabled == true)
        {
            if($this->active == false)
            {
                if(count($this->players) < $this->plugin->getConfig()->get("maxplayers"))
                {
                    $this->players[$player->getName()] = $player;
                    if(isset($this->players[$player->getName()]))
                    {
                        $player->arena = $this;
                        $player->teleport($this->getSpawn());
                        $player->prevGamemode = $player->getGamemode();
                        $player->setGamemode(0);
                        $player->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD."Joined arena '".$this->getArenaName()."'");
                        return true;
                    }
                    else
                    {
                        $player->sendMessage("Error while joining...");
                        return false;
                    }
                }
                else
                {
                    $player->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD."This arena is full");
                    return false;
                }
            }
            else
            {
                $player->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD."A game is already running in this arena.");
            }
        }
        else
        {
            $player->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD."This arena is disabled.");
            return false;
        }
    }

    /*
     * Remove a player from the arena
     * Returns: bool
     */
    public function removePlayer(Player $player)
    {
        if(isset($this->players[$player->getName()]))
        {
            unset($this->players[$player->getName()]);
            unset($player->arena);
            $player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
            $player->setGamemode($player->prevGamemode);
            unset($player->prevGamemode);
            $player->getInventory()->remove($player->breakItem);
            unset($player->breakItem);
            return true;
        }
        else
        {
            $player->sendMessage("You couldn't be removed from the arena.");
        }
    }

    /*
     * Getters
     */
    public function getArenaName()
    {
        return $this->arena_name;
    }

    public function getSpawn()
    {
        return $this->spawn;
    }

    /*
     * Setters
     */
    public function setName($name)
    {
        $this->arena_name = $name;
    }

    public function setSpawn(Position $pos)
    {
        $this->spawn = $pos;
    }

    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getEntity();
        if(isset($this->players[$player->getName()]))
        {
            /*
             * Remove a player from the arena when it dies
             */
            $this->removePlayer($player);
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        if(isset($this->players[$player->getName()]))
        {
            /*
             * Remove a player from the arena when it disconnects
             */
            $this->removePlayer($player);
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        if($this->active == false and isset($this->players[$event->getPlayer()->getName()]))
        {
            $event->setCancelled();
        }
        if(isset($this->players[$event->getPlayer()->getName()]))
        {
            if($event->getBlock()->getID() != $this->plugin->getConfig()->get("surface"))
            {
                $event->setCancelled();
            }
            else
            {
                if($this->active == true)
                {
                    $block = $event->getBlock();
                    $event->setInstaBreak(true);
                    $block = new Position($block->getX(), $block->getY(), $block->getZ(), $block->getLevel());
                    $this->broken[] = $block;
                }
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if(isset($this->players[$player->getName()]))
        {
            $event->setCancelled();
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();

        //Check if player lands on ground block
        if(isset($player->arena))
        {
            if(isset($this->players[$player->getName()]))
            {
                if($player->getFloorY() == $this->getFloor())
                {
                    foreach($this->players as $p)
                    {
                        if($p instanceof Player)
                        {
                            $p->sendMessage(TextFormat::AQUA."[SimpleSpleef] ".TextFormat::GOLD.$player->getDisplayName()." lost");
                        }
                    }
                    $this->removePlayer($player);
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $player = $event->getEntity();
        if($player instanceof Player)
        {
            if(isset($player->arena))
            {
                if($this->plugin->getConfig()->get("pvp") == false)
                {
                    $event->setCancelled();
                }
            }
        }
    }

} 
