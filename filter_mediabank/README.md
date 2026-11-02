# Media bank text alternatives (filter_mediabank)

Companion to `contenttype_mediabank`, which it requires (install that one first). For Moodle 5.0 to 5.3. Status: **Alpha**.

A text filter. For a file that a course **links** to from the Media bank, it carries the text kept with the item into the course:
- a picture with no text alternative, an empty one, or only its file name gets the item's text alternative (one a teacher wrote, and a picture marked as decorative, are left alone);
- a video or audio player gets the item's captions, as a **Captions** button that is off until the viewer turns it on, and its transcript in a **Transcript** block under the player (a player that already has a captions track of its own is left alone).

Copies made from the bank are independent files and get nothing. Captions and transcripts are offered in activity descriptions, File, Page, Book and Folder content, and course and section summaries, and not in forum posts, assignments or blocks.

Install the plugin zip from Site administration > Plugins > Install plugins, after the content type. Then switch it on under Site administration > Plugins > Filters > Manage filters. Release notes: `CHANGES.md`. The design is in `docs/CAPTIONS-DESIGN.md` in the project.
