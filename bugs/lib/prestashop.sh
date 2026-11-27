# Helpers for PrestaShop bug scripts. Sourced (by bash) from bugs/prestashop/*/apply.sh and check.sh.
# Provides: ps_php, ps_override_install, ps_override_is

PS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../apps/prestashop" && pwd)"
PS_LIB="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
set -a; . "$PS_DIR/.env"; set +a

# ps_php FILE  - run a PHP file inside the container as the web user, with bugs/lib/ps-bootstrap.php next to it
ps_php() {
  (cd "$PS_DIR" && docker compose exec -T prestashop sh -c 'mkdir -p /tmp/bench-bug && cat > /tmp/bench-bug/bootstrap.php') < "$PS_LIB/ps-bootstrap.php"
  (cd "$PS_DIR" && docker compose exec -T prestashop sh -c 'cat > /tmp/bench-bug/run.php && chown -R www-data /tmp/bench-bug') < "$1"
  local status=0
  (cd "$PS_DIR" && docker compose exec -T -u www-data prestashop php -d memory_limit=-1 /tmp/bench-bug/run.php 2>&1) \
    > /tmp/bench-ps-out.$$ || status=$?
  grep -v 'Cannot modify header information' /tmp/bench-ps-out.$$ || true
  rm -f /tmp/bench-ps-out.$$
  (cd "$PS_DIR" && docker compose exec -T prestashop rm -rf /tmp/bench-bug)
  return "$status"
}

# ps_override_install FILE TARGET  - install FILE as override/TARGET (e.g. classes/Cart.php) and refresh the class index
ps_override_install() {
  (cd "$PS_DIR" && docker compose exec -T prestashop sh -c \
    "mkdir -p \"\$(dirname /var/www/html/override/$2)\" && cat > /var/www/html/override/$2 && chown -R www-data:www-data /var/www/html/override && rm -f /var/www/html/var/cache/*/class_index.php") < "$1"
}

# ps_override_is FILE TARGET  - true if override/TARGET is exactly FILE
ps_override_is() {
  local want have
  want=$(shasum -a 256 < "$1" | cut -d' ' -f1)
  have=$(cd "$PS_DIR" && docker compose exec -T prestashop sh -c "sha256sum /var/www/html/override/$2 2>/dev/null" | cut -d' ' -f1)
  [ "$want" = "$have" ]
}
