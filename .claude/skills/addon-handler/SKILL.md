---
name: addon-handler
description: Implements an AddonHandler registration with enable/disable callbacks in src/Plugin.php. Covers fluent builder chain (setModule, set_text, set_text_match, set_cost, set_require_ip, setEnable, setDisable, register), callback signature (\ServiceHandler, $repeatInvoiceId, $regexMatch), history queue calls, and admin mail on disable. Use when user says 'add addon', 'register addon', 'enable/disable handler', or 'billing addon'. Do NOT use for page handlers (vps_hdspace) or settings-only changes.
---
# Addon Handler

## Critical

- All `Plugin` methods MUST be `public static` — no instance state.
- Call `function_requirements('class.AddonHandler')` before `new \AddonHandler()` — the class is lazy-loaded.
- Always call `$service->addAddon($addon)` after `->register()` or the addon is never attached to the service.
- Access service data exclusively via `$serviceOrder->getServiceInfo()` and `get_module_settings(self::$module)` — never hardcode table/prefix strings.
- Use `$settings['PREFIX'].'_id'` and `$settings['PREFIX'].'_custid'` for column access — these vary per module.

## Instructions

1. **Add the hook entry in `getHooks()`**
   ```php
   self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
   ```
   Verify `self::$module` matches the target module (e.g., `'vps'`) before proceeding.

2. **Implement `getAddon(GenericEvent $event)`**
   ```php
   public static function getAddon(GenericEvent $event)
   {
       /** @var \ServiceHandler $service */
       $service = $event->getSubject();
       function_requirements('class.AddonHandler');
       $addon = new \AddonHandler();
       $addon->setModule(self::$module)
           ->set_text('Additional GB')               // display label
           ->set_text_match('Additional (.*) GB')    // regex; capture group → $regexMatch
           ->set_cost(VPS_HD_COST)                   // defined constant or float
           ->set_require_ip(false)
           ->setEnable([__CLASS__, 'doEnable'])
           ->setDisable([__CLASS__, 'doDisable'])
           ->register();
       $service->addAddon($addon);
   }
   ```
   Verify the constant used in `set_cost()` is defined in the module's settings (e.g., `VPS_HD_COST`).

3. **Implement `doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)`**
   ```php
   public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   {
       $serviceInfo = $serviceOrder->getServiceInfo();
       $settings    = get_module_settings(self::$module);
       $space       = $regexMatch; // value captured from set_text_match regex
       myadmin_log(self::$module, 'info',
           self::$name." Activating {$space} GB additional HD space for "
           ."{$settings['TBLNAME']} {$serviceInfo[$settings['PREFIX'].'_id']}",
           __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
       $GLOBALS['tf']->history->add(
           self::$module.'queue',
           $serviceInfo[$settings['PREFIX'].'_id'],
           'update_hdsize',
           $space,
           $serviceInfo[$settings['PREFIX'].'_custid']);
   }
   ```

4. **Implement `doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)`**
   Mirror `doEnable`, then add user-facing output and admin email:
   ```php
   add_output('Additional '.$space.' GB HD Space Removed And Canceled');
   $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'
       .$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>'
       .'Repeat Invoice: '.$repeatInvoiceId.'<br>Additional Space: '.$space.' GB<br>'
       .'Description: '.self::$name.'<br>';
   $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id']
       .' Canceled Additional '.$space.' GB HD Space';
   (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_hdspace_canceled.tpl');
   ```
   Verify the `.tpl` path exists under `include/templates/email/admin/` before wiring it.

## Examples

**User says:** "Add a RAM upgrade addon for the VPS module."

**Actions taken:**
1. Add `self::$module.'.load_addons' => [__CLASS__, 'getAddon']` to `getHooks()`.
2. Implement `getAddon` with `set_text('Additional GB RAM')`, `set_text_match('Additional (.*) GB RAM')`, `set_cost(VPS_RAM_COST)`.
3. `doEnable` logs activation and queues `update_ramsize` in `$GLOBALS['tf']->history->add(self::$module.'queue', $id, 'update_ramsize', $ram, $custid)`.
4. `doDisable` queues same action and sends admin mail via `adminMail($subject, $email, false, 'admin/vps_ramupgrade_canceled.tpl')`.

**Result:** Identical structure to `src/Plugin.php` lines 54–104, swapping HD-specific text/constant/action.

## Common Issues

- **`Class 'AddonHandler' not found`** — missing `function_requirements('class.AddonHandler')` before `new \AddonHandler()`. Add it as the first line inside `getAddon()`.
- **Addon registered but never appears** — forgot `$service->addAddon($addon)` after `->register()`. Both calls are required.
- **`$regexMatch` is always `false`** — `set_text_match()` regex has no capture group. Ensure pattern contains `(.*)` e.g. `'Additional (.*) GB'`.
- **Undefined constant in `set_cost()`** — the constant (e.g., `VPS_HD_COST`) is defined by `getSettings()` via `add_text_setting`. Ensure `getSettings` hook is registered and the setting name matches `strtoupper('vps_hd_cost')` → `VPS_HD_COST`.
- **`$serviceInfo[$settings['PREFIX'].'_custid']` undefined** — wrong module passed to `get_module_settings()`. Confirm `self::$module` matches the module that owns the service table.