<?php
namespace RPGItem;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\command\defaults\GiveCommand;
use pocketmine\event\player\PlayerItemHeldEvent;
class Main extends PluginBase implements Listener {
	static function rc($g) {
		if ((int)$g >= 100) {
			$g = 100;
		}
		if (mt_rand(1,100) <= $g) {
			return true;
		} else {
			return false;
		}
		
	}
	public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
		}
		if(!file_exists($this->getDataFolder() . 'items')){
			mkdir($this->getDataFolder() . 'items');
		}
	}
	public function onCommand(CommandSender $sender,Command $command, $label,array $args) {
		$args[0] = urlencode($args[0]);
		if ($command->getName() == 'rpgitem') {
			if (!isset($args[0])) {
				$sender->sendMessage(TextFormat::WHITE . 'rpgitem <사용할 이름>');
				return true;
			}
			if (!isset($args[1])) {
				$message = <<<END
일부 미구현이 있을수 있습니다
/rpgitem <자기가하고싶은이름> create
/rpgitem <아까자기가적은이름> give
/rpgitem <자기가적은이름> display (그아이템의정하고싶은이름)
/rpgitem <자기가적은이름> item (아이템코드)
/rpgitem <자기가적은이름> damage (숫자)
/rpgitem <자기가적은이름> power lightning (확률)
/rpgitem <자기가적은이름> power fireball (쿨타임)
/rpgitem <자기가적은이름> power ice (쿨타임)
/rpgitem <자기가적은이름> power rainbow (쿨타임) (양털의갯수)
/rpgitem <자기가적은이름> power arrow (쿨타임)
/rpgitem <자기가적은이름> power tntcannon (쿨타임)
/rpgitem <자기가적은이름> power knockup (확률) (올라가는높이)
/rpgitem <자기가적은이름> power teleport (쿨타임) (이동블럭)
/rpgitem <자기가적은이름> power rumble (쿨타임) (띄우는높이) {마법의능력이갈수있는거리}
/rpgitem <자기가적은이름> power potiontick (포션레벨) (포션효과)
/rpgitem <자기가적은이름> power potionself (쿨타임) (몇초지속) (포션레벨) (포션효과)
/rpgitem <자기가적은이름> power potion (확률) (지속시간) (포션레벨) (포션효과)
/rpgitem <자기가적은이름> durability (횟수)
/rpgitem <자기가적은이름> armour Armour (Integer (0-100))
/rpgitem <자기가적은이름> removepower (능력)
/rpgitem <자기가적은이름> remove
END;
				$sender->sendMessage(TextFormat::WHITE . $message);
				return true;
			}
			//create
			if ($args[1] == 'create') {
				if (is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '이미 있는 아이템입니다');
					return true;
				}
				file_put_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json', '{}');
				$sender->sendMessage(TextFormat::WHITE . urldecode($args[0]) . '을 생성했습니다');
				return true;
			}
			//display
			if ($args[1] == 'display') {
				if (!is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '없는 아이템 이름입니다');
				}
				if (!isset($args[2])) {
					$sender->sendMessage(TextFormat::WHITE . '/rpgitem <자기가적은이름> display (그아이템의정하고싶은이름)');
					return true;
				}
				$item = json_decode(file_get_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json'), true);
				$item['display'] = urlencode($args[2]);
				file_put_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json', json_encode($item));
				return true;
			}
			//remove
			if ($args[1] == 'remove') {
				if (!is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '없는 아이템 이름입니다');
					return true;
				} else {
					unlink($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json');
					$sender->sendMessage(TextFormat::WHITE . '삭제하였습니다');
					return true;
				}
			}
			//removepower
			if ($args[1] == 'removepower') {
				if (!is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '없는 아이템 이름입니다');
				}
				if (!isset($args[2])) {
					$sender->sendMessage(TextFormat::white . '/rpgitem <자기가적은이름> removepower (지울 능력)');
					return true;
				}
				$item = json_decode(file_get_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json'), true);
				if (!isset($item[$args[2]])) {
					$sender->sendMessage(TextFormat::WHITE . '적용되어있지 않은 능력입니다');
					return true;
				}
				unset($item[$args[2]]);
				file_put_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json', json_encode($item));
				return true;
			}
			//item
			if ($args[1] == 'item') {
				if (!isset($args[2])) {
					$sender->sendMessage(TextFormat::WHITE . '아이템코드를 입력하여 주십시오');
					return true;
				}
				if (!is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '없는 아이템 이름입니다');
					return true;
				} else {
					$item = json_decode(file_get_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json'), true);
					$item['itemcode'] = $args[2];
					file_put_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json', json_encode($item));
					$sender->sendMessage(TextFormat::WHITE . '아이템코드를' . $args[2] . '로 설정하였습니다');
					return true;
				}
			}
			//give
			if ($args[1] == 'give') {
				if (!is_file($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json')) {
					$sender->sendMessage(TextFormat::WHITE . '없는 아이템 이름입니다');
					return true;
				}
				if (isset($args[2])) {
					if (!$this->getServer()->getPlayer($args[2]) instanceof Player) {
						$sender->sendMessage(TextFormat::WHITE . '없는 플레이어입니다');
						return true;
					}
				}				
				$item = json_decode(file_get_contents($this->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $args[0] . '.json'), true);
				//아이템 이름 적용
				$nbttag['rpgitem'] = $args[0];
				$nbttag['CanPlaceOn'] = array("air");
				if (isset($item['display'])) {
					$nbttag['display'] = array("Name" => urldecode($item['display']));
					$sender->sendMessage($nbttag['display']);
				}
				//아이템 코드
				if (!isset($item['itemcode'])) {
					$ic = 268;
				}
				if (!isset($args[2])) {
					$args[2] = $sender->getName();
				}
				$cargs[0] = $args[2];
				$cargs[1] = $ic;
				$cargs[2] = 1;
				$cargs[3] = stripslashes( json_encode($nbttag) );
				$sender->sendMessage($cargs[3]);
				$givecommand = new GiveCommand('rpgitem');
				$givecommand->execute($sender, 'give', $cargs);
			}
		}
	}
	public function onItemHeld(PlayerItemHeldEvent $e) {
	}
}
