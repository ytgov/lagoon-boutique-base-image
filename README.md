# lagoon-boutique-base-image

The shared Drupal codebase and Docker images for Government of Yukon "boutique"
sites: special-purpose Drupal sites run by eServices. It follows Lagoon's
[base image](https://docs.lagoon.sh/concepts-advanced/base-images/)
technique. Sites build on a tested release of the base instead of each
maintaining its own core, standard modules and images.

© 2026 Government of Yukon / Gouvernement du Yukon

**Contact:** Highways and Public Works, Information and Communications Technology,
eServices for Citizens [eservices@yukon.ca](mailto:eservices@yukon.ca)

## Contents

- [Overview](#overview)
  - [When to use it](#when-to-use-it)
  - [How it's structured](#how-its-structured)
- [Requirements](#requirements)
- [Using the base image](#using-the-base-image)
  - [Sites created from the boutique template](#sites-created-from-the-boutique-template)
  - [Other Drupal sites](#other-drupal-sites)
  - [Keeping a site up to date](#keeping-a-site-up-to-date)
- [Maintaining the base image](#maintaining-the-base-image)
  - [Maintenance commands](#maintenance-commands)
  - [Branches, versions and releases](#branches-versions-and-releases)
  - [Updating core or modules within a major version](#updating-core-or-modules-within-a-major-version)
  - [Adding or removing a standard module](#adding-or-removing-a-standard-module)
  - [Adding or changing a patch](#adding-or-changing-a-patch)
  - [Updating PHP, the Lagoon images or MariaDB](#updating-php-the-lagoon-images-or-mariadb)
  - [Upgrading to a new major Drupal version](#upgrading-to-a-new-major-drupal-version)
- [Testing with the canary](#testing-with-the-canary)
- [Script reference](#script-reference)
  - [boutique.sh](#boutiquesh)
  - [canary.sh](#canarysh)
- [GitHub repository settings](#github-repository-settings)
- [Security measures](#security-measures)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
  - [Roles](#roles)
- [Security](#security)
- [License](#license)
- [TODO](#todo)

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

- `scripts/canary.sh`: the canary test (see [Testing with the canary](#testing-with-the-canary)
  and [canary.sh](#canarysh)).
- `scripts/boutique.sh` and `scripts/boutique-sites.conf.example`: maintenance commands
  (see [Maintenance commands](#maintenance-commands)).
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
the two disagree. `scripts/boutique.sh bump` does both, and checks the local
site (see [Maintenance commands](#maintenance-commands)).

## Maintaining the base image

### Maintenance commands

`scripts/boutique.sh` works across the base, the template and the sites built
from them. It runs on Linux, macOS and WSL with bash 3.2 or later, git and the
GitHub CLI. GitHub access comes from your `gh` sign-in (`gh auth login`, and
`gh auth setup-git` for HTTPS remotes); the script stores no credentials, and
git fails rather than prompting.

| Command | What it does |
|---|---|
| `boutique.sh doctor` | Checks the tools (git, `gh` and its sign-in, Docker, DDEV), the platform, the workspace, and each checkout: location, remote, branch, uncommitted changes, DDEV project and your GitHub role. Read-only. |
| `boutique.sh status` | One line per repository: current branch against GitHub, uncommitted changes, the base release it's locked to (flagged when a newer one exists or the Dockerfiles disagree), open pull requests and the latest CI run on its release branch. Notes unreleased base commits and a set `.github/canary-template-ref`. Fetches first; changes nothing else. |
| `boutique.sh sync` | Fetches, then fast-forwards local branches that are behind: `prodNN` and `eservices/prepare-prodNN` in the base and the template (the working branch to its GitHub copy, then to `prodNN`), and each site's release branch. It never pushes, merges, rebases or switches branches, and leaves diverged branches alone. |
| `boutique.sh canary [REPO...]` | Runs this checkout's canary (`scripts/canary.sh` in DDEV) on the working tree of the template, a site, or `all` (the default), uncommitted changes included, and lists which passed. A site's own CI tests the release in its `composer.lock` instead. |
| `boutique.sh bump [REPO...]` | Moves the template or sites (`all` by default) to a base release (the latest by default): `composer.lock` and the `lagoon/*.dockerfile` tags, then `updb`, `cr` and `cst` in the local DDEV site. It needs the template on its working branch and each site on its release branch, with no uncommitted changes to `composer.*` or `lagoon/`, and the release's images published. It never downgrades or commits, and prints the commit commands. It can run the canary after each bump. |

Each command's options are in [boutique.sh](#boutiquesh) under
[Script reference](#script-reference).

**Workspace.** The commands work on a directory laid out like this:

```text
<workspace>/
  lagoon-boutique-base-image/
  lagoon-boutique-template/
  boutique-sites.conf          # copied from scripts/boutique-sites.conf.example
  sites/<name>/                # one checkout per site
```

The workspace is found from `$BOUTIQUE_ROOT`, else the current directory or
one of its parents (wherever `boutique-sites.conf` is), else the parent of the
base checkout running the script. `boutique-sites.conf` lists the sites in
git-config format; [scripts/boutique-sites.conf.example](scripts/boutique-sites.conf.example)
describes the keys. Keep it out of every repository, since it may hold
internal GitLab URLs.

**Running it.** It works from any directory, and always on the whole
workspace. An alias in your shell profile saves typing the path:

```bash
alias boutique="$HOME/src/<workspace>/lagoon-boutique-base-image/scripts/boutique.sh"
boutique status
```

Run the copy in the workspace's base checkout. Sites also have one in
`vendor/ytgov/lagoon-boutique-base-image/scripts/`, but only at the release
they're locked to, which may be older.

**Release line.** `prodNN` is the base repository's default branch on GitHub,
unless `boutique-sites.conf` sets `boutique.branch`. The working branch is
`eservices/prepare-prodNN`.

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
   export it in the template and test with the local template checkout (see
   [canary.sh](#canarysh)).
4. Release as a patch version.

Dependabot proposes these updates weekly; review them the same way.

### Adding or removing a standard module

The base canary requires every base module to be enabled by the template, so
the base and the template change together:

1. Base: `ddev composer require drupal/<module>`.
2. Template: enable and configure the module on its working branch and push
   it (no pull request yet).
3. Point the base canary at that branch: in the base pull request, add a file
   `.github/canary-template-ref` holding the branch name
   (`echo eservices/prepare-prod11 > .github/canary-template-ref`). CI then
   tests the pull request, the merge and the release tag against that template
   branch, and warns that the file is there.
4. Open the base pull request, merge it and tag a minor release.
5. Update the template to the release, open its pull request and merge it.
6. Remove `.github/canary-template-ref` in the next base pull request (there's
   no need to release for it), so the canary goes back to template `prodNN`.
   Until then, every base build tests against the working branch.

Anyone who can merge into `prodNN` (maintainers and administrators) can add
and remove the file; see [Testing with the canary](#testing-with-the-canary).

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
4. Create the template's `prodNN` branch with matching changes, and name a
   template branch in `.github/canary-template-ref` until it exists on GitHub.
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
  OpenID Connect settings are placeholders, and there's no password login once
  Yukon SSO is configured: login display `force_replace`, password reset and
  the JSON login off).

Run it locally before opening a pull request:

```bash
scripts/canary.sh ddev                                   # template from GitHub
scripts/canary.sh ddev --template ../lagoon-boutique-template   # local template
scripts/boutique.sh canary                               # the template and every site in the workspace
```

A failing run keeps the site and `canary.log` for inspection. CI runs the same
script against the newly built image (`docker` mode); a canary failure stops
the images from being published.

CI chooses the template branch to test against: a pull request uses the
template branch with the same name as its target branch, a push to `prodNN`
uses `prodNN`, and a `vNN.x.y` tag uses `prodNN`. The file
`.github/canary-template-ref` overrides all three while a base change needs
template work that isn't merged yet (see
[Adding or removing a standard module](#adding-or-removing-a-standard-module)).
It holds one branch name; blank lines and lines starting with `#` are ignored.
It's a file rather than a repository variable so that it goes through review
with the change that needs it, and so maintainers can set it, not only
administrators. Locally, choose the template branch with the canary's options
(see [canary.sh](#canarysh)).

## Script reference

The help text of the two scripts, as `scripts/boutique.sh help` and
`scripts/canary.sh --help` print it.

### boutique.sh

Maintenance commands for the boutique base image, the template and the sites
built from them. See [Maintenance commands](#maintenance-commands) for the
workspace layout, how to run it and the release line.

```bash
scripts/boutique.sh <command> [options]
```

| Command | What it does |
|---|---|
| `doctor` | Check this machine and the workspace: tools, GitHub sign-in, Docker, DDEV, and each checkout (location, remote, branch, uncommitted changes, access). Read-only. |
| `status` | One line per repository: current branch against GitHub, uncommitted changes, the base release it uses, open pull requests and the latest CI run on its release branch. Fetches first; changes nothing else. |
| `sync` | Fetch, then fast-forward local branches that are behind: `prodNN` and `eservices/prepare-prodNN` in the base and the template (the working branch to origin, then to `prodNN`), and each site's release branch. Never pushes, merges, rebases or switches branches. |
| `canary [REPO...]` | Run the base checkout's canary (`scripts/canary.sh` in DDEV) on the working tree of each REPO: `template`, a site name, or `all` (the default: the template and every site). Uncommitted changes are included. Each run replaces the previous `ytg-canary` DDEV project. |
| `bump [REPO...]` | Move each REPO (as for `canary`) to a base release: `composer.lock` and `lagoon/*.dockerfile`, then `updb`, `cr` and `cst` in its DDEV site. The template must be on its working branch, a site on its release branch, with no uncommitted changes to `composer.*` or `lagoon/`. Never commits. |
| `help` | Show the help. |

| Command | Option | What it does |
|---|---|---|
| `status` | `--no-fetch` | Use what was last fetched. |
| `sync` | `-D`, `--dry-run` | Show what `sync` would do. |
| `canary` | `--apache` | Use apache-fpm (LAMP parity). |
| `canary` | `--keep` | Keep the site after a passing run (one REPO only). |
| `bump` | `--to VERSION` | The release, such as `v11.3.1` (default: the latest). |
| `bump` | `--canary` | Run the canary on each repository after bumping it. |
| `bump` | `-D`, `--dry-run` | Show what `bump` would do. |

**Credentials.** GitHub API calls use the GitHub CLI's sign-in
(`gh auth login`). git over HTTPS needs a credential helper
(`gh auth setup-git`); SSH remotes use your keys. Nothing is stored, and git
never prompts.

**Requires** bash 3.2 or later, git and `gh`. `doctor` also checks Docker and
DDEV; `canary` and `bump` need them.

### canary.sh

Canary test for the base image. Builds a throwaway Drupal site from
lagoon-boutique-template, points its `ytgov/lagoon-boutique-base-image`
dependency at this checkout of the base (the "candidate"), installs it from
the template's config and checks that it is healthy. A failing canary means
the candidate must not be released. See
[Testing with the canary](#testing-with-the-canary) for what it checks.

```bash
scripts/canary.sh ddev [options]
scripts/canary.sh docker --image IMAGE [options]
```

| Mode | What it does |
|---|---|
| `ddev` | Run the site in a DDEV project named `ytg-canary`. Use this on a workstation. `--apache` switches DDEV to apache-fpm (LAMP parity). |
| `docker` | Run composer and drush inside IMAGE, normally the freshly built base CLI image (Lagoon parity), against an existing MariaDB, and serve the HTTP checks with PHP's built-in web server. Used by CI. |

| Option | What it does |
|---|---|
| `--template SRC` | Template git URL or local directory. A local directory is copied as-is, including uncommitted changes. Default: `https://github.com/ytgov/lagoon-boutique-template.git`. |
| `--template-ref REF` | Branch or tag to clone when SRC is a URL. Default: `prod11`. |
| `--workdir DIR` | Empty directory to build the site in. Default: a new temporary directory. |
| `--apache` | `ddev` mode only: use apache-fpm instead of nginx-fpm. |
| `--image IMAGE` | `docker` mode only: image to run composer and drush in. |
| `--keep` | Keep the site (and DDEV project) after a passing run. A failing run is always kept for inspection. |

`docker` mode reads these environment variables:

| Variable | Default |
|---|---|
| `CANARY_DB_HOST` | `127.0.0.1` |
| `CANARY_DB_PORT` | `3306` |
| `CANARY_DB_NAME`, `CANARY_DB_USER`, `CANARY_DB_PASSWORD` | `drupal` |
| `CANARY_DOCKER_NETWORK` | `host` |

On macOS use a user-defined Docker network instead of host networking, with
the database container on it.

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
  ("Require actions to be pinned to a full-length commit SHA"; the template
  requires it, this repository not yet: see [TODO](#todo)). A version tag
  can be moved to different code; a commit can't. Dependabot updates the
  SHAs and comments.
- The default `GITHUB_TOKEN` is read-only, and workflows can't approve pull
  requests.
- No repository secrets or variables are needed: workflows use
  `GITHUB_TOKEN`, and the canary's template branch override is a committed
  file (`.github/canary-template-ref`).

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
| Maintain | Also merge their own pull requests once the CI check passes (ruleset bypass, through a pull request only), approve others' pull requests (including Dependabot's) and create release tags. That includes adding and removing `.github/canary-template-ref`. |
| Admin | Also change repository settings and rulesets. |

GitHub never lets authors approve their own pull requests; the bypass is how
maintainers and administrators merge theirs. Nobody can bypass the CI check or
change a published tag.

## Security

Don't report security issues publicly. See [SECURITY.md](SECURITY.md).

## License

Unless otherwise noted, the source code of this project is distributed under
the [MIT License](LICENSE).

## TODO

- **Require pinned actions here.** Turn on "Require actions to be pinned to a
  full-length commit SHA" (Settings → Actions → General) once work on older
  branches has finished. Those branches still use action tags, so their
  builds would fail until they're updated from `prodNN`. The release and
  working branches are pinned, and the template already requires it.
- **Finish `scripts/boutique.sh`:** `release` and `pr`, then `flag` (set and
  clear `.github/canary-template-ref`); and run shellcheck on the scripts in
  CI (`canary.sh` has a few info-level notes to clear first).
- **Retire the Drupal 10 line.** Once its sites have moved to `prod10` (`:10.x`)
  or `prod11`, stop publishing `:latest` from `prod` and freeze the branch.
- **Share more than modules.** Decide how shared configuration reaches sites
  (a recipe, an install profile or config_split), and review the modules many
  sites add themselves as candidates for the base.
- **Settings from the environment.** Load `.env.defaults` and `.env` in
  `settings.php`, using Lagoon's variable names, so servers outside Lagoon
  are configured the same way; then remove the committed empty `.env`.
- **Deployment documentation.** A versioned `docs/DEPLOYMENT.md` for the steps
  every site deploys with.
- **More canary coverage.** Upgrade from the previous release (restore its
  database, then the deploy steps) as well as a fresh install, and a run of
  the real images with docker-compose.
- **Multi-architecture images.** The images are `linux/amd64` only.
- **composer-patches 2.x.** Retry the upgrade; it broke the canary last time.
