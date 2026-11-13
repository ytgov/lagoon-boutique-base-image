# Patches

Patches listed under `extra.patches` in the base's `composer.json` are applied
to the base itself and to every child site whose `composer.json` sets
`extra.enable-patching: true` (composer-patches 1.x). A child resolves a
relative path against its own root, so every patch must be referenced by URL:

- drupal.org issue files (`https://www.drupal.org/files/issues/...`) are
  immutable and can be used directly. Merge request diffs change as the MR is
  updated, so save a snapshot here instead.
- A patch kept in this directory is referenced by its raw GitHub URL, pinned to
  the commit that added it:
  `https://raw.githubusercontent.com/ytgov/lagoon-boutique-base-image/<full commit SHA>/patches/<file>`

To add or change a patch kept here:

1. Commit the file under a new name (never edit a referenced file in place) and
   push the branch.
2. Point `composer.json` at that commit's raw URL, run `composer update --lock`,
   and commit again.
3. Merge the PR with a merge commit so the pinned commit stays on `prod11`.

`composer-exit-on-patch-failure` is on, so a patch that stops applying (for
example after a core update) fails the image build and the canary.
