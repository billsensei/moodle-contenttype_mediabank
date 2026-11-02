#!/usr/bin/env python3
"""Take the listing screenshots from the seeded Behat site. See README.md.

    python3 scripts/screenshots/take_shots.py [OUTDIR]     (default /tmp/mediabank-shots)

Needs chromedriver on 127.0.0.1:9515 and the Behat site running (MEDIABANK_SHOT_SITE, default http://127.0.0.1:8010).
Looks the course, activity and item ids up through the site itself, because Behat does not reset its id counters.
"""
import json
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from wd import Browser  # noqa: E402

OUT = Path(sys.argv[1] if len(sys.argv) > 1 else '/tmp/mediabank-shots')
OUT.mkdir(parents=True, exist_ok=True)
EXTRA = 145  # The window size includes the browser's own frame; the page gets this much less.


def discover():
    ids = {}
    b = Browser(1280, 900)
    try:
        b.login('student1')
        b.go('/my/courses.php')
        time.sleep(4)
        links = b.js("return [...document.querySelectorAll('a')].filter(a=>/course\\/view.php/.test(a.href)&&/^(Biology|Chemistry)/"
                     ".test(a.textContent.trim())).map(a=>[a.textContent.trim().split('\\n')[0],a.href])")
        for name, href in links:
            ids[name] = int(href.split('id=')[1])
        for cid in list(ids.values()):
            b.go(f'/course/view.php?id={cid}')
            time.sleep(1.5)
            acts = b.js("return [...document.querySelectorAll('.activity a.aalink')].map(a=>[a.textContent.trim()"
                        ".replace(/\\s+(Page|File)$/,''),a.href])")
            for text, href in acts:
                ids[f'{cid}:{text}'] = int(href.split('id=')[1])
        b.close()
        b = Browser(1280, 900)
        b.login('teacher1')
        b.go(f'/course/view.php?id={ids["Biology 101"]}')
        time.sleep(1.5)
        bank = b.js("return document.querySelector('a[href*=\"contentbank/index.php\"]').href")
        b.go(bank)
        time.sleep(2)
        for text, href in b.js("return [...document.querySelectorAll('a[href*=\"contentbank/view.php\"]')]"
                               ".map(a=>[a.textContent.trim(),a.href])"):
            ids['item:' + text] = int(href.split('id=')[1])
    finally:
        b.close()
    return ids


def captions_on(b):
    """Turn the captions on through the player's own menu and park the video at 2 seconds."""
    result = b.js("""const items=[...document.querySelectorAll('.vjs-subs-caps-button .vjs-menu-item')];
      const cap=items.find(i=>/Captions/.test(i.innerText)&&!/off|settings/.test(i.innerText));
      if(!cap) return 'no captions item'; cap.click(); const v=document.querySelector('video.vjs-tech');
      v.currentTime=2; v.pause(); return 'ok';""")
    time.sleep(1.5)
    return result


def session(user, height):
    b = Browser(1280, height + EXTRA)
    b.login(user)
    return b


def main():
    ids = discover()
    json.dump(ids, open(OUT / 'ids.json', 'w'), indent=1)
    page = next(v for k, v in ids.items() if k.endswith(':Week 1: How a cell divides'))
    b = session('student1', 760)
    try:
        b.go(f'/mod/page/view.php?id={page}')
        time.sleep(3)
        print('captions:', captions_on(b))
        b.shot(str(OUT / 'teacher-8-captions-in-course.png'))
    finally:
        b.close()
    b = session('student1', 900)
    try:
        b.go(f'/mod/page/view.php?id={page}')
        time.sleep(3)
        captions_on(b)
        b.js("document.querySelector('details.filter-mediabank-transcript').open=true;")
        time.sleep(.5)
        b.shot(str(OUT / 'teacher-9-transcript-in-course.png'))
    finally:
        b.close()
    b = session('teacher1', 950)
    try:
        b.go(f"/contentbank/view.php?id={ids['item:The stages of mitosis']}")
        time.sleep(3.5)
        b.shot(str(OUT / 'teacher-10-where-used-and-convert.png'))
    finally:
        b.close()
    b = session('admin', 790)
    try:
        b.go('/local/mediabank/unlinked.php')
        time.sleep(2.5)
        b.shot(str(OUT / 'admin-1-unlinked-report.png'))
        b.go('/admin/filters.php')
        time.sleep(2.5)
        b.js("const r=[...document.querySelectorAll('tr')].find(r=>/Media bank text alternatives/.test(r.innerText));"
             "r.scrollIntoView({block:'center'}); r.style.outline='3px solid #1d6f86';")
        time.sleep(.6)
        b.shot(str(OUT / 'admin-2-manage-filters.png'))
    finally:
        b.close()
    print('wrote the screenshots to', OUT)


if __name__ == '__main__':
    main()
