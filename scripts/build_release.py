#!/usr/bin/env python3
"""Build the installable zip for this Moodle plugin.

    python3 scripts/build_release.py [--out DIR]

Writes <component>_<version>.zip into DIR (default: ./dist), for example contenttype_mediabank_2026112101.zip. The zip can be
installed with Site administration > Plugins > Install plugins.

The repository root is the plugin. The zip has one top folder called "mediabank", which is what Moodle's installer expects for
this plugin, and holds the plugin files, including LICENSE. Files that are about the project and not the plugin (docs, scripts,
the contributor files, CI configuration, composer.json) are left out. The zip is reproducible: file order and timestamps come from
the files and the last commit, not from the time of the build.

Run scripts/validate_plugin_zip.php against a Moodle tree to check a zip the way the installer does.
"""
import argparse
import re
import subprocess
import sys
import time
import zipfile
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
SKIP_DIR_NAMES = {'node_modules', 'vendor', '__pycache__', 'docs', 'scripts', 'dist'}
SKIP_FILE_NAMES = {'composer.json', 'composer.lock', 'CONTRIBUTING.md', 'CODE_OF_CONDUCT.md', 'SECURITY.md', 'CHANGELOG.md'}


def read_plugin() -> tuple[str, str, str]:
    """Return (component, version, release) from version.php."""
    text = (REPO / 'version.php').read_text()
    component = re.search(r"\$plugin->component\s*=\s*'([^']+)'", text).group(1)
    version = re.search(r"\$plugin->version\s*=\s*(\d+)", text).group(1)
    release = re.search(r"\$plugin->release\s*=\s*'([^']+)'", text).group(1)
    return component, version, release


def commit_time() -> int:
    out = subprocess.run(['git', 'log', '-1', '--format=%ct'], cwd=REPO, capture_output=True, text=True)
    return int(out.stdout.strip()) if out.returncode == 0 and out.stdout.strip() else 315532800


def included():
    for path in sorted(REPO.rglob('*')):
        relative = path.relative_to(REPO)
        parts = relative.parts
        if any(part.startswith('.') or part in SKIP_DIR_NAMES for part in parts):
            continue
        if len(parts) == 1 and parts[0] in SKIP_FILE_NAMES:
            continue
        if path.suffix == '.zip':
            continue
        yield path, relative


def add(zf: zipfile.ZipFile, name: str, data: bytes, timestamp: tuple, mode: int) -> None:
    info = zipfile.ZipInfo(name, timestamp)
    info.compress_type = zipfile.ZIP_DEFLATED
    info.external_attr = mode << 16
    zf.writestr(info, data)


def build(out: Path, timestamp: tuple) -> Path:
    component, version, _ = read_plugin()
    target = out / f'{component}_{version}.zip'
    with zipfile.ZipFile(target, 'w') as zf:
        for path, relative in included():
            name = 'mediabank/' + relative.as_posix()
            if path.is_dir():
                add(zf, name + '/', b'', timestamp, 0o40755)
            else:
                add(zf, name, path.read_bytes(), timestamp, 0o100644)
    return target


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--out', default=str(REPO / 'dist'), help='folder for the zip (default: ./dist)')
    args = parser.parse_args()
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    when = time.gmtime(commit_time())
    timestamp = (max(when.tm_year, 1980), when.tm_mon, when.tm_mday, when.tm_hour, when.tm_min, when.tm_sec)
    target = build(out, timestamp)
    print(f'{target}  ({target.stat().st_size} bytes)')
    return 0


if __name__ == '__main__':
    sys.exit(main())
