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
		$invalidCount = count($this->matcher->getInvalidPatterns());
		if ($invalidCount > 0) {
			$this->getLogger()->warning("Ignored $invalidCount invalid or empty-match profanity patterns");
		}
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
		return $this->getStringSetting("prefix", self::DEFAULT_PREFIX);
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
		return $this->getBooleanSetting("warningMode", true);
	}

	public function getCharacterReplaced(): string {
		$value = $this->getStringSetting("characterReplaced", self::DEFAULT_CHARACTER_REPLACED);
		return $value !== "" ? $value : self::DEFAULT_CHARACTER_REPLACED;
	}

	public function getShowProfanity(): bool {
		return $this->getBooleanSetting("showProfanity", true);
	}

	public function getViolationMode(): bool {
		return $this->getBooleanSetting("violationMode", true);
	}

	public function getMaxViolations(): int {
		return $this->getNonNegativeIntegerSetting("maxViolations", self::DEFAULT_MAX_VIOLATIONS);
	}

	public function getMuteDuration(): int {
		return $this->getNonNegativeIntegerSetting("muteDuration", self::DEFAULT_MUTE_DURATION);
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

		$format = $this->getStringSetting("warningMessage", self::DEFAULT_WARNING_MESSAGE);
		$this->getLogger()->info(strtr($format, [
			"{playerName}" => $player->getName(),
			"{msg}" => $message
		]));
	}

	private function getPlayerKey(Player $player): string {
		return strtolower($player->getName());
	}

	private function getStringSetting(string $key, string $default): string {
		$value = $this->getConfig()->get($key, $default);
		return is_string($value) ? $value : $default;
	}

	private function getBooleanSetting(string $key, bool $default): bool {
		$value = $this->getConfig()->get($key, $default);
		if (is_bool($value)) {
			return $value;
		}
		if (is_string($value)) {
			return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
		}
		return $default;
	}

	private function getNonNegativeIntegerSetting(string $key, int $default): int {
		$value = $this->getConfig()->get($key, $default);
		if (!is_int($value) && !is_string($value)) {
			return $default;
		}
		$parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
		return $parsed !== false ? $parsed : $default;
	}
}
