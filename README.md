# ProfanityFilter

Simple profanity filter for PocketMine-MP API 5.

## Installation

1. Put the plugin `.phar` in `plugins/`.
2. Start the server.
3. Edit `plugin_data/ProfanityFilter/` if needed.

## Configuration

- `profanities.yml`: words or regular-expression patterns to filter.
- `characterReplaced`: replacement character.
- `warningMode`: warn players when profanity is detected.
- `showProfanity`: log filtered messages to the console.
- `violationMode`: enable temporary chat mutes.
- `maxViolations`: violations before a mute.
- `muteDuration`: mute duration in seconds.

## Permission

`profanityfilter.bypass` — bypass filtering and mutes.
