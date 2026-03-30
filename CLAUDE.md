# MyAdmin HD Space VPS Addon

Standalone Composer plugin that sells additional HD space for VPS instances via the MyAdmin plugin system.

## Commands

```bash
composer install                                          # install deps
vendor/bin/phpunit tests/ -v  # run all tests
vendor/bin/phpunit tests/ -v --coverage-clover coverage.xml --whitelist src/  # with coverage
```

## Architecture

- **Namespace:** `Detain\MyAdminVpsHd\` → `src/` · Tests: `Detain\MyAdminVpsHd\Tests\` → `tests/`
- **Entry:** `src/Plugin.php` — registers hooks via `getHooks()` · `src/vps_hdspace.php` — page handler
- **Tests:** `tests/PluginTest.php` · `tests/VpsHdspaceFileTest.php` — PHPUnit 9.6, no mocks of MyAdmin internals
- **Events:** `Symfony\Component\EventDispatcher\GenericEvent` — all hook methods accept `GenericEvent $event`
- **CI/CD:** `.github/` workflows handle automated testing and deployment pipelines for this package
- **IDE Config:** `.idea/` stores inspectionProfiles, deployment.xml, and encodings.xml for JetBrains IDE settings

## Plugin Hook Pattern

`src/Plugin.php` registers three hooks:
- `function.requirements` → `getRequirements()` — registers `vps_hdspace` page via `$loader->add_page_requirement()`
- `vps.load_addons` → `getAddon()` — creates `\AddonHandler`, sets text/cost/callbacks, calls `->register()`
- `vps.settings` → `getSettings()` — adds `vps_hd_cost` setting via `$settings->add_text_setting()`

```php
public static function getHooks() {
    return [
        'function.requirements'        => [__CLASS__, 'getRequirements'],
        self::$module . '.load_addons' => [__CLASS__, 'getAddon'],
        self::$module . '.settings'    => [__CLASS__, 'getSettings'],
    ];
}
```

## AddonHandler Pattern

```php
$addon = new \AddonHandler();
$addon->setModule(self::$module)
    ->set_text('Additional GB')
    ->set_text_match('Additional (.*) GB')
    ->set_cost(VPS_HD_COST)
    ->set_require_ip(false)
    ->setEnable([__CLASS__, 'doEnable'])
    ->setDisable([__CLASS__, 'doDisable'])
    ->register();
```

Enable/disable callbacks receive `(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)`. Use `$serviceOrder->getServiceInfo()` and `get_module_settings(self::$module)` to get service data. Log via `myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__)`. Queue actions via `$GLOBALS['tf']->history->add(self::$module.'queue', $id, 'update_hdsize', $space, $custid)`.

## Page Handler Pattern (`src/vps_hdspace.php`)

```php
function vps_hdspace() {
    $module = 'vps';
    $settings = get_module_settings($module);
    $db = get_module_db($module);
    $id = (int)$GLOBALS['tf']->variables->request['id'];
    $serviceInfo = get_service($id, $module);
    if ($serviceInfo === false) { dialog('Error', 'Invalid ID'); return false; }
    // CSRF: $table->csrf('action_name') on form, verify_csrf('action_name') on submit
    // ORM: new \MyAdmin\Orm\Repeat_Invoice($db) · new \MyAdmin\Orm\Invoice($db)
    // Log: myadmin_log('vps', 'info', $msg, __LINE__, __FILE__)
}
```

## Conventions

- All `Plugin` methods are `public static` — no instance state
- `VPS_HD_COST` constant from parent platform — referenced, not defined here
- Tabs for indentation (see `.scrutinizer.yml` coding style)
- Test file assertions check string contents of `src/vps_hdspace.php` directly — keep docblock `@author`, `@package`, `@return`, `@throws` tags in that file
- Commit messages: lowercase, descriptive (`fix addon handler`, `update hook registration`)

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
