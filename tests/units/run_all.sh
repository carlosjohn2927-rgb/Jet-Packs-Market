#!/usr/bin/env bash
# Run all unit suites under tests/units/ via the orchestrator.
# Fails fast on the first non-zero exit.
#
#   bash tests/units/run_all.sh                # every suite
#   bash tests/units/run_all.sh foo bar         # only foo.php and bar.php
#   PHP_BIN=/usr/bin/php8.2 bash tests/units/run_all.sh
set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"
exec "$PHP_BIN" "$SCRIPT_DIR/run_all.php" "$@"
