# Media bank: Releasing

A step-by-step checklist for putting out a new version. It is for the maintainer. The reasons behind the steps are in [MAINTAINERS.md](MAINTAINERS.md), and the commands for tests and style are in the [Development guide](DEVELOPMENT.md).

Work through it in order and tick each box. Do not skip the checks because the change is small.

## The rules that matter most

1. **A change to the plugins' code needs a new version number and a new release.** Moodle only offers an upgrade when the number goes up, so a fix released under the old number never reaches the sites that already installed it.
2. **Never move or re-create a tag** after it is published.
3. **Merge to `main` only when all 64 CI jobs are green.**
4. **Make the tag only after CI is green on the exact commit it will name, and push it on its own, never in the same push as the branch.** A tag pushed before CI finishes has to be moved when CI finds something, and rule 2 forbids that. Release 0.12.1 went out this way and had its tag moved twice; do not repeat it.
5. **Publish as a pre-release** while the plugins are Beta.
6. **Attach the zips built from the commit that passed CI.**

A change to the guides alone is not a release. Push it to `main`, and, if the guides are in the bundle, replace the bundle on the latest release (see the end of this page).

## 1. Decide the version

| Change | `$plugin->release` | `$plugin->version` |
|---|---|---|
| Fix, no new feature | patch, for example 0.11.0 → 0.11.1 | `YYYYMMDDnn` of the day, `nn` counting up from 00 |
| New feature or setting | minor, for example 0.11.0 → 0.12.0 | same |
| Breaks something or changes what an administrator must do | minor while the plugin is 0.x, and say so at the top of the notes | same |

- `version` must be higher than the last release's. Two releases on the same day use `01`, `02` and so on.
- The project tag is `v<content type release>` (for example `v0.11.1`). If only `local_mediabank` changed, also raise the content type's release number by a patch so there is a new tag, and say in the notes that it has no code change.
- `local_mediabank` and `filter_mediabank` list the content type version they need under `$plugin->dependencies`. Raise that when they start calling something newer, and check it every release: a filter that calls a class the installed content type lacks does nothing, and a tools plugin that calls a missing method fails.
- `requires` stays at Moodle 5.0 (`2025041400`) and `supported` at `[500, 503]` unless you are adding or dropping a Moodle version (see "When Moodle releases a new version" in [MAINTAINERS.md](MAINTAINERS.md)).
- If the database changes, add an `upgrade.php` step. So far no release has needed one, because the plugins have no tables of their own.

## 2. Prepare a branch

    git switch -c release-<version>

Update, on that branch:

- [ ] `version.php` in each plugin that changed (`version` and `release`). `filter_mediabank/CHANGES.md` is the third changelog.
- [ ] Each plugin's `$plugin->dependencies` names the lowest content type version that has what it calls (the filter calls `link_captions`; the tools use `usage::find_unlinked()`).
- [ ] `contenttype_mediabank/CHANGES.md` and `local_mediabank/CHANGES.md`: rename "Unreleased" to the new version and date.
- [ ] Root `CHANGELOG.md` (see [CHANGELOG-POLICY.md](CHANGELOG-POLICY.md)): a new dated section with both plugin versions, and a `[x.y.z]: https://github.com/billsensei/moodle-contenttype_mediabank/releases/tag/vx.y.z` link at the bottom.
- [ ] [UPGRADING.md](UPGRADING.md): a row in the per-version table if an administrator sees any difference.
- [ ] [SECURITY.md](../SECURITY.md): the supported-version table (for example `0.11.x`).
- [ ] [ROADMAP.md](ROADMAP.md): the version numbers at the top, and move finished items out of "might come next".
- [ ] [COMMANDS.md](COMMANDS.md): the "Current numbers" table (tests, assertions, Behat).
- [ ] The guides, if what a person sees has changed. Redo the affected pages of the two PDF walk-throughs if a screen changed.
- [ ] Any user-visible words are language strings, kept in alphabetical order.

## 3. Check it locally

Run these on Moodle 5.0, 5.1, 5.2 and 5.3. The commands are in [DEVELOPMENT.md](DEVELOPMENT.md).

- [ ] PHPUnit for all three plugins, with no warnings.
- [ ] Behat for the plugins that have scenarios.
- [ ] `phpcs` with the Moodle standard, run inside a Moodle tree, as CI does it (section 3 of the Development guide). Check every file, including `lang/`: CI checks the order of language strings and fails on a warning (`--max-warnings 0`). Leave out nothing when you run it by hand.
- [ ] If the change touched uploading, serving files, SVG, video preparation, backup or privacy, run a security review of the changed files, and the privacy provider tests.

## 4. Build and validate the zips

    python3 scripts/build_release.py --out dist

This writes `contenttype_mediabank_<version>.zip`, `local_mediabank_<version>.zip`, `filter_mediabank_<version>.zip` and `mediabank-<release>-bundle.zip`. Then, for each zip, on each Moodle version, on a site where the plugin is not installed:

    php scripts/validate_plugin_zip.php --moodle=<moodle> --zip=dist/<zip> --component=contenttype_mediabank
    php scripts/validate_plugin_zip.php --moodle=<moodle> --zip=dist/<zip> --component=local_mediabank
    php scripts/validate_plugin_zip.php --moodle=<moodle> --zip=dist/<zip> --component=filter_mediabank

- [ ] Every check passes. The only warning expected is the Beta one. CI runs the build and these checks on every push (the `package` job, on Moodle 5.0 to 5.3), so a failure there means fix it before you go on; the manual run is still needed for the zips you upload.
- [ ] The bundle is **not** a plugin: do not run the validator on it, and do not upload it to Moodle.
- [ ] Spot-check the bundle: it holds both plugin zips, the guides in `GUIDES` in `scripts/build_release.py`, the two PDFs and the `images/` folder, and one `CHANGES-*.md` file for each of the three plugins.

Do a real install once on a clean site (content type first, then the tools plugin and the filter), or, for an upgrade, over the previous release. Follow [INSTALL.md](INSTALL.md) exactly as a user would.

## 5. Get CI green

    git push origin release-<version>
    gh run list --branch release-<version> --limit 1
    gh run view <id> --log-failed

- [ ] All 64 jobs are green. Read a failure with `--log-failed` once the whole run has finished; logs are not available while it runs.
- [ ] If a fix is needed, make it on the branch, rebuild the zips and start from section 4 again.

## 6. Merge and tag

Merge first, then wait, then tag. The tag is the last thing made, not the first.

    git switch main
    git merge --ff-only release-<version>
    git push origin main
    gh run list --branch main --limit 1
    gh run view <id>

- [ ] `main` fast-forwarded, so it is the commit CI tested.
- [ ] CI on `main` is **completed and successful, 64 of 64**, for the commit you are about to tag. Check `headSha` matches `git rev-parse HEAD`. Do not tag while any job is queued or running, and do not tag on the strength of the branch run alone.
- [ ] Rebuild the zips from `main` if the branch commit was not the same one, and check they match what you validated.

Only then:

    git tag -a v<release> -m "Media bank <release> (Beta)"
    git push origin v<release>

- [ ] The CI run for the tag also finishes green (it is the same commit, so it should). If it does not, treat it as a failed release: see "If a release is wrong" below.

## 7. Publish the release

    gh release create v<release> --prerelease --title "Media bank <release> (Beta)" \
        --notes-file <notes.md> \
        dist/contenttype_mediabank_<version>.zip \
        dist/local_mediabank_<version>.zip \
        dist/mediabank-<release>-bundle.zip

The notes should say, in plain words:

- what is new or fixed, and for whom;
- anything an administrator must decide or do, for example a new setting;
- **which file to download**: the two plugin zips, not the GitHub "Source code" files and not the bundle (see "Which file do I download?" in [INSTALL.md](INSTALL.md));
- that it is Beta, so try it on a copy of the site first.

Then check:

- [ ] `gh release view v<release>` shows the three assets, with the sizes you built.
- [ ] Download the two plugin zips from the release and run them through the validator once more.
- [ ] The release is marked **Pre-release**.

## 8. After the release

- [ ] Delete the branch: `git push origin --delete release-<version>` and `git branch -d release-<version>`.
- [ ] Open the README and check the guide links work.
- [ ] Add anyone who helped to [CONTRIBUTORS.md](CONTRIBUTORS.md).
- [ ] Answer any issues the release closes.

## Guides only, after a release

If only the guides changed:

1. Push to `main`.
2. If any changed guide is in the bundle, rebuild and replace only the bundle:

       python3 scripts/build_release.py --out <dir>
       gh release upload v<release> <dir>/mediabank-<release>-bundle.zip --clobber

3. Leave the two plugin zips alone. Check that the sizes of those two in `gh release view` did not change.

The guides in the bundle are the ones in `GUIDES` in `scripts/build_release.py`. Documents such as this one, [ROADMAP.md](ROADMAP.md), [SUPPORT.md](SUPPORT.md) and [MAINTAINERS.md](MAINTAINERS.md) are not in it, so changing only those needs no bundle swap.

## If a release is wrong

- **A mistake in the notes:** edit them with `gh release edit v<release> --notes-file <notes.md>`.
- **A mistake in a guide:** see "Guides only" above.
- **A mistake in the code:** do not replace the zips. Fix it on a branch and release the next version, and say in the notes that it replaces the one before. If a release is dangerous, mark it a draft or delete the release (not the tag) so it can no longer be downloaded, and say so in an issue and the changelog.
- **A security problem:** follow [SECURITY.md](../SECURITY.md). Release the fix first and describe it after.

## Copyright and licence

Copyright 2026 SenseiMoodle. Released under the GNU General Public License version 3 or later. See [LICENSE](../LICENSE).
