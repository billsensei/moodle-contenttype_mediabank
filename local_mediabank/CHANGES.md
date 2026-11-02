# Changelog

## Unreleased
- A backup is checked once for whether it is a general backup with files, not once for each course and activity.

## 0.4.0 (2026-11-02)
Version 2026110200. Needs contenttype_mediabank 0.12.0 (the unlinked-items report calls `usage::find_unlinked()`). No setting, no table.

- New command `php local/mediabank/cli/unlinked.php [--days=N] [--csv]`: list the items that nothing links to, largest first, with the total size. Read-only. `--days` skips recent uploads. Needs the matching change in contenttype_mediabank.
- New page Site administration > Reports > Media bank: items nobody links to (`unlinked.php`, `settings.php`, 2 Behat scenarios). Read-only; shows the largest 500 items; optional minimum age in days.
- The command's CSV output prefixes a name starting with = + - @ or tab with an apostrophe, and control characters are replaced with a space (found by a security review).

## 0.3.1 (2026-10-30)
Hardening only. The same code was put in the 0.3.0 zip attached to the v0.11.0 release on 2026-10-30 without a new version number (it was still 2026102904), so an install made before then does not have it and Moodle did not offer an upgrade. This release carries it under a new number (2026103000): install it over any 0.3.0 and Moodle upgrades.

- Restoring a backup whose embedded-file entry has no link id no longer raises a warning. The entry is skipped, as an entry with a bad content hash already was. Found by a security review (hardening).

## 0.3.0 (2026-10-29)
- New command `php local/mediabank/cli/optimize_videos.php [--dry-run]`: queue the MP4 videos already in the media bank that are not ready for streaming. Needs contenttype_mediabank 0.10.0.

## 0.2.2 (2026-10-29)
- Maturity changed from Alpha to **Beta**: released for testing on a copy of your site first.
- The README now describes the plugin as it is.

## 0.2.1 (2026-10-29)
- The command-line tool says "There is no content bank item with id N." for an id that does not exist, instead of Moodle's generic database message.
- Install zips and guides: see the main repository (`scripts/build_release.py`, `docs/`).

## 0.2.0 (2026-10-29)
- Backup and restore of linked files. With the new site setting (contenttype_mediabank "Include linked files in course backups", off by default), manual course backups carry the files behind media bank links in the course's own files and in activities. On restore, a link that core cannot restore (a different site, or the bank file is gone) is created as an ordinary copy; on the same site with the bank file present the link is kept. Import, course copy and automated backups are unchanged.
- Restore does not trust the backup file: a content hash must be 40 hex characters, the file must sit inside the backup's file pool and must hash to its own name.
- Limits: links in block and section-summary files are not carried; copies do not keep the item's alt text, transcript or captions; the setting is site-wide because Moodle gives plugins no per-backup option.

## 0.1.1 (2026-10-27)
- CLI `cli/item.php`: usage, detach links, list and restore versions.

## 0.1.0 (2026-10-27)
- Initial scaffold (alpha). Not for production.
