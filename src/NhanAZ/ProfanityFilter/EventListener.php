<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class EventListener implements Listener {
	private ProfanityFilter $profanityFilter;

	public function __construct(ProfanityFilter $profanityFilter) {
		$this->profanityFilter = $profanityFilter;
	}

	public function onPlayerChat(PlayerChatEvent $event): void {
		$player = $event->getPlayer();
		if ($player->hasPermission(ProfanityFilter::BYPASS_PERMISSION)) {
			return;
		}

		if ($this->profanityFilter->isPlayerMuted($player)) {
			$event->cancel();
			$this->profanityFilter->sendMuteMessage($player);
			return;
		}

		$message = $event->getMessage();
		if (!$this->profanityFilter->containsProfanity($message)) {
			return;
		}

		$this->profanityFilter->warningPlayer($player);
		$this->profanityFilter->showProfanity($player, $message);
		$event->setMessage($this->profanityFilter->handleMessage($message));

		if ($this->profanityFilter->registerViolation($player)) {
			$event->cancel();
			$this->profanityFilter->sendMuteMessage($player);
		}
	}
}
