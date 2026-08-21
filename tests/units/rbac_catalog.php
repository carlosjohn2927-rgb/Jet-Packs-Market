<?php
/**
 * Lock-down tests for the RBAC permission catalogue.
 *
 * These tests read application/config/permissions.php DIRECTLY (not
 * through the Acl library) and assert the static contract the dashboard
 * enforces. They run in <100ms with no DB, no HTTP, no installer:
 *
 *   - every permission row is [label, group, superOnly] shaped
 *   - every group referenced in $config['permissions'] exists in
 *     $config['permission_groups']
 *   - super_only contains exactly {admins.manage, system.manage}
 *   - super_only permissions are NOT in any non-super role's defaults
 *   - default permissions for every role reference only existing keys
 *
 * If the catalogue is amended without tests being updated, CI will
 * refuse to merge.
 */
require_once __DIR__ . '/_runner.php';

// Evaluate application/config/permissions.php in isolation. It defines
// $config['permissions'], so we just include it.
$permissions_file = dirname(__DIR__, 2) . '/application/config/permissions.php';
if (!is_file($permissions_file)) {
    fwrite(STDERR, "missing: $permissions_file\n");
    exit(2);
}

// Load only permissions.php; isolate its $config from any boot-time global.
// We use a closure-scoped variable so the test never pollutes runtime state.
$permissions_cfg = (static function () use ($permissions_file) {
    $config = [];
    require $permissions_file;
    return $config;
})();
$permissions     = $permissions_cfg['permissions']             ?? [];
$permission_grp  = $permissions_cfg['permission_groups']       ?? [];
$role_defaults   = $permissions_cfg['role_default_permissions'] ?? [];

section('Permission catalogue shape');
assert_true(is_array($permissions) && count($permissions) > 5, 'catalog has >5 entries');
foreach ($permissions as $key => $row) {
    assert_true(is_string($key) && strpos($key, '.') !== false,
        "permission key '{$key}' uses dotted namespace");
    assert_true(is_array($row) && count($row) === 3,
        "permission '{$key}' has exactly 3 fields");
    [$label, $group, $super] = $row;
    assert_true(is_string($label) && $label !== '', "{$key} label is non-empty string");
    assert_true(is_string($group),                      "{$key} group is a string");
    assert_true(is_bool($super),                         "{$key} superOnly is a bool");
    assert_true(isset($permission_grp[$group]),         "{$key} group '$group' is declared in \$config['permission_groups']");
}

section('super_only permissions');
$super_only = [];
foreach ($permissions as $key => [$label, $group, $super]) {
    if ($super) $super_only[$key] = true;
}
assert_true(isset($super_only['admins.manage']),  'admins.manage is super-only');
assert_true(isset($super_only['system.manage']),  'system.manage is super-only');
// Lock the set so we never accidentally add a third super-only permission
// (the Acl class *rejects* super_only grants to non-super roles, so a
// mistake here would silently deny a new section to everyone).
sort($super_only_keys = array_keys($super_only));
assert_eq(['admins.manage', 'system.manage'], $super_only_keys);

section('Role default permissions');
assert_true(is_array($role_defaults), 'role_default_permissions exists');
foreach (['ADMIN', 'SALES', 'ENGINEER', 'EDITOR'] as $role) {
    $list = $role_defaults[$role] ?? [];
    assert_true(is_array($list) && count($list) > 0, "default permissions for $role exist");
    foreach ($list as $k) {
        assert_true(is_string($k) && $k !== '',           "$role default contains a key");
        assert_true(isset($permissions[$k]),               "$role default '$k' exists in catalog");
        [$_, $_, $isSuper] = $permissions[$k];
        assert_true($isSuper === false,
            "$role must not be allowed to default to a super_only permission ($k)");
    }
}

// Specifically assert that the catalog's most important keys are
// reachable by some non-super role (catches the trap of accidentally
// putting a section-of-the-app behind only the SUPER_ADMIN).
section('Critical catalog keys reachable by non-super roles');
$critical = ['dashboard.view', 'products.manage', 'quotes.manage',
            'contacts.manage', 'blog.manage', 'media.manage',
            'homepage.manage', 'menus.manage', 'pages.manage',
            'appearance.manage', 'seo.manage', 'settings.manage'];
foreach ($critical as $k) {
    assert_true(isset($permissions[$k]),  "$k exists in catalog");
    [$label, $group, $super] = $permissions[$k];
    assert_true($super === false,          "$k is NOT super-only");
    // At least one role has it in defaults.
    $reached = false;
    foreach ($role_defaults as $id => $list) {
        if (in_array($k, $list, true)) { $reached = true; break; }
    }
    assert_true($reached, "$k is reachable by a non-super role default");
}

// Permission groups count is bounded (we should never accidentally
// re-introduce orphan groups).
section('Permission groups');
$groups = array_keys($permissions);
$referenced_groups = [];
foreach ($permissions as $label => [$_, $g, $_]) {
    $referenced_groups[$g] = true;
}
assert_eq(count($permission_grp), count($referenced_groups),
    'every permission group is declared (no orphans)');

summary();
exit($failures === 0 ? 0 : 1);
