<?php

declare(strict_types=1);

namespace NhanAZ\ProfanityFilter;

final class ViolationTracker {

	/** @var array<string, int> */
	private array $violations = [];

	/** @var array<string, int> */
	private array $mutedUntil = [];

	public function isMuted(string $playerKey, bool $enabled): bool {
		if (!$enabled || !isset($this->mutedUntil[$playerKey])) {
			return false;
		}

		if ($this->mutedUntil[$playerKey] <= time()) {
			unset($this->mutedUntil[$playerKey]);
			return false;
		}

		return true;
	}

	public function register(
		string $playerKey,
		bool $enabled,
		int $maxViolations,
		int $muteDuration
	): bool {
		if (!$enabled || $maxViolations < 1 || $muteDuration < 1) {
			return false;
		}

		$currentViolations = ($this->violations[$playerKey] ?? 0) + 1;
		if ($currentViolations < $maxViolations) {
			$this->violations[$playerKey] = $currentViolations;
			return false;
		}

		unset($this->violations[$playerKey]);
		$this->mutedUntil[$playerKey] = time() + $muteDuration;
		return true;
	}

	public function getRemaining(string $playerKey): int {
		$remaining = ($this->mutedUntil[$playerKey] ?? 0) - time();
		if ($remaining <= 0) {
			unset($this->mutedUntil[$playerKey]);
			return 0;
		}

		return $remaining;
	}
}
