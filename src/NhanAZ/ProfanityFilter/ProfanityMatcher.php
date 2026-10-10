<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

final class ProfanityMatcher {

	/** @var list<string> */
	private readonly array $patterns;

	/** @var list<string> */
	private readonly array $invalidPatterns;

	/** @param list<string> $patterns */
	public function __construct(array $patterns, private readonly string $replacementCharacter) {
		$valid = [];
		$invalid = [];
		foreach ($patterns as $pattern) {
			if ($pattern === "") {
				continue;
			}
			$delimiter = null;
			foreach (["~", "#", "%", "!", "/"] as $candidate) {
				if (!str_contains($pattern, $candidate)) {
					$delimiter = $candidate;
					break;
				}
			}
			if ($delimiter === null) {
				$invalid[] = $pattern;
				continue;
			}
			$regex = $delimiter . $pattern . $delimiter . "iu";
			$emptyMatch = @preg_match($regex, "");
			if ($emptyMatch === false || $emptyMatch === 1) {
				$invalid[] = $pattern;
				continue;
			}
			$valid[] = $regex;
		}
		$this->patterns = $valid;
		$this->invalidPatterns = $invalid;
	}

	/** @return list<string> */
	public function getInvalidPatterns(): array {
		return $this->invalidPatterns;
	}

	public function contains(string $message): bool {
		foreach ($this->patterns as $regex) {
			if (preg_match_all($regex, $message, $matches) > 0) {
				foreach ($matches[0] as $match) {
					if ($match !== "") {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function filter(string $message): string {
		foreach ($this->patterns as $regex) {
			$filteredMessage = preg_replace_callback(
				$regex,
				fn(array $match): string => $match[0] === "" ? "" : str_repeat($this->replacementCharacter, mb_strlen($match[0], "UTF-8")),
				$message
			);
			if ($filteredMessage !== null) {
				$message = $filteredMessage;
			}
		}

		return $message;
	}
}
