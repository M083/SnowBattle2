<?php

namespace snow;

# Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

# Event
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;

# Other
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\entity\Effect;
use pocketmine\entity\Snowball;
use pocketmine\level\sound\AnvilFallSound;

class core extends PluginBase implements Listener{

	private $pos = [];
	private $player = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//ここに移動先の座標を配列で入れる
		$pos = [
			[-123, 69, 64],
			[-134, 69, 57],
			[-128, 70, 45],
			[-140, 70, 33],
			[-135, 70, 20],
			[-156, 70, 25],
			[-169, 72, 30],
			[-167, 72, 45],
			[-161, 72, 47],
			[-151, 71, 42],
			[-150, 71, 49],
			[-152, 72, 60],
			[-167, 72, 67],
			[-152, 71, 73],
		];
		foreach ($pos as $value) {
			list($x, $y, $z) = $value;
			$this->pos[] = new Vector3($x, $y, $z);
		}
	}

	public function PlayerJoinEvent(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$player->teleport($player->getSpawn());
		$player->setGamemode(2);
		$player->setXpLevel(0);
		$inv = $player->getInventory();
		$inv->clearAll();
		$this->player[$player->getName()] = [
			"isBattle" => false
		];
		$player->sendMessage("§2やぁ、君も雪合戦しに来たのかい？暇人だねぇ...");
		$level = $player->getLevel();
		$pos = $player->getSpawn();
		$pos->y -= 1;
		$block = $level->getBlock($pos);
		if($block->getId() == 0 && $player->getName() == "moyasan083"){
			$player->setGamemode(1);
		}
		$pos->y += 1;
	}

	public function PlayerQuitEvent(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		unset($this->player[$name]);
	}

	public function ProjectileHitEvent(ProjectileHitEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof Snowball){
			$x = $entity->x;
			$y = $entity->y;
			$z = $entity->z;
			$level = $this->getServer()->getDefaultLevel();
			$level->addParticle(new DestroyBlockParticle(new Vector3($x, $y, $z), Block::get(80, 0)));
		}
	}

	public function EntityDamageEvent(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			if($event->getCause() == 2){
				$event->setDamage(20);
				$level = $this->getServer()->getDefaultLevel();
				$d = $event->getDamager();
				$p = $event->getEntity();
				$level->addSound(new AnvilFallSound($d, 1));

				$x1 = $d->x;
				$y1 = $d->y;
				$z1 = $d->z;
				$x2 = $p->x;
				$y2 = $p->y;
				$z2 = $p->z;

				$dis = floor(sqrt(pow($x1-$x2, 2)+pow($y1-$y2, 2)+pow($z1-$z2, 2))*10)/10;
				if($dis < 10){
					$color = "§2";
				}
				if($dis >= 10 && $dis < 20){
					$color = "§a";
				}
				if($dis >= 20 && $dis < 30){
					$color = "§e";
				}
				if($dis >= 30 && $dis < 40){
					$color = "§6";
				}
				if($dis >= 40 && $dis < 50){
					$color = "§c";
				}
				if($dis >= 50){
					$color = "§4";
				}

				$d_isDead = $this->isBattle($d->getName()) ? false : true;
				$dead = $d_isDead ? "§7(dead)§f" : "";

				$this->getServer()->broadCastMessage($d->getName().$dead." ➤➤ ".$p->getName()." (".$color.$dis."m§f)");
				if($d_isDead === false){
					$inv = $d->getInventory();
					$inv->addItem(Item::get(332, 0, 16));
					$inv->addItem(Item::get(80, 0, 4));
				}

				$this->setBattle($p->getName(), false);
				$lv = $p->getXpLevel();
				if($lv !== 0){
					$p->sendMessage("§a連続: ".$lv."kill");
				}
				$d->setXpLevel($d->getXpLevel()+1);
				$p->setXpLevel(0);
			}
		}
	}

/*	public function EntityDeathEvent(EntityDeathEvent $event){

		$entity = $event->getEntity();
		$x = $entity->x;
		$y = $entity->y;
		$z = $entity->z;

		$pos1 = new Vector3($x, $y, $z);
		$pos2 = new Vector3($x, $y+1, $z);
		$event->setDrops([]);
		$level = $this->getServer()->getDefaultLevel();
		$block1 = $level->getBlock($pos1);
		$block2 = $level->getBlock($pos2);
		$id1 = $block1->getId();
		$id2 = $block2->getId();

		if($id1 == 0 || $id1 == 78){

			$level->setBlock($pos1, Block::get(80, 0));
		}

		if($id2 == 0 || $id2 == 78){

			$level->setBlock($pos2, Block::get(80, 0));
		}
	}
*/
	public function PlayerRespawnEvent(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		$inv = $player->getInventory();
		$inv->clearAll();
	}

	public function PlayerDeathEvent(PlayerDeathEvent $event){
		$entity = $event->getPlayer();
		$x = $entity->x;
		$y = $entity->y;
		$z = $entity->z;

		$pos1 = new Vector3($x, $y, $z);
		$pos2 = new Vector3($x, $y+1, $z);
		$event->setKeepInventory(true);
		$level = $entity->getLevel();
		$block1 = $level->getBlock($pos1);
		$block2 = $level->getBlock($pos2);
		$id1 = $block1->getId();
		$id2 = $block2->getId();
		$event->setDeathMessage("");

		if($id1 == 0 || $id1 == 78){
			$level->setBlock($pos1, Block::get(80, 0));
		}

		if($id2 == 0 || $id2 == 78){
			$level->setBlock($pos2, Block::get(80, 0));
		}

		$snow = 3;
		$block = Block::get(78, 0);

		for($xx = -floor($snow/2); $xx < ceil($snow/2); $xx++){
			for($yy = -floor($snow/2); $yy < ceil($snow/2); $yy++){
				for($zz = -floor($snow/2); $zz < ceil($snow/2); $zz++){
					$pos_1 = new Vector3(floor($xx+$x), floor($yy+$y), floor($zz+$z));
					$pos_2 = new Vector3(floor($xx+$x), floor($yy+$y-1), floor($zz+$z));
					if($level->getBlock($pos_1)->getId() == 0 && (!$level->getBlock($pos_2)->isTransparent() || $level->getBlock($pos_2)->getId() == 18 || $level->getBlock($pos_2)->getId() == 198)){
						$level->setBlock($pos_1, $block);
					}
				}
			}
		}
	}

	public function PlayerInteractEvent(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		$block = $event->getBlock();
		$inv = $player->getInventory();
		if($item->getId() == 280){
			//座標指定用
			$player->sendMessage($block->x.", ".$block->y.", ".$block->z);
		}

		if($item->getId() == 80 && $block->y >= 32 && $player->getGamemode() == 2){

			$event->setCancelled(true);
		}

		if($block->getId() == 42){
			$player->sendMessage("§7転送中です...");
			$player->setXpLevel(0);

			/*
			$effect = Effect::getEffect(14);
			$effect->setDuration(100);
			$effect->setAmplifier(1);
			$player->addEffect($effect);
			*/

			$this->setBattle($player->getName(), true);

			$level = $player->getLevel();
			$pos = $this->pos[mt_rand(0, count($this->pos)-1)];
			$safepos = $level->getSafeSpawn($pos);

			if($safepos !== true){

				$pos = $safepos;
			}
			$player->teleport($pos);

			$inv = $player->getInventory();
			$inv->clearAll();
			$inv->addItem(Item::get(332, 0, 16));
			$inv->addItem(Item::get(256, 0));

			$player->setNameTagVisible(false);

			$player->sendMessage("§2アイテムを配布したぞ！\n§2今すぐ雪玉を投げに行こう！");
		}

		if($item->getid() == 256){
			if($block->getId() == 78){
				$block->onBreak(Item::get(0, 0));
				$inv->addItem(Item::get(332, 0, 3));
			}

			if($block->getId() == 80){
				$block->onBreak(Item::get(0, 0));
				$inv->addItem(Item::get(332, 0, 16));
				$inv->addItem(Item::get(332, 0, 16));
				$inv->addItem(Item::get(332, 0, 16));
			}
		}
	}

	private function isBattle($name){
		return $this->player[$name]["isBattle"];
	}

	private function setBattle($name, $flag){
		return $this->player[$name]["isBattle"] = $flag;
	}

}