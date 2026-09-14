<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

use pocketmine\event\Listener;
use pocketmine\lang\Language;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

final class ProfanityFilter extends PluginBase {

	use SingletonTrait;

	public const BYPASS_PERMISSION = "profanityfilter.bypass";

	private const DEFAULT_LANGUAGE = "eng";
	private const DEFAULT_PREFIX = "&f[&cProfanityFilter&f]&r ";
	private const DEFAULT_WARNING_MESSAGE = "{playerName} > {msg}";
	private const DEFAULT_CHARACTER_REPLACED = "*";
	private const DEFAULT_MAX_VIOLATIONS = 3;
	private const DEFAULT_MUTE_DURATION = 60;

	/** @var list<string> */
	private const SUPPORTED_LANGUAGES = [
		"eng",
		"vie"
	];

	/** @var list<string> */
	private const DEFAULT_PROFANITIES = [
		"wtf",
		"đụ"
	];

	private Config $profanities;
	private Language $language;
	private ProfanityMatcher $matcher;
	private ViolationTracker $violationTracker;

	protected function onLoad(): void {
		self::setInstance($this);
	}

	protected function onEnable(): void {
		$this->loadResources();
		$this->loadLanguage();
		$this->matcher = new ProfanityMatcher(
			$this->getProfanities(),
			$this->getCharacterReplaced()
		);
		$this->violationTracker = new ViolationTracker();

		$this->getServer()->getPluginManager()->registerEvents(
			new EventListener($this),
			$this
		);
		$this->logDevelopmentBuild();
	}

	private function loadResources(): void {
		$this->saveDefaultConfig();
		$this->saveResource("profanities.yml");
		$this->profanities = new Config(
			$this->getDataFolder() . "profanities.yml",
			Config::YAML
		);
	}

	private function loadLanguage(): void {
		$language = strval($this->getConfig()->get("language", self::DEFAULT_LANGUAGE));
		$this->initLanguageFiles($language, self::SUPPORTED_LANGUAGES);
	}

	public function initLanguageFiles(string $language, array $languageFiles): void {
		$path = $this->getDataFolder() . "languages/";
		if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
			throw new \RuntimeException("Unable to create language directory: " . $path);
		}

		foreach ($languageFiles as $languageFile) {
			if (!is_string($languageFile) || $languageFile === "") {
				continue;
			}

			$file = $path . $languageFile . ".ini";
			if (!is_file($file) && !$this->saveResource("languages/" . $languageFile . ".ini")) {
				throw new \RuntimeException("Unable to save language resource: " . $languageFile);
			}
		}

		if (!is_file($path . $language . ".ini")) {
			$this->getLogger()->warning(
				"Language \"" . $language . "\" was not found. Falling back to \"" . self::DEFAULT_LANGUAGE . "\"."
			);
			$language = self::DEFAULT_LANGUAGE;
		}

		$this->language = new Language($language, $path);
	}

	private function logDevelopmentBuild(): void {
		if (VersionInfo::IS_DEVELOPMENT_BUILD) {
			$this->getLogger()->warning($this->translate("is.development.build"));
		}
	}

	public function getLanguage(): Language {
		return $this->language;
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
		$message = $this->translate("mute.message", [
			"{duration}" => strval($this->getMuteRemaining($player))
		]);
		$player->sendMessage(TextFormat::colorize($this->getPrefix() . $message));
	}

	public function containsProfanity(string $message): bool {
		return $this->matcher->contains($message);
	}

	public function warningPlayer(Player $player): void {
		if (!$this->getWarningMode()) {
			return;
		}

		$message = $this->translate("warning.message");
		$player->sendMessage(TextFormat::colorize($this->getPrefix() . $message));
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

	private function translate(string $key, array $replacements = []): string {
		return strtr($this->language->translateString($key), $replacements);
	}

	private function getPlayerKey(Player $player): string {
		return strtolower($player->getName());
	}
}
