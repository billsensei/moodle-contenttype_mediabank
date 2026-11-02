#!/usr/bin/env python3
"""Make the demo files for the screenshots: a 20 s video, a captions file, a diagram and a one-page PDF handout.

    python3 scripts/screenshots/make_assets.py [OUTDIR]     (default /tmp/mediabank-shot-assets)

Needs Pillow and ffmpeg. The files are invented for the demo and carry no third-party content.
"""
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

OUT = Path(sys.argv[1] if len(sys.argv) > 1 else '/tmp/mediabank-shot-assets')
OUT.mkdir(parents=True, exist_ok=True)
F = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
FB = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'


def font(path, size):
    return ImageFont.truetype(path, size)


def diagram():
    w, h = 1000, 520
    im = Image.new('RGB', (w, h), '#f7f9fb')
    d = ImageDraw.Draw(im)
    d.text((w // 2, 34), 'The stages of mitosis', font=font(FB, 30), fill='#12384a', anchor='mm')
    for i, name in enumerate(['Prophase', 'Metaphase', 'Anaphase', 'Telophase']):
        cx, cy = 125 + i * 250, 240
        d.ellipse((cx - 95, cy - 95, cx + 95, cy + 95), fill='#e3f1f4', outline='#1d6f86', width=4)
        red = '#b23a48'
        if i == 0:
            for k in range(4):
                d.line((cx - 40 + k * 22, cy - 30, cx - 25 + k * 22, cy + 30), fill=red, width=6)
        if i == 1:
            d.line((cx - 70, cy, cx + 70, cy), fill='#9aa7ad', width=2)
            for k in range(4):
                d.line((cx - 33 + k * 22, cy - 25, cx - 33 + k * 22, cy + 25), fill=red, width=6)
        if i == 2:
            for k in range(4):
                d.line((cx - 33 + k * 22, cy - 62, cx - 33 + k * 22, cy - 22), fill=red, width=6)
                d.line((cx - 33 + k * 22, cy + 22, cx - 33 + k * 22, cy + 62), fill=red, width=6)
        if i == 3:
            d.ellipse((cx - 90, cy - 90, cx + 90, cy - 5), outline='#1d6f86', width=3)
            d.ellipse((cx - 90, cy + 5, cx + 90, cy + 90), outline='#1d6f86', width=3)
            for k in range(3):
                d.line((cx - 30 + k * 30, cy - 55, cx - 30 + k * 30, cy - 30), fill=red, width=6)
                d.line((cx - 30 + k * 30, cy + 30, cx - 30 + k * 30, cy + 55), fill=red, width=6)
        d.text((cx, cy + 135), name, font=font(FB, 22), fill='#12384a', anchor='mm')
        d.text((cx, cy + 165), f'Stage {i + 1}', font=font(F, 16), fill='#5b6b73', anchor='mm')
        if i < 3:
            d.polygon([(cx + 112, cy - 8), (cx + 138, cy), (cx + 112, cy + 8)], fill='#1d6f86')
    im.save(OUT / 'mitosis-diagram.png')


def video():
    w, h = 1280, 720
    im = Image.new('RGB', (w, h), '#12384a')
    d = ImageDraw.Draw(im)
    for y in range(h):
        d.line((0, y, w, y), fill=(int(18 + y * 0.03), 56 + int(y * 0.04), 74 + int(y * 0.04)))
    # The title sits above the middle so the player's play button does not cover it.
    d.text((w // 2, 200), 'Cell division', font=font(FB, 84), fill='#ffffff', anchor='mm')
    d.text((w // 2, 290), 'Biology 101 · Week 1', font=font(F, 38), fill='#bfe3ea', anchor='mm')
    im.save(OUT / 'title.png')
    subprocess.run(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y', '-loop', '1', '-i', str(OUT / 'title.png'),
                    '-f', 'lavfi', '-i', 'sine=frequency=220:sample_rate=44100', '-t', '20', '-r', '25',
                    '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-tune', 'stillimage', '-c:a', 'aac', '-b:a', '48k',
                    '-movflags', '+faststart', '-shortest', str(OUT / 'cell-division-lecture.mp4')], check=True)


def captions():
    (OUT / 'captions.vtt').write_text(
        "WEBVTT\n\n"
        "00:00.000 --> 00:05.000\nWelcome to week one. Today we look at how a cell divides.\n\n"
        "00:05.000 --> 00:10.000\nBefore mitosis begins, the cell copies its DNA.\n\n"
        "00:10.000 --> 00:15.000\nThen the chromosomes line up in the middle of the cell.\n\n"
        "00:15.000 --> 00:20.000\nFinally the cell splits into two identical daughter cells.\n")


def handout():
    w, h = 1240, 1754  # A4 at 150 dpi
    im = Image.new('RGB', (w, h), 'white')
    d = ImageDraw.Draw(im)
    d.rectangle((0, 0, w, 150), fill='#12384a')
    d.text((80, 75), 'Lab safety handout', font=font(FB, 58), fill='white', anchor='lm')
    d.text((80, 215), 'Biology 101 and Chemistry 201 · Read before your first practical', font=font(F, 28), fill='#5b6b73', anchor='lm')
    y = 300
    sections = [
        ('Before you start', 'Tie back long hair, wear a lab coat and closed shoes. Bags stay on the shelves by the door.'),
        ('Eye protection', 'Wear safety glasses whenever anyone in the room is working with chemicals or heat.'),
        ('Chemicals', 'Label every container. Never taste or smell a chemical. Pour waste into the marked bottles only.'),
        ('If something goes wrong', 'Tell your teacher at once. Spills, breakages and burns are always reported, however small.'),
        ('When you finish', 'Wash your hands, wipe your bench and return equipment to the trays.'),
    ]
    for head, body in sections:
        d.text((80, y), head, font=font(FB, 36), fill='#12384a')
        y += 58
        line = ''
        for word in body.split():
            test = (line + ' ' + word).strip()
            if d.textlength(test, font=font(F, 30)) > 1080:
                d.text((80, y), line, font=font(F, 30), fill='#222')
                y += 44
                line = word
            else:
                line = test
        d.text((80, y), line, font=font(F, 30), fill='#222')
        y += 100
    im.save(OUT / 'lab-safety-handout.pdf', 'PDF', resolution=150)


if __name__ == '__main__':
    diagram()
    video()
    captions()
    handout()
    print('wrote', ', '.join(sorted(p.name for p in OUT.iterdir() if p.name != 'title.png')), 'to', OUT)
