# Screenshots for the listing and the guides

How the pictures in `docs/images/` for 0.12.0 were made, so they can be retaken after a screen changes. It needs a Moodle 5.x tree with its Behat environment (see [DEVELOPMENT.md](../../docs/DEVELOPMENT.md)), `chromedriver` and Chromium, Pillow and ffmpeg. Nothing here is loaded by the plugins and nothing here is installed on a real site.

1. **Make the demo files:** `python3 scripts/screenshots/make_assets.py /tmp/mediabank-shot-assets` (a 20 s video with a captions file, a diagram, a one-page PDF).
2. **Copy the plugins into the tree** instead of symlinking them: the pages `convert.php` and `unlinked.php` load `config.php` relative to their own folder, which fails through a symlink.

       cp -r contenttype_mediabank <moodle>/public/contentbank/contenttype/mediabank
       cp -r local_mediabank       <moodle>/public/local/mediabank
       cp -r filter_mediabank      <moodle>/public/filter/mediabank

3. **Add the seed step to the copy, not to the repository.** Paste the method in `seed_step.php.txt` into `behat_contenttype_mediabank.php` in the *copy*, and save `zz_seed.feature.txt` as `tests/behat/zz_seed.feature` in the copy. It builds two courses (Biology 101, Chemistry 201), a teacher (`teacher1`) and a student (`student1`) (the password is the username, as in Behat), six bank items, a Page and two File resources that link to them, a Label, and switches the filter on.
4. **Initialise Behat and seed:** `php public/admin/tool/behat/cli/init.php` (from the tree), then

       MEDIABANK_SHOT_ASSETS=/tmp/mediabank-shot-assets vendor/bin/behat --config <behat.yml> --tags=@zz_seed

5. **Start the site and the driver:** the Behat site (for example `php -S 127.0.0.1:8010 -t public`, the address in `config.php`'s `behat_wwwroot`) and `chromedriver --port=9515`.
6. **Take the pictures:** `python3 scripts/screenshots/take_shots.py /tmp/mediabank-shots`. It looks the ids up through the site, signs in as `student1`, `teacher1` and `admin`, turns the captions on in the player, parks the video at 2 seconds, opens the transcript, and writes five PNGs of 1280 pixels wide.
7. **Put the symlinks back** in the tree, and copy the pictures you want into `docs/images/`.

Notes:
- Behat does not reset its id counters, so the scripts never hard-code an id.
- A headless window's size includes the browser's own frame; `take_shots.py` adds 145 pixels to get the page height it asks for.
- Run `take_shots.py` on a freshly seeded site. The convert button changes the data: after pressing it the course no longer links to the item.
- The pictures show invented content only. Check that no real name, address or logo is in a new one.
