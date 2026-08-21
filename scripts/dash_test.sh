#!/usr/bin/env bash
# Quick manual smoke helpers for the dashboard (development only).
# Usage: source scripts/dash_test.sh ; login super ; post admin/... "a=b&c=d"
BASE="${BASE:-http://127.0.0.1:8080}"
JAR="${JAR:-/tmp/vp_jar.txt}"

csrf() {
  curl -s -b "$JAR" -c "$JAR" "$BASE/$1" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'
}
login() {
  rm -f "$JAR"
  local email="$1" pass="$2"
  local tok; tok=$(csrf admin/login)
  curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "login:%{http_code} " \
    -X POST -d "csrf_token=$tok&email=$email&password=$pass" "$BASE/admin/login"
  curl -s -b "$JAR" -o /dev/null -w "dash:%{http_code}\n" "$BASE/admin"
}
get() { curl -s -b "$JAR" -o /tmp/vp_out.html -w "%{http_code} $1\n" "$BASE/$1"; }
post() {
  local path="$1"; shift
  local tok; tok=$(csrf "${REF:-admin}")
  curl -s -b "$JAR" -c "$JAR" -o /tmp/vp_out.html -w "%{http_code} POST $path\n" \
    -X POST -d "csrf_token=$tok&$*" "$BASE/$path"
}
