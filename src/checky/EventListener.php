<?php
namespace checky;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;

class EventListener implements Listener {

	/** @var Checky */
	private $plugin;

	public function __construct(Checky $plugin){
		$this->plugin = $plugin;
	}

	/**
	* @param PlayerInteractEvent $ev
	*
	* @ignoreCancelled false
	* @priority MONITOR
	*/
	public function onInteract(PlayerInteractEvent $ev){
		if(!$this->getPlugin()->isPlaying($ev->getPlayer())) return;

		$data = $this->getPlugin()->getPlayer($ev->getPlayer());
		$b = $ev->getBlock();
		$fb = $data['arena']->getFinishBlock();
		if($b->getX() === $fb->getX() and $b->getY() === $fb->getY() and $b->getZ() === $fb->getZ()){
			$this->getPlugin()->finishRace($ev->getPlayer());
		}
	}

	/**
	* @param PlayerRespawnEvent $ev
	*
	* @ignoreCancelled false
	* @priority LOW
	*/
	public function onRespawn(PlayerRespawnEvent $ev){
		if(!$this->getPlugin()->isPlaying($ev->getPlayer())) return;

		$data = $this->getPlugin()->getPlayer($ev->getPlayer());
		if(!empty($data['checkpoints'])){
			$ev->setRespawnPosition($data['checkpoints'][(count($data['checkpoints']))] - 1);
			$ev->getPlayer()->sendMessage("Teleported to last checkpoint");
		} else {
			$ev->setRespawnPosition($data['arena']->getSpawn());
			$ev->getPlayer()->sendMessage("No checkpoints was set! Teleported to start.");
		}
	}

	public function getPlugin(){
		return $this->plugin;
	}
}