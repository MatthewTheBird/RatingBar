# w4g_ratingbar

This project is based on the orignal work of David and Franck Dernoncourt for *Wiki 4 Games*, it displays a rating bar on a wiki page that the user can use to rate something from 1 - 100.

For full installation instructions, see [this page](https://www.mediawiki.org/wiki/Extension:W4G_Rating_Bar).

For full usage directions, see [this page](http://www.wiki4games.com/Wiki4Games:W4G_Rating_Bar/syntax).

## License

This is licensed under a [Creative Commons Attribution-ShareAlike License, version 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
We are aware that this is a terrible license for code, but we didn't choose it.

## Notes

I've only tested the rating bar functionality on MediaWiki 1.27.1 probably works with 1.27. 1.26 and earlier may not work.
The migration feature to update from rating bar v1.1 I have not tested at all. I updated it so it no longer generates errors but nothing else- use at your own risk.

## Configuration

### Configuration Keys

| Key | Value Type | Default | Purpose |
| --- | --- | --- | --- |
| W4GRB_Path | string | '/extensions/RatingBar' | Relative system path to extension directory. |
| W4GRB_Settings | arr | see [#settings](#settings) | The main settings of the extension. |
| ExtensionAliasesFiles | string | '/extensions/RatingBar/W4GRB.alias.php' | ? |

### Settings

Override default settings by defining your values in `LocalSettings.php` using an array with `$wgW4GRB_Settings[];`.

| Name | Type | Default | Purpose |
| --- | --- | --- | --- |
| ajax-fresh-data | bool | true | ? |
| allow-unoptimized-queries | bool | true | ? |
| anonymous-voting-enabled | bool | false | ? |
| auto-include | bool | false | ? |
| default-items-per-list | int | 10 | ? |
| fix-spaces | bool | true | ? |
| max-bars-per-page | int | 2 | Maximum number of rating bars that are allowed on one page. |
| max-items-per-page | int | 100 | ? |
| max-lists-per-page | int | 10 | ? |
| multivote-cooldown | int | 604800 | ? |
| category-cache-time | int | 604800 | ? |
| show-mismatching-bar | bool | true | ? |
| show-voter-names | bool | false | ? |

## Hooks

| Hook | Callback | Purpose |
| --- | --- | --- |
| LoadExtensionSchemaUpdates | W4G::makeRatingBarDBChanges | ? |
| ParserFirstCallInit | W4G::W4GrbSetup | ? |
| BeforePageDisplay | W4G::W4GrbAutoShow | ? |

## Group Permissions & Rights

|  |  |  |
| --- | --- | --- |
| W4GRB |  | ? |
| W4GRBPage |  | ? |
| W4G |  | ? |

## Special Pages

|  |  |  |
| --- | --- | --- |
| W4GRB |  | ? |
| W4GRBPage |  | ? |
| W4G |  | ? |

## Classes

| Class |  | Purpose |
| --- | --- | --- |
| W4G |  | Not a class... a script. |
| W4GRB | extends `UnlistedSpecialPage` |  |
| W4GRBMigrate | extends `UnlistedSpecialPage` |  |
| W4GRBPage |  | ? |

## Extension Layout

- **includes/** - PHP class definitions
- **maintenance/** - CLI maintenance scripts
- **resources/** - Extension resources
  - w4grb.css
  - w4grb.js
- **sql/** - Database schemas
- **CHANGELOG.md** - Extension changelog
- **CREDITS.md** - Author/developer credit
- **extension.json** - Extension definition
- **LICENSE.md** - Copy of extension license
- **RatingBar.i18n.alias.php** - For magic words functionality
- **RatingBar.i18n.magic.php** - For magic words functionality
- **README.md** - Extension documentation
