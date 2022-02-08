<?php

namespace StaffMode;

use pocketmine\{Player, Server};
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerChatEvent;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\ConsoleCommandSender;

use StaffMode\Task\ScoreMod;

use StaffMode\Depends\FormAPI;
use StaffMode\Depends\ScoreAPI;

class Main extends PluginBase implements Listener {
	
	public $freeze = array();
	public $playerList = [];
	public $targetPlayer = [];
	public $task = null;
	public $staff = array();
	
	public $showPlayer = [];
	public $hidePlayer = [];
	
	use FormAPI;
	
	public $prefix = TextFormat::DARK_GRAY.TextFormat::BOLD."[".TextFormat::RED."STAFF".TextFormat::WHITE."MODE".TextFormat::DARK_GRAY."] ".TextFormat::RESET;
	
	public function onEnable(){
		
		$this->getLogger()->info(TextFormat::GREEN."Due to copyright reasons, i didn't own this plugin. This plugin is still developed and created by SoyDeusX. Due to this plugin are not authorized by creator to edit the inside of this plugin, this plugin is in private use by SVMS Network and MaharliKraft Network and we will not giving this plugin to public. The creator has a right to remove/terminate this modded plugin in our serverby contacting dpg#6934 in discord. ");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$this->score = new ScoreAPI($this);
		
		$this->lx = new \SQLite3($this->getDataFolder() . "StaffMode.lx");
		
		$this->lx->exec("CREATE TABLE IF NOT EXISTS banPlayers(player TEXT PRIMARY KEY, banTime INT, reason TEXT, staff TEXT);");
		
		$this->message = (new Config($this->getDataFolder() . "Message.yml", Config::YAML, array(
		"BanTempMessage" => "§8§l(§cYou are banned from server, time banned §7{day}/{hour}/{minute}, §creason §7{reason}§8)",
		"BanTempBroadcast" => "§8§l(§7{player} §chas been banned on the network§8)\n§r§cTime banned §7{day} days | {hour} hours | {minute} minutes\n§cReason §7{reason}",
		"LoginBanTempMessage" => "§8§l(§c§lYOU ARE BANNED FROM SERVER§r§8)\n§cTime banned §7{day}/{hour}/{minute}/{second}\n§cReason §7{reason}",
		"BanMe" => "§8§l(§cYou can't ban yourself§8)",
		"UnBan" => "§8§l(§7{player}§c has been unban from server",
		"AutoUnBan" => "§8§l(§7{player}§c has been unban from server, end time to ban",
		"BanInfoUI" => "§8§l(§cInformation to Player§8)§r\n\n§8»§c Days:§7 {day}\n§8»§c Hours:§7 {hour}\n§8»§c Minutes:§7 {minute}\n§8»§c Seconds:§7 {second}\n\n§8»§c Reason:§7 {reason}\n§8»§c Banned by:§7 {staff}\n\n\n",
		"NoBanPlayers" => "§c§lNo already players banned.",
		"TeleportMe" => "§8§l(§cYou can't teleport yourself§8)",
		)))->getAll();
		
		@mkdir($this->getDataFolder());
		
	}
	
	public function getInstance(): Main {
		
		return self::instance;
		
	}
	
	public function onJoin(PlayerJoinEvent $ev){
		
		$pl = $ev->getPlayer();
		
		$inv = Item::get(345, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::GOLD." RANDOMTP ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(405, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::RED." BAN ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(340, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::AQUA." TELEPORT ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(174, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::GREEN." FREEZE ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(369, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::RED." PLAYERINFO ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(399, 0, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(351, 10, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::GREEN." VANISH ");
		$pl->getInventory()->removeItem($inv);
		
		$inv = Item::get(345, 8, 1);
		$inv->setCustomName(TextFormat::BOLD.TextFormat::RED." UNVANISH ");
		$pl->getInventory()->removeItem($inv);
		
		if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
			
			$pl->sendMessage("\n");
			$pl->sendMessage(TextFormat::DARK_GRAY.TextFormat::BOLD."[".TextFormat::RED."STAFF".TextFormat::DARK_GRAY."]");
			$pl->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::YELLOW." This is StaffMode Engish Version For MaharliKraft Network");
			$pl->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::YELLOW." Your data has been saved successfully");
			$pl->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::YELLOW." Enjoy moderating players by typing /staff");
			$pl->sendMessage(TextFormat::DARK_GRAY.TextFormat::BOLD."[".TextFormat::RED."STAFF".TextFormat::DARK_GRAY."]");
			$pl->sendMessage("\n");
			
			foreach (Server::getInstance()->getOnlinePlayers() as $staff) {
				
				$staff->sendMessage(TextFormat::DARK_GRAY.TextFormat::BOLD."[".TextFormat::RED."STAFF".TextFormat::DARK_GRAY."] ".TextFormat::RESET.TextFormat::YELLOW.$pl->getName().TextFormat::GRAY." joined the server");
				
			}
		}
	}
	
	public function onDrop(PlayerDropItemEvent $ev){
		
		$pl = $ev->getPlayer();
		
		if (in_array ($pl->getName(), $this->staff)) {
			
			if ($pl->getLevel()) {
				
				$ev->setCancelled();
				
			}
		}
	}
	
	public function onQuit(PlayerQuitEvent $ev){
		
		$pl = $ev->getPlayer();
		
		if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
			
			if (in_array ($pl->getName(), $this->staff)) {
				
				if (in_array ($pl->getName(), $this->hidePlayer)) {
					
					if (in_array ($pl->getName(), $this->showPlayer)) {
						
						unset($this->staff [$pl->getName()]);
						unset($this->hidePlayer [$pl->getName()]);
						unset($this->showPlayer [$pl->getName()]);
						
						foreach (Server::getInstance()->getOnlinePlayers() as $staff) {
							
							$staff->sendMessage(TextFormat::DARK_GRAY.TextFormat::BOLD."[".TextFormat::RED."STAFF".TextFormat::DARK_GRAY."] ". TextFormat::RESET.TextFormat::YELLOW.$pl->getName().TextFormat::GRAY." leaved the server");
							$this->score->remove($staff);
							
							if (($taks = $this->getTask()) instanceof ScoreMod) {
								
								Server::getInstance()->getScheduler()->cancelTask($taks->getTaskId());
								
							}
						}
					}
				}
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $ev){
		
		$pl = $ev->getPlayer();
		
		if (!in_array ($pl->getName(), $this->freeze)) {
			
			if (in_array ($pl->getName(), $this->staff)) {
				
				$ev->setCancelled();
			
			}
		}
	}
	
	public function onPlace(BlockPlaceEvent $ev){
		
		$pl = $ev->getPlayer();
		
		if (!in_array ($pl->getName(), $this->freeze)) {
			
			if (in_array ($pl->getName(), $this->staff)) {
				
				$ev->setCancelled();
			
			}
		}
	}
	
	public function onMove(PlayerMoveEvent $ev){
		
		$pl = $ev->getPlayer();
		
		if (in_array ($pl->getName(), $this->freeze)) {
			
			$to = clone $ev->getFrom();
			$to->yaw = $ev->getTo()->yaw;
			$to->pitch = $ev->getTo()->pitch;
			$ev->setTo($to);
			$pl->sendPopup(TextFormat::RED." You've been frezeed. You can't move.");
			$pl->addTitle(TextFormat::RED."Youve been", TextFormat::RED."FREEZED");
		
		}
	}
	
	public function onCommand(CommandSender $pl, Command $cmd, string $label, array $args): bool {
		
		switch ($cmd->getName()) {
			
			case "staff":
			
			if (!in_array ($pl->getName(), $this->staff)) {
				
				$this->staff [$pl->getName()] = $pl->getName();
				$this->getScheduler()->scheduleRepeatingTask(($task = new ScoreMod($this)), 20);
				$pl->sendMessage($this->prefix.TextFormat::GREEN."StaffMode turned on Successfully.");
				$pl->getInventory()->clearAll();
				$pl->getArmorInventory()->clearAll();
				$pl->setGamemode(1);
				
				$pl->getInventory()->setItem(0, Item::get(345, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GOLD." RANDOMTP "));
				$pl->getInventory()->setItem(1, Item::get(405, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." BAN "));
				$pl->getInventory()->setItem(2, Item::get(340, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::AQUA." TELEPORT "));
				$pl->getInventory()->setItem(4, Item::get(174, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." FREEZE "));
				$pl->getInventory()->setItem(6, Item::get(369, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." PLAYERINFO "));
				$pl->getInventory()->setItem(7, Item::get(399, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE "));
				$pl->getInventory()->setItem(8, Item::get(351, 10, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." VANISH "));
				
			 } else {
				
				if (in_array ($pl->getName(), $this->staff)) {
						
					unset($this->staff [$pl->getName()]);
					unset($this->hidePlayer [$pl->getName()]);
					$this->score->remove($pl);
					$this->getMode($pl);
					$pl->getArmorInventory()->clearAll();
					$pl->setGamemode(0);
					$pl->getInventory()->clearAll();
					$pl->sendMessage($this->prefix.TextFormat::RED."StaffMode turned off Successfully.");
					
				}
			}
			
			return true;
		
		}
	}
	
	public function getTask(){
		
		return $this->task;
		
	}
	
	public function getTeleportUI(Player $pl){
		
		$form = $this->createSimpleForm(function (Player $pl, $data = null) {
			
			$target = $data;
			
			if ($target === null) {
				
				return true;
			
			}
			
			$this->targetPlayer[$pl->getName()] = $target;
			$this->getTp($pl);
			
		});
		
		$form->setTitle(TextFormat::RED.TextFormat::BOLD."PLAYER LIST");
		$form->setContent(TextFormat::GRAY."Choose a Player");
		
		foreach (Server::getInstance()->getOnlinePlayers() as $on) {
			
			$form->addButton(TextFormat::RED.$on->getName(), -1, "", $on->getName());
		
		}
		
		$form->sendToPlayer($pl);
		
		return $form;
	
	}
	
	public function getTp(Player $pl){
		
		if (isset ($this->targetPlayer [$pl->getName()])) {
			
			if ($this->targetPlayer[$pl->getName()] == $pl->getName()) {
				
				$pl->sendMessage($this->message["TeleportMe"]);
				
				return true;
			
			}
			
			$target = $this->getServer()->getPlayerExact($this->targetPlayer [$pl->getName()]);
			
			if ($target instanceof Player) {
				
				$pl->teleport($target);
				$pl->sendMessage(TextFormat::RED."Teleported to: ".TextFormat::GRAY.$target->getName());
				
			}
		}
	}
	
	public function getMenuBan(Player $pl){
		
		$form = $this->createSimpleForm(function (Player $pl, $data = null) {
			
			$result = $data;
			
			if ($result === null) {
				
				return true;
				
			}
			
			switch ($result){
				
				case 0:
				$this->getBanList($pl);
				break;
				case 1:
				$this->getCheckBanUI($pl);
				break;
			
			}
		});
		
		$form->setTitle(TextFormat::RED.TextFormat::BOLD."BAN OPTIONS");
		$form->addButton(TextFormat::RED."BAN PLAYER/S");
		$form->addButton(TextFormat::RED."BAN PLAYER/S LIST");
		$form->sendToPlayer($pl);
		
		return $form;
	
	}
				
	public function getBanList(Player $pl){
		
		$form = $this->createSimpleForm(function (Player $pl, $data = null) {
			
			$target = $data;
			
			if ($target === null) {
				
				return true;
				
			}
			
			$this->targetPlayer[$pl->getName()] = $target;
			$this->getBanUI($pl);
			
		});
		
		$form->setTitle(TextFormat::RED.TextFormat::BOLD."PLAYERS LIST");
		$form->setContent(TextFormat::GRAY."Choose a player");
		
		foreach (Server::getInstance()->getOnlinePlayers() as $on) {
			
			$form->addButton(TextFormat::RED.$on->getName(), -1, "", $on->getName());
		
		}
		
		$form->sendToPlayer($pl);
		
		return $form;
	
	}
	
	public function getBanUI(Player $pl){
		
		$form = $this->createCustomForm(function (Player $pl, array $data = null) {
			
			$result = $data[0];
			
			if ($result === null) {
				
				return true;
			
			}
			
			if (isset ($this->targetPlayer [$pl->getName()])) {
				
				if ($this->targetPlayer[$pl->getName()] == $pl->getName()) {
					
					$pl->sendMessage($this->message["BanMe"]);
					
					return true;
				
				}
				
				$now = time();
				$day = ($data[1] * 86400);
				$hour = ($data[2] * 3600);
				
				if ($data[3] > 1) {
					
					$min = ($data[3] * 60);
				
				} else {
					
					$min = 60;
				
				}
				
				$banTime = $now + $day + $hour + $min;
				$banInfo = $this->lx->prepare("INSERT OR REPLACE INTO banPlayers (player, banTime, reason, staff) VALUES (:player, :banTime, :reason, :staff);");
				$banInfo->bindValue(":player", $this->targetPlayer [$pl->getName()]);
				$banInfo->bindValue(":banTime", $banTime);
				$banInfo->bindValue(":reason", $data[4]);
				$banInfo->bindValue(":staff", $pl->getName());
				$banInfo->execute();
				$target = $this->getServer()->getPlayerExact($this->targetPlayer [$pl->getName()]);
				
				if ($target instanceof Player) {
					
					$target->kick(str_replace(["{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$data[1], $data[2], $data[3], $data[4], $pl->getName()], $this->message["BanTempMessage"]));
				
				}
				
				$this->getServer()->broadcastMessage(str_replace(["{player}", "{day}", "{hour}", "{minute}", "{reason}", "{staff}"], [$this->targetPlayer[$pl->getName()], $data[1], $data[2], $data[3], $data[4], $pl->getName()], $this->message["BanTempBroadcast"]));
				unset($this->targetPlayer[$pl->getName()]);
			
			}
		});
		
		$list[] = $this->targetPlayer[$pl->getName()];
		$form->setTitle(TextFormat::RED.TextFormat::BOLD."BAN TEMPORALITY");
		$form->addDropdown("\nSelected player", $list);
		$form->addSlider("Days Time", 0, 30, 1);
		$form->addSlider("Hours Time", 0, 24, 1);
		$form->addSlider("Minutes Time", 0, 60, 5);
		$form->addInput("Reason");
		$form->sendToPlayer($pl);
		
		return $form;
		
	}
	
	public function getCheckBanUI(Player $pl){
		
		$form = $this->createSimpleForm(function (Player $pl, $data = null) {
			
			if ($data === null) {
				
				return true;
			
			}
			
			$this->targetPlayer[$pl->getName()] = $data;
			$this->getBanInfoUI($pl);
		
		});
		
		$banInfo = $this->lx->query("SELECT * FROM banPlayers;");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		
		if (empty ($array)) {
			
			$pl->sendMessage($this->message["NoBanPlayers"]);
			
			return true;
		
		}
		
		$form->setTitle(TextFormat::RED.TextFormat::BOLD."BAN LIST");
		$banInfo = $this->lx->query("SELECT * FROM banPlayers;");
		$i = -1;
		
		while ($resultArr = $banInfo->fetchArray(SQLITE3_ASSOC)) {
			
			$j = $i + 1;
			$banPlayer = $resultArr['player'];
			$form->addButton(TextFormat::RED."$banPlayer", -1, "", $banPlayer);
			$i = $i + 1;
		
		}
		
		$form->sendToPlayer($pl);
		
		return $form;
	
	}
	
	public function getBanInfoUI(Player $pl){
		
		$form = $this->createSimpleForm(function (Player $pl, $data = null) {
			
			$result = $data;
			
			if ($data === null) {
				
				return true;
			
			}
			
			switch ($result) {
				
				case 0:
				
				$banplayer = $this->targetPlayer [$pl->getName()];
				$banInfo = $this->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				
				if (!empty($array)) {
					
					$this->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
					$pl->sendMessage(str_replace(["{player}"], [$banplayer], $this->message["UnBan"]));
				
				}
				
				unset($this->targetPlayer [$pl->getName()]);
				
				break;
			
			}
		});
		$banPlayer = $this->targetPlayer [$pl->getName()];
		$banInfo = $this->lx->query("SELECT * FROM banPlayers WHERE player = '$banPlayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		
		if (!empty($array)) {
			
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			
			if ($banTime < $now) {
				
				$banplayer = $this->targetPlayer [$pl->getName()];
				$banInfo = $this->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
				$array = $banInfo->fetchArray(SQLITE3_ASSOC);
				
				if (!empty($array)) {
					
					$this->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
					$pl->sendMessage(str_replace(["{player}"], [$banplayer], $this->message["AutoUnBan"]));
				
				}
				
				unset($this->targetPlayer [$pl->getName()]);
				
				return true;
			
			}
			
			$remainingTime = $banTime - $now;
			$day = floor($remainingTime / 86400);
			$hourSeconds = $remainingTime % 86400;
			$hour = floor($hourSeconds / 3600);
			$minuteSec = $hourSeconds % 3600;
			$minute = floor($minuteSec / 60);
			$remainingSec = $minuteSec % 60;
			$second = ceil($remainingSec);
		
		}
		
		$form->setTitle(TextFormat::BOLD.TextFormat::RED.$banPlayer." INFO");
		$form->setContent(str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["BanInfoUI"]));
		$form->addButton(TextFormat::RED.TextFormat::BOLD."UNBAN PLAYER");
		$form->sendToPlayer($pl);
		
		return $form;
	
	}
		
	public function onPlayerLogin(PlayerPreLoginEvent $ev){
		
		$pl = $ev->getPlayer();
		$banplayer = $pl->getName();
		$banInfo = $this->lx->query("SELECT * FROM banPlayers WHERE player = '$banplayer';");
		$array = $banInfo->fetchArray(SQLITE3_ASSOC);
		
		if (!empty ($array)) {
			
			$banTime = $array['banTime'];
			$reason = $array['reason'];
			$staff = $array['staff'];
			$now = time();
			
			if ($banTime > $now) {
				
				$remainingTime = $banTime - $now;
				$day = floor($remainingTime / 86400);
				$hourSeconds = $remainingTime % 86400;
				$hour = floor($hourSeconds / 3600);
				$minuteSec = $hourSeconds % 3600;
				$minute = floor($minuteSec / 60);
				$remainingSec = $minuteSec % 60;
				$second = ceil($remainingSec);
				$pl->close("", str_replace(["{day}", "{hour}", "{minute}", "{second}", "{reason}", "{staff}"], [$day, $hour, $minute, $second, $reason, $staff], $this->message["LoginBanTempMessage"]));
			
			} else {
				
				$this->lx->query("DELETE FROM banPlayers WHERE player = '$banplayer';");
			
			}
		}
	}
	
	public function getMode(){
		
		foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
			
			$this->score->remove($pl);
			
			if (($taks = $this->getTask()) instanceof ScoreMod) {
				
				Server::getInstance()->getScheduler()->cancelTask($taks->getTaskId());
				
			}
		}
	}
	
	public function onInteract(PlayerInteractEvent $ev){
		
		$pl = $ev->getPlayer();
		$item = $ev->getItem();
		$block = $ev->getBlock();
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::AQUA." TELEPORT ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$this->getTeleportUI($pl);
				
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			
			}
		}
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::GOLD." RANDOMTP ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$test = array_rand($this->getServer()->getOnlinePlayers());
				$playerTotp = $this->getServer()->getOnlinePlayers()[$test];
				
				if ($playerTotp !== $pl) {
					
					$pl->teleport($playerTotp);
					$pl->sendMessage(TextFormat::RED."Teleported to: ".TextFormat::GRAY.$playerTotp->getName());
					
				}
				
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			}
		}
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::RED." BAN ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$this->getMenuBan($pl);
				
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
				
			}
		}
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->getInventory()->clearAll();
				$pl->getArmorInventory()->clearAll();
				$pl->getInventory()->setItem(3, Item::get(339)->setCustomName(TextFormat::RED.TextFormat::BOLD." SURVIVAL "));
				$pl->getInventory()->setItem(4, Item::get(339)->setCustomName(TextFormat::RED.TextFormat::BOLD." CREATIVE "));
				$pl->getInventory()->setItem(5, Item::get(339)->setCustomName(TextFormat::RED.TextFormat::BOLD." SPECTATOR "));
				$pl->getInventory()->setItem(8, Item::get(351, 1)->setCustomName(TextFormat::RED.TextFormat::BOLD." RETURN "));
				
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			
			}
		}
		
		if ($item->getName() == TextFormat::RED.TextFormat::BOLD." SURVIVAL ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->setGamemode(0);
				$pl->sendMessage(TextFormat::RED."Anonymously changed your gamemode to ".TextFormat::GRAY."Survival");
			
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			
			}
		}
		
		if ($item->getName() == TextFormat::RED.TextFormat::BOLD." CREATIVE ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->setGamemode(1);
				$pl->sendMessage(TextFormat::RED."Anonymously changed your gamemode to ".TextFormat::GRAY."Creative");
			
			} else {
				
				$pl->sendMessage(TextFormat::RED."Wala kang permission para gamitin ito");
			
			}
		}
		
		if ($item->getName() == TextFormat::RED.TextFormat::BOLD." SPECTATOR ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->setGamemode(3);
				$pl->sendMessage(TextFormat::RED."Anonymously changed your gamemode to ".TextFormat::GRAY."Spectator");
				
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
				
			}
		}
		
		if ($item->getName() == TextFormat::RED.TextFormat::BOLD." RETURN ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->getInventory()->clearAll();
				
				$pl->getInventory()->setItem(0, Item::get(345, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GOLD." RANDOMTP "));
				$pl->getInventory()->setItem(1, Item::get(405, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." BAN "));
				$pl->getInventory()->setItem(2, Item::get(340, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::AQUA." TELEPORT "));
				$pl->getInventory()->setItem(4, Item::get(174, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." FREEZE "));
				$pl->getInventory()->setItem(6, Item::get(369, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." PLAYERINFO "));
				$pl->getInventory()->setItem(7, Item::get(399, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE "));
				$pl->getInventory()->setItem(8, Item::get(351, 10, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." VANISH "));
			
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
				
			}
		}
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::GREEN." VANISH ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->sendMessage($this->prefix.TextFormat::GREEN."Vanished.");
				
				foreach (Server::getInstance()->getOnlinePlayers() as $players) {
					
					$players->hidePlayer($pl);
					$pl->setNameTagVisible(false);
					$pl->despawnFromAll();
					$pl->getInventory()->clearAll();
					$pl->getInventory()->setItem(0, Item::get(345, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GOLD." RANDOMTP "));
				    $pl->getInventory()->setItem(1, Item::get(405, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." BAN "));
				    $pl->getInventory()->setItem(2, Item::get(340, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::AQUA." TELEPORT "));
				    $pl->getInventory()->setItem(4, Item::get(174, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." FREEZE "));
				    $pl->getInventory()->setItem(6, Item::get(369, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." PLAYERINFO "));
				    $pl->getInventory()->setItem(7, Item::get(399, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE "));
				    $pl->getInventory()->setItem(8, Item::get(351, 8, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." UNVANISH "));
				
				}
			
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			
			}
		}
		
		if ($item->getName() == TextFormat::BOLD.TextFormat::RED." UNVANISH ") {
			
			if ($pl->hasPermission("staffmode.cmd") or $pl->isOp()) {
				
				$pl->sendMessage($this->prefix.TextFormat::RED."Unvanished.");
				
				foreach (Server::getInstance()->getOnlinePlayers() as $players) {
					
					$players->showPlayer($pl);
					$pl->spawnToAll();
					$pl->setNameTagVisible(true);
					$pl->getInventory()->clearAll();
					$pl->getInventory()->setItem(0, Item::get(345, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GOLD." RANDOMTP "));
				    $pl->getInventory()->setItem(1, Item::get(405, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." BAN "));
				    $pl->getInventory()->setItem(2, Item::get(340, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::AQUA." TELEPORT "));
				    $pl->getInventory()->setItem(4, Item::get(174, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." FREEZE "));
				    $pl->getInventory()->setItem(6, Item::get(369, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::RED." PLAYERINFO "));
				    $pl->getInventory()->setItem(7, Item::get(399, 0, 1)->setCustomName(TextFormat::BOLD.TextFormat::YELLOW." GAMEMODE "));
				    $pl->getInventory()->setItem(8, Item::get(351, 10, 1)->setCustomName(TextFormat::BOLD.TextFormat::GREEN." VANISH "));
				
				}
			
			} else {
				
				$pl->sendMessage(TextFormat::RED."No permission");
			
			}
		}
	}
	
	public function onFreezed(EntityDamageEvent $ev){
		
		if ($ev instanceof EntityDamageByEntityEvent) {
			
			$damager = $ev->getDamager();
			$pl = $ev->getEntity();
			
			if ($damager instanceof Player) {
						
				if ($damager->getInventory()->getItemInHand()->getCustomName() === TextFormat::BOLD.TextFormat::GREEN." FREEZE ") {
							
					if (!in_array ($pl->getName(), $this->freeze)) {
								
						$this->getServer()->broadcastMessage(TextFormat::DARK_GRAY."» ".TextFormat::GRAY.$pl->getName().TextFormat::RED." has been freezed by ".TextFormat::GRAY.$damager->getName());
						$this->freeze[] = $pl->getName();
						$ev->setCancelled();
						
					} else {
							
						if (in_array ($pl->getName(), $this->freeze)) {
								
							$pl->addTitle(TextFormat::GREEN."YOU'VE BEEN", "UNFREEZED");
							$this->getServer()->broadcastMessage(TextFormat::DARK_GRAY."» ".TextFormat::GRAY.$pl->getName().TextFormat::RED." has been unfreezed by ".TextFormat::GRAY.$damager->getName());
							unset($this->freeze [array_search ($pl->getName(), $this->freeze)]);
							$ev->setCancelled();
						
						}
					}
				}
			}
		}
	}
	
	public function onInfo(EntityDamageEvent $ev){
		
		if ($ev instanceof EntityDamageByEntityEvent) {
			
			$damager = $ev->getDamager();
			$pl = $ev->getEntity();
			
			if ($damager instanceof Player) {
						
				if ($damager->getInventory()->getItemInHand()->getCustomName() === TextFormat::BOLD.TextFormat::RED." PLAYERINFO ") {
					
					$damager->sendMessage(TextFormat::GRAY."---------------");
					$damager->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::RED."Tag: ".TextFormat::GRAY.$pl->getName());
					$damager->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::RED."Ping: ".TextFormat::GRAY.$pl->getPing().TextFormat::RED." ms");
					$damager->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::RED."Address: ".TextFormat::GRAY.$pl->getAddress());
					$damager->sendMessage(TextFormat::DARK_GRAY."» ".TextFormat::RED."Health: ".TextFormat::GRAY.$pl->getHealth().TextFormat::RED." Health");
					$damager->sendMessage(TextFormat::GRAY."---------------");
					$ev->setCancelled();
					
				}
			}
		}
	}
}
