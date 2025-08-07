# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Use PHP namespace (`MediaWiki\Extension\W4G\RatingBar`) for extension codebase.

### Changed

- CHANGELOG file now follows "Keep a Changelog" 1.1.0 formatting.
- Reorganize project to follow latest MediaWiki extension directory layout.
- Rewrite `extension.json` to use MediaWiki extension manifest version 2.
- Use updated method of registering "magic words".
- Rename classes to better represent functionality.

### Removed

-

## [2.2.0] - 2020-02-20

### Fixed

- Compatability updates for MediaWiki 1.34 (mostly getting "db_replica" instead
  of "db_slave")

## [2.1.2] - 2011-05-21

### Added

- Votes from anonymous users can now be displayed in toplists, provided that
  a "user zero" (with `user_id=0`) exists in the "user" database.
- Now possible to hide average rating in the toppages toplist using argument
  `hideavgrating`.

### Changed

- JavaScript is compacted using [Closure Compiler](https://closure-compiler.appspot.com/home).

### Security

- Add option to display the name of the page being voted on if it's not
  the same as the page on which the bar is displayed:
  `$wgW4GRB_Settings['show-mismatching-bar'] (default: true)`.

## [2.1.1] - 2011-03-20

### Fixed

- Bugs related to anonymous voting.

## [2.1.0] - 2011-03-06

### Added

- Feature: Anonymous voting, enabled with:
  `$wgW4GRB_Settings['anonymous-voting-enabled']`.
- New option to (somewhat) deal with multivoting:
  `$wgW4GRB_Settings['multivote-cooldown']`.

### Fixed

- PHP notice about UserLogin and Listgrouprights (wrong case).
- If no idpage is provided, rating toplist now defaults to current page.
  (Used to generate an error.)

### Security

- Dealt with an XSS problem.

## [2.0.1] - 2010-12-26

### Changed

- Increased default number ofi tems shown in toplists and made it configurable.

### Fixed

- Problem when in read-only.
- Bar not showing on a newly-created page.

## [2.0.0] - 2010-10-17

First stable release!
