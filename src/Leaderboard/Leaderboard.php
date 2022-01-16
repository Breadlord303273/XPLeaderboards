<?php

namespace Leaderboard;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\{Server,Player};
use pocketmine\command\{Command,CommandSender};
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\Config;
use slapper\events\SlapperCreationEvent;
use Leaderboard\Tasks\LevelsTask;
use Leaderboard\Tasks\KillsTask;

class Leaderboard extends PluginBase implements Listener{
    
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder());
		$this->runTasks();
		$this->exp = new Config($this->getDataFolder() . "toplevels.yml", Config::YAML);
		$this->k = new Config($this->getDataFolder() . "topkills.yml", Config::YAML);
		$this->kills = $this->getServer()->getPluginManager()->getPlugin("XPSystem");
	}
	
    public function runTasks() {
		$this->getScheduler()->scheduleRepeatingTask(new LevelsTask($this), 20 * 60);
		$this->getScheduler()->scheduleRepeatingTask(new KillsTask($this), 20 * 60);
	}
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		switch($cmd->getName()) {                    
			case "top":
			if($sender instanceof Player) {
				$arg = array_shift($args);
				switch($arg) {
					
					case "addtoplevels":
						$command = "slapper create human TopLevels";
						$this->getServer()->getCommandMap()->dispatch($sender, $command); 
						break;
					
					case "addtopkills":
						$command = "slapper create human TopKills";
						$this->getServer()->getCommandMap()->dispatch($sender, $command); 
						break;
				break;
			}
			return true;
		}
	}
	
	public function updateXp($player) {
		$this->exp->set($player->getName(), $player->getXpLevel());
		$this->exp->save();
		$this->onTopLevels();
    }
	
	public function updateKills($player) {
		$this->k->set($player->getName(), $this->kills->getKill($player));
		$this->k->save();
		$this->onTopKills();
    }

	
	public function onSlapperCreate(SlapperCreationEvent $event) {
		$entity = $event->getEntity();
		$name = $entity->getNameTag();
		
		if($name == "TopLevels") {
			$entity->namedtag->setString("toplevels", "toplevels");
			$this->onTopLevels();
		}
		
		if($name == "TopKills") {
			$entity->namedtag->setString("topkills", "topkills");
			$this->onTopKills();
		}
	
	public function onTopLevels() {
		$exp = $this->exp->getAll();
		arsort($exp);
		$exp = array_slice($exp, 0, 10);
		$counter = 1;
		$text = "§l§bTOP LEVELS LEADERBOARD\n";
		foreach($exp as $name => $value){
			$text .= "§e#{$counter} §7{$name} - §e{$value}\n";
			$counter++;
		}
		foreach($this->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity->namedtag->hasTag("toplevels", StringTag::class)) {
					if($entity->namedtag->getString("toplevels") == "toplevels") {
						$entity->setNameTag($text);
						$entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
						$entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
					}
				}
			}
		}
	}
	
	public function onTopKills() {
		$kills = $this->k->getAll();
		arsort($kills);
		$kills = array_slice($kills, 0, 10);
		$counter = 1;
		$text = "§l§bTOP KILLS LEADERBOARD\n";
		foreach($kills as $name => $value){
			$text .= "§e#{$counter} §7{$name} - §e{$value}\n";
			$counter++;
		}
		foreach($this->getServer()->getLevels() as $levels){
			foreach($levels->getEntities() as $entity){
				if($entity->namedtag->hasTag("topkills", StringTag::class)) {
					if($entity->namedtag->getString("topkills") == "topkills") {
						$entity->setNameTag($text);
						$entity->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 3);
						$entity->getDataPropertyManager()->setFloat(Entity::DATA_SCALE, 0.0);
					}
				}
			}
		}
	}
}