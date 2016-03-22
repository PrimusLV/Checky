<?php
namespace checky;

use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\level\Level;

class Parkour {

	/** @var Checky */
	private $plugin;
	
	/** @var string */
	protected $name;

	/** @var Vector3 */
	protected $spawn;
	protected $pos1;
	protected $pos2;
	
	/** @var Block */
	protected $finishBlock;

	/** @var Level */
	protected $level;
	
	/** @var int */
	protected $record;
	protected $maxChecks;

	public function  __construct(Checky $plugin, $name, Position $spawn, Vector3 $pos1, Vector3 $pos2, Level $level, Block $finishBlock, $maxChecks, $record){
		if($maxChecks < 0) $maxChecks = PHP_INT_MAX; // Unlimited checkpoints
		$this->name = $name;
		$this->plugin = $plugin;
		$this->spawn = $spawn;
		$this->level = $level;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->finishBlock = $finishBlock;
		$this->maxChecks = $maxChecks;
		$this->record = $record;

		$finishBlock->getLevel()->setBlock($finishBlock, new Block(247, 1, 0));
	}

	public function getMaxChecks(){
		return $this->maxChecks;
	}

	public function getPositions(){
		return [$this->pos1, $this->pos2];
	}

	public function getSpawn(){
		return $this->spawn;
	}

	public function getFinishBlock(){
		return $this->finishBlock;
	}

	public function isInside(Position $test){
		$a = $this->getPositions()[0];
		$b = $this->getPositions()[1];
		// TODO
		return ($test->getX() >= $a->getX() && $test->getX() <= $b->getX() && $test->getY() >= $a->getY() && $test->getY() <= $b->getY() && $test->getZ() >= $a->getZ() && $test->getZ() <= $b->getZ());
	}

	public function border(){
		$a = $this->getPositions()[1];
		$b = $this->getPositions()[0];

		var_dump($b);
		var_dump($a);

		for($minX = $b->getX(); $minX <= $a->getX(); $minX++){
			for($minY = $b->getY(); $minY <= $a->getY(); $minY++){
				for($minZ = $b->getZ(); $minZ <= $a->getZ(); $minZ++){
					if($a->getZ() - $minZ === 0 or $a->getY() - $minY === 0 or $a->getX() - $minX === 0 or $a->getZ() - $minZ === $minZ or $a->getY() - $minY === $minY or $a->getX() - $minX === $minX){
						$this->getLevel()->setBlock(new Vector3($minX, $minY, $minZ), new Block(20, 0, 1));
						$this->plugin->getLogger()->info($minX.", ".$minY.", ".$minZ."");
					} else {
						$this->getLevel()->setBlock(new Vector3($minX, $minY, $minZ), new Block(0, 0, 1));
					}
				}
			}
		}

		return true;
	}

	public function getLevel(){
		return $this->level;
	}

	public function getName(){
		return $this->name;
	}
}