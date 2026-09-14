<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class ProfanityFilter extends PluginBase {

	use SingletonTrait;

	public const BYPASS_PERMISSION = "profanityfilter.bypass";

	private const DEFAULT_PREFIX = "&f[&cProfanityFilter&f]&r ";
	private const DEFAULT_WARNING_MESSAGE = "{playerName} > {msg}";
	private const DEFAULT_CHARACTER_REPLACED = "*";
	private const DEFAULT_MAX_VIOLATIONS = 3;
	private const DEFAULT_MUTE_DURATION = 60;
	private const WARNING_MESSAGE = "&cPlease don't use profanity!";
	private const MUTE_MESSAGE = "&cYou cannot chat for {duration} seconds because of repeated profanity.";

	/** @var list<string> */
	private const DEFAULT_PROFANITIES = [
		"wtf",
		"đụ"
	];

	private Config $profanities;
	private ProfanityMatcher $matcher;
	private ViolationTracker $violationTracker;

	protected function onLoad(): void {
		self::setInstance($this);
	}

	protected function onEnable(): void {
		$this->loadResources();
		$this->matcher = new ProfanityMatcher(
			$this->getProfanities(),
			$this->getCharacterReplaced()
		);
		$this->violationTracker = new ViolationTracker();

		$this->getServer()->getPluginManager()->registerEvents(
			new EventListener($this),
			$this
		);
	}

	private function loadResources(): void {
		$this->saveDefaultConfig();
		$this->saveResource("profanities.yml");
		$this->profanities = new Config(
			$this->getDataFolder() . "profanities.yml",
			Config::YAML
		);
	}

	public function getPrefix(): string {
		return strval($this->getConfig()->get("prefix", self::DEFAULT_PREFIX));
	}

	/** @return list<string> */
	public function getProfanities(): array {
		$profanities = $this->profanities->get("profanities", self::DEFAULT_PROFANITIES);
		if (!is_array($profanities)) {
			return self::DEFAULT_PROFANITIES;
		}

		return array_values(array_filter(
			$profanities,
			static fn(mixed $profanity): bool => is_string($profanity) && $profanity !== ""
		));
	}

	public function getWarningMode(): bool {
		return (bool) $this->getConfig()->get("warningMode", true);
	}

	public function getCharacterReplaced(): string {
		return strval($this->getConfig()->get("characterReplaced", self::DEFAULT_CHARACTER_REPLACED));
	}

	public function getShowProfanity(): bool {
		return (bool) $this->getConfig()->get("showProfanity", true);
	}

	public function getViolationMode(): bool {
		return (bool) $this->getConfig()->get("violationMode", true);
	}

	public function getMaxViolations(): int {
		return max(0, (int) $this->getConfig()->get("maxViolations", self::DEFAULT_MAX_VIOLATIONS));
	}

	public function getMuteDuration(): int {
		return max(0, (int) $this->getConfig()->get("muteDuration", self::DEFAULT_MUTE_DURATION));
	}

	public function isPlayerMuted(Player $player): bool {
		return $this->violationTracker->isMuted(
			$this->getPlayerKey($player),
			$this->getViolationMode()
		);
	}

	public function registerViolation(Player $player): bool {
		return $this->violationTracker->register(
			$this->getPlayerKey($player),
			$this->getViolationMode(),
			$this->getMaxViolations(),
			$this->getMuteDuration()
		);
	}

	public function getMuteRemaining(Player $player): int {
		return $this->violationTracker->getRemaining($this->getPlayerKey($player));
	}

	public function sendMuteMessage(Player $player): void {
		$message = str_replace(
			"{duration}",
			strval($this->getMuteRemaining($player)),
			self::MUTE_MESSAGE
		);
		$player->sendMessage(TextFormat::colorize($this->getPrefix() . $message));
	}

	public function containsProfanity(string $message): bool {
		return $this->matcher->contains($message);
	}

	public function warningPlayer(Player $player): void {
		if (!$this->getWarningMode()) {
			return;
		}

		$player->sendMessage(TextFormat::colorize($this->getPrefix() . self::WARNING_MESSAGE));
	}

	public function handleMessage(string $message): string {
		return $this->matcher->filter($message);
	}

	public function showProfanity(Player $player, string $message): void {
		if (!$this->getShowProfanity()) {
			return;
		}

		$format = strval($this->getConfig()->get("warningMessage", self::DEFAULT_WARNING_MESSAGE));
		$this->getLogger()->info(strtr($format, [
			"{playerName}" => $player->getName(),
			"{msg}" => $message
		]));
	}

	private function getPlayerKey(Player $player): string {
		return strtolower($player->getName());
	}
}
