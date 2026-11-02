# Media bank: Listing the plugins on the Moodle Marketplace

The text and checklist for listing the three plugins where Moodle administrators look for plugins. Copy the text into the submission form. The form and the rules change from time to time, so read the current guide first (links in section 1) and treat the field names below as a guide.

**This page used to be about the Moodle Plugins directory (moodle.org/plugins).** That directory has been replaced by the **Moodle Marketplace** (marketplace.moodle.com). Moodle's developer documentation now calls the old contribution steps legacy guidance that "should not be used for new plugin submissions". The Marketplace lists **free** plugins as well as paid ones, so a free, GPL project like this one is listed there. Nothing here is about selling the plugins ([FUNDING.md](FUNDING.md)).

## 1. What the Marketplace asks for (checked 2 November 2026)

From Moodle's own pages: the [plugin contribution page](https://moodledev.io/general/community/plugincontribution), the [Marketplace home page](https://marketplace.moodle.com), the [Plugins FAQ](https://docs.moodle.org/501/en/Plugins_FAQ) and the [Marketplace API page](https://moodledev.io/general/community/plugincontribution/moodlemarketplaceapi). These are summaries, not quotes of rules, and they may have changed.

- Sign in with the same email as your moodle.org account, choose **Submit a plugin**, say whether the plugin is free or paid, upload the plugin zip, and fill in the details: description, documentation, setup instructions and screenshots.
- The plugin name with its Frankenstyle prefix must be unique.
- **Automated checks** run on the upload: validation of the archive, and Moodle plugin CI tests.
- **Review.** A submission becomes a ticket in the Marketplace team's queue. Free plugins are reviewed once, with no promised time. The reviewers may ask for changes; answer on the ticket, fix in the repository, resubmit.
- **A plugin is not published when review passes.** You complete the plugin page and publish it yourself.
- **New versions.** Version uploads go through an API with a token (created under your account's security settings). The first listing is made with the web form. When the Marketplace launched, uploading through the API was switched off and uploads were done by hand from the plugin's dashboard, with the API promised as a priority. Workflow runs for other plugins from this maintainer have since created new versions through it, so it works at least for a repository with one plugin at its root. Check which is true when you release.
- The old Plugins directory is read-only.

What the Moodle plugin review criteria have always covered: coding standard, language strings instead of hard-coded text, accessibility, security and input handling, backup and restore behaviour, tests, and whether someone maintains the plugin. This project has a green CI run of all of them on Moodle 5.0 to 5.3 ([COMMANDS.md](COMMANDS.md)).

## 2. What you submit

Three separate entries, one for each plugin. The Marketplace works one plugin at a time.

| Order | Plugin | Zip | Maturity | Required? |
|---|---|---|---|---|
| 1 | `contenttype_mediabank` | `contenttype_mediabank_<version>.zip` | Beta | Yes. This is the main plugin. |
| 2 | `local_mediabank` | `local_mediabank_<version>.zip` | Beta | Optional. Backup of linked files, the unlinked-items report and command-line tools. Needs the first. |
| 3 | `filter_mediabank` | `filter_mediabank_<version>.zip` | **Alpha** | Optional. Carries text alternatives, captions and transcripts into courses. Needs the first. |

Submit the content type first either way, because the other two name it as a dependency. You can submit the content type alone and add the others later. **Whether to list an Alpha plugin yet is your decision** (section 3, point 2).

Upload the **plugin zips from the GitHub release**, not the bundle and not GitHub's source zip. The folder inside each is called `mediabank`, as Moodle expects.

## 3. Things to settle before submitting

1. **The repository name.** Moodle's guidance prefers one plugin per repository, named `moodle-{plugintype}_{pluginname}`, with the plugin at the root. This repository holds three plugins in subfolders and is called `moodle-repository_mediabank` (the name comes from an early plan, and there is no `repository_mediabank` plugin). A reviewer may ask about it. The options are to explain it (the three plugins are released together and the zips are built by `scripts/build_release.py`), or to split the repository into three. Splitting would also let the Marketplace's release workflow publish new versions by itself, because the workflow's documentation does not describe packaging a repository with several plugins, so assume it cannot. It is a large change, so do not do it unless asked.
2. **Whether to list `filter_mediabank` now.** It is Alpha, new, and its captions and transcript have been tested by machines and not yet seen by a person in a browser. Listing only the first two and adding the filter later, once it has had real use, is the cautious choice.
3. **Screenshots.** Twelve exist in `docs/images/` (section 6): the seven from 0.11 and five new ones taken on 2 November 2026 for 0.12, which show the **Captions** button with a caption on screen, the **Transcript** block, *Where this item is used* with activities and **Convert to copies**, the *items nobody links to* report and the **Manage filters** switch. They were taken on a Moodle 5.3 test site with invented content ([scripts/screenshots/README.md](../scripts/screenshots/README.md)). A text alternative cannot be photographed: say in the listing that a picture's alt text comes from the bank. Look at them once before you upload, because they are the first thing an administrator sees.
4. **Who is the contact and where is support.** The GitHub issue tracker ([SUPPORT.md](SUPPORT.md)). Say so in the listing.

## 4. Before you submit (checklist)

- [ ] A moodle.org account, and you are content to be the listed maintainer.
- [ ] The release you are submitting is tagged and published on GitHub, and CI is green for it (0.12.0: tag `v0.12.0`, 48 of 48 jobs).
- [ ] The zips from that release pass `scripts/validate_plugin_zip.php` on Moodle 5.0, 5.1, 5.2 and 5.3. See [RELEASING.md](RELEASING.md).
- [ ] `version.php` has the right `release`, `maturity`, `requires` and `supported` for each plugin.
- [ ] The privacy provider tests pass. All three plugins have a privacy provider: the content type erases a user's items; the tools plugin and the filter store nothing.
- [ ] English strings only ship with the plugins; other languages come later through Moodle's translation tool ([TRANSLATING.md](TRANSLATING.md)).
- [ ] The README and the guides are current, with no personal email, token or password anywhere ([STYLE-GUIDE.md](STYLE-GUIDE.md)).
- [ ] The issue tracker on GitHub is open and the issue forms work.
- [ ] You have read the Marketplace's current submission guide and have nothing in the plugins it forbids.

## 5. Entry 1: Media bank content type

**Plugin name (as shown):** Media bank content type

**Frankenstyle name:** `contenttype_mediabank`

**Free or paid:** Free

**Short description** (one or two sentences):

> Keep videos, audio files, PDFs and images in Moodle's Content bank and use them in many courses, either as a link that follows the original or as an independent copy.

**Full description:**

> The Media bank adds a content type to Moodle's Content bank for videos, audio files, PDFs and images. A teacher uploads a file once, then picks it from Moodle's own file picker in any course and chooses:
>
> * **Link to the file**: the course follows the Content bank item. Replace the item and every course that links to it gets the new file.
> * **Make a copy of the file**: the course gets its own file that never changes.
>
> Features:
>
> * Upload checks that read what is inside a file, not just its name, with limits for each kind (video, audio, PDF, image).
> * A "Where this item is used" panel that lists the courses that link to it, and under each course the activities that hold the link, each one a link you can open. An item that courses link to cannot be deleted by accident.
> * A teacher who can edit a course's activities can turn just that course's links into copies, with a **Convert to copies** button. Other courses keep their links.
> * Replace a file, keep the earlier one for a time, and put it back.
> * Accessibility fields: a required text alternative for images, and a transcript and WebVTT captions for video and audio.
> * MP4 videos are prepared in the background so playback can start before the whole file has downloaded. Nothing is re-encoded, and no extra software is needed on the server.
> * SVG images are supported only if an administrator turns them on. They are off by default, checked against a list of what they may contain, shown as pictures and only sent as downloads.
> * Three read-only web service functions to list items, get one item, and see which courses use it.
> * A privacy provider: when a user is erased their items, versions, captions and transcripts are erased, and courses that linked to them keep the file as a copy.
>
> **Beta.** Released for testing. Try it on a copy of your site first. It is tested on Moodle 5.0 to 5.3 with PHP 8.3 and 8.4, on PostgreSQL and MariaDB. It does not support the Moodle mobile app.
>
> Install the content type first. Two optional plugins build on it: "Media bank tools" (`local_mediabank`), for backup of linked files, a report of unused items and command-line tools, and "Media bank text alternatives" (`filter_mediabank`, Alpha), which carries an item's text alternative, captions and transcript into the courses that link to it.

**Moodle versions:** 5.0, 5.1, 5.2, 5.3

**Maturity:** Beta

**Licence:** GNU GPL v3 or later

**Tags / categories** (choose from the Marketplace's list): Content bank, Files, Media, Accessibility, Video, Audio, PDF

**URLs**

| Field | Address |
|---|---|
| Source code | https://github.com/billsensei/moodle-repository_mediabank |
| Issue tracker | https://github.com/billsensei/moodle-repository_mediabank/issues |
| Documentation | https://github.com/billsensei/moodle-repository_mediabank/blob/main/docs/GETTING-STARTED.md |
| Release notes | https://github.com/billsensei/moodle-repository_mediabank/blob/main/contenttype_mediabank/CHANGES.md |

**Release notes for this version:** copy the section for the version from [`contenttype_mediabank/CHANGES.md`](../contenttype_mediabank/CHANGES.md).

**Setup instructions:** install the zip from Site administration > Plugins > Install plugins; nothing else is needed. The settings are under Site administration > Plugins > Content bank > Media bank ([ADMIN-GUIDE.md](ADMIN-GUIDE.md)).

## 6. Screenshots

Add alt text to each in the form. From `docs/images/`. The first group is for the content type (Entry 1).

1. `teacher-1-content-bank.png`: the Content bank in list view, with three items.
2. `teacher-10-where-used-and-convert.png`: an item page for an image, with *Where this item is used* listing two courses, the activity that holds each link and a **Convert to copies** button for each course. Alt text: "An item page for a picture of the stages of mitosis. Below it, Where this item is used lists Biology 101 and Chemistry 201, each with a Convert to copies button and the activity that holds the link."
3. `teacher-4-link-or-copy.png`: the file picker, with Link to the file selected.
4. `teacher-5-more-menu.png`: the More menu, with Replace with file.
5. `teacher-3-edit-form.png`, `teacher-6-edit-video.png`, `teacher-7-edit-image.png`: the Edit page, with the accessibility fields.
6. `teacher-2-item-page.png`: an item page for a PDF. It shows the 0.11 layout, without the activities and the Convert button, so prefer number 2 for the listing. It stays in the guides until they are redone.

For the filter (Entry 3), the first two also show the filter's work:

7. `teacher-8-captions-in-course.png`: a course page as a student sees it, with a video player and a caption on the video. Alt text: "A course page in Biology 101. A video player shows a title card, Cell division, with the caption 'Welcome to week one. Today we look at how a cell divides.' over it, and a Transcript heading under the player."
8. `teacher-9-transcript-in-course.png`: the same page with the transcript open. Alt text: "The same course page with the Transcript opened, showing the four lines of the lecture under the video."
9. `admin-2-manage-filters.png`: Site administration > Plugins > Filters > Manage filters, with Media bank text alternatives set to On. Alt text: "The Manage filters page with a row, Media bank text alternatives, set to On."

For the tools (Entry 2):

10. `admin-1-unlinked-report.png`: Site administration > Reports > Media bank: items nobody links to. Alt text: "The report Media bank: items nobody links to, a table of three items with their size and upload date, and a box to hide recent uploads."

**Not shown:** a picture getting its alt text from the bank (it is invisible by nature). The 0.12 changes in the Teacher guide and the two walk-through PDFs (still the 0.11 screens) are not redone; see [RELEASING.md](RELEASING.md).

## 7. Entry 2: Media bank tools

**Plugin name (as shown):** Media bank tools

**Frankenstyle name:** `local_mediabank`

**Free or paid:** Free

**Short description:**

> Optional companion to the Media bank content type: carries linked Media bank files in course backups, finds items no course uses, and adds command-line tools.

**Full description:**

> This plugin needs the **Media bank content type** (`contenttype_mediabank`), which must be installed first. The content type works without it.
>
> * **Backup and restore of linked files.** With the content type's setting **Include linked files in course backups** switched on (it is off by default), manual course backups carry the files behind Media bank links, so restoring on a different site does not leave them out. Where a link cannot be kept on restore, the file becomes an ordinary copy. Restore does not trust the backup file: hashes and paths are checked.
> * **A report of items nobody links to,** under Site administration > Reports, for administrators: the largest 500 items that no course uses, with size and upload date, and an optional minimum age. It only reads. The same list is available as a command that can also write CSV.
> * **Command-line tools.** `local/mediabank/cli/item.php` shows one item, the courses that link to it and its earlier versions, and can turn its links into copies or restore a version. `local/mediabank/cli/optimize_videos.php` queues videos uploaded before streaming preparation existed (with `--dry-run` to only count them). `local/mediabank/cli/unlinked.php` lists unused items.
>
> It has no database tables of its own and stores no personal data. **Beta**: try it on a copy of your site first.

**Dependency:** `contenttype_mediabank`, the version named in `local_mediabank/version.php`.

**Moodle versions, maturity, licence:** as above.

**URLs**

| Field | Address |
|---|---|
| Source code | https://github.com/billsensei/moodle-repository_mediabank |
| Issue tracker | https://github.com/billsensei/moodle-repository_mediabank/issues |
| Documentation | https://github.com/billsensei/moodle-repository_mediabank/blob/main/docs/ADMIN-GUIDE.md |
| Release notes | https://github.com/billsensei/moodle-repository_mediabank/blob/main/local_mediabank/CHANGES.md |

**Screenshots:** `admin-1-unlinked-report.png` (section 6, number 10), and optionally `teacher-1-content-bank.png`.

## 8. Entry 3: Media bank text alternatives (Alpha)

**Plugin name (as shown):** Media bank text alternatives

**Frankenstyle name:** `filter_mediabank`

**Free or paid:** Free

**Short description:**

> Optional filter for the Media bank: shows a linked picture's text alternative, and a linked video or audio player's captions and transcript, in the courses that use them.

**Full description:**

> This plugin needs the **Media bank content type** (`contenttype_mediabank`), which must be installed first. It does nothing until an administrator switches it on under Site administration > Plugins > Filters > Manage filters.
>
> When a course **links** to a Media bank item, it normally gets the bare file. This filter carries the text kept with the item into the course:
>
> * A picture with no text alternative, an empty one, or only its file name gets the item's text alternative. One a teacher wrote, and a picture marked as decorative, are left alone.
> * A video or audio player gets the item's captions, as a **Captions** button that is off until the viewer turns it on, and its transcript in a **Transcript** block under the player. A player that already has a captions track of its own is left alone.
>
> Correct a caption or an alt text once in the bank and every linked course shows the correction. A copy of the file is independent and gets nothing.
>
> Students never need access to the Content bank. Captions are served through the course, after a check that the viewer may open the page the link is on. They are offered in activity descriptions, and in File, Page, Book and Folder content and course and section summaries; not in forum posts, assignments, quizzes or blocks, and a hidden book chapter keeps its captions from people who may not see it.
>
> It works before Moodle cleans the HTML, so text from users who are not trusted is cleaned after the filter.
>
> **Alpha**: new, tested by automated tests on Moodle 5.0 to 5.3 and not yet by long use. Not for the Moodle mobile app, and not for a plain link to a file (the filter works on a player the editor inserted, or a File resource shown on its own page). Try it on a copy of your site first.

**Dependency:** `contenttype_mediabank`, the version named in `filter_mediabank/version.php`.

**Moodle versions, licence:** as above. **Maturity:** Alpha.

**URLs**

| Field | Address |
|---|---|
| Source code | https://github.com/billsensei/moodle-repository_mediabank |
| Issue tracker | https://github.com/billsensei/moodle-repository_mediabank/issues |
| Documentation | https://github.com/billsensei/moodle-repository_mediabank/blob/main/docs/CAPTIONS-DESIGN.md |
| Release notes | https://github.com/billsensei/moodle-repository_mediabank/blob/main/filter_mediabank/CHANGES.md |

**Screenshots:** `teacher-8-captions-in-course.png`, `teacher-9-transcript-in-course.png` and `admin-2-manage-filters.png` (section 6, numbers 7 to 9).

## 9. Things to say honestly in the listing

The Marketplace's readers are administrators. Do not leave these out:

- The content type and the tools are **Beta** and the filter is **Alpha**: tested, but not used for long on real sites.
- The mobile app is **not supported**.
- A course that **copies** a file gets the bare file. A course that **links** to it gets the text alternative, captions and transcript only if the optional filter is installed and switched on, and only in the places listed above.
- SVG has not had a manual security review, and it is off by default.
- Uninstalling the content type **deletes every item** in the bank.
- Moodle 4.5 and earlier are not supported.

These are all in the [FAQ](FAQ.md) and the [Administrator guide](ADMIN-GUIDE.md).

## 10. After submitting

- Expect a review. Answer the reviewers on the ticket, and fix in the repository.
- A fix that changes code means a **new version and a new release** ([RELEASING.md](RELEASING.md)). Upload the new zip as a new version of the entry: by hand from the plugin's dashboard, or through the API if it is available to you (section 1).
- Keep the listing text and the README in step with each other. The plugin descriptions above change when a release changes what the plugins do.
- When a plugin is published, add a line to the changelog and the README ("Also available from the Moodle Marketplace"), and update [ROADMAP.md](ROADMAP.md) and [FUNDING.md](FUNDING.md).
- Say in the listing where you want support: the GitHub issue tracker ([SUPPORT.md](SUPPORT.md)).

## Copyright and licence

Copyright 2026 SenseiMoodle. Released under the GNU General Public License version 3 or later. See [LICENSE](../LICENSE).
