<?php
namespace RPGItem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\plugin\Plugin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\nbt\NBT;
use pocketmine\event\entity\EntityDamageByEntityEvent;
class EventListener implements Listener {
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
	}
	public function ItemHeld(PlayerItemHeldEvent $e) {
		if($e->getItem()->getId() == 0) {
			return true;
		}
		if (!isset($e->getItem()->getNamedTag()->rpgitem)) {
			return true;
		}
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if (!is_file($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json')) {
			$e->getPlayer()->getInventory()->removeItem($e->getItem());
			$e->getPlayer()->sendMessage('등록되지 않은 rpgitem으로, 회수되었습니다');
			return true;
		}
		if ($e->getItem()->getId() . ':' . $e->getItem()->getDamage() !== $item['itemcode'][0] . ':' . $item['itemcode'][1]) {
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
		if ($e->getItem()->isTool() == true) {
			$e->getItem()->setDamage('0');
		}
		$nbttag = array();
		//내구도 부족시 회수
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if(isset($e->getItem()->getNamedTag()->rpg_dura)) {
			$nbttag['rpg_dura'] = (int)$e->getItem()->getNamedTag()->rpg_dura->getValue() - 1;
			$rpg_dura = ' ' . $nbttag['rpg_dura'] . '/' . $item['dura'];
			if($nbttag['rpg_dura'] <= 0) {
				$e->getPlayer()->getInventory()->removeItem($e->getItem());
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
		$gitem = \pocketmine\item\Item::get((int)$item['itemcode'][0], (int)$item['itemcode'][1], 1);
		$gitem->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
		$e->getPlayer()->getInventory()->removeItem($e->getItem());
		$e->getPlayer()->getInventory()->setItemInHand($gitem);
		return true;
	}
	/*
	public function onHit(EntityDamageByEntityEvent $e) {
		$player = $this->plugin->getServer()->getPlayer($e->getDamager()->getNameTag());
		if (!isset($e->getItem()->getNamedTag()->rpgitem)) {
			return true;
		}
		$nbttag = array();
		//내구도 부족시 회수
		$item = json_decode(file_get_contents($this->plugin->getDataFolder() . 'items' . DIRECTORY_SEPARATOR . $e->getItem()->getNamedTag()->rpgitem->getValue() . '.json'), true);
		if(isset($e->getItem()->getNamedTag()->rpg_dura)) {
			$nbttag['rpg_dura'] = (int)$e->getItem()->getNamedTag()->rpg_dura->getValue() - 1;
			$rpg_dura = ' ' . $nbttag['rpg_dura'] . '/' . $item['dura'];
			if($nbttag['rpg_dura'] <= 0) {
				$player->getInventory()->removeItem($e->getItem());
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
		$gitem = \pocketmine\item\Item::get((int)$item['itemcode'][0], (int)$item['itemcode'][1], 1);
		$gitem->setNamedTag(NBT::parseJSON(json_encode($nbttag)));
		$player->getInventory()->removeItem($e->getItem());
		$player->getInventory()->setItemInHand($gitem);
		return true;
	}
	*/
}