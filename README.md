# ProfanityFilter

ProfanityFilter is a PocketMine-MP plugin that detects configured profanity in
player chat, masks matching text, and optionally applies a temporary chat mute
after repeated violations.

## Features

- Case-insensitive profanity detection with Unicode support.
- Configurable profanity patterns from `profanities.yml`.
- Replacement characters configured through `config.yml`.
- Player warnings and optional console logging of filtered messages.
- Per-player violation tracking with configurable temporary chat mutes.
- Bypass permission for moderators and administrators.
- English and Vietnamese language files.

## Requirements

- PocketMine-MP API 4.0.0 or newer.
- PHP 8.0 or newer, as required by PocketMine-MP API 4.

## Installation

1. Download the plugin `.phar` from the project's release or build page.
2. Place the file in the server's `plugins/` directory.
3. Start or restart the server.
4. Edit the generated files in `plugin_data/ProfanityFilter/`.

The plugin creates `config.yml`, `profanities.yml`, and the configured language
files on first start.

## Configuration

The default `config.yml` contains the following options:

```yaml
language: "eng"
prefix: "&f[&cProfanityFilter&f]&r "
warningMode: true
warningMessage: "{playerName} > {msg}"
characterReplaced: "*"
showProfanity: true

# Repeated-violation handling
violationMode: true
maxViolations: 3
muteDuration: 60
```

### Violation handling

Every chat message containing profanity counts as one violation for that
player. When `maxViolations` is reached, the message is cancelled and the
player is muted for `muteDuration` seconds. Further chat messages are blocked
until the mute expires.

- Set `violationMode` to `false` to disable violation handling.
- Set `maxViolations` to `0` to disable the violation counter.
- Set `muteDuration` to `0` to disable automatic mutes.
- The mute text is translated through the `mute.message` key in the selected language file.
- Use `{duration}` in `mute.message` to display the remaining mute time.
- Violation and mute state is kept in memory and resets when the server stops.

### Profanity list

Edit `plugin_data/ProfanityFilter/profanities.yml`:

```yaml
profanities:
  - "wtf"
  - "đụ"
```

Entries are used as case-insensitive regular-expression patterns. Escape regex
characters when you want to match them literally.

### Languages

Set `language` to `eng` or `vie`. The plugin loads language files from
`plugin_data/ProfanityFilter/languages/`.

## Permissions

| Permission | Default | Description |
| --- | --- | --- |
| `profanityfilter.bypass` | Operator | Bypass profanity filtering and violation mutes. |

## Developer API

Other plugins can obtain the singleton instance and use the public methods on
`NhanAZ\ProfanityFilter\ProfanityFilter`, including:

- `containsProfanity(string $message): bool`
- `handleMessage(string $message): string`
- `getProfanities(): mixed`
- `isPlayerMuted(Player $player): bool`
- `getMuteRemaining(Player $player): int`

## Building from source

This repository follows the standard PocketMine-MP plugin layout. The source is
under `src/`, bundled resources are under `resources/`, and the plugin metadata
is defined in `plugin.yml`.

Build status and automated packaging are provided by
[Poggit-CI](https://poggit.pmmp.io/ci/nhanaz-pm-pl/ProfanityFilter).

## License

ProfanityFilter is licensed under the [GNU Lesser General Public License v2.1](LICENSE).
