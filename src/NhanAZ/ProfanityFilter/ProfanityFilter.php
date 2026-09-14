<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

use pocketmine\lang\Language;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class ProfanityFilter extends PluginBase {

	use SingletonTrait;

	protected Config $config;

	protected Config $profanities;

	private static Language $language;

	private array $languages = [
		"eng",
		"vie"
	];

	public static function getLanguage(): Language {
		return self::$language;
	}

	public function initLanguageFiles(string $lang, array $languageFiles): void {
		$path = $this->getDataFolder() . "languages/";
		if (!is_dir($path)) {
			@mkdir($path);
		}
		foreach ($languageFiles as $file) {
			if (!is_file($path . $file . ".ini")) {
				$this->saveResource("languages/" . $file . ".ini");
			}
		}
		self::$language = new Language($lang, $path);
	}

	protected function onLoad(): void {
		self::setInstance($this);
	}

	private function initResource(): void {
		$this->saveDefaultConfig();
		$this->config = $this->getConfig();
		$this->saveResource("profanities.yml");
		$this->profanities = new Config($this->getDataFolder() . "profanities.yml", Config::YAML);
	}

	private function checkVersion(): void {
		if (VersionInfo::IS_DEVELOPMENT_BUILD) {
			$isDevelopmentBuild = ProfanityFilter::getLanguage()->translateString("is.development.build");
			$this->getLogger()->warning($isDevelopmentBuild);
		}
	}

	protected function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->initResource();
		$this->initLanguageFiles(strval($this->config->get("language", "eng")), $this->languages);
		$this->checkVersion();
	}

	public function getPrefix(): string {
		return strval($this->config->get("prefix", "&f[&cProfanityFilter&f]&r "));
	}

	public function getProfanities(): mixed {
		return $this->profanities->get("profanities", ["wtf", "đụ"]);
	}

	public function getWarningMode(): bool {
		return (bool) $this->config->get("warningMode", true);
	}

	public function getCharacterReplaced(): string {
		return strval($this->config->get("characterReplaced", "*"));
	}

	public function getShowProfanity(): bool {
		return (bool) $this->config->get("showProfanity", true);
	}

	public function containsProfanity(string $msg): bool {
		$profanities = (array) $this->getProfanities();
		$filterCount = sizeof($profanities);
		for ($i = 0; $i < $filterCount; $i++) {
			$condition = preg_match('/' . $profanities[$i] . '/iu', $msg) > 0;
			if ($condition) {
				return true;
			}
		}
		return false;
	}

	public function warningPlayer(Player $player): void {
		if ($this->getWarningMode()) {
			$prefix = $this->getPrefix();
			$warningMessage = ProfanityFilter::getLanguage()->translateString("warning.message");
			$colorize = TextFormat::colorize($prefix . $warningMessage);
			$player->sendMessage($colorize);
		}
	}

	public function handleMessage(string $msg): string {
		foreach ((array) $this->getProfanities() as $profanity) {
			if (!is_string($profanity) || $profanity === "") {
				continue;
			}

			$replacement = str_repeat(
				$this->getCharacterReplaced(),
				mb_strlen($profanity, "UTF-8")
			);
			$filteredMsg = preg_replace(
				"/" . $profanity . "/iu",
				$replacement,
				$msg
			);

			// Keep the original message when a configured pattern is invalid.
			if ($filteredMsg !== null) {
				$msg = $filteredMsg;
			}
		}

		return $msg;
	}

	public function showProfanity(Player $player, string $msg) {
		if ($this->getShowProfanity()) {
			$warningMessage = $this->config->get("warningMessage", "{playerName} > {msg}");
			// TODO: Implement InfoAPI Here (Help Wanted)
			$search = [
				"{playerName}",
				"{msg}"
			];
			$replace = [
				$player->getName(),
				$msg
			];
			$subject = $warningMessage;
			$this->getLogger()->info(str_replace($search, $replace, strval($subject)));
		}
	}
}
