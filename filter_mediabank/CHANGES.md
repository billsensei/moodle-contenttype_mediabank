# Changelog

## 0.1.0 (2026-11-02)
Version 2026110200. Alpha. Needs contenttype_mediabank 0.12.0.

- First version, 0.1.0, **Alpha**. A text filter for courses that link to a media bank item: a picture with no real text alternative gets the one kept with the item; a video or audio player gets the item's captions (a **Captions** track, off until the viewer turns it on) and its transcript (a **Transcript** block under the player: collapsible where Moodle's cleaner keeps `<details>`, which is 5.3 and later, and a plain labelled block on 5.0 to 5.2). Captions are read through a route in `contenttype_mediabank` (`link_captions`) that checks the viewer against the activity or course the link is in. It works before Moodle cleans the HTML, so text that is not trusted is cleaned after it. Switch it on under Site administration > Plugins > Filters > Manage filters. Needs contenttype_mediabank 0.12.0. See `docs/CAPTIONS-DESIGN.md` in the project.
