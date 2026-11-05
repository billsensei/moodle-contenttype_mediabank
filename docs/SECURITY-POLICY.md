# Media bank: Security policy, in full

[SECURITY.md](../SECURITY.md) in the root of the repository is the short version, and the one GitHub shows on the **Security** tab. If the two ever differ, SECURITY.md applies. This page explains how security is handled: what the plugins do to protect a site, what happens to a report, and what an administrator can do.

## 1. Reporting a problem

**Do not open a public issue, pull request or discussion for a security problem.** Report it privately:

1. Open the repository's **Security** tab and choose **Report a vulnerability**, or go to <https://github.com/billsensei/moodle-contenttype_mediabank/security/advisories/new>.
2. Say which plugin and version, the Moodle and PHP versions and the database, what a person can do with the problem, what access they need, and how to reproduce it.

If you are not sure whether something is a security problem, report it privately anyway. It is easy to move a report into the open, and not possible to take one back.

Problems in Moodle itself go to Moodle: <https://moodle.org/security/>. If you cannot tell, report it here and it will be passed on if it belongs there.

## 2. What happens to a report

This is a one-person project, so these are the steps and not a promise of speed. There is no guaranteed reply time ([SUPPORT.md](SUPPORT.md)).

| Step | What happens |
|---|---|
| Read | The maintainer reads the report and tries to reproduce it. Questions come back through the private report. |
| Decide | You are told whether it is accepted, and how serious it is judged to be. |
| Fix | The fix is made privately, with a test that fails before it and passes after. |
| Release | A new version number and a new release. A fix is never slipped into a version people already have ([RELEASING.md](RELEASING.md)). |
| Describe | After the fix is released, the changelog and the release notes say what kind of problem it was, who it affected and what the fix does. |
| Credit | You are named in the notes and in [CONTRIBUTORS.md](CONTRIBUTORS.md) if you want to be. |

The date for telling the public is agreed with the reporter. The notes do not give steps to exploit the problem.

There is no bounty. The project is free and unfunded ([FUNDING.md](FUNDING.md)).

## 3. Which versions get fixes

Only the newest release of each plugin, for Moodle 5.0 to 5.3. Older releases are not patched: upgrade ([UPGRADING.md](UPGRADING.md)). The plugins are **Beta**, so try them on a copy of your site first.

Because Moodle only offers an upgrade when the version number goes up, a code change after a release is always a new release. The one exception so far is 0.11.0, whose zips were replaced on 2026-10-30 without a version bump. That is why 0.11.1 and 0.3.1 exist, and why the 0.11.0 release says it is superseded.

## 4. What the plugins do

These are the protections built in, so a reviewer knows what to look for. They are described, not guaranteed: no software is free of mistakes.

| Area | What it does |
|---|---|
| Who may see a file | Serving a file is decided by the plugin's own check (`can_serve`), not left to Moodle alone. Files go only to people who may see the item. An item can be made unlisted. |
| Uploads | A file's content must match its name, so a document renamed to `.png` is refused. Kinds and size limits are settings. |
| SVG | Off by default. When an administrator turns it on, the file is checked against a list of elements and attributes it may contain, shown only as a picture, and sent only as a download. |
| Video | Preparing an MP4 for streaming reads only a limited amount of the file, refuses what it does not understand, and does not run any program on the server. |
| Backup and restore | A restore checks the contents of the backup (since tools 0.2.0). |
| Data | No tables of its own. Personal data is handled through the Moodle privacy API, including export and erasure ([ADMIN-GUIDE.md](ADMIN-GUIDE.md)). |
| Web services | Read only. |

Earlier fixes are listed in the changelog ([CHANGELOG.md](../CHANGELOG.md)).

## 5. Known limits

Be told plainly:

- **The SVG check has had no manual security review.** It has been through several rounds of automated review. Leave SVG off unless you need it ([ROADMAP.md](ROADMAP.md)).
- The plugins are Beta and have not had long use on real sites.
- Uploads near the 1 GB limit have not been tried through a real web server, with or without antivirus scanning.
- The plugin does not scan for viruses. Use Moodle's antivirus setting if you need it.
- Oracle, SQL Server and the Moodle mobile app have not been tested.

## 6. What an administrator can do

- Keep Moodle and PHP up to date. A site that has not applied Moodle's own security updates is not protected by this plugin.
- Install the newest release of both plugins (0.12.0 and 0.4.0 or later) and take backups first.
- Leave **SVG** off unless you need it.
- Keep the list of allowed kinds and the size limit to what your teachers use.
- Give the Content bank upload permission only to people who need it.
- Turn on Moodle's antivirus scanning if your site takes uploads from many people.
- Check **Site administration → Plugins → Plugins overview** now and then for the plugin versions, and watch the [releases page](https://github.com/billsensei/moodle-contenttype_mediabank/releases) (use **Watch → Custom → Releases**).

## 7. If you think your site has been affected

1. Do not post details in a public place.
2. Report it privately (section 1). Say which version you run.
3. Upgrade to the newest release when it is published.
4. Check the items and files on your site that were added around the time you noticed the problem, and ask the people who added them.

## 8. Contributors and reviewers

A change that reads an uploaded file has a limit and refuses what it does not understand. Check access in the plugin, not only in Moodle. See "Security review habits" in the [Development guide](DEVELOPMENT.md) and the standing rules in [MAINTAINERS.md](MAINTAINERS.md).

Reviews are welcome. If you have security experience with SVG or with Moodle file serving, a look at the SVG check would help more than anything else on the list.

## Copyright and licence

Copyright 2026 SenseiMoodle. Released under the GNU General Public License version 3 or later. See [LICENSE](../LICENSE).
