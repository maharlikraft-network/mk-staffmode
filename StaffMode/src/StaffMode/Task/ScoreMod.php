<?php

namespace StaffMode\Task;

use pocketmine\{Player, Server};
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;

use StaffMode\Depends\ScoreAPI;

use StaffMode\Main;

class ScoreMod extends Task {
	
	private $plugin;
	
	public function __construct(Main $plugin){
		
		$this->plugin = $plugin;
		
	}
	
	public function getCancel() {
		
		$this->plugin->getScheduler()->cancelTask($this->getTaskID());
		
	}
	
	public function onRun(int $currentTask){
		
		foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
			
			if (in_array ($pl->getName(), $this->plugin->staff)) {
				
				$player = $pl;
				
				$name = $pl->getName();
				$ping = $pl->getPing();
				$tps = $pl->getServer()->getTicksPerSecond();
				$online = count(Server::getInstance()->getOnlinePlayers());
				$world = $pl->getLevel()->getName();
				$api = $this->plugin->score;
				$api->new($pl, $pl->getName(), TextFormat::BOLD.TextFormat::RED."STAFF".TextFormat::WHITE."MODE");
				$i = 0;
				$lines = [
				TextFormat::BLUE."§7---------------   ",
				TextFormat::DARK_GRAY."» ".TextFormat::RED."Pangalan: ".TextFormat::GRAY.$name,
				TextFormat::DARK_GRAY."» ".TextFormat::RED."Koneksyon: ".TextFormat::GRAY.$ping,
				TextFormat::AQUA."  ",
				TextFormat::DARK_GRAY."» ".TextFormat::RED."TPS: ".TextFormat::GRAY.$tps,
				TextFormat::DARK_GRAY."» ".TextFormat::RED."Online: ".TextFormat::GRAY.$online,
				TextFormat::GREEN."  ",
				TextFormat::DARK_GRAY."» ".TextFormat::RED."World: ".TextFormat::GRAY.$world,
				TextFormat::YELLOW."  ",
				TextFormat::RED." Ikaw ay kasalukuyang naka staffmode ",
				TextFormat::WHITE."§7---------------   ",
				];
				
				foreach ($lines as $line) {
					
					if ($i < 15) {
						
						$i++;
						$api->setLine($player, $i, $line);
					
					}
				}
			}
		}
	}
}
