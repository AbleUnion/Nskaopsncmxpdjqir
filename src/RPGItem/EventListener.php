<?php
namespace RPGItem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\plugin\Plugin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\nbt\NBT;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Player;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\DiamondPickaxe;
use pocketmine\item\Tool;
class EventListener implements Listener {
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
	}
	public function debug($msg) {
		$this->plugin->getServer()->getLogger()->info('DEBUG:' . $msg);
		return true;
	}
	public function ItemHeld(PlayerItemHeldEvent $e) {
		if($e->getItem()->getId() == 0) {
			return true;
		}
		if (!isset($e->getItem()->getNamedTag()->rpgitem)) {
			return true;
		}
		if (!is_file($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json')) {
			$e->getPlayer()->getInventory()->removeItem($e->getItem());
			$e->getPlayer()->sendMessage('등록되지 않은 rpgitem으로, 회수되었습니다');
			return true;
		}
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if ($e->getItem()->isTool()) {
			$isdatamod = false;
		} else {
			$isdatamod = $e->getItem()->getDamage() !== $item['itemcode'][1];
		}
		if ((int)$e->getItem()->getId() !== (int)$item['itemcode'][0] or $isdatamod) {
			$e->getPlayer()->sendMessage('Debug: updating...');
			//아이템 제거
			$nbttag = array();
			if(isset($e->getItem()->getNamedTag()->rpg_dura)) {
				$nbttag['rpg_dura'] = $e->getItem()->getNamedTag()->rpg_dura->getValue();
				$rpg_dura = ' ' . $nbttag['rpg_dura'] . '/' . $item['dura'];
			} else {
				$rpg_dura = NULL;
			}
			$nbttag['rpgitem'] = $e->getItem()->getNamedTag()->rpgitem->getValue();
			$nbttag['CanPlaceOn'] = array("air");
			if (isset($item['display'])) {
				$nbttag['display'] = array("Name" => urldecode($item['display']) . $rpg_dura);
			}
			//아이템 코드
			$gitem = \pocketmine\item\Item::get((int)$item['itemcode'][0], (int)$item['itemcode'][1], 1);
			$gitem->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
			$e->getPlayer()->getInventory()->removeItem($e->getItem());
			$e->getPlayer()->getInventory()->setItemInHand($gitem);
		}
		return true;
	}
	public function blockBreak(BlockBreakEvent $e) {
		if (!isset($e->getItem()->getNamedTag()->rpgitem)) {
			return true;
		}
		$nbttag = array();
		//내구도 부족시 회수
		if ($e->getItem()->isTool()) {
			$e->getItem()->setDamage(0);
		}
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if(isset($e->getItem()->getNamedTag()->rpg_dura)) {
			$nbttag['rpg_dura'] = (int)$e->getItem()->getNamedTag()->rpg_dura->getValue() - 1;
			$rpg_dura = ' ' . $nbttag['rpg_dura'] . '/' . $item['dura'];
			if($nbttag['rpg_dura'] <= 0) {
				$e->getItem()->setCount(0);
				return true;
			}
		} else {
			$rpg_dura = NULL;
		}
		$nbttag['rpgitem'] = $e->getItem()->getNamedTag()->rpgitem->getValue();
		$nbttag['CanPlaceOn'] = array("air");
		if (isset($item['display'])) {
			$nbttag['display'] = array("Name" => urldecode($item['display']) . $rpg_dura);
		}
		//$gitem = \pocketmine\item\Item::get((int)$item['itemcode'][0], (int)$item['itemcode'][1], 1);
		//$gitem->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
		//$e->getPlayer()->getInventory()->removeItem($e->getItem());
		//$e->getPlayer()->getInventory()->setItemInHand($gitem);
		$e->getItem()->clearNamedTag();
		$e->getItem()->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
		return true;
	}
	public function EntityDamage(EntityDamageEvent $e) {
		if(substr($e->getEventName(), -25) !== 'EntityDamageByEntityEvent') {
			return true;
		}
		if(!$e->getDamager() instanceof Player) {
			return true;
		}
		self::debug('its player');
		$player = $this->plugin->getServer()->getPlayer($e->getDamager()->getNameTag());
		$iteminhand = $player->getItemInHand();
		if (!isset($iteminhand->getNamedTag()->rpgitem)) {
			return true;
		}
		self::debug('is rpgitem');
		$nbttag = array();
		//내구도 부족시 회수
		if ($iteminhand->isTool()) {
			$iteminhand->setDamage(0);
			self::debug('its tool');
		}
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $iteminhand->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if (isset($item['damage'])) {
			$e->getEntity()->setHealth($e->getEntity()->getHealth() - (int)$item['damage']);
			if($e->getEntity()->getHealth() <= 0) {
				$e->getEntity()->kill();
			}
		}
		if(isset($iteminhand->getNamedTag()->rpg_dura)) {
			self::debug('set dura');
			$nbttag['rpg_dura'] = (int)$iteminhand->getNamedTag()->rpg_dura->getValue() - 1;
			$rpg_dura = ' ' . $nbttag['rpg_dura'] . '/' . $item['dura'];
			if($nbttag['rpg_dura'] <= 0) {
				$iteminhand->setCount(0);
				return true;
			}
		} else {
			$rpg_dura = NULL;
		}
		$nbttag['rpgitem'] = $iteminhand->getNamedTag()->rpgitem->getValue();
		$nbttag['CanPlaceOn'] = array("air");
		if (isset($item['display'])) {
			$nbttag['display'] = array("Name" => urldecode($item['display']) . $rpg_dura);
		}

		$gitem = \pocketmine\item\Item::get((int)$item['itemcode'][0], (int)$item['itemcode'][1], 1);
		$gitem->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
		$iteminhand->setCount(0);
		//$player->getInventory()->setItemInHand($gitem);
		return true;
	}
}