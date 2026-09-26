# Releasing

Checklist for publishing a new version to Packagist.

## Before tagging

1. `composer check` passes locally and CI is green on `master` (2.x) or `1.x`.
2. `CHANGELOG.md`: rename the `Unreleased` section to the version and today's
   date, add the compare link at the bottom, and start a new empty `Unreleased`
   section.
3. `UPGRADE.md` describes every behaviour change and deprecation of the release.
4. `README.md` examples still match the API; `docs/api/README.md` lists the
   snapshot date of the API definition that was checked.
5. For a new major version: `composer.json` `branch-alias`, `SECURITY.md`
   supported versions and `CONTRIBUTING.md` target branches are updated, and the
   previous major has its maintenance branch (`1.x`).

## Tagging

Tags are annotated and signed with the maintainer identity. Create them from a
local clone, not from CI:

```bash
git checkout master && git pull
git tag -a v2.1.0 -m "Version 2.1.0"
git push origin v2.1.0
```

Packagist picks the tag up through the GitHub webhook within a minute. If a
version does not appear, open the package page and click "Update".

## After tagging

1. Create the GitHub release from the tag. The release notes are generated
   from the merged pull requests according to `.github/release.yml`; paste the
   CHANGELOG section on top.
2. Check https://packagist.org/packages/metabytes-sro/epost-api shows the new
   version with the right PHP requirement.
3. For a bug fix that applies to both lines, cherry-pick the commit to `1.x`
   and release a 1.x patch version the same way.

## Never

- Never re-tag a published version. Publish a patch release instead.
- Never rewrite history on `master` or `1.x` after a tag was published from it.
