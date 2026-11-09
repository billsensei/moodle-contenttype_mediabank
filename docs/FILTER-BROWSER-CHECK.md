# Media bank filter: real-browser check

What to do, by hand, to move `filter_mediabank` from Alpha to Beta. The automated tests pass; this list covers what they cannot: what a person sees and hears. Allow about 1.5 hours for one Moodle version, 30 minutes for each further one.

Do it on a **test site**, never a live one. Write the result next to each line (pass, fail, note) and keep the list with the release notes.

## 0. Set up

- [ ] Moodle 5.3 test site (then repeat sections 2 to 6 on 5.0, because the transcript differs: collapsible on 5.3, a plain labelled block on 5.0 to 5.2).
- [ ] Install `contenttype_mediabank`, `local_mediabank` and `filter_mediabank` from the **release zips** (Site administration > Plugins > Install plugins), not from source.
- [ ] Site administration > Plugins > Filters > Manage filters: **Media bank text alternatives** set to **On**.
- [ ] Two courses, **Biology 101** and **Chemistry 201**. A teacher in both, a student in Biology 101 only, and a second student in Chemistry 201 only.
- [ ] Demo files: `python3 scripts/screenshots/make_assets.py /tmp/mediabank-shot-assets` gives a 20-second video, a diagram and a PDF. Make the video's captions file with a typo you will correct later.
- [ ] In the Content bank (site level), upload the video with captions and a transcript, and the diagram with a text alternative.
- [ ] Browsers: Chrome and Firefox, desktop. Safari too if you have it.

## 1. Which tags does the editor insert? (open question 4 in CAPTIONS-DESIGN.md)

The filter only works on a `<video>` or `<audio>` element, not on a plain link.

- [ ] As the teacher, in a Page in Biology 101, use the editor's **Insert media** and pick the bank video with **Link to the file**. Open **Edit HTML / source code**: is there a `<video>` element? Write down what you see.
- [ ] Same on a Label and on a Book chapter.
- [ ] Repeat on Moodle 5.0 and 5.3.
- [ ] Add the video as a **File** resource, shown on its own page. It should show a player.
- [ ] Add the video as a plain link. Note that no captions are expected for this one (documented limit).

## 2. Captions

As the **student in Biology 101**, open each page from section 1.

- [ ] The player has a **Captions** (CC) button.
- [ ] Captions are **off** until you turn them on. Turn them on: the text shows over the video, in time with the speech, and is readable (size and contrast).
- [ ] Turn them off again. They go away.
- [ ] Keyboard only (Tab, Enter, Space): reach the Captions button, switch it on and off. The focus is visible.
- [ ] Screen reader (NVDA or VoiceOver): the Captions control has a sensible name.
- [ ] Reload the page. No error in the browser console (F12) and no failed request for `captions.vtt`. Look at the Network tab: the caption request goes to the **course** address (`/pluginfile.php/<context>/contenttype_mediabank/linkcaptions/...`), status 200, type `text/vtt`.
- [ ] A player that already has a captions track the teacher added is left alone (no second Captions entry).
- [ ] A **copy** of the file (use Make a copy) shows no bank captions. That is correct.

## 3. Transcript

- [ ] Under the player there is a **Transcript** block.
- [ ] On 5.3 it opens and closes (`details`). On 5.0 to 5.2 it is a labelled block that is always shown.
- [ ] Open and close it with the keyboard. A screen reader announces it as a Transcript.
- [ ] Line breaks are kept. Special characters (`<`, `&`, quotation marks) show as text and are not treated as HTML.
- [ ] Put a long transcript (a few thousand words) on the item. The page still loads quickly and reads well.
- [ ] Narrow the window to phone width. The player and transcript still fit, with no sideways scrolling.

## 4. Alt text on pictures

- [ ] A bank picture linked in a Page, with no alt text typed in the editor, gets the bank's alt text. In the browser, "Inspect" the picture to see it.
- [ ] A picture where the teacher typed their own alt text keeps it.
- [ ] A picture marked as decorative (empty alt) stays decorative.
- [ ] A picture whose alt text is only its file name gets the bank's.
- [ ] Correct the alt text in the bank. Reload the course: the correction shows, with no change to the course.

## 5. Corrections reach the course

- [ ] In the Content bank, fix the typo in the captions file (replace it with a corrected file) and change a line in the transcript.
- [ ] Reload as the student (a hard refresh: Ctrl+Shift+R). The new caption and transcript show. If an old caption stays, note whether it clears after a normal reload (the file is sent with private caching).

## 6. Who must not get the captions

The caption address has to be no weaker than "may play the video".

- [ ] The **Chemistry 201 student** opens the **Biology 101** caption address directly (copy it from the Network tab in section 2). Expected: the same "not found" answer as for a file that does not exist.
- [ ] A **logged-out** browser opens the same address. Expected: refused or a login page, no caption text.
- [ ] A **hidden** Page in Biology 101: the student cannot open it, so cannot get its captions. Also try the caption address directly.
- [ ] A **hidden Book chapter**: the student gets no captions for it.
- [ ] Try the caption address with the number changed by one. Expected: the same "not found" answer, never a different message.
- [ ] A link in a **forum post** gets **no** captions (outside the allowed places, which is by design). A forum post with a picture gets no bank alt text.
- [ ] Switch the filter **off** in Manage filters. Captions and transcripts disappear from the pages and nothing else breaks.
- [ ] Switch it back on.

## 7. Other things that can break

- [ ] Backup and restore Biology 101 into a new course on the same site, then on a second site that has the plugins. Captions work after the restore, or the player works without them and no error shows.
- [ ] Delete the bank item while a course links to it. Moodle refuses, or the course keeps a copy. The page shows no error.
- [ ] Upgrade from the earlier version (install 0.1.1, then 0.1.2): Moodle offers the upgrade and nothing is lost.
- [ ] Uninstall the filter only. The content type and the courses keep working.

## 8. Decide

All of sections 1 to 6 pass on 5.3 and 5.0, and nothing in 7 is worse than a documented limit:

1. In `filter_mediabank/version.php` change `MATURITY_ALPHA` to `MATURITY_BETA` and raise the `version`.
2. Change "Alpha" to "Beta" in the filter's README, `CHANGES.md`, the project README, `docs/PLUGIN-DIRECTORY.md`, `docs/FAQ.md` and `CHANGELOG.md`.
3. Remove "not yet seen in a browser" from the listing text, and say which browsers and Moodle versions you checked.
4. Release it ([RELEASING.md](RELEASING.md)), then submit it (Entry 3 in [PLUGIN-DIRECTORY.md](PLUGIN-DIRECTORY.md)).

Anything that fails: write it down with the browser, the Moodle version, the page and what you saw, and open an issue. A failure in section 6 (who may read a caption) is a security problem: stop and follow [SECURITY-POLICY.md](SECURITY-POLICY.md).

## Copyright and licence

Copyright 2026 SenseiMoodle. Released under the GNU General Public License version 3 or later. See [LICENSE](../LICENSE).
