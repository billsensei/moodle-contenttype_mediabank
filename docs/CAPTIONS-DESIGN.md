# Media bank: Design for carrying captions and transcripts into courses

**Status: option B was chosen on 2 November 2026 and is built (unreleased).** See section 10 for what was decided and where the build differs from this proposal. Written the same day, after `filter_mediabank` 0.1.0 (alt text for pictures) was built. The evidence is in section 2; file names in square brackets are in the Moodle 5.3 tree.

## 1. The problem

A video or audio item in the Media bank can have a WebVTT caption file and a written transcript. A course that **links** to the item gets the bare media file and nothing else, so its viewers lose both ([ROADMAP.md](ROADMAP.md), Teacher guide section on accessibility). For pictures this is solved by the filter. Captions need more, because a caption is a *file the viewer's browser has to fetch*.

Why the obvious way fails: the caption file lives in the **item's** context (system, category or a course). A student in course B cannot read that context: they have no Content bank access there, and `contenttype_mediabank_pluginfile()` (rightly) refuses them. A `<track src>` pointing at the original file would be refused for exactly the people who need it.

**Goal:** a viewer who can play the linked video in a course sees its captions (and can open its transcript) with no action by the teacher, and a correction made once in the bank reaches every course.

**Not goals:** captions for copies (a copy is an independent file, and stays so); captions in the Moodle mobile app; several languages; creating or fixing captions; changing what a teacher typed.

## 2. What Moodle does (checked in the 5.3 tree)

1. **Players keep tracks that are already in the HTML.** `media_videojs` and `media_html5video` rebuild a player from `<video><source>...`, but when the text already had a media tag they keep it and only replace the sources; videojs also notices `<track>` (`$hastracks`) [`media/player/videojs/classes/plugin.php`, `media/player/html5video/classes/plugin.php`]. So adding a `<track>` child to a `<video>` or `<audio>` element is enough; no player work is needed.
2. **The File resource embeds media through the same machinery.** `resource_display_embed()` builds the player with `core_media_manager::embed_url()`, then calls `format_text($code, FORMAT_HTML, ['noclean' => true])` [`mod/resource/locallib.php`]. `noclean` still runs a filter's **pre-clean** stage. So a filter that works at that stage sees the `<video>` the File resource shows.
3. **The cleaner allows tracks.** HTMLPurifier in Moodle defines `video`, `audio`, `source` and `track` (`src`, `kind`, `srclang`, `label`, `default`) and also `details`/`summary` [`lib/weblib.php`]. Text that is cleaned afterwards keeps both.
4. **A filter must work before the cleaner** (learned the hard way with the alt text filter): `filter_stage_pre_clean()`, so untrusted text is purified after the filter. The same rule applies here.
5. **Plain links are converted late.** `filter_mediaplugin` turns `<a href="...mp4">` into a player at the *post-clean* stage, after our filter has run, so a track cannot be attached to a link by a pre-clean filter. It can be attached to an existing `<video>`/`<audio>` element, which is what the editor's *Insert media* produces and what the File resource displays. **To check in a browser** (open question 4): that TinyMCE inserts `<video>` and not a link in 5.0 to 5.3.
6. **Moodle gives a file alias no place for extra data.** An alias is a row in `files` with a `referencefileid`; nothing can be hung on it. That is why this needs a filter and a route, not a field.

## 3. Options considered

| | Idea | For | Against |
|---|---|---|---|
| **A** | Keep a **copy of the caption file in each course** next to every link, kept in sync (like `sync_links`) | Standard pluginfile access in the course's own context | A sync job, orphans, stale copies, backup/restore questions, storage, and every link needs a row of bookkeeping. A lot of moving parts for a small file |
| **B** | **Serve the item's caption through a route in the course's context**, checked against the *alias file*. No copies | Nothing to sync or clean; a correction in the bank is visible at once; no new table; the only new code is one `pluginfile` branch and the filter | Needs an access rule written by us (section 5), which is the part that must be reviewed carefully |
| **C** | Make the bank's caption readable to everyone who can see a link | Simplest | Opens the item's context to people who should not see it. Rejected |
| **D** | Do nothing; document that video and audio linked from the bank have no captions and teachers should add their own track | No risk | Defeats the point of captions that are kept with the file |

**Recommendation: B.**

## 4. The design (option B)

### 4.1 Data flow

```
Course page text (a <video> whose <source src> is a pluginfile URL)
        |  format_text() -> filter_mediabank, filter_stage_pre_clean()
        v
1. find each <video>/<audio>; take its first <source src> (or src)        [alt_text-style scanner]
2. resolve the URL to a stored alias file                                 [file_storage::get_file]
3. follow the alias to the bank item                                      [get_reference -> contentbank_content]
4. does the item have a caption file?  Is the alias in an allowed place?  (section 5)
5. add   <track kind="captions" label="Captions" src="<URL below>" default>
6. optionally add after the element:  <details><summary>Transcript</summary><p>...</p></details>
        |  then Moodle's cleaner (untrusted text) and, later, the player
        v
Viewer's browser asks for  /pluginfile.php/<ALIAS CONTEXT>/contenttype_mediabank/linkcaptions/<ALIAS FILE ID>/captions.vtt
        |  contenttype_mediabank_pluginfile()  (new branch)
        v
checks access to the alias file (section 5) -> streams the ITEM's caption file
```

The URL names the **alias**, not the item. The server finds the item itself. A viewer cannot ask for an item by guessing its id.

### 4.2 Pieces

| Where | What |
|---|---|
| `filter_mediabank` | Adds a `media_tracks` step beside `alt_text`: finds `<video>`/`<audio>`, resolves the source, adds `<track>` and (if wanted) the transcript. Reuses the tokenizer in `alt_text`. Still works only at `pre_clean`. |
| `contenttype_mediabank` | `lib.php`: new file area `linkcaptions` in `contenttype_mediabank_pluginfile()`; a small class (`link_access`) that decides whether a viewer may read captions through a given alias. `usage` or `captions` gets a helper `captions::get_for_alias()` used by both the filter and the route. |
| Lang strings | `Captions`, `Transcript`. |
| Docs | Teacher guide: the note "a course gets the bare file" becomes "a course that *links* shows captions for embedded players", with the exceptions below. |

No new table. No new capability. No scheduled task.

### 4.3 What the HTML looks like

```html
<video controls>
  <source src="https://site/pluginfile.php/123/mod_resource/content/1/talk.mp4" type="video/mp4">
  <track kind="captions" label="Captions" src="https://site/pluginfile.php/123/contenttype_mediabank/linkcaptions/987/captions.vtt" default>
</video>
```

- `kind="captions"`, because the bank's file is a caption file. There is no language stored with the item, so there is no `srclang` (the HTML rule requires it only for `kind="subtitles"`). A later version could add a language setting to the item.
- `default` is **not** set unless the teacher has not set anything else; browsers show the captions button either way. (Open question 2.)
- If the author already put a `<track>` in the element, **nothing is added**. A teacher's own captions win.

## 5. Security: who may read a caption (the part that needs review)

The rule must be **no weaker than "may play the video"**, and it must not reveal anything about items the viewer cannot see.

1. The route works from the **alias file**: `linkcaptions/<file id>/captions.vtt` in the alias's own context. The context in the URL must equal the alias file's context (as the existing route already does for items).
2. The file must be a real alias whose source is a `contenttype_mediabank` item in `contentbank/public` (the same checks `alt_text::lookup()` makes).
3. **Where the alias lives decides the access check.** The route applies a fixed **allow-list** of places whose access rule is simply "may see this activity or course", and refuses everything else:

   | Alias component / area | Check |
   |---|---|
   | any activity, area `intro` | `require_course_login($course, true, $cm)` (login, enrolment or guest rules, visibility and availability of the activity) |
   | `mod_resource` / `content`, `mod_page` / `content`, `mod_book` / `chapter`, `mod_folder` / `content` | the same, plus the activity's own *view* capability (`mod/<name>:view`) |
   | `course` / `summary`, `course` / `section` | `require_course_login($course)` |
   | everything else (forum posts, assignment submissions, quiz answers, block content, user files) | **refused**, because there access depends on the record (group, owner), which the route cannot know |

   An allow-list fails closed: a place nobody thought of gets no captions, not a leak.
4. A viewer who fails any step gets the **same answer** as for a missing file (as in `convert.php`), so the route cannot be used to learn which files, items or captions exist.
5. The caption is sent as `text/vtt`, never forced to download (a track must be fetched), with `X-Content-Type-Options: nosniff`, `Cache-Control: private` and a `Last-Modified` from the caption file, so a corrected caption is picked up. `\core\session\manager::write_close()` before streaming, as the existing route does.
6. Input is typed (`file id` is an integer); the filter never trusts a URL it did not resolve to a real alias.
7. **Transcript.** The text is stored in the item and shown to anyone who can see the video, so it is as sensitive as the captions and is only added when step 3 would allow them. It is escaped with `s()`; it goes into the page **before** cleaning, so the cleaner checks it. Plain text only, line breaks kept (`nl2br` after escaping).
8. Everything the filter adds is added at `pre_clean`, so for untrusted text HTMLPurifier checks the `<track>` and `<details>` it produced.

**Residual risks to accept or reject (question 3):** the allow-list is a policy, not a proof; if a listed module changes who can see its content (for example a future per-file permission in `mod_folder`), captions could be shown to someone who cannot open that file. The captions of a video are rarely more sensitive than the video, and the viewer can already see the activity, but it is a deliberate choice.

## 6. Edge cases

| Case | Behaviour |
|---|---|
| Teacher already added a `<track>` | Nothing added |
| Item has no captions | Nothing added (a transcript alone is still added, if it exists) |
| **Convert to copies** | The copy is an ordinary file; the filter finds no alias and adds nothing. The confirm page should say so |
| Backup and restore | The restored file is a copy (existing behaviour), so no captions. Same as alt text |
| Item moved, replaced or deleted | The route reads the item *now*, so a replaced caption shows at once. Deleting an item first turns its links into copies, so no dangling route |
| Item in an unlisted state | **Built differently from this proposal:** unlisted does not hide captions through a link. Unlisted only keeps an item out of lists; a course that links to it can already play the video, so hiding its captions would only hurt those viewers (section 10) |
| Plain `<a href="...mp4">` links | Not covered (converted after our stage). Documented |
| Moodle mobile app | Not covered (its own file route). Documented as untested |
| Audio | Same as video |
| Several players with the same file | The lookup is cached per request |
| Performance | Work happens only for text containing `<video`/`<audio` and `pluginfile.php`; one file lookup and one item read per distinct source; no extra cost on other pages |

## 7. Tests we would write

- PHPUnit, filter: a `<video>` with an alias source gets a `<track>`; an existing `<track>` blocks it; no captions means no track; a plain file means no track; hostile attribute lookalikes inside `<video>`/`<source>` (the same traps as for `<img>`); untrusted text through `format_text` has no script attribute; the post-clean and string stages do nothing.
- PHPUnit, route: enrolled student gets the caption; a user outside the course is refused with the same answer as a missing file; a hidden activity is refused; guest per course setting; a place not on the allow-list (forum post) is refused; a wrong context in the URL is refused; a converted copy is refused; a replaced caption is served at once; headers.
- Behat: a File resource showing a bank video has a `track` element whose URL a student can fetch (the video itself need not play); the same for a user without access.
- Checked by hand: a real browser shows the captions button; TinyMCE inserts `<video>`.

## 8. Steps and effort

1. **Route and access class** with all its tests (the risky part; review it before the filter): about a day.
2. **Filter step for `<video>`/`<audio>`**, tracks only: about half a day.
3. **Transcript** under the player: a few hours.
4. Docs, changelog, the confirm text on *Convert to copies*, release notes: a few hours.

`filter_mediabank` stays **Alpha** until the route has had an independent look.

## 9. Questions for the maintainer

1. **Go or no go on option B?** Or choose D (document the gap) until real sites ask for it.
2. **`default` on the track?** Showing captions to everyone by default helps some viewers and annoys others; leaving it off means the viewer turns them on. Suggest: off.
3. **Is the allow-list in section 5 acceptable**, or should captions only be offered for `intro` areas and the File resource, to keep the surface smallest?
4. **Can you check, on a real site, what the editor inserts for a video?** If it is a link, not a `<video>`, the filter would also need to turn that link into a player, which is a larger change.
5. **Transcript:** under the player as a `<details>` block, or leave it to the bank page? (It is the part some teachers will want to hide.)
6. **Languages:** one caption file per item is enough for now?

## 10. Decisions and what was built (2 November 2026)

**Decided by the maintainer:** go with option B, and captions are **off by default** (no `default` on the track). The other questions were answered by following the recommendations above: the list of places in section 5 as written, the transcript under the player as a `<details>` block, and one caption file per item. Question 4 was checked in the Moodle 5.3 tree: TinyMCE's *Insert media* produces `<video><source><track>` (`lib/editor/tiny/plugins/media/templates/embed/embed_media_video.mustache`), so the filter has to deal with players, not with plain links.

**Built:**

| Where | What |
|---|---|
| `contenttype_mediabank/classes/link_captions.php` | `url_for()`, `item_of()`, `is_place_allowed()` (the list), `viewer_may_read()` (login, enrolment or guest rules, activity visibility and availability, the activity's `view` capability, section visibility) and `find_file()` (the route's whole decision; every refusal is `null`) |
| `contenttype_mediabank/lib.php` | New file area `linkcaptions` in `contenttype_mediabank_pluginfile()`: login for the activity or course, `find_file()`, then `text/vtt` with `nosniff`, private cache, session closed |
| `filter_mediabank/classes/media_tracks.php` | Adds `<track kind="captions" label="Captions" src="...">` before `</video>` or `</audio>` and the transcript after it |
| `filter_mediabank/classes/link_lookup.php`, `html_tag.php` | Shared by the alt text and track steps: URL to link, and the tag tokenizer |

**Where the build differs from the proposal:**

1. **Unlisted items.** Not applied to links (see the edge-case table). The video plays through the link, so its captions must too.
2. **The filter only acts when the link is in the same context as the text** (`$this->context`), so text in one place cannot borrow a link from another and show its transcript to people who cannot see the file. The route checks the viewer separately.
3. **The viewer is not checked in the filter**, only the place. The filter's output can be cached with the page; the route checks the viewer on every request. A transcript is added to text that the viewer can already see with the video in it.
4. **No `default`** and no `srclang` (the item has no language).
5. **The dependency.** `filter_mediabank` calls `link_captions`, which is new in `contenttype_mediabank`. The filter does nothing if the class is missing. At release the filter's dependency in `version.php` must be raised to the content type version that has the class.

**Tests:** `contenttype_mediabank/tests/link_captions_test.php` (5 tests, 37 assertions: a student through a File resource; an outsider, a hidden activity, a wrong context and bad arguments; only the listed places, with a forum post, attachment and block refused; a replaced or removed caption and a converted copy; guests follow the course's guest access) and `filter_mediabank/tests/media_tracks_test.php` (7 tests, 30 assertions: track and transcript, audio with `src`, players left alone, no captions, other contexts and places, hostile text through the cleaner, other stages do nothing). With the place check switched off, one test in each file fails. The contenttype suite is 127 tests and the filter suite 20, all passing on 5.3.

**Not done or not tested:** a Behat scenario (the steps cannot create a link without the file picker); a real browser showing the captions button; other Moodle versions; the Moodle mobile app.
