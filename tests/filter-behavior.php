<?php

declare(strict_types=1);

use NhanAZ\ProfanityFilter\ProfanityMatcher;
use NhanAZ\ProfanityFilter\ViolationTracker;

$phar = $argv[1] ?? null;
if (is_string($phar)) {
    require 'phar://' . realpath($phar) . '/src/NhanAZ/ProfanityFilter/ProfanityMatcher.php';
    require 'phar://' . realpath($phar) . '/src/NhanAZ/ProfanityFilter/ViolationTracker.php';
} else {
    require dirname(__DIR__) . '/src/NhanAZ/ProfanityFilter/ProfanityMatcher.php';
    require dirname(__DIR__) . '/src/NhanAZ/ProfanityFilter/ViolationTracker.php';
}

function check(bool $condition, string $case): void {
    if (!$condition) {
        throw new RuntimeException("Failed: $case");
    }
}

$matcher = new ProfanityMatcher(['ba+d', 'đụ', 'a/b', '[', '.*'], '*');
check(count($matcher->getInvalidPatterns()) === 2, 'invalid and empty-match regexes skipped');
check($matcher->contains('baaad'), 'variable-length regex detected');
check($matcher->filter('baaad') === '*****', 'replacement follows matched length');
check($matcher->filter('đụ') === '**', 'Unicode match preserves character count');
check($matcher->filter('a/b') === '***', 'slash in regex body supported');
check($matcher->filter('clean') === 'clean', 'clean text unchanged');
check(!(new ProfanityMatcher(['.*'], '*'))->contains('clean'), 'empty-match pattern cannot filter all text');
check(!(new ProfanityMatcher(['(?=a)'], '*'))->contains('a'), 'zero-length lookahead does not count as profanity');

$tracker = new ViolationTracker();
check(!$tracker->register('player', true, 2, 60), 'first violation does not mute');
check($tracker->register('player', true, 2, 60), 'second violation mutes');
check($tracker->isMuted('player', true), 'mute is active');
check($tracker->getRemaining('player') > 0, 'remaining duration is positive');
check(!$tracker->isMuted('player', false), 'disabled violation mode bypasses mute');
check(!$tracker->register('other', true, 0, 60), 'zero threshold disables counter');
check($tracker->register('long', true, 1, PHP_INT_MAX), 'large duration is accepted safely');
check($tracker->getRemaining('long') > 0, 'large duration stays an integer timestamp');

echo "PASS: 16 filter and violation cases\n";
