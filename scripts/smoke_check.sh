#!/bin/bash
# Vortex Precision - pre-deploy smoke check (no PHP required).
#
# Verifies every file the boot path will load actually exists.
# Useful when you have a freshly-cloned repo and want to make sure nothing's
# missing before you upload to a server. Does NOT need PHP or MySQL.
#
# Run:  ./scripts/smoke_check.sh

set -u
cd "$(dirname "$0")/.."
APP=app
MISSING=0
OK=0

# Find a CI3 library file. CI3's load->library() is case-insensitive and will
# also look in subdirs (system/libraries/Session/Session.php). The `database`
# library lives at system/database/DB.php, not system/libraries/.
find_library() {
    local lib="$1"
    local f
    # Special case: the `database` library
    if [ "$lib" = "database" ] && [ -f "$APP/system/database/DB.php" ]; then
        echo "$APP/system/database/DB.php"; return 0
    fi
    f=$(find "$APP/system/libraries" -maxdepth 3 -iname "$lib.php" 2>/dev/null | head -1)
    [ -n "$f" ] && { echo "$f"; return 0; }
    f=$(find "$APP/application/libraries" -maxdepth 3 -iname "$lib.php" 2>/dev/null | head -1)
    [ -n "$f" ] && { echo "$f"; return 0; }
    return 1
}

# Find a CI3 helper file. CI3's load->helper() strips a trailing _helper
# suffix and looks for <name>_helper.php in application/helpers or system/helpers.
find_helper() {
    local h="$1"
    local base
    base=$(echo "$h" | sed -E 's/_helper$//')
    local f
    f=$(find "$APP/application/helpers" -maxdepth 1 -iname "$base""_helper.php" 2>/dev/null | head -1)
    [ -n "$f" ] && { echo "$f"; return 0; }
    f=$(find "$APP/system/helpers" -maxdepth 1 -iname "$base""_helper.php" 2>/dev/null | head -1)
    [ -n "$f" ] && { echo "$f"; return 0; }
    return 1
}

# Find a CI3 language file. CI3's Lang::load strips a trailing _lang suffix
# and re-adds it, then appends .php. So it looks for ${name}_lang.php
# where ${name} is the input with _lang stripped (or just the input as-is).
# So for input "app_lang" we look for "app_lang.php".
find_lang() {
    local l="$1"
    local base
    base=$(echo "$l" | sed -E 's/_lang$//')
    # Either "${base}_lang.php" or just "$l.php" works in practice
    find "$APP/application/language" -maxdepth 3 -iname "$base""_lang.php" 2>/dev/null | head -1
}

# Parse the comma-separated items inside the [...] of an autoload line.
parse_autoload() {
    local key="$1"
    grep -E "autoload\\[['\"]?$key['\"]?\\]" "$APP/application/config/autoload.php" \
        | sed -E "s/.*\\[(.*)\\].*/\\1/"
}

check_file() {
    local label="$1" path="$2"
    if [ -e "$path" ]; then
        printf "  \033[32m[OK]\033[0m  %-32s %s\n" "$label" "$path"
        OK=$((OK+1))
    else
        printf "  \033[31m[!!]\033[0m  %-32s %s\n" "$label" "$path"
        MISSING=$((MISSING+1))
    fi
}

echo "=== 1. Front controller + CI3 core ==="
check_file "index.php" "$APP/index.php"
check_file "CodeIgniter.php" "$APP/system/core/CodeIgniter.php"

echo
echo "=== 2. Config files ==="
for f in config database autoload routes constants migration session; do
    check_file "$f.php" "$APP/application/config/$f.php"
done

echo
echo "=== 3. Autoloaded libraries ==="
LIB_LINE=$(parse_autoload "libraries")
IFS=',' read -ra LIBS <<< "$LIB_LINE"
for raw in "${LIBS[@]}"; do
    lib=$(echo "$raw" | tr -d " '\"")
    [ -z "$lib" ] && continue
    if f=$(find_library "$lib"); then
        check_file "$lib" "$f"
    else
        check_file "$lib" "NOT FOUND"
    fi
done

echo
echo "=== 4. Autoloaded helpers ==="
HELP_LINE=$(parse_autoload "helper")
IFS=',' read -ra HELPS <<< "$HELP_LINE"
for raw in "${HELPS[@]}"; do
    h=$(echo "$raw" | tr -d " '\"")
    [ -z "$h" ] && continue
    if f=$(find_helper "$h"); then
        check_file "$h" "$f"
    else
        check_file "$h" "NOT FOUND"
    fi
done

echo
echo "=== 5. Autoloaded language files ==="
LANG_LINE=$(parse_autoload "language")
IFS=',' read -ra LANGS <<< "$LANG_LINE"
for raw in "${LANGS[@]}"; do
    l=$(echo "$raw" | tr -d " '\"")
    [ -z "$l" ] && continue
    if f=$(find_lang "$l"); then
        check_file "$l" "$f"
    else
        check_file "$l" "NOT FOUND"
    fi
done

echo
echo "=== 6. Required directories (created on first write) ==="
for d in assets/uploads assets/logs assets/logs/cache assets/logs/sess \
         assets/logs/ratelimit application/cache application/logs; do
    if [ -d "$APP/$d" ]; then
        perms=$(stat -c %a "$APP/$d" 2>/dev/null || echo '?')
        printf "  \033[32m[OK]\033[0m  %-32s exists, perms %s\n" "$d" "$perms"
        OK=$((OK+1))
    else
        printf "  \033[33m[--]\033[0m  %-32s (will be created on first write)\n" "$d"
    fi
done

echo
echo "=== 7. Install tooling (OUTSIDE the web root - install/) ==="
for f in install.sql seed.sql install.php; do
    if [ -f "install/$f" ]; then
        size=$(wc -c < "install/$f")
        printf "  \033[32m[OK]\033[0m  %-32s %s bytes\n" "install/$f" "$size"
        OK=$((OK+1))
    else
        printf "  \033[31m[!!]\033[0m  %-32s NOT FOUND\n" "install/$f"
        MISSING=$((MISSING+1))
    fi
done
if [ -d "$APP/sql" ]; then
    printf "  \033[31m[!!]\033[0m  %-32s legacy app/sql/ still exists inside the web root\n" "app/sql/"
    MISSING=$((MISSING+1))
else
    printf "  \033[32m[OK]\033[0m  %-32s removed from web root\n" "app/sql/"
    OK=$((OK+1))
fi

echo
echo "=== 8. CI3 system completeness ==="
for sub in core database/drivers helpers libraries; do
    c=$(find "$APP/system/$sub" -name "*.php" 2>/dev/null | wc -l)
    if [ "$c" -gt 0 ]; then
        printf "  \033[32m[OK]\033[0m  %-32s %d files\n" "system/$sub" "$c"
        OK=$((OK+1))
    else
        printf "  \033[31m[!!]\033[0m  %-32s MISSING\n" "system/$sub"
        MISSING=$((MISSING+1))
    fi
done

echo
echo "=== 9. .htaccess ==="
if [ -f "$APP/.htaccess" ]; then
    size=$(wc -c < "$APP/.htaccess")
    printf "  \033[32m[OK]\033[0m  %-32s %s bytes\n" ".htaccess" "$size"
    OK=$((OK+1))
else
    printf "  \033[31m[!!]\033[0m  %-32s NOT FOUND\n" ".htaccess"
    MISSING=$((MISSING+1))
fi

echo
echo "=== 10. No hard-coded admin credentials anywhere ==="
if grep -q "INSERT INTO \`users\`" install/seed.sql 2>/dev/null; then
    printf "  \033[31m[!!]\033[0m  seed.sql still inserts a user account\n"
    MISSING=$((MISSING+1))
else
    printf "  \033[32m[OK]\033[0m  seed.sql contains no user accounts (admin created by install/install.php)\n"
    OK=$((OK+1))
fi
PW1="admin""123"; PW2="sales""123"
if grep -rq "$PW1\|$PW2" install/ app/application app/tests 2>/dev/null; then
    printf "  \033[31m[!!]\033[0m  known default passwords found in the repository\n"
    MISSING=$((MISSING+1))
else
    printf "  \033[32m[OK]\033[0m  no known default passwords in the repository\n"
    OK=$((OK+1))
fi

echo
echo "=== 11. Secret keys in config.php ==="
if grep -q "CHANGE-ME" "$APP/application/config/config.php" 2>/dev/null; then
    printf "  \033[31m[!!]\033[0m  encryption_key/auth_secret still have CHANGE-ME placeholders\n"
    MISSING=$((MISSING+1))
else
    printf "  \033[32m[OK]\033[0m  no hard-coded placeholders (secrets come from env or .secrets.php)\n"
    OK=$((OK+1))
fi

echo
echo "=== 12. MySQL extension sanity (looked up via PHP if available) ==="
# CI3 needs mysqli, mbstring, intl, json at runtime. Can't verify without
# running PHP, but we can at least confirm the .php files that need them
# exist (mb_strlen, json_encode, mysqli_*).
for sym in mb_strlen json_encode password_hash random_bytes; do
    c=$(grep -rl "$sym" "$APP/application" 2>/dev/null | wc -l)
    if [ "$c" -gt 0 ]; then
        printf "  \033[32m[OK]\033[0m  %-32s used in %d files\n" "$sym" "$c"
        OK=$((OK+1))
    fi
done

echo
echo "=== 13. First-request simulation: GET / (home page) ==="
# Walking the boot path: every file that gets loaded when the home page
# renders. If any of these is missing, the home page returns 500.
declare -a home_files=(
    # Front controller + CI3 core
    "application/config/routes.php"
    "application/config/autoload.php"
    "application/config/config.php"
    "application/config/database.php"
    "application/config/constants.php"
    "application/config/session.php"
    # Autoloaded libraries
    "system/database/DB.php"
    "system/libraries/Session/Session.php"
    "application/libraries/Vp_auth.php"
    "application/libraries/Rbac.php"
    "application/libraries/Settings.php"
    "application/libraries/Audit.php"
    "application/libraries/Rate_limiter.php"
    "application/libraries/Mailer.php"
    # Autoloaded helpers
    "system/helpers/url_helper.php"
    "system/helpers/form_helper.php"
    "system/helpers/text_helper.php"
    "system/helpers/date_helper.php"
    "application/helpers/app_helper.php"
    "application/helpers/security_helper.php"
    # Language
    "application/language/english/app_lang.php"
    # Base controllers
    "application/core/MY_Controller.php"
    "application/core/Auth_Controller.php"
    "application/core/Admin_Controller.php"
    "application/core/Admin_Crud.php"
    "application/core/MY_Model.php"
    # Home controller and its models
    "application/controllers/Home.php"
    "application/models/Category_model.php"
    "application/models/Product_model.php"
    "application/models/Industry_model.php"
    "application/models/Testimonial_model.php"
    "application/models/Partner_model.php"
    "application/models/Setting_model.php"
    # View files
    "application/views/layouts/public.php"
    "application/views/partials/header.php"
    "application/views/partials/footer.php"
    "application/views/home/index.php"
    # CSS / JS / img that get served alongside
    "assets/css/app.css"
    "assets/img/favicon.svg"
)
home_missing=0
for f in "${home_files[@]}"; do
    if [ -f "$APP/$f" ]; then
        OK=$((OK+1))
    else
        printf "  \033[31m[!!]\033[0m  %s (would break first home page load)\n" "$f"
        MISSING=$((MISSING+1))
        home_missing=$((home_missing+1))
    fi
done
if [ "$home_missing" -eq 0 ]; then
    printf "  \033[32m[OK]\033[0m  %d files needed for GET / - all present\n" "${#home_files[@]}"
fi

echo
echo "=== 14. Admin login simulation: GET /admin/login ==="
declare -a admin_files=(
    "application/controllers/Auth.php"
    "application/controllers/admin/Dashboard.php"
    "application/views/auth/login.php"
    "application/views/layouts/admin.php"
    "application/views/partials/admin_nav.php"
    "application/views/partials/admin_sidebar.php"
    "assets/css/admin.css"
    "assets/js/admin.js"
    "application/models/User_model.php"
    "application/models/Notification_model.php"
)
admin_missing=0
for f in "${admin_files[@]}"; do
    if [ -f "$APP/$f" ]; then
        OK=$((OK+1))
    else
        printf "  \033[31m[!!]\033[0m  %s (would break admin login)\n" "$f"
        MISSING=$((MISSING+1))
        admin_missing=$((admin_missing+1))
    fi
done
if [ "$admin_missing" -eq 0 ]; then
    printf "  \033[32m[OK]\033[0m  %d files needed for GET /admin/login - all present\n" "${#admin_files[@]}"
fi

echo
echo "==========================================================="
if [ "$MISSING" -eq 0 ]; then
    printf "\033[32m=== PASSED ===\033[0m  %d checks OK, 0 missing\n" "$OK"
    echo "Repo is ready to upload to a PHP host."
    exit 0
else
    printf "\033[31m=== FAILED ===\033[0m  %d checks OK, %d missing\n" "$OK" "$MISSING"
    exit 1
fi
