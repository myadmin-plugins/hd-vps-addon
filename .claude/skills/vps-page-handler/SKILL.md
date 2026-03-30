---
name: vps-page-handler
description: Creates a procedural VPS page handler function in src/vps_hdspace.php style. Use when user says 'add page', 'new handler', 'purchase flow', 'billing page', or 'addon page'. Covers get_module_db, get_module_settings, get_service validation, CSRF with $table->csrf()/$verify_csrf(), Repeat_Invoice ORM, prorated billing calc, and myadmin_log calls. Do NOT use for Plugin.php hook methods (getHooks, getRequirements, getAddon, getSettings).
---
# VPS Page Handler

## Critical

- **Never use PDO** — always `$db = get_module_db($module)`
- **Always validate `get_service()` immediately** — return `false` on failure before any other logic
- **Always check service status** (`_status == 'active'`) before allowing mutations
- **CSRF is mandatory** on every form: `$table->csrf('action_name')` on render, `verify_csrf('action_name')` on submit
- **Escape user input** via `$db->real_escape()` or cast to `(int)` — never interpolate `$_GET`/`$_POST` raw
- **Log every mutation step** with `myadmin_log($module, 'info', $msg, __LINE__, __FILE__)`

## Instructions

1. **Create the page function** in `src/vps_hdspace.php`. The function name must match the page slug registered in `Plugin::getRequirements()` via `$loader->add_page_requirement()`.

   ```php
   function vps_myaction()
   {
       $module = 'vps';
       $settings = get_module_settings($module);
       page_title('My Action Title');
       $db  = get_module_db($module);
       $db2 = clone $db; // second connection for nested queries
   ```

2. **Read and validate the service ID** from request globals, then fetch and gate on service:

   ```php
       $id = (int)$GLOBALS['tf']->variables->request['id'];
       $serviceInfo = get_service($id, $module);
       if ($serviceInfo === false) {
           dialog('Error', 'Invalid ID Passed');
           return false;
       }
       if ($serviceInfo[$settings['PREFIX'].'_status'] != 'active') {
           dialog('Error!', 'Service is not active!');
           return false;
       }
   ```

   Verify `$serviceInfo` is populated before accessing `$settings['PREFIX']` keys.

3. **Set up the TFTable form**, handle iframe context, and add the hidden service ID:

   ```php
       $table = new TFTable();
       if (mb_strpos($_SERVER['PHP_SELF'], 'iframe.php') !== false) {
           $table->set_post_location('iframe.php');
       }
       $table->add_hidden('id', $serviceInfo[$settings['PREFIX'].'_id']);
       $table->set_title('Action Title');
   ```

4. **Load existing Repeat_Invoice** (if the addon may already be purchased):

   ```php
       $repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
       $repeat_invoices = $repeat_invoice->find([['description','like','Additional % GB Space for VPS '.$serviceInfo['vps_id']]]);
       if (count($repeat_invoices) > 0) {
           $repeat_invoice->load_real($repeat_invoices[0]);
           $rinv = $repeat_invoice->get_raw_row();
       }
   ```

5. **Calculate prorated billing cost** using the service invoice date:

   ```php
       $costInfo = get_service_cost($serviceInfo, $module);
       $frequency = $costInfo['frequency'];
       $cost = round($unitCost * $quantity * $frequency, 2);

       $service_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
       $service_invoice = $service_invoice->load_real($serviceInfo[$settings['PREFIX'].'_invoice']);
       if ($service_invoice->loaded === true) {
           $service_date = $db->fromTimestamp($service_invoice->get_date());
           $curday      = date('d');
           $oday        = date('d', $service_date);
           $diffday     = abs($curday - $oday);
           $daysinmonth = date('t');
           $daycost     = $cost / $daysinmonth;
           $diffcost    = number_format(($daysinmonth - $diffday) * $daycost, 2);
           $new_date    = date('Y-m').'-'.date('d', $service_date).' 01:01:01';
       } else {
           $diffcost = $cost;
           $new_date = date('Y-m-d 01:01:01');
       }
       if ($frequency > 1) {
           $diffcost = round($diffcost + ($cost * ($frequency - 1)), 2);
       }
   ```

6. **Branch on confirm parameter** — render form or process submission:

   ```php
       if (!isset($GLOBALS['tf']->variables->request['confirm'])
           || $GLOBALS['tf']->variables->request['confirm'] != 'yes') {
           // --- RENDER FORM ---
           $table->csrf('my_action_name');
           // add fields...
           $table->add_field('Confirm', 'r');
           $table->add_field('<select name=confirm><option value=no>No</option><option value=yes>Yes</option></select>', 'r');
           $table->add_row();
           $table->set_colspan(2);
           $table->add_field($table->make_submit('Continue'));
           $table->add_row();
           add_output($table->get_table());
       } elseif (verify_csrf('my_action_name')) {
           // --- PROCESS SUBMISSION ---
           myadmin_log($module, 'info', 'My Action Called', __LINE__, __FILE__);
           myadmin_log($module, 'info', '  ID: '.$serviceInfo[$settings['PREFIX'].'_id'], __LINE__, __FILE__);
   ```

7. **Create or update Repeat_Invoice and generate Invoice** on submission:

   ```php
           // New purchase:
           $repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
           $repeat_invoice->setId(null)
               ->setDescription($description)
               ->setType(1)
               ->setCost($cost)
               ->setCustid($serviceInfo[$settings['PREFIX'].'_custid'])
               ->setFrequency($frequency)
               ->setDate($new_date)
               ->setModule($module)
               ->setService($id)
               ->save();
           $rid = $repeat_invoice->get_id();

           if ($diffcost > 0) {
               $invoice = $repeat_invoice->invoice($new_date, (float)$diffcost, false);
               $iid = $invoice->get_id();
               myadmin_log($module, 'info', "Created Invoice {$iid} for {$diffcost}", __LINE__, __FILE__);
               add_output('Invoice created. Please pay to activate.');
           } else {
               $GLOBALS['tf']->history->add($module.'queue', $serviceInfo[$settings['PREFIX'].'_id'], 'update_hdsize', '', $serviceInfo[$settings['PREFIX'].'_custid']);
           }
       } // end elseif verify_csrf
   } // end function
   ```

8. **Register the page** in `Plugin::getRequirements()` (this step uses the function name from Step 1):

   ```php
   $loader->add_page_requirement('vps_myaction', '/../vendor/detain/myadmin-hd-vps-addon/src/vps_myaction.php');
   ```

   Verify the slug and file path match exactly.

## Examples

**User says:** "Add a purchase flow for additional IP addresses on a VPS"

**Actions taken:**
1. Create `src/vps_extra_ip.php` with function `vps_extra_ip()`
2. Set `$module = 'vps'`, get settings/db, read `(int)$GLOBALS['tf']->variables->request['id']`
3. Call `get_service($id, $module)` → return false + `dialog()` on invalid
4. Check `$serviceInfo['vps_status'] == 'active'` → dialog on inactive
5. Build `TFTable`, call `$table->csrf('extra_ip')` on render
6. On submit, call `verify_csrf('extra_ip')`, log with `myadmin_log('vps', 'info', ..., __LINE__, __FILE__)`, create `\MyAdmin\Orm\Repeat_Invoice`, call `->invoice()` for prorated amount
7. Register in `Plugin::getRequirements()`: `$loader->add_page_requirement('vps_extra_ip', '/../vendor/detain/myadmin-hd-vps-addon/src/vps_extra_ip.php')`

**Result:** A function matching the `vps_hdspace()` pattern exactly, with CSRF, ORM billing, and logging.

## Common Issues

- **`get_service()` returns `false` unexpectedly:** The `id` request var is missing or not an integer. Confirm `(int)$GLOBALS['tf']->variables->request['id']` is non-zero before calling `get_service()`.
- **CSRF verification always fails:** Token name in `$table->csrf('name')` must exactly match the string passed to `verify_csrf('name')`. A mismatch silently fails — nothing is processed.
- **`$repeat_invoice->loaded` is `null` not `true`:** `load_real()` returns the object but sets `->loaded = true` only on success. Always check `=== true` (strict), not truthy.
- **Prorated cost is negative:** `bcsub()` reduced `$diffcost` below zero from paid invoice credits. Gate with `if ($diffcost > 0)` before calling `->invoice()`, and queue the size update instead.
- **`save()` inserts duplicate repeat invoices:** Call `->setId(null)` before `->save()` for new records; omit `setId` only when updating an existing loaded row.
- **`myadmin_log` call drops silently:** The function requires 5 args: `($module, $level, $message, __LINE__, __FILE__)`. Missing `__LINE__`/`__FILE__` causes a PHP warning and no log entry.
