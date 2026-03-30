---
name: plugin-hook
description: Creates or modifies a MyAdmin plugin following the Plugin.php hook pattern. Generates getHooks() array, static handler methods accepting GenericEvent $event, and proper function.requirements / {module}.load_addons / {module}.settings event keys. Use when user says 'add hook', 'new plugin', 'register event', 'add addon', or modifies src/Plugin.php. Do NOT use for modifying src/vps_hdspace.php page logic or for non-plugin PHP classes.
---
# plugin-hook

## Critical

- All `Plugin` methods MUST be `public static` — no instance state, ever.
- Hook handlers for events MUST accept exactly `GenericEvent $event` as their sole parameter.
- `doEnable` / `doDisable` MUST have signature `(\ ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)` — not `GenericEvent`.
- Hook keys for module events MUST use `self::$module` (not a hardcoded string): `self::$module.'.load_addons'`, `self::$module.'.settings'`.
- Never call `get_module_db()` or `get_module_settings()` in `getAddon()` — defer that to `doEnable`/`doDisable`.

## Instructions

1. **Define static class properties** at the top of `src/Plugin.php`:
   ```php
   public static $name = 'My Addon Name';
   public static $description = 'Short description of what this addon sells.';
   public static $help = '';
   public static $module = 'vps';   // target module slug
   public static $type = 'addon';
   ```
   Verify: exactly 5 static properties exist (tests assert `assertCount(5, $staticProperties)`).

2. **Implement `getHooks()`** returning exactly 3 entries:
   ```php
   public static function getHooks()
   {
       return [
           'function.requirements'        => [__CLASS__, 'getRequirements'],
           self::$module.'.load_addons'   => [__CLASS__, 'getAddon'],
           self::$module.'.settings'      => [__CLASS__, 'getSettings'],
       ];
   }
   ```
   Verify: `Plugin::getHooks()` returns an array with keys `function.requirements`, `{module}.load_addons`, `{module}.settings`.

3. **Implement `getRequirements(GenericEvent $event)`** — registers the page handler file:
   ```php
   public static function getRequirements(GenericEvent $event)
   {
       /** @var \MyAdmin\Plugins\Loader $loader */
       $loader = $event->getSubject();
       $loader->add_page_requirement('my_page_func', '/../vendor/detain/myadmin-my-addon/src/my_page_func.php');
   }
   ```
   Verify: the `.php` file path registered here physically exists at `src/` in this package.

4. **Implement `getAddon(GenericEvent $event)`** — builds and registers an `AddonHandler`:
   ```php
   public static function getAddon(GenericEvent $event)
   {
       /** @var \ServiceHandler $service */
       $service = $event->getSubject();
       function_requirements('class.AddonHandler');
       $addon = new \AddonHandler();
       $addon->setModule(self::$module)
           ->set_text('My Addon Label')
           ->set_text_match('My Addon (.*) Label')
           ->set_cost(MY_ADDON_COST_CONSTANT)
           ->set_require_ip(false)
           ->setEnable([__CLASS__, 'doEnable'])
           ->setDisable([__CLASS__, 'doDisable'])
           ->register();
       $service->addAddon($addon);
   }
   ```
   Verify: `set_text_match` regex has a capture group `(.*)` — this value becomes `$regexMatch` in `doEnable`/`doDisable`.

5. **Implement `doEnable` and `doDisable`** — use service info and queue the action:
   ```php
   public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   {
       $serviceInfo = $serviceOrder->getServiceInfo();
       $settings = get_module_settings(self::$module);
       $value = $regexMatch;
       myadmin_log(self::$module, 'info',
           self::$name." Activating for {$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}",
           __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
       $GLOBALS['tf']->history->add(
           self::$module.'queue',
           $serviceInfo[$settings['PREFIX'].'_id'],
           'action_name',
           $value,
           $serviceInfo[$settings['PREFIX'].'_custid']
       );
   }
   ```
   `doDisable` follows the same pattern; also call `add_output(...)` and `(new \MyAdmin\Mail())->adminMail(...)` for cancellation notice.

6. **Implement `getSettings(GenericEvent $event)`** — registers a module setting:
   ```php
   public static function getSettings(GenericEvent $event)
   {
       /** @var \MyAdmin\Settings $settings */
       $settings = $event->getSubject();
       $settings->setTarget('module');
       $settings->add_text_setting(self::$module, _('Addon Costs'), 'my_addon_cost',
           _('My Addon Cost'), _('Cost description.'), $settings->get_setting('MY_ADDON_COST'));
       $settings->setTarget('global');  // always reset to global after
   }
   ```
   Verify: `setTarget('global')` is called after adding settings — forgetting this leaks module scope.

7. **Run tests** to validate structure:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```

## Examples

**User says:** "Add a hook to sell extra RAM as a VPS addon"

**Actions taken:**
1. Set `$module = 'vps'`, `$name = 'RAM VPS Addon'`, `$type = 'addon'`
2. `getHooks()` returns keys: `function.requirements`, `vps.load_addons`, `vps.settings`
3. `getRequirements` registers `'vps_ramspace'` → `src/vps_ramspace.php`
4. `getAddon` builds `AddonHandler` with `set_text('Additional GB RAM')`, `set_text_match('Additional (.*) GB RAM')`, `set_cost(VPS_RAM_COST)`
5. `doEnable`/`doDisable` call `$GLOBALS['tf']->history->add('vpsqueue', $id, 'update_ramsize', $regexMatch, $custid)`
6. `getSettings` adds `vps_ram_cost` setting scoped to `'module'`, resets to `'global'`

**Result:** Plugin integrates with `vps.load_addons` event; addon appears in billing with regex-captured quantity.

## Common Issues

- **`Call to undefined method AddonHandler::register()`**: Missing `function_requirements('class.AddonHandler');` before `new \AddonHandler()`.
- **Hook key wrong — event never fires**: Used a hardcoded string like `'vps.load_addons'` instead of `self::$module.'.load_addons'`. If `$module` changes, hardcoded keys break.
- **`$regexMatch` is always `false` in `doEnable`**: `set_text_match()` regex has no capture group. Change `'My Label'` to `'My (.*) Label'`.
- **Settings scope bleeds across modules**: Missing `$settings->setTarget('global')` at the end of `getSettings()`. Always reset after `add_text_setting()`.
- **Test `testPublicMethodCount` fails with count mismatch**: Added a method without updating the test assertion in `tests/PluginTest.php` line ~345. Update `assertCount(7, ...)` to match the actual count.
- **`doEnable` receives `null` serviceInfo keys**: Using wrong prefix. Always use `$settings['PREFIX'].'_id'` and `$settings['PREFIX'].'_custid'` — never hardcode column names.
