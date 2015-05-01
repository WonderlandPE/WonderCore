<?php

namespace mamayadesu\scp;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\item\Item;

class scp extends PluginBase implements CommandExecutor, Listener{

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
            $this->getConfig()->set("mysql_table", "shoppingcartpe");
            $this->getConfig()->set("mysql_column_row_id", "id");
            $this->getConfig()->set("mysql_column_username", "name");
            $this->getConfig()->set("mysql_column_item_id", "item");
            $this->getConfig()->set("mysql_column_items_count", "count");
            $this->getConfig()->save();
        }
        
        $this->getLogger()->info("Loading ShoppingCartPE v1.1 by MamayAdesu...");
        
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
          `".$this->getConfig()->get("mysql_column_row_id")."` int(11) NOT NULL AUTO_INCREMENT,
          `".$this->getConfig()->get("mysql_column_username")."` varchar(255) NOT NULL,
          `".$this->getConfig()->get("mysql_column_item_id")."` varchar(255) NOT NULL DEFAULT '0',
          `".$this->getConfig()->get("mysql_column_items_count")."` varchar(255) NOT NULL,
          PRIMARY KEY (`".$this->getConfig()->get("mysql_column_row_id")."`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

        ") or die("FAILED TO USE MYSQL COMMAND! QUERY 1");
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
public function onDisable()
    {
        @mysqli_close($this->link);
        $this->getLogger()->info("Disabling ShoppingCartPE v1.1 by MamayAdesu...");
    }

public function addGoods($username, $item_id, $count)
    {
        $check_goods = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_item_id")."`='$item_id' AND `".$this->getConfig()->get("mysql_column_username")."`='$username'") or die("FAILED TO USE MYSQL COMMAND! QUERY 2");
        if(@mysqli_num_rows($check_goods)) @mysqli_query($this->link, "UPDATE `".$this->getConfig()->get("mysql_table")."` SET `".$this->getConfig()->get("mysql_column_items_count")."`=".$this->getConfig()->get("mysql_column_items_count")."+$count WHERE `".$this->getConfig()->get("mysql_column_item_id")."`='$item_id' AND `".$this->getConfig()->get("mysql_column_username")."`='$username'") or die("FAILED TO USE MYSQL COMMAND! QUERY 3");
        else @mysqli_query($this->link, "INSERT INTO `".$this->getConfig()->get("mysql_table")."` (`".$this->getConfig()->get("mysql_column_username")."`, `".$this->getConfig()->get("mysql_column_item_id")."`, `".$this->getConfig()->get("mysql_column_items_count")."`) VALUES
         ('$username', '$item_id', '$count')") or die("FAILED TO USE MYSQL COMMAND! QUERY 4");
    }

public function onCommand(CommandSender $sender, Command $command, $label, array $params)
    {
        $username = strtolower($sender->getName());
        $player = $this->getServer()->getPlayer($username);
        if(! ($player instanceof Player))
            {
                $sender->sendMessage("Use this command in game!");
                return true;
            }
        switch($command->getName())
        {
            case "cart":
                $action = array_shift($params);
                $id = implode("", $params);
                $allpurchases = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_username")."`='$username'") or die("FAILED TO USE MYSQL COMMAND! QUERY 5");
                $purchasesbyid = @mysqli_query($this->link, "SELECT * FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_row_id")."`='$id' AND `".$this->getConfig()->get("mysql_column_username")."`='".$sender->getName()."'") or die("FAILED TO USE MYSQL COMMAND! QUERY 6");
                if(empty($action))
                    {
                        if(@mysqli_num_rows($allpurchases))
                            {
                                $sender->sendMessage("======== Your shopping cart ========");
                                while($ap = @mysqli_fetch_assoc($allpurchases))
                                    {
                                        $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $ap[$this->getConfig()->get("mysql_column_item_id")]);
                                        $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $ap[$this->getConfig()->get("mysql_column_item_id")]);
                                        $fullitem = Item::get($item, $damage, $ap[$this->getConfig()->get("mysql_column_items_count")]);
                                        $fullitem = preg_replace("/x([0-9]+)/s", "", $fullitem);
                                        $fullitem = str_replace("Item ", "", $fullitem);
                                        $sender->sendMessage($ap[$this->getConfig()->get("mysql_column_row_id")].". Item: $fullitem | Count: ".$ap[$this->getConfig()->get("mysql_column_items_count")]);
                                    }
                            }
                        else $sender->sendMessage("Your shopping cart is empty!");
                        return true;
                    }
                    
                elseif($action == "get")
                    {
                        if($id != "all")
                            {
                                if(! empty($id))
                                    {
                                        if(@mysqli_num_rows($purchasesbyid))
                                            {
                                                $pbi = @mysqli_fetch_array($purchasesbyid);
                                                $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $pbi[$this->getConfig()->get("mysql_column_item_id")]);
                                                $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $pbi[$this->getConfig()->get("mysql_column_item_id")]);
                                                $fullitem = Item::get($item, $damage, $pbi[$this->getConfig()->get("mysql_column_items_count")]);
                                                $sender->getInventory()->addItem($fullitem);
                                                #$this->getServer()->dispatchCommand(new ConsoleCommandSender(),"give $username ".$pbi[$this->getConfig()->get("mysql_column_item_id")]." ".$pbi[$this->getConfig()->get("mysql_column_items_count")]); // This method of give things was used in beta version of plugin
                                                @mysqli_query($this->link, "DELETE FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_row_id")."`='$id'") or die("FAILED TO USE MYSQL COMMAND! QUERY 7");
                                                $sender->sendMessage("These goods were moved to your inventory!");
                                                if($this->getConfig()->get("enable_logger")) $this->getLogger()->info($sender->getName()." gained ".$fullitem." by '/cart get ".$pbi[$this->getConfig()->get("mysql_column_row_id")]."'");
                                            }
                                        else $sender->sendMessage("Unknown purchase ID!");
                                        return true;
                                    }
                                else return false;
                            }
                        else
                            {
                                if(@mysqli_num_rows($allpurchases))
                                    {
                                        while($ap = @mysqli_fetch_assoc($allpurchases))
                                            {
                                                $item = preg_replace("/^([0-9]+):([0-9]+)/", "$1", $ap[$this->getConfig()->get("mysql_column_item_id")]);
                                                $damage = preg_replace("/^([0-9]+):([0-9]+)/", "$2", $ap[$this->getConfig()->get("mysql_column_item_id")]);
                                                $fullitem = Item::get($item, $damage, $ap[$this->getConfig()->get("mysql_column_items_count")]);
                                                $sender->getInventory()->addItem($fullitem);
                                                if($this->getConfig()->get("enable_logger")) $this->getLogger()->info($sender->getName()." gained ".$fullitem." by '/cart get all'");
                                                #$this->getServer()->dispatchCommand(new ConsoleCommandSender(),"give $username ".$ap[$this->getConfig()->get("mysql_column_item_id")]." ".$ap[$this->getConfig()->get("mysql_column_items_count")]); // This method of give things was used in beta version of plugin
                                            }
                                        @mysqli_query($this->link, "DELETE FROM `".$this->getConfig()->get("mysql_table")."` WHERE `".$this->getConfig()->get("mysql_column_username")."`='$username'") or die("FAILED TO USE MYSQL COMMAND! QUERY 8");
                                        $sender->sendMessage("All your goods were moved to your inventory!");
                                    }
                                else $sender->sendMessage("Your shopping cart is empty!");
                                return true;
                            }
                    }
                else $sender->sendMessage("Unknown subcommand!");
                return true;
                break;
        }
    }
}
