<?php

namespace snow;

# Base
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

# Event
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;

# Other
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

class core extends PluginBase implements Listener{

	private $pos = [];
	private $player = [];

	public $destroy = null;
	public $anvil = null;
	public $p = null;
	public $firstLogin = true;

	public $bound = 1;

	const MAX_X = -124;
	const MAX_Z = 84;
	const MIN_X = -179;
	const MIN_Z = 17;
	const MAX_Y = 100;
	const MIN_Y = 62;

	public static $nameList = [
		"moyasan083" => "§ayudaruma334§r",
		"Zmix00" => "Xx_masato1102_xX",
		"nuyoppoi" => "negitorooi",
		"wakame0731" => "Tt_matsutake_tT",
		"hhokkun" => "wwwXXXxx_GORIRA_xxXXXwww",
		"tsukinomiya1206" => "mako427",
		"EMOnemi" => "ikemenGO_",
		"N0poh" => "天照国照彦天火明櫛玉饒速日命",
		"Parasect00" => "kirale_nisemono",
		"Pietro3939" => "Pietroganger",
		"YuuHituzi" => "【https://web.lobi.co/game/minecraftpe/group/3d95559b41d37e617b916bce6943a187545869fe】",
		"scyphas" => "中の人げえみんぐ中の人A",
		"amayzom" => "rakutencardman",
		"pupupu0328" => "Purasect00",
		"jinp" => "NakanohitogamingB",
		"Rancome152" => "§bRancome152§r"
	];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//ここに移動先の座標を配列で入れる
		$pos = [
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
		$v = new Vector3(0, 0, 0);
		$this->destroy = new DestroyBlockParticle(clone $v, Block::get(80, 0));
		$this->anvil = new AnvilFallSound(clone $v, 1);
		$this->p = $v;
	}

	public function Login(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		if(isset(self::$nameList[$name])){
			$player->setDisplayName(self::$nameList[$name]);
			$player->setNameTag(self::$nameList[$name]);
		}
	}

	public function PlayerJoinEvent(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$player->teleport($player->getSpawn());
		$player->setGamemode(0);
		$player->setXpLevel(0);
		$inv = $player->getInventory();
		$inv->clearAll();
		$this->player[$player->getName()] = [
			"isBattle" => false
		];
		$player->sendMessage("§2やぁ、君も雪合戦しに来たのかい？暇人だねぇ...");
		$player->sendMessage("§2そこに鉄ブロックがあるからタップしなよ");
		$level = $player->getLevel();
		$pos = $player->getSpawn();
		$pos->y -= 1;
		$block = $level->getBlock($pos);
		if($block->getId() == 0 && $player->getName() == "moyasan083"){
			$player->setGamemode(1);
		}
		$pos->y += 1;
		if($this->firstLogin){
			$this->resetSnow($level);
			$this->firstLogin = false;
		}
	}

	public function resetSnow($level){
		$block = Block::get(0);
		echo "雪をリセットしています";
		for($xx = self::MIN_X; $xx <= self::MAX_X; $xx++){
			for($yy = self::MIN_Y; $yy <= self::MAX_Y; $yy++){
				for($zz = self::MIN_Z; $zz <= self::MAX_Z; $zz++){
					$pos = clone $this->p;
					$pos->setComponents($xx, $yy, $zz);
					if($level->getBlock($pos)->getId() === 80){
						$level->setBlock($pos, $block);
						echo "・";
					}
				}
			}
		}
		echo "\nリセットが完了しました\n";
	}

	public function PlayerQuitEvent(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		unset($this->player[$name]);
	}

	public function Break(BlockBreakEvent $event){
		if($event->getPlayer()->getGamemode() == 0){
			$event->setCancelled(true);
		}
	}

	public function leaves(LeavesDecayEvent $event){
		$event->setCancelled(true);
	}

	public function ProjectileHitEvent(ProjectileHitEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof Snowball){
			$level = $this->getServer()->getDefaultLevel();
			$des = clone $this->destroy;
			$des->setComponents($entity->x, $entity->y, $entity->z);
			$level->addParticle($des);
			if((!isset($entity->hit) || $entity->hit < $this->bound) && $entity->getOwningEntity() instanceof Player){
				$pos = $entity->getPosition();
				$vec = new Vector3($entity->lastMotionX, $entity->lastMotionY, $entity->lastMotionZ);
				if($level->getBlockIdAt(floor($entity->x+1), floor($entity->y), floor($entity->z)) !== 0
				|| $level->getBlockIdAt(floor($entity->x-1), floor($entity->y), floor($entity->z)) !== 0){
					$vec->x *= -1;
				}
				if($level->getBlockIdAt(floor($entity->x), floor($entity->y+1), floor($entity->z)) !== 0
				|| $level->getBlockIdAt(floor($entity->x), floor($entity->y-1), floor($entity->z)) !== 0){
					$vec->y *= -1;
				}
				if($level->getBlockIdAt(floor($entity->x), floor($entity->y), floor($entity->z+1)) !== 0
				|| $level->getBlockIdAt(floor($entity->x), floor($entity->y), floor($entity->z-1)) !== 0){
					$vec->z *= -1;
				}
				$e = $this->throwSnowball($entity->getOwningEntity(), $vec, $pos);

				if(!isset($entity->hit)){
					$e->hit = 0;
				}else{
					$e->hit = $entity->hit+1;
				}
			}elseif(isset($entity->hit)){
				$entity->hit++;
			}
		}
	}

	public function throwSnowball(Player $player, Vector3 $directionVector, Vector3 $pos){
		$nbt = new CompoundTag("", [
			new ListTag("Pos", [
				new DoubleTag("", $pos->x),
				new DoubleTag("", $pos->y),
				new DoubleTag("", $pos->z)
			]),
			new ListTag("Motion", [
				new DoubleTag("", $directionVector->x),
				new DoubleTag("", $directionVector->y),
				new DoubleTag("", $directionVector->z)
			]),
			new ListTag("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
			]),
		]);

		$snowball = Entity::createEntity("Snowball", $player->getLevel(), $nbt, $player);
		$snowball->spawnToAll();
		return $snowball;
	}

	public function EntityDamageEvent(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$d = $event->getDamager();
			$p = $event->getEntity();
				if($d !== $p && $event->getCause() == 2 && $event instanceof EntityDamageByChildEntityEvent){
				$event->setDamage(20);
				$level = $this->getServer()->getDefaultLevel();

				$sound = clone $this->anvil;
				$level->addSound($this->anvil->setComponents($d->x, $d->y, $d->z));

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
				$arrow = "➤➤";
				if($dis < 1){
					$arrow = "§d♥♥§f";
				}

				$bound = "";
				if(isset($event->getChild()->hit) && $event->getChild()->hit){
					$bound = "ﾊﾞｳﾝﾄﾞ";
				}

				$this->getServer()->broadCastMessage($d->getDisplayName().$dead." {$arrow}{$bound} ".$p->getDisplayName()." (".$color.$dis."m§f)");
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
			}else{
				$event->setCancelled(true);
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

		$pos1 = clone $this->p;
		$pos1->setComponents($x, $y, $z);
		$pos2 = clone $this->p;
		$pos2->setComponents($x, $y+1, $z);
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
					$pos_1 = clone $this->p;
					$pos_1->setComponents(floor($xx+$x), floor($yy+$y), floor($zz+$z));
					$pos_2 = clone $this->p;
					$pos_2->setComponents(floor($xx+$x), floor($yy+$y-1), floor($zz+$z));
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

		if($item->getId() == 80 && $block->y >= 98 && $player->getGamemode() == 0){

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

			$player->setNameTag("");

			$player->sendMessage("§2アイテムを配布したぞ！\n§2今すぐ雪玉を投げに行こう！\n§2スコップで雪をタップで雪玉を補充しよう！");
		}

		if($item->getid() == 256){
			if($block->getId() == 2){
				$event->setCancelled(true);
			}

			if($block->getId() == 78){
				$block->onBreak(Item::get(0, 0));
				$inv->addItem(Item::get(332, 0, 3));
			}

			if($block->getId() == 80){
				$block->onBreak(Item::get(0, 0));
				$ball = Item::get(332, 0, 16);
				$inv->addItem(clone $ball);
				$inv->addItem(clone $ball);
				$inv->addItem(clone $ball);
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
