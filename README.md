## Writing Streak

Adds a panel and shows your current writing streak.

Built on [merkushin/wpal](https://github.com/merkushin/wpal).

### Development

Requirements: PHP 7.4+, Composer 2, Node.js 20.9+, WP-CLI (for translations).

```bash
make install   # composer install && npm ci
make build-js  # build assets into assets/dist
make test      # run PHPUnit
make lint      # WordPress Coding Standards and PHP 7.4+ compatibility (make lint-fix to auto-fix)
```

CI (`.github/workflows/ci.yml`) runs the linter, checks that `languages/writing-streak.pot` is up to date and that versions match, runs the tests on PHP 7.4–8.4, builds the plugin (uploaded as the `writing-streak` artifact) and runs [Plugin Check](https://wordpress.org/plugins/plugin-check/) on the build.

In a development checkout the plugin loads `vendor/autoload.php`, so it can be symlinked into a local WordPress install as-is.

### Translations

All user-facing text lives in `src/views/` and uses the `writing-streak` text domain. `languages/` holds the template (`writing-streak.pot`) and a `.po` file per locale: de_DE, es_ES, fr_FR, it_IT, ja, nl_NL, pl_PL, pt_BR, pt_PT, ru_RU, tr_TR, uk and zh_CN.

After changing strings, regenerate the template and merge it into every `.po` file (needs [WP-CLI](https://wp-cli.org/)):

```bash
make i18n
```

Then translate the new entries. `.mo` and `.l10n.php` files are compiled during `make dist` and are not committed; run `make i18n-compile` to compile them in a development checkout. Once the plugin is on WordPress.org, language packs from translate.wordpress.org take precedence over the bundled files.

### Building a release

```bash
make dist
```

This creates `writing-streak.zip`. The build happens in `build/writing-streak`, so the working copy is left untouched:

1. Plugin files and built assets are copied to `build/writing-streak`, and translations are compiled.
2. Runtime dependencies are installed there and prefixed with [wp-scoper](https://github.com/veronalabs/wp-scoper) into `vendor-prefixed/` (namespace `Merkushin\Wpstreak\Vendor`), and the `use` statements in `src/` are rewritten to match.
3. `vendor/` and the Composer files are removed and the directory is zipped.

`vendor-prefixed/` is never committed. The wp-scoper Composer plugin is disabled in `composer.json` (`allow-plugins`) so it doesn't rewrite the sources on `composer install`; it only runs from `make dist`.

### Releasing

1. Bump the version in `writing-streak.php` (header), `readme.txt` (`Stable tag`), `Wpstreak::VERSION` and `package.json`, and add a changelog entry to `readme.txt`. `make version-check` confirms they match.
2. Push a tag with that version, e.g. `v1.0.1`. The release workflow builds the zip, deploys it to WordPress.org SVN with the listing assets from `.wordpress-org/`, and creates a GitHub release with the zip attached.

The workflow needs the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets (your WordPress.org username and SVN password). The very first version is submitted by hand at [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/); tags only deploy after the plugin is approved.

