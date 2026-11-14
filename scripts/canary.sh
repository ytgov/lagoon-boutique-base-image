#!/usr/bin/env bash
#
# Canary test for the base image.
#
# Builds a throwaway Drupal site from lagoon-boutique-template, points its
# ytgov/lagoon-boutique-base-image dependency at this checkout of the base
# (the "candidate"), installs it from the template's config and checks that
# it is healthy. A failing canary means the candidate must not be released.
#
# Usage:
#   scripts/canary.sh ddev [options]
#   scripts/canary.sh docker --image IMAGE [options]
#
# Modes:
#   ddev    Run the site in a DDEV project named ytg-canary. Use this on a
#           workstation. --apache switches DDEV to apache-fpm (LAMP parity).
#   docker  Run composer and drush inside IMAGE, normally the freshly built
#           base CLI image (Lagoon parity), against an existing MariaDB, and
#           serve the HTTP checks with PHP's built-in web server. Used by CI.
#
# Options:
#   --template SRC      Template git URL or local directory. A local directory
#                       is copied as-is, including uncommitted changes.
#                       Default: https://github.com/ytgov/lagoon-boutique-template.git
#   --template-ref REF  Branch or tag to clone when SRC is a URL. Default: prod11.
#   --workdir DIR       Empty directory to build the site in. Default: a new
#                       temporary directory.
#   --apache            ddev mode only: use apache-fpm instead of nginx-fpm.
#   --image IMAGE       docker mode only: image to run composer and drush in.
#   --keep              Keep the site (and DDEV project) after a passing run.
#                       A failing run is always kept for inspection.
#
# docker mode reads these environment variables:
#   CANARY_DB_HOST (127.0.0.1), CANARY_DB_PORT (3306), CANARY_DB_NAME,
#   CANARY_DB_USER and CANARY_DB_PASSWORD (all "drupal"), and
#   CANARY_DOCKER_NETWORK (host). On macOS use a user-defined Docker network
#   instead of host networking, with the database container on it.

set -euo pipefail

BASE_DIR=$(cd "$(dirname "$0")/.." && pwd)
BASE_PACKAGE=ytgov/lagoon-boutique-base-image
DDEV_PROJECT=ytg-canary
HTTP_PORT=8888

usage() {
  awk 'NR > 1 && /^#/ { sub(/^# ?/, ""); print; next } NR > 1 { exit }' "$0"
}

mode=${1:-}
[ $# -gt 0 ] && shift
template=https://github.com/ytgov/lagoon-boutique-template.git
template_ref=prod11
workdir=
apache=0
image=
keep=0

while [ $# -gt 0 ]; do
  case $1 in
    --template) template=$2; shift 2 ;;
    --template-ref) template_ref=$2; shift 2 ;;
    --workdir) workdir=$2; shift 2 ;;
    --apache) apache=1; shift ;;
    --image) image=$2; shift 2 ;;
    --keep) keep=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
  esac
done

case $mode in
  ddev) ;;
  docker) [ -n "$image" ] || { echo "docker mode needs --image" >&2; exit 2; } ;;
  *) usage >&2; exit 2 ;;
esac

nfail=0
failed=
server=
step() { printf '\n==> %s\n' "$*"; }
pass() { printf '    PASS  %s\n' "$*"; }
warn() { printf '    WARN  %s\n' "$*"; }
fail() {
  printf '    FAIL  %s\n' "$*"
  nfail=$((nfail + 1))
  failed="$failed
  - $*"
}

cleanup() {
  if [ -n "$server" ]; then
    docker rm -f "$server" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

finish() {
  echo
  if [ "$nfail" -gt 0 ]; then
    echo "CANARY FAILED ($nfail):$failed"
    echo
    echo "Site and logs kept in $workdir"
    [ "$mode" = ddev ] && echo "Remove with: ddev delete -Oy $DDEV_PROJECT && rm -rf '$workdir'"
    exit 1
  fi
  echo "CANARY PASSED"
  if [ "$keep" = 1 ]; then
    echo "Site kept in $workdir"
  else
    [ "$mode" = ddev ] && ddev delete -Oy "$DDEV_PROJECT" >/dev/null 2>&1
    rm -rf "$workdir"
  fi
  exit 0
}

# ---------------------------------------------------------------------------
# Build the site: template + candidate base.

if [ -z "$workdir" ]; then
  workdir=$(mktemp -d "${TMPDIR:-/tmp}/ytg-canary.XXXXXX")
fi
mkdir -p "$workdir"
workdir=$(cd "$workdir" && pwd)
if [ -n "$(ls -A "$workdir")" ]; then
  echo "Workdir $workdir is not empty" >&2
  exit 2
fi
log=$workdir/canary.log
: > "$log"

if [ -d "$template" ]; then
  step "Template: $template (working tree)"
  (cd "$template" && git ls-files -z -co --exclude-standard |
    while IFS= read -r -d '' f; do
      if [ -e "$f" ]; then printf '%s\0' "$f"; fi
    done | tar --null -T - -cf -) | tar -xf - -C "$workdir"
else
  step "Template: $template ($template_ref)"
  git clone -q --depth 1 --branch "$template_ref" "$template" "$workdir/.clone"
  (cd "$workdir/.clone" && tar -cf - --exclude=.git .) | tar -xf - -C "$workdir"
  rm -rf "$workdir/.clone"
fi

base_rev=$(git -C "$BASE_DIR" rev-parse --short HEAD 2>/dev/null || echo unknown)
if [ -n "$(git -C "$BASE_DIR" status --porcelain 2>/dev/null)" ]; then
  base_rev="$base_rev + uncommitted changes"
fi
step "Base candidate: $BASE_DIR ($base_rev)"
mkdir "$workdir/.base-pkg"
cp "$BASE_DIR/composer.json" "$workdir/.base-pkg/"

cd "$workdir"

# ---------------------------------------------------------------------------
# Runtime: how composer, drush and php are run, and where the site is served.

if [ "$mode" = ddev ]; then
  step "Runtime: DDEV ($([ "$apache" = 1 ] && echo apache-fpm || echo nginx-fpm))"
  # The canary runs composer itself, so drop the template's post-start hook.
  sed -i.orig '/^hooks:/,$d' .ddev/config.yaml && rm .ddev/config.yaml.orig
  if ddev describe "$DDEV_PROJECT" >/dev/null 2>&1; then
    echo "    Deleting the previous $DDEV_PROJECT DDEV project"
    ddev delete -Oy "$DDEV_PROJECT" >>"$log" 2>&1
  fi
  ddev config --project-name="$DDEV_PROJECT" \
    --webserver-type="$([ "$apache" = 1 ] && echo apache-fpm || echo nginx-fpm)" >>"$log" 2>&1
  ddev start >>"$log" 2>&1
  COMPOSER=(ddev composer)
  DRUSH=(ddev drush)
  PHP=(ddev exec php)
  base_url=https://$DDEV_PROJECT.ddev.site
else
  db_host=${CANARY_DB_HOST:-127.0.0.1}
  db_port=${CANARY_DB_PORT:-3306}
  db_name=${CANARY_DB_NAME:-drupal}
  db_user=${CANARY_DB_USER:-drupal}
  db_pass=${CANARY_DB_PASSWORD:-drupal}
  network=${CANARY_DOCKER_NETWORK:-host}
  step "Runtime: $image (network $network, database $db_host:$db_port)"
  # Lagoon images run as any user in group 0; other groups cannot write the
  # PHP config the image's entrypoint generates.
  run=(docker run --rm --network "$network" -u "$(id -u):0"
    -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer
    -e COMPOSER_CACHE_DIR=/canary/.composer-cache -e COMPOSER_AUTH
    -v "$workdir:/canary" -w /canary "$image")
  COMPOSER=("${run[@]}" composer)
  DRUSH=("${run[@]}" vendor/bin/drush)
  PHP=("${run[@]}" php)
  cat > web/sites/default/settings.local.php <<PHP
<?php
// Written by scripts/canary.sh for a throwaway canary site.
\$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => '$db_name',
  'username' => '$db_user',
  'password' => '$db_pass',
  'host' => '$db_host',
  'port' => $db_port,
  'prefix' => '',
  'collation' => 'utf8mb4_unicode_ci',
];
\$settings['hash_salt'] = 'ytg-canary-not-a-secret';
PHP
  dsn="mysql:host=$db_host;port=$db_port;dbname=$db_name"
  for i in $(seq 1 30); do
    if "${PHP[@]}" -r "new PDO('$dsn', '$db_user', '$db_pass');" >>"$log" 2>&1; then
      break
    fi
    if [ "$i" = 30 ]; then
      fail "Database $db_host:$db_port not reachable"
      finish
    fi
    sleep 2
  done
  base_url=http://127.0.0.1:$HTTP_PORT
fi

# Runs drush and puts its stdout, minus image entrypoint chatter, in $out.
# Returns drush's exit status.
drush_capture() {
  local status=0
  out=$("${DRUSH[@]}" "$@" 2>>"$log") || status=$?
  out=$(printf '%s\n' "$out" | grep -v -e '^configured lagoon-php.ini$' -e '^$' || true)
  return $status
}

# ---------------------------------------------------------------------------
# Composer: what a child site does when the base is bumped.

step "Composer"
# PHP helpers are written to files, not passed with -r: `ddev exec` runs its
# command through a shell, which would expand the PHP variables.
mkdir -p .canary
cat > .canary/drop-base-repository.php <<'PHP'
<?php
// The candidate comes from .base-pkg, so drop the template's repository entry
// for the published base; it would only cost GitHub API requests.
$composer = json_decode(file_get_contents('composer.json'), TRUE);
$composer['repositories'] = array_values(array_filter($composer['repositories'],
  fn($repo) => strpos($repo['url'] ?? '', 'lagoon-boutique-base-image') === FALSE));
file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
PHP
cat > .canary/base-patches.php <<'PHP'
<?php
// Checks that every patch the base defines was applied to the child site.
// composer-patches writes a PATCHES.txt listing each patch's source into every
// package it patches. Prints "disabled" if the child does not set
// enable-patching, otherwise one "missing <source>" line per unapplied patch.
$base = json_decode(file_get_contents('.base-pkg/composer.json'), TRUE);
$child = json_decode(file_get_contents('composer.json'), TRUE);
if (empty($child['extra']['enable-patching'])) {
  echo "disabled\n";
  exit;
}
$applied = '';
foreach (['web', 'vendor'] as $root) {
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->getFilename() === 'PATCHES.txt') {
      $applied .= file_get_contents($file->getPathname());
    }
  }
}
foreach ($base['extra']['patches'] ?? [] as $patches) {
  foreach ($patches as $source) {
    if (strpos($applied, $source) === FALSE) {
      echo "missing $source\n";
    }
  }
}
PHP
cat > .canary/base-modules.php <<'PHP'
<?php
// Prints the machine name of every drupal/* module the base requires.
$composer = json_decode(file_get_contents('.base-pkg/composer.json'), TRUE);
foreach (array_keys($composer['require']) as $package) {
  if (strpos($package, 'drupal/') === 0 && strpos($package, 'drupal/core') !== 0) {
    echo substr($package, 7), "\n";
  }
}
PHP
if ! "${PHP[@]}" .canary/drop-base-repository.php >>"$log" 2>&1; then
  fail "Could not edit composer.json (see $log)"
  finish
fi
"${COMPOSER[@]}" config repositories.canary-base \
  "{\"type\": \"path\", \"url\": \".base-pkg\", \"options\": {\"symlink\": false, \"versions\": {\"$BASE_PACKAGE\": \"dev-canary\"}}}" >>"$log" 2>&1
"${COMPOSER[@]}" require --no-update "$BASE_PACKAGE:dev-canary" >>"$log" 2>&1
if "${COMPOSER[@]}" update "$BASE_PACKAGE" -W --no-interaction --no-progress >>"$log" 2>&1; then
  pass "composer update $BASE_PACKAGE -W"
else
  fail "composer update $BASE_PACKAGE -W (see $log)"
  finish
fi
patches_status=0
patches=$("${PHP[@]}" .canary/base-patches.php 2>>"$log") || patches_status=$?
patches=$(printf '%s\n' "$patches" | grep -v -e '^configured lagoon-php.ini$' -e '^$' || true)
if [ "$patches_status" != 0 ]; then
  fail "Could not check the base's patches (see $log)"
elif [ "$patches" = disabled ]; then
  warn "The child does not set extra.enable-patching, so it gets none of the base's patches"
elif [ -n "$patches" ]; then
  fail "Base patches not applied: $(printf '%s\n' "$patches" | sed -n 's/^missing //p' | tr '\n' ' ')"
else
  pass "Every base patch is applied"
fi

# ---------------------------------------------------------------------------
# Install from the template's config.

step "Install"
profile=$(sed -n 's/^profile: *//p' config/sync/core.extension.yml)
mkdir -p web/sites/default/files private
if [ "$mode" = docker ]; then
  # Drush empties the database with the mysql client, which in the Lagoon
  # image insists on TLS the database does not offer, so do it with PDO.
  cat > .canary/empty-database.php <<PHP
<?php
\$db = new PDO('$dsn', '$db_user', '$db_pass');
\$db->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (\$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as \$table) {
  \$db->exec("DROP TABLE \`\$table\`");
}
PHP
  if ! "${PHP[@]}" .canary/empty-database.php >>"$log" 2>&1; then
    fail "Could not empty database $db_name (see $log)"
    finish
  fi
fi
if "${DRUSH[@]}" site:install "$profile" --existing-config -y \
  --account-name=admin --account-pass="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20)" >>"$log" 2>&1; then
  pass "drush site:install $profile --existing-config"
else
  fail "drush site:install (see $log)"
  finish
fi

# ---------------------------------------------------------------------------
# Checks.

step "Checks"

missing=
base_modules=$("${PHP[@]}" .canary/base-modules.php 2>>"$log" | grep -v '^configured lagoon-php.ini$' || true)
if [ -z "$base_modules" ]; then
  fail "Could not list the base's modules (see $log)"
elif drush_capture pm:list --status=enabled --type=module --field=name; then
  for module in $base_modules; do
    printf '%s\n' "$out" | grep -qx "$module" || missing="$missing $module"
  done
  if [ -z "$missing" ]; then
    pass "All $(echo $base_modules | wc -w | tr -d ' ') base modules are enabled by the template's config"
  else
    fail "Base modules not enabled by the template's config:$missing"
  fi
else
  fail "drush pm:list failed"
fi

if drush_capture updatedb:status --format=list; then
  [ -z "$out" ] && pass "No pending database updates" || fail "Pending database updates: $(echo $out)"
else
  fail "drush updatedb:status failed"
fi

if drush_capture config:status --format=list; then
  [ -z "$out" ] && pass "Active config matches config/sync" || fail "Config differs from config/sync: $(echo $out)"
else
  fail "drush config:status failed"
fi

if drush_capture core:requirements --severity=2 --field=title; then
  [ -z "$out" ] && pass "No errors in the status report" || fail "Status report errors: $out"
else
  fail "drush core:requirements failed"
fi

if [ "$mode" = docker ]; then
  publish=()
  bind=127.0.0.1
  if [ "$network" != host ]; then
    publish=(-p "127.0.0.1:$HTTP_PORT:$HTTP_PORT")
    bind=0.0.0.0
  fi
  server=$(docker run -d --network "$network" ${publish[@]+"${publish[@]}"} -u "$(id -u):0" \
    -v "$workdir:/canary" -w /canary/web "$image" php -S "$bind:$HTTP_PORT" .ht.router.php)
  for i in $(seq 1 15); do
    curl -s -o /dev/null "$base_url/" && break
    sleep 1
  done
fi

# Follows redirects, so a site with language prefixes (/ -> /en) passes.
http_check() {
  local got path
  got=$(curl -skL -o /dev/null -w '%{http_code} %{url_effective}' "$base_url$1" || true)
  path=${got#* }
  path=${path#"$base_url"}
  got=${got%% *}
  [ "$path" != "$1" ] && path="$1 (redirected to $path)" || path=$1
  if [ "$got" = "$2" ]; then
    pass "GET $path -> $got"
  else
    fail "GET $path -> $got (expected $2)"
  fi
}
http_check / 200
if [ -f config/sync/language.entity.fr.yml ]; then
  http_check /fr 200
fi
http_check /admin/config 403
http_check /canary-page-that-does-not-exist 404

# After the HTTP checks, so errors from serving pages are caught too.
if drush_capture watchdog:show --severity-min=Error --count=20 --field=message; then
  [ -z "$out" ] && pass "No errors in the Drupal log" || fail "Drupal log errors:
$out"
else
  fail "drush watchdog:show failed"
fi

finish
