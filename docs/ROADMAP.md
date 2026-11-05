# Media bank: Roadmap

Where the project stands and where it could go. **This is a list of ideas, not promises.** Nothing here has a date. The maintainer decides what gets built, and some of it may never be. What *has* been released is in [CHANGELOG.md](../CHANGELOG.md).

## Where it stands

The content type and the tools are **Beta** (released for testing): `contenttype_mediabank` 0.12.0 and `local_mediabank` 0.4.0, for Moodle 5.0 to 5.3. A third plugin, `filter_mediabank` 0.1.0, is optional and **Alpha**. The features work and are well tested, but the plugins have not had long use on real sites. What has and has not been tested is listed in the Administrator guide, section 12. 0.12.0 is a feature release: the first three teacher ideas below and the report of items nobody links to are in it (see [CHANGELOG.md](../CHANGELOG.md)).

## 1. Towards a stable release

These are the gaps the guides already admit to. Closing them is what would justify dropping the word Beta. The list is a proposal.

| Gap | What would close it |
|---|---|
| Little use on real sites | Trials on real Moodle sites, and their bug reports. Open an [issue](https://github.com/billsensei/moodle-contenttype_mediabank/issues) with what you found. |
| The SVG check has had no manual security review | A review by a person who knows SVG and Moodle's file serving. Until then SVG stays **off by default**. |
| Only the **File** resource was tried for Link or Copy | Try the choice in other activities that use the standard file picker (for example Folder or Assignment attachments) and fix what differs. |
| The browser scenarios do not cover video or SVG | The 6 scenarios were re-run for 0.11.1 and pass in CI on all four Moodle versions. Add scenarios for video upload and for SVG. |
| Site-level items were covered only by automated tests | Browser scenarios for items in the site context. |
| The captions button, transcript and alt text were tried in headless Chromium on a test site only | Try a File resource and a Page with a linked video and picture, as a student, on a real site, in other browsers and with a screen reader, and on a page saved by the TinyMCE editor; add a Behat scenario if the steps allow it. |
| `filter_mediabank` is Alpha and new | Real use on a site, then move it to Beta. |
| Very large files and a real web server | Test uploads near the 1 GB limit through a real web server, with and without antivirus scanning. |

## 2. Ideas that would help teachers

Not decided. Each needs a design check against how Moodle works.

- **Convert just my course's link to a copy.** Done in 0.12.0: a **Convert to copies** button in *Where this item is used*. It was clicked through in a browser with a real link on 2 November 2026 (it converts only that course, a plain GET does not convert). Still open: Behat covers only the "no links" answer, because the test steps cannot create a link without the file picker.
- **Carry alt text, captions and transcripts with the file.** Done in 0.12.0 for courses that **link** to an item, by the optional `filter_mediabank` (Alpha): a linked picture gets the bank's alt text; a video or audio player gets a captions track (off until the viewer turns it on) and a transcript. The design and the decisions are in [CAPTIONS-DESIGN.md](CAPTIONS-DESIGN.md). Still open: plain links to a file, forum posts and assignments, the Moodle mobile app, several caption languages, and a Behat scenario.
- **A clearer "where is this used" page.** Done in 0.12.0: activities are named and linked under each course. Still open: the `get_usage` web service returns courses only.
- **Translations.** The plugins ship English only. Language packs are welcome: see [TRANSLATING.md](TRANSLATING.md).

## 3. Ideas for administrators

- **Listing on the Moodle Marketplace** (which replaced the Moodle Plugins directory), so sites can find the plugins where administrators look. The listing text and checklist are in [PLUGIN-DIRECTORY.md](PLUGIN-DIRECTORY.md), updated for three entries and the Marketplace's route. Submitting needs your decisions (section 3 of that page: whether to list the Alpha filter now, the repository name, and screenshots taken on a real site).
- **A report of items nobody links to,** to help clear space. Done in 0.12.0: a page under Site administration > Reports and a command (`cli/unlinked.php`).

## 4. Video, audio and files

- **Streaming:** done in 0.10.0 (the MP4 index is moved to the front, in PHP). Not done, and not planned for now: re-encoding, resolution caps, several quality levels and HLS. They need a program such as ffmpeg on the server, which the project has chosen to avoid.
- **Other containers:** `.webm` and `.m4a` are left as they are.
- **More file kinds** only if there is a clear need and a safe way to check them. SVG showed how much work "safe" can be.

## 5. Not planned

- **Moodle 4.5 and earlier.** The plugins use Moodle 5.0 features.
- **Oracle and SQL Server.** Never tested. The plugins have no tables of their own, so they may work, but nobody has tried.
- **The Moodle mobile app.** The plugin has no mobile support.

## How to influence this

- **Ask or suggest:** open an issue and pick the feature request form. Say what you are trying to do, not only the feature you imagine.
- **Build it:** read [CONTRIBUTING.md](../CONTRIBUTING.md) and the [Development guide](DEVELOPMENT.md), and open a pull request. A small, tested change is more likely to be merged.
- **Report a security problem privately,** as [SECURITY.md](../SECURITY.md) describes.
