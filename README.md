# ProfanityFilter

ProfanityFilter masks configured patterns in player chat. Repeated matching messages can temporarily mute a player. Server operators can bypass filtering with a permission.

## Requirements and compatibility

- The manifest requires Axolotl-PM API 5.0.0. The [build workflow](.github/workflows/build.yml) pins the exact server source used for PHPStan and PHAR verification. Compatibility with every intermediate 5.x release has not been established.
- No external plugin dependency is required.
- The filter and violation tests run without a Minecraft client. A connected player chat test has not been completed in this maintenance campaign.

## Installation

Download the PHAR from [GitHub Releases](https://github.com/NhanAZ-Plugins/ProfanityFilter/releases), place it in the server's `plugins` directory, and restart Axolotl-PM. The plugin writes `config.yml` and `profanities.yml` to `plugins/ProfanityFilter` when the server uses the default plugins directory. With a custom plugins directory, look for the ProfanityFilter data directory there.

## Configuration

Edit `profanities.yml` to list words or PCRE expression bodies without surrounding delimiters. Matching is case insensitive and Unicode aware. A plain entry can also match inside a longer word, so use word boundaries in the expression when necessary. Malformed expressions and expressions that match empty input are skipped with a startup warning. Zero-length matches within a message are ignored.

```yaml
profanities:
  - "wtf"
  - "ba+d"
```

The matched text is replaced by `characterReplaced` once per Unicode code point. For example, with `*` as the replacement, `ba+d` changes `baaad` to `*****`.

The main `config.yml` settings control these behaviors.

| Setting | Purpose | Default |
|---|---|---|
| `prefix` | Prefix for player warnings and mute messages | Colored ProfanityFilter label |
| `characterReplaced` | Text repeated for each matched code point | `*` |
| `warningMode` | Warn players when their message matches | `true` |
| `warningMessage` | Console log format using `{playerName}` and `{msg}` | `{playerName} > {msg}` |
| `showProfanity` | Log the original matching message to the console | `true` |
| `violationMode` | Enable the violation counter and temporary mute | `true` |
| `maxViolations` | Number of matching messages before a mute, or `0` to disable it | `3` |
| `muteDuration` | Mute duration in seconds, or `0` to disable it | `60` |

`showProfanity` writes unmasked player messages to server logs. Disable it if those logs should not retain that text. The violation counter and mute state are kept in memory and reset when the plugin or server restarts. Restart to load edited configuration.

## Permissions and behavior

`profanityfilter.bypass` defaults to operators and skips both filtering and mute checks. Other players receive a warning when enabled. Before the threshold, the matching message is masked. At the threshold, that message is cancelled and the player receives a temporary mute notice. The plugin has no commands.

## Build and verification

The [workflow](.github/workflows/build.yml) runs the filter and violation fixture, PHPStan at maximum level, and a PHAR build with [DevTools](https://github.com/NhanAZ/DevTools). DevTools and Axolotl-PM source are pinned to reviewed revisions in CI. The verified PHAR is available as a workflow artifact before a stable release is created.

Run `php tests/filter-behavior.php` to check the matcher and mute counter locally. The retained Poggit manifest serves existing development-build consumers. GitHub Releases contain stable artifacts after their release checks pass.

## Support and license

Report bugs in [GitHub Issues](https://github.com/NhanAZ-Plugins/ProfanityFilter/issues) and include the plugin version, server API and relevant configuration without posting private chat logs. The existing [MIT license](LICENSE) is retained.
