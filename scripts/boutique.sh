#!/usr/bin/env bash
#
# Maintenance commands for the boutique base image, the template and the
# sites built from them.
#
# Usage:
#   scripts/boutique.sh <command> [options]
#
# Commands:
#   doctor            Check this machine and the workspace: tools, GitHub
#                     sign-in, Docker, DDEV, and each checkout (location,
#                     remote, branch, uncommitted changes, access). Read-only.
#   status            One line per repository: current branch against
#                     GitHub, uncommitted changes, the base release it uses,
#                     open pull requests and the latest CI run on its release
#                     branch. Fetches first; changes nothing else.
#     --no-fetch      Use what was last fetched.
#   sync              Fetch, then fast-forward local branches that are
#                     behind: prodNN and eservices/prepare-prodNN in the base
#                     and the template (the working branch to origin, then to
#                     prodNN), and each site's release branch. Never pushes,
#                     merges, rebases or switches branches.
#     --dry-run       Show what sync would do.
#   help              Show this help.
#
# Workspace:
#   The directory holding boutique-sites.conf, found from $BOUTIQUE_ROOT,
#   else the current directory or one of its parents, else a parent of this
#   script. It holds:
#     lagoon-boutique-base-image/   the base
#     lagoon-boutique-template/     the template
#     boutique-sites.conf           the sites (see scripts/boutique-sites.conf.example)
#     sites/<name>/                 site checkouts
#
# Release line:
#   prodNN is the base repository's default branch on GitHub, unless
#   boutique-sites.conf sets boutique.branch. The working branch is
#   eservices/prepare-prodNN.
#
# Credentials:
#   GitHub API calls use the GitHub CLI's sign-in (`gh auth login`). git over
#   HTTPS needs a credential helper (`gh auth setup-git`); SSH remotes use
#   your keys. Nothing is stored, and git never prompts.
#
# Requires bash 3.2 or later, git and gh. doctor also checks Docker and DDEV.

set -euo pipefail

SCRIPT_BASE=$(cd "$(dirname "$0")/.." && pwd)
OWNER=ytgov
BASE_NAME=lagoon-boutique-base-image
TEMPLATE_NAME=lagoon-boutique-template
BASE_PACKAGE=ytgov/lagoon-boutique-base-image
CONF_NAME=boutique-sites.conf
FLAG_FILE=.github/canary-template-ref

# git must fail rather than wait for a password, a passphrase or an
# unreachable host (internal GitLab sites off the YG network).
export GIT_TERMINAL_PROMPT=0
export GIT_SSH_COMMAND=${GIT_SSH_COMMAND:-ssh -o BatchMode=yes -o ConnectTimeout=10}

usage() {
  awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0"
}

nfail=0
nwarn=0
step() { printf '\n==> %s\n' "$*"; }
pass() { printf '    PASS  %s\n' "$*"; }
info() { printf '    INFO  %s\n' "$*"; }
warn() { printf '    WARN  %s\n' "$*"; nwarn=$((nwarn + 1)); }
fail() { printf '    FAIL  %s\n' "$*"; nfail=$((nfail + 1)); }
die() { echo "boutique: $*" >&2; exit 2; }

# ---------------------------------------------------------------------------
# Workspace and configuration.

find_up() {
  local dir=$1
  while :; do
    if [ -f "$dir/$CONF_NAME" ]; then
      echo "$dir"
      return 0
    fi
    [ "$dir" = / ] && return 1
    dir=$(dirname "$dir")
  done
}

ROOT=
CONF=
find_workspace() {
  if [ -n "${BOUTIQUE_ROOT:-}" ]; then
    ROOT=$(cd "$BOUTIQUE_ROOT" && pwd) || die "BOUTIQUE_ROOT $BOUTIQUE_ROOT doesn't exist"
  elif ROOT=$(find_up "$PWD") || ROOT=$(find_up "$SCRIPT_BASE"); then
    :
  elif [ "$(basename "$SCRIPT_BASE")" = "$BASE_NAME" ] && [ -e "$SCRIPT_BASE/.git" ]; then
    # A base checkout without a sites file yet: the workspace is its parent.
    ROOT=$(dirname "$SCRIPT_BASE")
  else
    die "no $CONF_NAME found; set BOUTIQUE_ROOT or run from the workspace"
  fi
  if [ -f "$ROOT/$CONF_NAME" ]; then
    CONF=$ROOT/$CONF_NAME
  fi
}

conf_get() {
  [ -n "$CONF" ] || return 0
  git config -f "$CONF" --get "$1" 2>/dev/null || true
}

site_names() {
  [ -n "$CONF" ] || return 0
  git config -f "$CONF" --name-only --get-regexp '^site\.' 2>/dev/null |
    sed -E 's/^site\.(.*)\.[^.]+$/\1/' | awk '!seen[$0]++' || true
}

# The repositories as tab-separated lines: kind, name, path, remote, branch.
# For the base and the template, branch is the release branch prodNN.
REPOS=
load_repos() {
  local sites_dir name remote branch path
  REPOS="base	$BASE_NAME	$ROOT/$BASE_NAME	https://github.com/$OWNER/$BASE_NAME.git	$RELEASE_BRANCH
template	$TEMPLATE_NAME	$ROOT/$TEMPLATE_NAME	https://github.com/$OWNER/$TEMPLATE_NAME.git	$RELEASE_BRANCH"
  sites_dir=$(conf_get boutique.sites)
  sites_dir=${sites_dir:-sites}
  while IFS= read -r name; do
    [ -n "$name" ] || continue
    remote=$(conf_get "site.$name.remote")
    branch=$(conf_get "site.$name.branch")
    path=$(conf_get "site.$name.path")
    path=$ROOT/${path:-$sites_dir/$name}
    REPOS="$REPOS
site	$name	$path	${remote:--}	${branch:--}"
  done <<EOF
$(site_names)
EOF
}

# prodNN: the conf setting, else the base's default branch on GitHub, else the
# highest prodNN the local base checkout knows about.
RELEASE_BRANCH=
RELEASE_SOURCE=
find_release_branch() {
  RELEASE_BRANCH=$(conf_get boutique.branch)
  RELEASE_SOURCE="boutique.branch in $CONF_NAME"
  if [ -z "$RELEASE_BRANCH" ]; then
    RELEASE_BRANCH=$(gh repo view "$OWNER/$BASE_NAME" --json defaultBranchRef \
      --jq .defaultBranchRef.name 2>/dev/null || true)
    RELEASE_SOURCE="the base's default branch on GitHub"
  fi
  if [ -z "$RELEASE_BRANCH" ] && [ -d "$ROOT/$BASE_NAME/.git" ]; then
    RELEASE_BRANCH=$(git -C "$ROOT/$BASE_NAME" for-each-ref --format='%(refname:strip=3)' \
      refs/remotes/origin/ | grep -E '^prod[0-9]+$' | sed 's/^prod//' | sort -n |
      tail -n 1 | sed 's/^/prod/' || true)
    RELEASE_SOURCE="the highest prodNN in the local base checkout (GitHub unreachable)"
  fi
  [ -n "$RELEASE_BRANCH" ] || die "can't work out the release branch; set boutique.branch in $CONF_NAME"
  [[ $RELEASE_BRANCH =~ ^prod[0-9]+$ ]] || die "release branch $RELEASE_BRANCH isn't prodNN"
  WORK_BRANCH=eservices/prepare-$RELEASE_BRANCH
  MAJOR=${RELEASE_BRANCH#prod}
}

# ---------------------------------------------------------------------------
# Git and GitHub helpers.

# owner/name for a GitHub URL, nothing for anything else.
github_slug() {
  local slug
  case $1 in
    https://github.com/*) slug=${1#https://github.com/} ;;
    git@github.com:*) slug=${1#git@github.com:} ;;
    ssh://git@github.com/*) slug=${1#ssh://git@github.com/} ;;
    *) return 0 ;;
  esac
  slug=${slug%/}
  echo "${slug%.git}"
}

# Comparable form of a remote: owner/name on GitHub, else the URL without .git.
remote_key() {
  local slug
  slug=$(github_slug "$1")
  if [ -n "$slug" ]; then echo "github:$slug"; else echo "${1%.git}"; fi
}

is_checkout() { [ -d "$1" ] && git -C "$1" rev-parse --is-inside-work-tree >/dev/null 2>&1; }
current_branch() { git -C "$1" symbolic-ref -q --short HEAD || echo "(detached)"; }
has_ref() { git -C "$1" rev-parse -q --verify "$2^{commit}" >/dev/null; }

fetch() {
  local dir=$1
  shift
  git -C "$dir" fetch -q "$@" origin 2>/dev/null
}

# Base release in a composer.lock, such as v11.3.0.
locked_base() {
  [ -f "$1/composer.lock" ] || return 0
  awk -v pkg="\"$BASE_PACKAGE\"" '
    $1 == "\"name\":" && $2 == pkg "," { found = 1; next }
    found && $1 == "\"version\":" { gsub(/[",]/, "", $2); print $2; exit }
  ' "$1/composer.lock"
}

# The base image tags used in lagoon/*.dockerfile, one per line.
dockerfile_tags() {
  cat "$1"/lagoon/*.dockerfile 2>/dev/null |
    grep -o "$BASE_NAME-[a-z]*:[^ ]*" | sed 's/.*://' | sort -u || true
}

# The latest vMAJOR.x.y tag in the base checkout.
latest_release() {
  [ -d "$ROOT/$BASE_NAME/.git" ] || return 0
  git -C "$ROOT/$BASE_NAME" tag -l "v$MAJOR.*" | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' |
    sed 's/^v//' | sort -t . -k 1,1n -k 2,2n -k 3,3n | tail -n 1 | sed 's/^/v/' || true
}

# ---------------------------------------------------------------------------
# doctor

check_tools() {
  step "Tools"
  pass "bash $BASH_VERSION"
  if command -v git >/dev/null; then
    pass "$(git --version)"
  else
    fail "git is not installed"
  fi
  if ! command -v gh >/dev/null; then
    fail "GitHub CLI (gh) is not installed: https://cli.github.com"
  elif gh auth status --hostname github.com >/dev/null 2>&1; then
    pass "gh signed in to github.com as $(gh api user --jq .login 2>/dev/null || echo '?')"
  else
    fail "gh isn't signed in to github.com: run gh auth login"
  fi
  if [ -n "$(git config --get-urlmatch credential.helper https://github.com 2>/dev/null || true)" ]; then
    pass "git has a credential helper for https://github.com"
  else
    warn "git has no credential helper for https://github.com: run gh auth setup-git (or use SSH remotes)"
  fi
  if ! command -v docker >/dev/null; then
    fail "Docker is not installed"
  elif docker info >/dev/null 2>&1; then
    pass "Docker is running"
  else
    fail "Docker isn't running: start it"
  fi
  if command -v ddev >/dev/null; then
    pass "$(ddev --version 2>/dev/null | head -n 1)"
  else
    fail "DDEV is not installed: https://ddev.com"
  fi
}

check_platform() {
  step "Platform"
  case $(uname -s) in
    Darwin) pass "macOS $(sw_vers -productVersion 2>/dev/null || true) ($(uname -m))" ;;
    Linux)
      if grep -qi microsoft /proc/version 2>/dev/null; then
        pass "WSL ($(uname -m))"
        case $ROOT in
          /mnt/*) warn "the workspace is on the Windows filesystem ($ROOT): move it into the Linux filesystem for DDEV" ;;
        esac
      else
        pass "Linux ($(uname -m))"
      fi
      ;;
    *) warn "untested platform: $(uname -s)" ;;
  esac
}

check_workspace() {
  local name
  step "Workspace: $ROOT"
  if [ -z "$CONF" ]; then
    warn "no $CONF_NAME: copy $BASE_NAME/scripts/$CONF_NAME.example to $ROOT/$CONF_NAME"
  elif ! git config -f "$CONF" --list >/dev/null 2>&1; then
    fail "$CONF_NAME can't be read: $(git config -f "$CONF" --list 2>&1 >/dev/null | head -n 1)"
  else
    pass "$CONF_NAME lists $(site_names | grep -c . || true) site(s)"
    while IFS= read -r name; do
      [ -n "$name" ] || continue
      [ -n "$(conf_get "site.$name.remote")" ] || fail "site $name has no remote in $CONF_NAME"
      [ -n "$(conf_get "site.$name.branch")" ] || fail "site $name has no branch in $CONF_NAME"
    done <<EOF
$(site_names)
EOF
  fi
  info "release branch $RELEASE_BRANCH (from $RELEASE_SOURCE), working branch $WORK_BRANCH"
}

check_repo() {
  local kind=$1 name=$2 dir=$3 remote=$4 branch=$5 origin current slug role dirty
  step "$name ($kind)"
  if [ "$remote" = - ] || [ "$branch" = - ]; then
    return 0 # Reported under Workspace.
  fi
  if [ ! -e "$dir" ]; then
    warn "missing: git clone -b $branch $remote ${dir#"$ROOT"/}"
    return 0
  fi
  if ! is_checkout "$dir"; then
    fail "${dir#"$ROOT"/} isn't a git checkout"
    return 0
  fi
  pass "checkout at ${dir#"$ROOT"/}"

  origin=$(git -C "$dir" remote get-url origin 2>/dev/null || true)
  if [ -z "$origin" ]; then
    fail "no origin remote"
  elif [ "$(remote_key "$origin")" != "$(remote_key "$remote")" ]; then
    fail "origin is $origin, expected $remote"
  elif git -C "$dir" ls-remote -q --exit-code origin HEAD >/dev/null 2>&1; then
    pass "origin $origin is reachable"
  else
    warn "can't reach origin $origin (credentials, VPN or YG network?)"
  fi

  current=$(current_branch "$dir")
  if [ "$kind" = site ]; then
    if [ "$current" = "$branch" ]; then
      pass "on $branch"
    else
      warn "on $current, not its release branch $branch"
    fi
  elif [ "$current" = "$WORK_BRANCH" ] || [ "$current" = "$branch" ]; then
    pass "on $current"
  else
    warn "on $current, not $WORK_BRANCH or $branch"
  fi
  has_ref "$dir" "origin/$branch" || warn "origin/$branch not fetched or doesn't exist"

  dirty=$(git -C "$dir" status --porcelain 2>/dev/null | grep -c . || true)
  if [ "$dirty" = 0 ]; then
    pass "no uncommitted changes"
  else
    warn "$dirty uncommitted change(s)"
  fi

  if [ -f "$dir/.ddev/config.yaml" ]; then
    pass "DDEV project $(awk '$1 == "name:" { print $2; exit }' "$dir/.ddev/config.yaml")"
  else
    warn "no DDEV project (.ddev/config.yaml)"
  fi

  slug=$(github_slug "$remote")
  if [ -n "$slug" ]; then
    role=$(gh api "repos/$slug" --jq '.permissions // empty |
      if .admin then "admin" elif .maintain then "maintain" elif .push then "write"
      elif .triage then "triage" elif .pull then "read" else empty end' 2>/dev/null || true)
    if [ -n "$role" ]; then
      info "your GitHub role on $slug: $role"
    else
      warn "can't read $slug on GitHub with your gh sign-in"
    fi
  fi
}

cmd_doctor() {
  [ $# -eq 0 ] || die "doctor takes no options"
  check_tools
  check_platform
  check_workspace
  while IFS='	' read -r kind name dir remote branch <&3; do
    check_repo "$kind" "$name" "$dir" "$remote" "$branch"
  done 3<<EOF
$REPOS
EOF
  echo
  if [ "$nfail" -gt 0 ]; then
    echo "$nfail problem(s) and $nwarn warning(s)"
    exit 1
  fi
  echo "No problems ($nwarn warning(s))"
}

# ---------------------------------------------------------------------------
# status

cmd_status() {
  local do_fetch=1 notes='' latest kind name dir remote branch
  local current upstream counts ahead behind tree locked tags base prs ci slug unreleased workflow
  while [ $# -gt 0 ]; do
    case $1 in
      --no-fetch) do_fetch=0; shift ;;
      *) die "unknown status option: $1" ;;
    esac
  done

  if [ "$do_fetch" = 1 ]; then
    echo "Fetching..."
    while IFS='	' read -r kind name dir remote branch <&3; do
      is_checkout "$dir" || continue
      if [ "$kind" = base ]; then
        fetch "$dir" --tags || notes="$notes
$name: fetch failed, showing what was last fetched"
      else
        fetch "$dir" || notes="$notes
$name: fetch failed, showing what was last fetched"
      fi
    done 3<<EOF
$REPOS
EOF
  fi
  latest=$(latest_release)

  echo
  echo "Release branch $RELEASE_BRANCH, latest base release ${latest:-none}"
  echo
  printf '%-30s %-26s %-9s %-6s %-10s %-4s %s\n' REPOSITORY BRANCH ORIGIN TREE BASE PRS "CI ON RELEASE BRANCH"
  while IFS='	' read -r kind name dir remote branch <&3; do
    if [ "$remote" = - ] || [ "$branch" = - ] || ! is_checkout "$dir"; then
      printf '%-30s %s\n' "$name" "(not checked out; run scripts/boutique.sh doctor)"
      continue
    fi
    current=$(current_branch "$dir")

    # Commits ahead (+) and behind (-) the branch's upstream.
    upstream=-
    if counts=$(git -C "$dir" rev-list --left-right --count 'HEAD...@{upstream}' 2>/dev/null); then
      read -r ahead behind <<<"$counts"
      if [ "$ahead" = 0 ] && [ "$behind" = 0 ]; then
        upstream="="
      else
        upstream="+$ahead/-$behind"
      fi
    fi

    if [ -z "$(git -C "$dir" status --porcelain 2>/dev/null)" ]; then tree=clean; else tree=dirty; fi

    # The base release: the latest tag for the base, else the locked release,
    # marked * if it isn't the latest and ! if the Dockerfiles disagree.
    if [ "$kind" = base ]; then
      base=${latest:--}
      if [ -n "$latest" ] && has_ref "$dir" "origin/$branch"; then
        unreleased=$(git -C "$dir" rev-list --first-parent --count "$latest..origin/$branch" 2>/dev/null || echo 0)
        [ "$unreleased" = 0 ] || notes="$notes
$name: origin/$branch has $unreleased commit(s) since $latest"
      fi
      if git -C "$dir" cat-file -e "origin/$branch:$FLAG_FILE" 2>/dev/null; then
        notes="$notes
$name: $FLAG_FILE is set on origin/$branch ($(git -C "$dir" show "origin/$branch:$FLAG_FILE" | grep -v -e '^[[:space:]]*#' -e '^[[:space:]]*$' | head -n 1 || true))"
      fi
      if [ -f "$dir/$FLAG_FILE" ] && [ "$current" != "$branch" ]; then
        notes="$notes
$name: $FLAG_FILE is set on $current"
      fi
    else
      locked=$(locked_base "$dir")
      base=${locked:--}
      if [ -n "$locked" ] && [ -n "$latest" ] && [ "$locked" != "$latest" ]; then
        base="$base*"
        notes="$notes
$name: uses base $locked; $latest is released"
      fi
      tags=$(dockerfile_tags "$dir")
      if [ -n "$tags" ] && [ "$tags" != "${locked#v}" ]; then
        base="$base!"
        notes="$notes
$name: lagoon/*.dockerfile use $(echo "$tags" | tr '\n' ' ')but composer.lock has $locked"
      fi
    fi

    prs=-
    ci=-
    slug=$(github_slug "$remote")
    if [ -n "$slug" ]; then
      prs=$(gh pr list -R "$slug" --state open --json number --jq length 2>/dev/null || echo '?')
      # The base's image build, else the canary every site copies from the
      # template.
      if [ "$kind" = base ]; then workflow=docker-build.yml; else workflow=canary.yml; fi
      ci=$(gh run list -R "$slug" -w "$workflow" -b "$branch" -L 1 --json status,conclusion,event \
        --jq '.[0] // empty | (if .status == "completed" then .conclusion else .status end) + " (" + .event + ")"' \
        2>/dev/null || echo '?')
      ci=${ci:--}
    fi

    printf '%-30s %-26s %-9s %-6s %-10s %-4s %s\n' "$name" "$current" "$upstream" "$tree" "$base" "$prs" "$ci"
  done 3<<EOF
$REPOS
EOF

  if [ -n "$notes" ]; then
    echo
    echo "Notes:"
    printf '%s\n' "$notes" | sed '/^$/d; s/^/  - /'
  fi
  echo
  echo "ORIGIN: commits ahead (+) and behind (-) the branch on origin. BASE: * not the latest release, ! Dockerfiles disagree with composer.lock."
}

# ---------------------------------------------------------------------------
# sync

DRY_RUN=0
pushes=

# Fast-forwards local branch $2 in checkout $1 to $3 if it's behind.
fast_forward() {
  local dir=$1 branch=$2 target=$3 old new count
  if ! has_ref "$dir" "refs/heads/$branch"; then
    info "$branch: no local branch"
    return 0
  fi
  if ! has_ref "$dir" "$target"; then
    warn "$branch: $target doesn't exist"
    return 0
  fi
  old=$(git -C "$dir" rev-parse "refs/heads/$branch")
  new=$(git -C "$dir" rev-parse "$target^{commit}")
  if [ "$old" = "$new" ]; then
    pass "$branch is up to date with $target"
    return 0
  fi
  if git -C "$dir" merge-base --is-ancestor "$new" "$old"; then
    info "$branch has $(git -C "$dir" rev-list --count "$new..$old") commit(s) not in $target"
    return 0
  fi
  if ! git -C "$dir" merge-base --is-ancestor "$old" "$new"; then
    warn "$branch has diverged from $target; left as it is"
    return 0
  fi
  count=$(git -C "$dir" rev-list --count "$old..$new")
  if [ "$DRY_RUN" = 1 ]; then
    info "would fast-forward $branch to $target ($count commit(s))"
    return 0
  fi
  if [ "$(current_branch "$dir")" = "$branch" ]; then
    if ! git -C "$dir" merge -q --ff-only "$new" 2>/dev/null; then
      warn "$branch: git refused to fast-forward to $target (uncommitted changes in the way?)"
      return 0
    fi
  else
    git -C "$dir" update-ref -m "boutique sync: fast-forward to $target" "refs/heads/$branch" "$new" "$old"
  fi
  pass "fast-forwarded $branch to $target ($count commit(s))"
}

cmd_sync() {
  local kind name dir remote branch
  while [ $# -gt 0 ]; do
    case $1 in
      --dry-run) DRY_RUN=1; shift ;;
      *) die "unknown sync option: $1" ;;
    esac
  done

  while IFS='	' read -r kind name dir remote branch <&3; do
    step "$name"
    if [ "$remote" = - ] || [ "$branch" = - ] || ! is_checkout "$dir"; then
      warn "not checked out or not configured; run scripts/boutique.sh doctor"
      continue
    fi
    if [ "$kind" = base ]; then
      fetch "$dir" --tags || { warn "fetch failed; skipped"; continue; }
    else
      fetch "$dir" || { warn "fetch failed; skipped"; continue; }
    fi
    fast_forward "$dir" "$branch" "origin/$branch"
    if [ "$kind" != site ]; then
      if has_ref "$dir" "origin/$WORK_BRANCH"; then
        fast_forward "$dir" "$WORK_BRANCH" "origin/$WORK_BRANCH"
      fi
      fast_forward "$dir" "$WORK_BRANCH" "origin/$branch"
      # Suggest a push only when it would be a fast-forward on GitHub.
      if has_ref "$dir" "refs/heads/$WORK_BRANCH" && has_ref "$dir" "origin/$WORK_BRANCH" &&
        git -C "$dir" merge-base --is-ancestor "origin/$WORK_BRANCH" "$WORK_BRANCH" &&
        [ "$(git -C "$dir" rev-list --count "origin/$WORK_BRANCH..$WORK_BRANCH")" != 0 ]; then
        pushes="$pushes
  git -C ${dir#"$ROOT"/} push origin $WORK_BRANCH"
      fi
    fi
  done 3<<EOF
$REPOS
EOF

  echo
  if [ -n "$pushes" ]; then
    echo "Local working branches are ahead of GitHub. sync doesn't push; to push them:$pushes"
  fi
  [ "$DRY_RUN" = 1 ] && echo "Dry run: nothing was changed."
  return 0
}

# ---------------------------------------------------------------------------

command=${1:-help}
[ $# -gt 0 ] && shift
case $command in
  help|-h|--help) usage; exit 0 ;;
  doctor|status|sync) ;;
  *) usage >&2; exit 2 ;;
esac

find_workspace
find_release_branch
load_repos
"cmd_$command" "$@"
