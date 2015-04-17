<?php

namespace /WonderCore/

use /pocketmine/plugin/PluginBase;
use /pocketmine/player/player;

?>

#IP_Auth
IP Auth can be configured in the 'config' file of SimpleAuth by the PocketMine Team

#DisableCommands
 /tp
 /help
 ~Disable these commands for noraml players of the server.
 ~Only let 'OP'users use these commands
 
#FullCommandsDisables
 /op
 /deop
 #These can be still used by the server console. No OP's can use it!
 
#RankCommandsDisable
 /addvip
 /addextreme
 /addstaff
 /deletestaff
 /stop
 #Everyone exept these ranks access these commands "Owner,Co_Owner,Dev"

#NewCommands
  ~command_new
  command: "/quit"
  description: Quit yourself from the server.
  action: console.command="kick %player%"
  perm: ()
  
  ~command_new
  command: "/hub"
  description: Teleports yourslef to spawn
  action: player.teleport="spawn"
  perm: ()
  
  ~command_new
  command: "/account"
  description: View all accounts registered on the server.
  action: player.accounts
  perm: (OP)
  
~command_new
  command: "/account del"
  description: Delete an account from the server. Can also be used to reset an account.
  action: player.accounts.delete
  perm: (OP)
  
~command_new
  command: "/ophelp"
  description: List all help commands without many pages. (Lots of Commands)
  action: console.command="help showall"
  perm: (OP)
  
~command_new
  command: "/spawn <entity>"
  description: Let's everyone spawn mobs in the game.
  action: entity.spawn="%entity%"
  perm: ()
  
~command_new
  command: "/move <gamemode>
  description: Adds a friend to your friend list
  action: player.teleport=<gamemode>
  perm: ()
  
~command_new
  command: "/friends"
  description: View frinds commands
  action: chat.message."/friends list - View all friends, /friends add %player% - Add Player as friend, /friends del %player%
  perm: ()
  
~command_new
  command: "/friends add %player%"
  description: Adds a friend to your friend list
  action: plugin.wondercore.friends.add
  perm: ()

~command_new
  command: "/friends del %player%"
  description: Adds a friend to your friend list
  action: plugin.wondercore.friends.delete
  perm: ()
  
~command_new
  command: "/addvip %player%"
  description: Add VIP Rank to player
  action: player.rank.get.vip
  perm: ()
  
~command_new
  command: "/addextreme %player%"
  description: Add Extreme Rank to player
  action: player.rank.get.extreme
  perm: ()
  
~command_new
  command: "/addstaff %player%"
  description: Add Staff Rank to player
  action: player.rank.get.staff - console.command"op %player%
  perm: ()
  
~command_new
  command: "/delstaff %player%"
  description: Removes Staff Rank from player
  action: player.rank.del.staff - console.command"deop %player%
  perm: ()
  
#Friends
 ~Friends acript soon!
