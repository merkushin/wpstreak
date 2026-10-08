## WP Streak

Adds a panel and shows your current writing streak.

Built on [merkushin/wpal](https://github.com/merkushin/wpal).

### Development

Requirements: PHP 7.4+, Composer 2, Node.js 20.9+, WP-CLI (for translations).

```bash
make install   # composer install && npm ci
make build-js  # build assets into assets/dist
make test      # run PHPUnit
```

In a development checkout the plugin loads `vendor/autoload.php`, so it can be symlinked into a local WordPress install as-is.

### Translations

All user-facing text lives in `src/views/` and uses the `wpstreak` text domain. `languages/` holds the template (`wpstreak.pot`) and a `.po` file per locale: de_DE, es_ES, fr_FR, it_IT, ja, nl_NL, pl_PL, pt_BR, pt_PT, ru_RU, tr_TR, uk and zh_CN.

After changing strings, regenerate the template and merge it into every `.po` file (needs [WP-CLI](https://wp-cli.org/)):

```bash
make i18n
```

Then translate the new entries. `.mo` and `.l10n.php` files are compiled during `make dist` and are not committed; run `make i18n-compile` to compile them in a development checkout. Once the plugin is on WordPress.org, language packs from translate.wordpress.org take precedence over the bundled files.

### Building a release

```bash
make dist
```

This creates `wpstreak.zip`. The build happens in `build/wpstreak`, so the working copy is left untouched:

1. Plugin files and built assets are copied to `build/wpstreak`, and translations are compiled.
2. Runtime dependencies are installed there and prefixed with [wp-scoper](https://github.com/veronalabs/wp-scoper) into `vendor-prefixed/` (namespace `Merkushin\Wpstreak\Vendor`), and the `use` statements in `src/` are rewritten to match.
3. `vendor/` and the Composer files are removed and the directory is zipped.

`vendor-prefixed/` is never committed. The wp-scoper Composer plugin is disabled in `composer.json` (`allow-plugins`) so it doesn't rewrite the sources on `composer install`; it only runs from `make dist`.
