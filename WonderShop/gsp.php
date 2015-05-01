<?php

namespace mamayadesu\gsp;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;

class gsp extends PluginBase implements CommandExecutor, Listener{

private $configfile;

public function onEnable()
    {
        if(! file_exists($this->getDataFolder()."config.yml"))
        {
            mkdir($this->getDataFolder());
            $this->configfile = new Config($this->getDataFolder()."config.yml", Config::YAML);
            $this->getConfig()->set("enable_logger", true);
            $this->getConfig()->set("mysql_addr", "127.0.0.1");
            $this->getConfig()->set("mysql_user", "root");
            $this->getConfig()->set("mysql_pass", "passwd");
            $this->getConfig()->set("mysql_base", "db");
            $this->getConfig()->set("mysql_port", 3306);
            $this->getConfig()->set("mysql_table", "giantshoppe");
            $this->getConfig()->set("mysql_column_id", "id");
            $this->getConfig()->set("mysql_column_itemid", "item_id");
            $this->getConfig()->set("mysql_column_count", "count");
            $this->getConfig()->set("mysql_column_price", "price");
            $this->getConfig()->save();
        }
        
        $this->getLogger()->info("Loading GiantShopPE v1.0 by MamayAdesu...");
        
        $this->link = @mysqli_connect(
         $this->getConfig()->get("mysql_addr"),
         $this->getConfig()->get("mysql_user"),
         $this->getConfig()->get("mysql_pass"),
         $this->getConfig()->get("mysql_base"),
         $this->getConfig()->get("mysql_port")
        ) or die("FAILED TO CONNECT TO MYSQL SERVER!");
        
        $this->getLogger()->info("Successful connected to MySQL!");

        @mysqli_query($this->link, "

        CREATE TABLE IF NOT EXISTS `".$this->getConfig()->get("mysql_table")."` (
          `".$this->getConfig()->get("mysql_column_id")."` int(11) NOT NULL AUTO_INCREMENT,
          `".$this->getConfig()->get("mysql_column_itemid")."` varchar(255) NOT NULL,
          `".$this->getConfig()->get("mysql_column_count")."` varchar(255) NOT NULL DEFAULT '0',
          `".$this->getConfig()->get("mysql_column_price")."` varchar(255) NOT NULL,
          PRIMARY KEY (`".$this->getConfig()->get("mysql_column_id")."`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

        ") or die("FAILED TO USE MYSQL COMMAND! QUERY 1");
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
public function onDisable()
    {
        @mysqli_close($this->link);
        $this->getLogger()->info("Disabling GiantShopPE v1.0 by MamayAdesu...");
    }

public function onCommand(CommandSender $sender, Command $command, $label, array $params)
    {
        $username = $sender->getName();
        $player = $this->getServer()->getPlayer($username);
        
        switch($command->getName())
        {
            case "shop":
                $action = array_shift($params);
                if($action == "buy")
                    {
                        if($player instanceof Player)
                            {
                                $itemid = array_shift($params);
                                $balance = $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->getMoney($username);
                                if(! empty($itemid))
                                    {
                                        $query = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_itemid")."`='$itemid'");
                                        if(@mysqli_num_rows($query))
                                            {
                                                $array = @mysqli_fetch_array($query);
                                                if($balance == $array[$this->getConfig()->get("mysql_column_price")] || $balance > $array[$this->getConfig()->get("mysql_column_price")])
                                                    {
                                                        $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $array[$this->getConfig()->get("mysql_column_itemid")]);
                                                        $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $array[$this->getConfig()->get("mysql_column_itemid")]);
                                                        $fullitem = Item::get($item, $damage, $array[$this->getConfig()->get("mysql_column_count")]);
                                                        $fullitem = preg_replace("/x([0-9]+)/s", "", $fullitem);
                                                        $fullitem = str_replace("Item ", "", $fullitem);
                                                        $newbalance = $balance - $array[$this->getConfig()->get("mysql_column_price")];
                                                        $this->getServer()->getPluginManager()->getPlugin('ShoppingCartPE')->addGoods(strtolower($username), $itemid, $array[$this->getConfig()->get("mysql_column_count")]);
                                                        $this->getServer()->getPluginManager()->getPlugin("PocketMoney")->setMoney($username, $newbalance);
                                                        $player->sendMessage("You bought $fullitem (x".$array[$this->getConfig()->get("mysql_column_count")].") for ".$array[$this->getConfig()->get("mysql_column_price")].".");
                                                    }
                                                else $player->sendMessage("Not enough money!");
                                            }
                                        else $player->sendMessage("Goods not found!");
                                    }
                                else $player->sendMessage("You not selected item!\nUsage: /shop buy <item_id>");
                            }
                        else $sender->sendMessage("Use this command in game!");
                    }
                elseif($action == "list")
                    {
                        $page = array_shift($params);
                        if(! empty($page) && $page != "1") $page_sql = ($page - 1) * 10 .",10";
                        elseif(empty($page) || $page == "1") $page_sql = 10;
                        $query = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` LIMIT $page_sql");
                        if(@mysqli_num_rows($query))
                            {
                                $sender->sendMessage("======= Goods list =======\nUse /shop list <page_num>\n");
                                while($goods = @mysqli_fetch_assoc($query))
                                    {
                                        $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $goods[$this->getConfig()->get("mysql_column_itemid")]);
                                        $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $goods[$this->getConfig()->get("mysql_column_itemid")]);
                                        $fullitem = Item::get($item, $damage, $goods[$this->getConfig()->get("mysql_column_count")]);
                                        $fullitem = preg_replace("/x([0-9]+)/s", "", $fullitem);
                                        $fullitem = str_replace("Item ", "", $fullitem);
                                        $sender->sendMessage("* $fullitem (x".$goods[$this->getConfig()->get("mysql_column_count")].") for ".$goods[$this->getConfig()->get("mysql_column_price")]);
                                    }
                            }
                        else $sender->sendMessage("No goods.. Sorry.");
                    }
                elseif(empty($action)) $sender->sendMessage("======= Commands =======\n/shop - general command.\n/shop buy <item_id> - buy goods.\n/shop list <page_num> - list of goods.");
                break;
            
            case "gsp":
                $action = array_shift($params);
                if($action == "add")
                    {
                        $itemid = array_shift($params);
                        $count = array_shift($params);
                        $price = array_shift($params);
                        $check = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_itemid")."`='$itemid'");
                        if(! @mysqli_num_rows($check))
                            {
                                $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $itemid);
                                $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $itemid);
                                $fullitem = Item::get($item, $damage, $count);
                                $fullitem = preg_replace("/x([0-9]+)/s", "", $fullitem);
                                $fullitem = str_replace("Item ", "", $fullitem);
                                
                                if(! empty($itemid) && ! empty($count) && ! empty($price))
                                    {
                                        if(! preg_match("/^([0-9]+):([0-9]+)/", $itemid)) $sender->sendMessage("Incorrect item ID!");
                                        elseif(! preg_match("/^([0-9]+)/", $count)) $sender->sendMessage("Incorrect count!");
                                        elseif(! preg_match("/^([0-9]+)/", $price)) $sender->sendMessage("Incorrect price!");
                                        else
                                            {
                                                @mysqli_query($this->link, "INSERT INTO `".$this->getConfig()->get("mysql_table")."` (`".$this->getConfig()->get("mysql_column_itemid")."`, `".$this->getConfig()->get("mysql_column_count")."`, `".$this->getConfig()->get("mysql_column_price")."`) VALUES
                                                 ('$itemid', '$count', '$price')");
                                                
                                                $sender->sendMessage("Added $itemid (x$count) for $price.");
                                            }
                                    }
                                else $sender->sendMessage("Usage: /gsp add <item_id:damage> <count> <price>");
                            }
                        else $sender->sendMessage("That item already added to shop.");
                    }
                elseif($action == "remove")
                    {
                        $itemid = array_shift($params);
                        $check = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_itemid")."`='$itemid'");
                        if(@mysqli_num_rows($check))
                            {
                                if(! empty($itemid))
                                    {
                                        @mysqli_query($this->link, "DELETE FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_itemid")."`='$itemid'");
                                        
                                        $sender->sendMessage("Removed $itemid from shop.");
                                    }
                                else $sender->sendMessage("Usage: /gsp remove <itemid>");
                            }
                        else $sender->sendMessage("The requested goods could not be found.");
                    }
                elseif(empty($action)) $sender->sendMessage("======= GiantShopPE =======\n/gsp add <item_id:damage> <count> <price> - add item to shop.\n/gsp remove <itemid> - remove item from shop.\nVersion: 1.0\nAuthor: MamayAdesu");
                break;
        }
        return true;
    }
}
