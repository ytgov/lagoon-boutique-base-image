# lagoon-boutique-base-image

The shared Drupal codebase and Docker images for Government of Yukon "boutique"
sites: special-purpose Drupal sites run by eServices. It follows Lagoon's
[base image](https://docs.lagoon.sh/concepts-advanced/base-images/)
technique. Sites build on a tested release of the base instead of each
maintaining its own core, standard modules and images.

© 2026 Government of Yukon / Gouvernement du Yukon

**Contact:** [eservices@yukon.ca](mailto:eservices@yukon.ca)

## Overview

### When to use it

Use the base for any Government of Yukon Drupal site that should follow the
boutique standards: the same Drupal core, Drush, standard contrib modules and
patches, PHP version and Lagoon images as every other boutique site, updated
together.

Don't use it for a site that needs a different Drupal major version than the
current base line, or one that can't use the standard modules. Each site may
require other modules as needed.

### How it's structured

The repository is two things at once:

- **A Composer package**, `ytgov/lagoon-boutique-base-image`. Its
  `composer.json` requires Drupal core (`drupal/core-recommended`), Drush and
  the standard contrib modules, and lists the standard patches under
  `extra.patches`. A site requires this one package instead of core and the
  modules.
- **Three Docker images**, built from `Dockerfile.cli`, `Dockerfile.php` and
  `Dockerfile.nginx` on top of Lagoon's images and published to GitHub
  Container Registry:
  - `ghcr.io/ytgov/lagoon-boutique-base-image-cli`: the codebase with
    Composer and Drush.
  - `ghcr.io/ytgov/lagoon-boutique-base-image-fpm`: PHP-FPM.
  - `ghcr.io/ytgov/lagoon-boutique-base-image-nginx`: Nginx.

A site's own images start `FROM` these and add the site's code. On servers that
don't use the images, `composer install` brings in the same codebase.

**Branches and versions:**

| Branch | Drupal | Release tags | Image tags |
|---|---|---|---|
| `prod11` (default) | 11 | `v11.Y.Z` | `:11.Y.Z`, `:11.Y`, and `:11.x` / `:prod11` for the branch head |

Every build is also tagged `:sha-<commit>`. Sites use release tags
(`^11.0` in Composer, an exact tag such as `:11.3.0` in their Dockerfiles), not
the branch.

The older Drupal 10 lines (`prod`, `prod10`, image tags `:latest` and `:10.x`)
are kept only for sites not yet on Drupal 11 and will be decommissioned.

**Other files:**

- `scripts/canary.sh`: the canary test (see [Testing with the canary](#testing-with-the-canary)).
- `patches/`: patches the base keeps itself (see [patches/README.md](patches/README.md)).
- `.github/workflows/docker-build.yml`: builds, tests and publishes the images.
- `.github/dependabot.yml`: weekly update pull requests.
- `.ddev/`: a DDEV project for working on the base.

## Requirements

- [DDEV](https://ddev.com/) for local work. Run Composer and Drush through it
  (`ddev composer …`, `ddev drush …`).
- [GitHub CLI](https://cli.github.com/) (`gh`) for pull requests and releases.
- Access to GitHub: Composer reads the base from GitHub. If Composer reports
  "Could not authenticate", see [Troubleshooting](#troubleshooting).

The images are built for `linux/amd64`.

> **Apple Silicon:** DDEV works as-is. To build a site's images locally, pass
> `--platform linux/amd64` to `docker build`.

## Using the base image

### Sites created from the boutique template

[lagoon-boutique-template](https://github.com/ytgov/lagoon-boutique-template)
is already set up to use the base. Create the site from it on GitHub ("Use this
template") and follow the template's README. The template provides:

- `composer.json` requiring `ytgov/lagoon-boutique-base-image: ^11.0`, with
  `extra.enable-patching` and `composer-exit-on-patch-failure` set so the base's
  patches apply.
- `lagoon/*.dockerfile` built `FROM` the base images at the same release as
  `composer.lock`.
- Configuration that enables every base module, and the standard settings
  (search, login, breadcrumbs, translation).
- GitHub workflows: a canary run on every push and pull request, and
  Dependabot pull requests for new base releases.

"Use this template" copies files only. Apply the template repository's GitHub
settings (branch rules, security settings) to the new repository by hand.

Deployment steps belong to the site, because they depend on its Lagoon
configuration, settings and environment variables. Document them in the site's
repository.

### Other Drupal sites

To move an existing Drupal 11 site onto the base:

1. In `composer.json`:
   - add the base's repository:
     `{"type": "vcs", "url": "https://github.com/ytgov/lagoon-boutique-base-image.git"}`
   - require `ytgov/lagoon-boutique-base-image: ^11.0`, and remove requirements
     the base already provides (core, Drush, standard modules);
   - set `extra.enable-patching: true` and
     `extra.composer-exit-on-patch-failure: true`;
   - set `minimum-stability: dev` with `prefer-stable: true`.
2. `ddev composer update -W`, then `ddev drush updb` and `ddev drush cex`.
3. Enable the base's modules (they're listed in its `composer.json`), and
   review the template's configuration for the standards to adopt.
4. To build images, start them `FROM` the base images at the release in
   `composer.lock`; copy the template's `lagoon/*.dockerfile` as a starting
   point.
5. Copy the template's `.github/workflows/canary.yml` and
   `.github/dependabot.yml` to get the same checks and update pull requests.

Compare the site with the template (`settings.php`, `all.services.yml`, the
`yg_login` module) to pick up the remaining standards.

### Keeping a site up to date

When the base is released, Dependabot opens a pull request on each site that
updates `composer.lock` and the image tags in `lagoon/*.dockerfile` together.
Check the canary result, then merge.

- On those pull requests, use "Update branch" in GitHub, not
  `@dependabot rebase`; Dependabot ignores rebase requests on grouped updates.
- After deploying, run `drush updb`, `drush cim` and `drush cr` as usual. Some
  releases also need `drush locale:check && drush locale:update` (translations)
  or a search reindex.
- A site's canary uses the `scripts/canary.sh` of the release it's on, so new
  checks reach a site with its next base update.

To update by hand: `ddev composer update ytgov/lagoon-boutique-base-image -W`,
then set the same release in `lagoon/*.dockerfile`. The site canary fails if
the two disagree.

## Maintaining the base image

### Branches, versions and releases

Work happens on a working branch, `eservices/prepare-prodNN`, merged into
`prodNN` by pull request:

1. Bring the working branch up to date: `git merge --ff-only origin/prod11`.
2. Make the change, run the canary locally, push, and open a pull request into
   `prod11`. The `build-and-push` check (which runs the canary) must pass, and
   the pull request needs an approval. Maintainers and administrators can merge
   their own pull request once the check passes, using **Merge without waiting
   for requirements to be met** (see [Roles](#roles)).
3. After merging, a maintainer or administrator tags the merged commit from
   this repository:

   ```bash
   git fetch origin
   git tag -a v11.Y.Z -m "v11.Y.Z" origin/prod11
   git push origin v11.Y.Z
   ```

   Wait for the tag's build, then publish release notes, including any steps
   sites need after updating:
   `gh release create v11.Y.Z --verify-tag --notes "…"`.
4. Update the template to the new release (lock and Dockerfiles) by pull
   request. Sites follow through Dependabot.

Versions follow semantic versioning: a patch release (`v11.3.1`) for updates
and fixes, a minor release (`v11.4.0`) for added modules or other new
capabilities. Tags can't be moved or deleted; to fix a bad release, release the
next version.

### Updating core or modules within a major version

1. Update on the working branch:
   - core: `ddev composer update "drupal/core-*" -W`;
   - one module: `ddev composer update drupal/<module> -W`;
   - a module's new major version: change its constraint in `composer.json`
     first. Dependabot doesn't propose major versions; they're upgraded on
     purpose.
2. Read the release notes for update hooks, configuration changes and
   dropped patches. If a patch no longer applies, remove or replace it (see
   [patches/README.md](patches/README.md)).
3. Run the canary against the template. If an update changes configuration,
   export it in the template and test with `--template <template checkout>`.
4. Release as a patch version.

Dependabot proposes these updates weekly; review them the same way.

### Adding or removing a standard module

The base canary requires every base module to be enabled by the template, so
the base and the template change together:

1. Base: `ddev composer require drupal/<module>`.
2. Template: enable and configure the module on its working branch and push
   it (no pull request yet).
3. Point the base canary at that branch (an administrator: only administrators
   can manage Actions variables):
   `gh variable set CANARY_TEMPLATE_REF --body eservices/prepare-prod11 -R ytgov/lagoon-boutique-base-image`
4. Open the base pull request, merge it and tag a minor release.
5. Update the template to the release, open its pull request and merge it.
6. Delete the variable (an administrator):
   `gh variable delete CANARY_TEMPLATE_REF -R ytgov/lagoon-boutique-base-image`

Removing a module is the reverse: uninstall it in the template first, then
remove it from the base.

### Adding or changing a patch

Patches are listed in `composer.json` under `extra.patches` and referenced by
URL, so that sites can apply them too. See [patches/README.md](patches/README.md)
for how to add, change and remove one. The canary checks that every patch
applies.

### Updating PHP, the Lagoon images or MariaDB

The Lagoon image tags are set in `Dockerfile.cli`, `Dockerfile.php` and
`Dockerfile.nginx`; Dependabot proposes new ones. Change all three together,
and the MariaDB image in `docker-compose.yml` and in the workflow's database
service to match.

A PHP version change also means changing `config.platform.php` in
`composer.json` and the PHP version in `.ddev/config.yaml`. Release it as a
minor version, and check that the servers sites deploy to support it.

### Upgrading to a new major Drupal version

A new major version gets its own branch, so sites move when they're ready:

1. Check contrib readiness (the Upgrade Status module) and drop modules that
   won't be ready.
2. Create `prodNN` from the current branch and an
   `eservices/prepare-prodNN` working branch.
3. Raise the constraints in `composer.json` (core, Drush, contrib majors), run
   `ddev composer update -W`, and update patches, the PHP version and the
   Lagoon images as needed.
4. Create the template's `prodNN` branch with matching changes, and use
   `CANARY_TEMPLATE_REF` until it exists on GitHub.
5. Update the branch-specific settings: Dependabot's `target-branch` and the
   repository's default branch (in both repositories), once the new line is
   ready.
6. Release `vNN.0.0`. The major version must match the branch: the canary
   tests a `vNN.*` tag against template branch `prodNN`.

Sites upgrade by applying the template's changes between the two branches
(`git diff prod11 prodNN` in the template), then `composer update`, `drush
updb` and `drush cim`. The previous line stays buildable until its sites have
moved, then is decommissioned.

## Testing with the canary

`scripts/canary.sh` builds a site from the template against a checkout of the
base, installs it from the template's configuration and checks it:

- every base module is enabled, and every base patch is applied;
- no pending database updates, no configuration differences, no status report
  errors and no errors in the log;
- the main pages respond as expected, including the standards (for example,
  password reset is off and OpenID Connect settings are placeholders).

Run it locally before opening a pull request:

```bash
scripts/canary.sh ddev                                   # template from GitHub
scripts/canary.sh ddev --template ../lagoon-boutique-template   # local template
```

A failing run keeps the site and `canary.log` for inspection. CI runs the same
script against the newly built image (`docker` mode); a canary failure stops
the images from being published.

## GitHub repository settings

These are the settings on this repository and the template. Use them as the
defaults for every boutique repository: "Use this template" doesn't copy them,
so apply them to each new site repository by hand.

**General**

- Default branch: the release branch (`prod11` here and in the template; the
  site's release branch in a site repository).
- Pull requests are merged with merge commits. Merged branches are kept
  (automatic deletion is off), because working branches are reused.
- "Always suggest updating pull request branches" is on, so pull requests that
  have fallen behind (such as Dependabot's) show an **Update branch** button.

**Rulesets**

| Ruleset | Applies to | Rules |
|---|---|---|
| Release branches | `prod` and `prod[0-9]*` | No deletion or force push. Changes by pull request with 1 approval; approvals are dismissed by new pushes; conversations must be resolved. Administrators and maintainers can bypass these rules, only through a pull request, to merge their own pull requests. |
| Release branches: CI must pass | `prod` and `prod[0-9]*` | The `build-and-push` check must pass. No bypass, so nobody can merge a failing canary. |
| Release tags: admins and maintainers create | `v*` | Only administrators and maintainers create tags. |
| Release tags: immutable | `v*` | Nobody, administrators included, can delete or move a tag. No bypass. |

Don't use a bare `prod*` pattern: it also matches working branches such as
`prod-ci-dependabot`.

**Actions**

- Only GitHub's actions and actions from verified creators may run.
- Every action is pinned to a full commit SHA, with its version in a comment
  (`uses: actions/checkout@<sha> # v7.0.1`), and the repository requires it
  ("Require actions to be pinned to a full-length commit SHA"). A version tag
  can be moved to different code; a commit can't. Dependabot updates the
  SHAs and comments.
- The default `GITHUB_TOKEN` is read-only, and workflows can't approve pull
  requests.
- No repository secrets are needed: workflows use `GITHUB_TOKEN`. The only
  variable, `CANARY_TEMPLATE_REF`, exists only while a new module is being
  added.

**Security**

Secret scanning with push protection, Dependabot security updates and private
vulnerability reporting are on (see [Security measures](#security-measures)).

## Security measures

This repository and the canary are the model for child projects. Each site
should keep these measures.

**Repository**

- Secret scanning and push protection stop credentials from being pushed.
- Vulnerabilities are reported privately: through GitHub's private
  vulnerability reporting or by email ([SECURITY.md](SECURITY.md)).
- Dependabot opens security updates as they're published, and version updates
  weekly.
- Release branches need a reviewed pull request and a passing canary; release
  tags can't be changed once sites depend on them.
- Actions are pinned to commit SHAs, so a compromised action can't change
  what the build runs, and through it the images every site builds on.
- CI has only the permissions it needs: the build job can read the code and
  publish packages, and pull request builds never log in to the registry or
  publish images.

**Code and configuration**

- No secrets in git: `settings.local.php` is ignored, and environment-specific
  credentials (such as OpenID Connect) come from environment variables, with
  placeholders in `config/sync`.
- `composer.lock` is committed, and sites pin exact image tags.
- Patches are referenced by immutable URLs, and Composer stops if one fails to
  apply.
- Drupal's super user is off (`security.enable_super_user: false`).
- In the template: staff sign in with MyYukon; password reset and
  self-registration are off; translation exports (TMGMT XLIFF files) are
  private files.

**Checked by the canary on every build**

- OpenID Connect credentials and endpoints in `config/sync` are placeholders,
  and clients send no `prompt`.
- Password reset is off (`/user/password` returns 403) and anonymous visitors
  can't reach administration pages (`/admin/config` returns 403).
- The status report has no errors, every base patch is applied, active
  configuration matches `config/sync`, and the log has no errors.

## Troubleshooting

- **Composer: "Could not authenticate"** (GitHub API rate limit). Pass a token
  for that run only:

  ```bash
  ddev exec env COMPOSER_AUTH='{"github-oauth":{"github.com":"'$(gh auth token)'"}}' composer …
  ```

- **New settings missing after an update.** Sites installed from configuration
  (`drush site:install --existing-config`) record every update hook as already
  run. When a module adds settings or modules in an update hook, the template's
  configuration may need the result added by hand. The canary's configuration
  check catches most cases.
- **Apple Silicon:** see the note under [Requirements](#requirements).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Changes go through pull requests into
`prodNN`.

### Roles

| Role | Can |
|---|---|
| Write | Push working branches and open pull requests. Pull requests need an approval from someone else. |
| Maintain | Also merge their own pull requests once the CI check passes (ruleset bypass, through a pull request only), approve others' pull requests (including Dependabot's) and create release tags. |
| Admin | Also change repository settings and rulesets, and set or delete Actions variables such as `CANARY_TEMPLATE_REF`. |

GitHub never lets authors approve their own pull requests; the bypass is how
maintainers and administrators merge theirs. Nobody can bypass the CI check or
change a published tag.

## Security

Don't report security issues publicly. See [SECURITY.md](SECURITY.md).

## License

Unless otherwise noted, the source code of this project is distributed under
the [MIT License](LICENSE).
