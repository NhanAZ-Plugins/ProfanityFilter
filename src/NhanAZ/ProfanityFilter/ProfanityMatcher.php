<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

final class ProfanityMatcher {

	/** @var list<string> */
	private readonly array $patterns;

	public function __construct(array $patterns, private readonly string $replacementCharacter) {
		$this->patterns = array_values(array_filter(
			$patterns,
			static fn(mixed $pattern): bool => is_string($pattern) && $pattern !== ""
		));
	}

	public function contains(string $message): bool {
		foreach ($this->patterns as $pattern) {
			if (preg_match($this->buildPattern($pattern), $message) === 1) {
				return true;
			}
		}

		return false;
	}

	public function filter(string $message): string {
		foreach ($this->patterns as $pattern) {
			$replacement = str_repeat(
				$this->replacementCharacter,
				mb_strlen($pattern, "UTF-8")
			);
			$filteredMessage = preg_replace(
				$this->buildPattern($pattern),
				$replacement,
				$message
			);

			// Keep the current message when a configured pattern is invalid.
			if ($filteredMessage !== null) {
				$message = $filteredMessage;
			}
		}

		return $message;
	}

	private function buildPattern(string $pattern): string {
		return "/" . $pattern . "/iu";
	}
}
