# Changelog

All notable changes to the project. Each plugin also keeps its own, more detailed list:
[`contenttype_mediabank/CHANGES.md`](contenttype_mediabank/CHANGES.md), [`local_mediabank/CHANGES.md`](local_mediabank/CHANGES.md) and [`filter_mediabank/CHANGES.md`](filter_mediabank/CHANGES.md).

The content type and the tools are **Beta**; the filter is **Alpha**. A release of the project is a tag on GitHub, `v<content type version>`, with the three plugin zips
and the bundle zip attached.

## Unreleased

## 0.12.4 (2026-11-03): content type 0.12.4, tools 0.4.2 (filter 0.1.2 unchanged)

- Content type: each "Convert to copies" link on an item page has an accessible name that includes the course, so a screen reader can tell them apart.
- Tools: the unlinked-items report table has a visually hidden caption.

## 0.12.3 (2026-11-02): content type 0.12.3, filter 0.1.2 (tools 0.4.1 unchanged)

- Filter: a picture gets an item's text alternative only when the link is in a place the captions and transcript already allow, not in any place in the text's own context.
- Content type: no change in behaviour. Test-only changes: PHPUnit attributes instead of doc-comment annotations, and axe accessibility checks in the Behat features.

## 0.12.2 (2026-11-02): content type 0.12.2 (tools 0.4.1 and filter 0.1.1 unchanged)

- Web services: `contenttype_mediabank_get_items` no longer carries every transcript, which could be 100,000 characters each. New parameter `includetranscripts` (default off) and new field `hastranscript`. A client that read `transcript` from a list now gets an empty string: pass `includetranscripts=1`, or use `contenttype_mediabank_get_item`, which always includes it.

## 0.12.1 (2026-11-02): content type 0.12.1, tools 0.4.1, filter 0.1.1

Fixes and hardening from a code and security audit of 0.12.0. No new setting, no table.

- The filter gives a picture the text alternative of an item only when the link is in the same context as the text.
- A link made in a form can no longer stop the file being saved, whatever the error. Moving an item and converting its links is one transaction. Files are served to their owner even when *display unlisted* is off.
- Faster: *Where this item is used*, the `get_items` web service, the unlinked-items report and backups with embedded files do much less repeated work.
- SVG: a DOCTYPE that points at a DTD outside w3.org is refused. Files, captions and versions are sent with private caching. The `upload` and `useeditor` capabilities carry `RISK_XSS`.

- Documentation only: five new screenshots (captions on a video, the transcript, *Where this item is used* with Convert to copies, the items-nobody-links-to report, and Manage filters) in `docs/images/`, and the scripts to retake them (`scripts/screenshots/`). Taking them was also the first check of the 0.12.0 features in a real browser: no fault was found.
- Documentation only: the listing guide (`docs/PLUGIN-DIRECTORY.md`) now follows the Moodle Marketplace, which replaced the Moodle Plugins directory, and covers all three plugins.

## [0.12.0] - 2026-11-02

`contenttype_mediabank` 0.12.0 (2026110200), `local_mediabank` 0.4.0 (2026110200), and a new optional plugin, `filter_mediabank` 0.1.0 (2026110200, **Alpha**).

New features for teachers and administrators, no new setting, and no database change. Moodle offers the upgrade for the two existing plugins. Nothing needs deciding, except whether to install the optional filter and switch it on (*Site administration > Plugins > Filters > Manage filters*).

**Install the content type first, then the tools, then (optionally) the filter.** The tools plugin and the filter now need content type 0.12.0.

- New plugin `filter_mediabank` 0.1.0 (**Alpha**, optional; built into the release zips and the bundle): a text filter that gives a picture added to a course as a **link** the text alternative kept with the bank item. It only fills a picture with no alt, an empty alt or just the file name; a teacher's own alt and a decorative picture are left alone. It also gives a video or audio player that links to a bank item its captions (a **Captions** track, off until the viewer turns it on) and its transcript (a **Transcript** block under the player, collapsible on Moodle 5.3 and always open on 5.0 to 5.2, whose cleaner removes `<details>`), read through a new route that checks the viewer against the activity or course the link is in (`contenttype_mediabank` `link_captions`, file area `linkcaptions`). Only listed places get them: activity descriptions, File, Page, Book and Folder content, and course and section summaries. See [docs/CAPTIONS-DESIGN.md](docs/CAPTIONS-DESIGN.md). The filter needs `contenttype_mediabank` 0.12.0, which has the new `link_captions` class; `local_mediabank` 0.4.0 needs the same version for `usage::find_unlinked()`. Version numbers for this release are set (content type 0.12.0 / 2026110200, tools 0.4.0 / 2026110200, filter 0.1.0 / 2026110200); change them if it is released on a later day. An administrator switches it on under *Site administration > Plugins > Filters > Manage filters*. It works before Moodle cleans the HTML, so untrusted text is cleaned after it. It is in the CI matrix and in `scripts/build_release.py`.
- `contenttype_mediabank`: *Where this item is used* now lists, under each course, the activities that hold the links, each with a link to open it. An activity the viewer cannot see, or any activity of a course the viewer cannot enter, is not named; it is counted under "Elsewhere in the course". New `usage::get_places()`.
- `contenttype_mediabank`: a teacher who can edit a course's activities can convert just that course's links to an item into copies, from a **Convert to copies** button in *Where this item is used* (new page `convert.php`, asks first, needs the session key). Other courses keep their links. No new capability: it uses `moodle/course:manageactivities` in the course.
- `local_mediabank`: new command `php local/mediabank/cli/unlinked.php [--days=N] [--csv]` lists the items that nothing links to, largest first, to help clear space. It only reads. Uses the new `usage::find_unlinked()` in `contenttype_mediabank`. The same report is a page under Site administration > Reports > "Media bank: items nobody links to" (administrators only, read-only, largest 500 items, optional minimum age in days). CSV output neutralises names that a spreadsheet would read as a formula.

Tested: PHPUnit (`--fail-on-warning`) and Behat for all three plugins on Moodle 5.0 to 5.3, PHP 8.3 and 8.4, PostgreSQL and MariaDB, in GitHub Actions (48 jobs); the three zips pass Moodle's installer validation. Not tested: the captions button in a real browser, the Moodle mobile app, and a real install of the zips on a clean site (see [docs/RELEASING.md](docs/RELEASING.md)).

## [0.11.1] - 2026-10-30

`contenttype_mediabank` 0.11.1 (2026103000), `local_mediabank` 0.3.1 (2026103000).

Hardening found by a security review of both plugins. No new feature, no new setting, and nothing for an administrator to decide.

- `contenttype_mediabank`: a transcript may have at most 100,000 characters. The edit form refuses a longer one.
- `local_mediabank`: restoring a backup whose embedded-file entry has no link id skips the entry quietly.
- Administrator guide: web service results hold text typed by teachers, so a program that shows them must escape them; and a
  backup with linked files holds the item's current file, even if the owner has since made the item unlisted.
- Privacy provider: an unused import was removed, and the Administrator guide now says that an item belongs to the person who created it, as in Moodle's own Content bank.

**A note on v0.11.0.** The same code was put in the v0.11.0 plugin zips and bundle on GitHub on 2026-10-30, without a new version
number (`contenttype_mediabank` 2026102910 and `local_mediabank` 2026102904). An install made before that date does not have it,
and Moodle did not offer an upgrade. 0.11.1 carries it under a new number, so Moodle offers the upgrade. Install it over any
v0.11.0 download.

Documentation and repository only, no change to the plugins: a Getting started guide, a Governance page, Maintainers, Releasing,
Changelog policy, Style guide, Translating, Funding, Acknowledgements, Code of conduct (short version) and Plugin directory
pages; a "Which file do I download?" section in the Install guide; and a `READ-ME-FIRST.txt` in the bundle, which says the bundle
itself is not installable.

## [0.11.0] - 2026-10-29

`contenttype_mediabank` 0.11.0 (2026102910), `local_mediabank` 0.3.0 (2026102904).

- **SVG images** can be allowed with a setting that is **off by default**. When on, an SVG is shown as a picture and sent only as a
  sandboxed download, and it is read as XML and refused unless every element and attribute is on a list of what is allowed (no
  scripts, animation, embedded pages or links that load anything).

Repository and documentation only, no change to the plugins:

- Added `README.md` (it was only a title), `LICENSE` (GNU GPL v3), `composer.json`, `CONTRIBUTING.md`, `SECURITY.md`,
  `CODE_OF_CONDUCT.md`, a pull request template and issue forms.
- Teacher guide: screenshots retaken with real sample content and a neutral site name, and three new ones (the More menu, the
  Edit page of a video and of a picture). The bundle zip on the v0.10.0 release was replaced with a version that has them; the two
  plugin zips are the same files.

## [0.10.0] - 2026-10-29

`contenttype_mediabank` 0.10.0 (2026102909), `local_mediabank` 0.3.0 (2026102904).

### Added
- **MP4 videos are prepared for streaming.** After an MP4 is uploaded or replaced, a background task moves the file's index in
  front of the picture and sound, so playback can start before the whole file has downloaded. Nothing is re-encoded and the file
  keeps its size. It is done in PHP: no ffmpeg or other program is needed on the server.
- The file as uploaded is kept as an earlier version for the usual retention time. Links from courses follow the new file.
- Setting "Prepare MP4 videos for streaming" (on by default) and the task "Prepare a media bank video for streaming".
- `php local/mediabank/cli/optimize_videos.php [--dry-run]` queues videos that were uploaded before this version.

### Security
- Limits on what is read from a video file: an index over 32 MB, more than 4 million chunk offsets, or boxes nested deeper than a
  real index are refused, and nothing large is built in memory. Found by an automated review before release.
- A file that cannot be prepared is remembered and not tried again until it is replaced. Doing so adds one key to the item's
  current metadata, so edits made meanwhile are kept. Disk and read errors are retried.

### Tested
- 109 plugin tests and 7 backup tests on Moodle 5.0 to 5.3, and the 7 browser scenarios on 5.3. GitHub Actions: all 32 jobs
  (PHP 8.3 and 8.4, Moodle 5.0 to 5.3, PostgreSQL and MariaDB), including a test that decodes a real video before and after.

## [0.9.2] - 2026-10-29

`contenttype_mediabank` 0.9.2 (2026102908), `local_mediabank` 0.2.2 (2026102903). The first published release.

### Added
- **Content type** `contenttype_mediabank`: video, audio, PDF and image files in Moodle's Content bank, used in courses as a link
  or as a copy through the standard file picker.
- Upload rules: allowed kinds, size limits per kind, SVG off by default, and a check that a file's content matches its name.
- A "where this item is used" panel, and protection against deleting an item that courses link to.
- Replace with undo: replaced files are kept as versions for a set number of days and can be restored.
- Edit form: text alternative (required for images), transcript, WebVTT captions, and converting all links to copies.
- Read-only web services, events for links, conversions and restores, and a privacy provider for GDPR export and erasure.
  When a person is erased, courses that linked to their media keep the file as an ordinary copy.
- **Tools** `local_mediabank`: optional carrying of linked files in course backups (restored as copies where a link cannot be kept),
  and the command-line tool `cli/item.php`.
- Install, administrator and teacher guides, and scripts to build and validate the release zips.

### Security
- An item's file is served only to people who may see the item. Before, any logged-in user who knew the address could fetch any
  item, unlisted ones included.
- Restore does not trust a backup file: a content hash must be 40 hex characters, the file must sit inside the backup's file
  pool, and it must hash to its own name.

### Changed
- Both plugins are Beta. Language strings are in alphabetical order and the privacy test is in `tests/privacy/`, as Moodle's
  current coding standard requires (found by the first GitHub Actions run).

[0.12.0]: https://github.com/billsensei/moodle-repository_mediabank/releases/tag/v0.12.0
[0.11.1]: https://github.com/billsensei/moodle-repository_mediabank/releases/tag/v0.11.1
[0.11.0]: https://github.com/billsensei/moodle-repository_mediabank/releases/tag/v0.11.0
[0.10.0]: https://github.com/billsensei/moodle-repository_mediabank/releases/tag/v0.10.0
[0.9.2]: https://github.com/billsensei/moodle-repository_mediabank/releases/tag/v0.9.2
