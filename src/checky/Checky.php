<?php
namespace checky;

use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use checky\Parkour;

class Checky extends PluginBase {

	/** @var array */
	// protected $checkpoints = [];
	protected $players = [];
	/** @var Parkour[] */
	protected $parkours;

	public function onEnable(){
		if(!file_exists($this->getDataFolder() . "parkours.yml")){
			$this->createDefaultParkourArena();
		}
		$this->loadParkours( (new Config($this->getDataFolder() . "parkours.yml", Config::YAML))->getAll() );
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}
	public function onDisable(){}

	private function loadParkours(array $arenas){
		foreach($arenas as $name => $prefs){
			$spawn = new Position($prefs['start-pos']['x'], $prefs['start-pos']['y'], $prefs['start-pos']['z'], $this->getServer()->getLevelByName($prefs['start-pos']['level']));
			$a = new Position($prefs['pos1']['x'], $prefs['pos1']['y'], $prefs['pos1']['y'], $this->getServer()->getLevelByName($prefs['pos1']['level']));
			$b = new Position($prefs['pos2']['x'], $prefs['pos2']['y'], $prefs['pos2']['y'], $this->getServer()->getLevelByName($prefs['pos2']['level']));
			$finishBlock = $this->getServer()->getLevelByName($prefs['finish-block']['level'])->getBlock(new Vector3($prefs['finish-block']['x'], $prefs['finish-block']['y'], $prefs['finish-block']['z']));
			if(!$a->getLevel() === $b->getLevel() and !$a->getLevel() === $spawn->getLevel() and !$a->getLevel() === $finishBlock->getLevel()){
				$this->getLogger()->info("$name both positions has to be on same level!");
				continue;
			}
			$this->parkours[] = new Parkour($this, $name, $spawn, new Vector3(min($a->getX(), $b->getX()), min($a->getY(), $b->getY()), min($a->getZ(), $b->getZ())), new Vector3(max($a->getX(), $b->getX()), max($a->getY(), $b->getY()), max($a->getZ(), $b->getZ())), $a->getLevel(), $finishBlock, $prefs['max-checkpoints'], $prefs['record']);
		}
	}

	public function getParkours(){
		return $this->parkours;
	}

	private function createDefaultParkourArena(){
		new Config($this->getDataFolder() . "parkours.yml", Config::YAML, [
				"Lava alley" => [ // This is how i call the default parkour arena!
					"start-pos" => [
						"x" => 45,
						"y" => 8,
						"z" => 45,
						"level" => "world"
					],
					"finish-block" => [
						"x" => 45,
						"y" => 24,
						"z" => 42,
						"level" => "world"
					],
					"pos1" => [
						"x" => 20,
						"y" => 7,
						"z" => 20,
						"level" => "world"
					],
					"pos2" => [
						"x" => 60,
						"y" => 30,
						"z" => 60,
						"level" => "world"
					],
					"max-checkpoints" => 20,
					"record" => null
				]
			]);
	}

	public function finishRace(Player $player){
		if(empty($data = $this->getPlayer($player))) return false;

		$player->teleport($player->getLevel()->getSafeSpawn());
		$player->sendMessage("You've finished ".$data['arena']->getName()." parkour");
		// Broadcast, reward and blah blah blah
	}


	public function getPlayer(Player $player){
		if(isset($this->players[$player->getName()])){
			return $this->players[$player->getName()];
		}
		return [];
	}

	public function isPlaying(Player $player){
		if(isset($this->players[$player->getName()])){
			return $this->players[$player->getName()]['started'];
		} else {
			return false;
		}
	}

	public function isInParkour(Position $pos){
		foreach($this->getParkours() as $arena){
			if($arena->isInside($pos)) return $arena;
		}
		return false;
	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($sender instanceof Player){
			/////////////////////// Set Checkpoint ///////////////////////
			if(strtolower($command->getName()) === 'setcheck'){
				if(!isset($this->players[$sender->getName()]['started'])){
					$sender->sendMessage("You haven't started parkour race yet!");
					return true;
				}
				if(!$arena = $this->isInParkour($sender)){
					$sender->sendMessage("You must be in parkour arena");
					return true;
				}
				$arena = $this->isInParkour($sender);
				if($this->players[$sender->getName()]['arena']->getName() !== $arena->getName()){
					$sender->sendMessage("You must be in same parkour arena where you started!");
					return true;
				}
				if(count($this->players[$sender->getName()]['checkpoints']) >= $arena->getMaxChecks()){
					$sender->sendMessage("You have exceeded max checkpoints");
					return true;
				}
				$this->players[$sender->getName()]['checkpoints'][] = $sender->getPosition();
				$sender->sendMessage("Checkpoint set! (".$sender->getFloorX().", ".$sender->getFloorY().", ".$sender->getFloorZ().", ".$sender->getLevel()->getName().")");
				return true;
			}
			/////////////////////// Go Checkpoint ///////////////////////
			if(strtolower($command->getName())  === 'gocheck'){
				if(!isset($this->players[$sender->getName()]['started'])){
					$sender->sendMessage("You haven't started parkour race yet!");
					return true;
				}
				if(!$arena = $this->isInParkour($sender)){
					$sender->sendMessage("You must be in parkour arena");
					return true;
				}
				$arena = $this->isInParkour($sender);
				if($this->players[$sender->getName()]['arena'] !== $arena){
					$this->getLogger()->info($this->players[$sender->getName()]['arena']->getName()." !== ".$arena->getName());
					$sender->sendMessage("You must be in same parkour arena where you started!");
					return true;
				}
				if(empty($this->players[$sender->getName()]['checkpoints'])){
					$sender->sendMessage("You have no checkpoints set!");
					return true;
				}
				$cnr = count($this->players[$sender->getName()]['checkpoints']);
				$sender->teleport($this->players[$sender->getName()]['checkpoints'][($cnr - 1)]);
				$this->players[$sender->getName()]['checks']++;
				$sender->sendMessage("Teleported to last checkpoint. #$cnr, checks: ".$this->players[$sender->getName()]['checks']);
				return true;
			}
			/////////////////////// Start ///////////////////////
			if(strtolower($command->getName()) === 'start'){
				if(isset($this->players[$sender->getName()])){
					$sender->sendMessage("Parkour is already started.");
					return true;
				}
				if(!$arena = $this->isInParkour($sender)){
					$sender->sendMessage("You must be in parkour arena");
					return true;
				}
				$this->players[$sender->getName()]['checkpoints'] = [];
				$this->players[$sender->getName()]['checks'] = 0;
				$this->players[$sender->getName()]['started'] = true;
				$this->players[$sender->getName()]['arena'] = $this->isInParkour($sender);

				$sender->teleport($arena->getSpawn());

				$sender->sendMessage("You have started parkour challenge in ".$arena->getName().".");
				if($arena->getMaxChecks() === PHP_INT_MAX){
					$sender->sendMessage("You can use unlimited checkpoints");
				} else {
					$sender->sendMessage("You have ".$arena->getMaxChecks()." checkpoints to use!");
				}
				return true;
			}
			/////////////////////// Restart ///////////////////////
			if(strtolower($command->getName()) === 'restart'){
				if(!isset($this->players[$sender->getName()]['started'])){
					$sender->sendMessage(TextFormat::RED."You haven't started parkour race yet.");
					return true;
				}

				$this->players[$sender->getName()]['checkpoints'] = [];
				$this->players[$sender->getName()]['checks'] = 0;
				$this->players[$sender->getName()]['started'] = true;
				$this->players[$sender->getName()]['arena'] = $this->players[$sender->getName()]['arena'];

				$sender->teleport($arena->getSpawn());

				$sender->sendMessage("Parkour race has been restarted!");
				return true;
			}
			/////////////////////// Stop ///////////////////////
			if(strtolower($command->getName()) === 'stop'){
				if(!isset($this->players[$sender->getName()]['started'])){
					$sender->sendMessage(TextFormat::RED."You haven't started parkour race yet.");
					return true;
				}
				$sender->teleport($sender->getLevel()->getSafeSpawn());
				$sender->sendMessage("You have stopped your parkour race.");
				$sender->sendMessage("Checkpoints set: ".count($this->players[$sender->getName()]['checkpoints']));
				$sender->sendMessage("Checks: ".$this->players[$sender->getName()]['checks']);
				$sender->sendMessage("Distance till finish: ".$this->players[$sender->getName()]['arena']->getSpawn()->distance($sender));
				unset($this->players[$sender->getName()]);
			}	

		} else {
			$sender->sendMessage(TextFormat::RED."Please run this command in-game");
			return true;
		}
	}

}