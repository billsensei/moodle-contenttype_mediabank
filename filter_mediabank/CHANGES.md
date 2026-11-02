# Changelog

## 0.1.2 (2026-11-02)
Version 2026110202. Hardening. No new setting and no table. Moodle offers the upgrade; nothing else is needed. Needs contenttype_mediabank 0.12.0, as before.

- A picture gets the text alternative of a media bank item only when the link is in a place where seeing the page means being allowed to read the file (activity descriptions, File, Page, Book and Folder content, course and section summaries). Before, any link in the text's own context counted. This is the same rule the captions and transcript already followed.

## 0.1.1 (2026-11-02)
Version 2026110201. Fixes and hardening from a code and security audit of the previous release. No new setting and no table. Moodle offers the upgrade; nothing else is needed.

- A picture gets the text alternative of a media bank item only when the link is in the same context as the text, as the captions and transcript already required. Before, text in one course could show the alt text of an item linked in another.

## 0.1.0 (2026-11-02)
Version 2026110200. Alpha. Needs contenttype_mediabank 0.12.0.

- First version, 0.1.0, **Alpha**. A text filter for courses that link to a media bank item: a picture with no real text alternative gets the one kept with the item; a video or audio player gets the item's captions (a **Captions** track, off until the viewer turns it on) and its transcript (a **Transcript** block under the player: collapsible where Moodle's cleaner keeps `<details>`, which is 5.3 and later, and a plain labelled block on 5.0 to 5.2). Captions are read through a route in `contenttype_mediabank` (`link_captions`) that checks the viewer against the activity or course the link is in. It works before Moodle cleans the HTML, so text that is not trusted is cleaned after it. Switch it on under Site administration > Plugins > Filters > Manage filters. Needs contenttype_mediabank 0.12.0. See `docs/CAPTIONS-DESIGN.md` in the project.
