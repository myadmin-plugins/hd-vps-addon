---
name: phpunit-addon-test
description: Writes PHPUnit 9 tests for Plugin.php or vps_hdspace.php following patterns in tests/PluginTest.php and tests/VpsHdspaceFileTest.php. Use when user says 'add test', 'write test', or 'test coverage'. Uses ReflectionClass for static method/property assertions and assertStringContainsString for file-content checks — no mocking of MyAdmin globals or internals. Do NOT use for integration tests that call MyAdmin functions at runtime.
---
# phpunit-addon-test

## Critical

- **Never mock MyAdmin internals** (`get_module_db`, `get_module_settings`, `myadmin_log`, `AddonHandler`, etc.) — these are not available in the test environment. Test structure and static shape via `ReflectionClass` instead.
- **Never call hook methods at runtime** (`Plugin::getRequirements($event)`, `Plugin::getAddon($event)`) — they depend on MyAdmin globals that don't exist in tests.
- All test classes use `declare(strict_types=1)` and namespace `Detain\MyAdminVpsHd\Tests`.
- Run tests with: `vendor/bin/phpunit tests/ -v`

## Instructions

### Testing `src/Plugin.php` → `tests/PluginTest.php`

1. **Scaffold the test class** — extend `PHPUnit\Framework\TestCase`, import `ReflectionClass` and `Symfony\Component\EventDispatcher\GenericEvent`:
   ```php
   <?php
   declare(strict_types=1);
   namespace Detain\MyAdminVpsHd\Tests;
   use Detain\MyAdminVpsHd\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   class PluginTest extends TestCase {
       private ReflectionClass $reflection;
       protected function setUp(): void {
           $this->reflection = new ReflectionClass(Plugin::class);
       }
   }
   ```
   Verify `Plugin` autoloads via Composer before proceeding.

2. **Assert static properties** — test each `public static` property by value:
   ```php
   public function testNameProperty(): void {
       $this->assertSame('HD Space VPS Addon', Plugin::$name);
   }
   // Repeat for $description, $help, $module ('vps'), $type ('addon')
   public function testStaticPropertyCount(): void {
       $this->assertCount(5, $this->reflection->getStaticProperties());
   }
   ```

3. **Assert `getHooks()` shape** — verify keys, count, and callable structure:
   ```php
   public function testGetHooksContainsExpectedKeys(): void {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey('function.requirements', $hooks);
       $this->assertArrayHasKey('vps.load_addons', $hooks);   // uses Plugin::$module
       $this->assertArrayHasKey('vps.settings', $hooks);
       $this->assertCount(3, $hooks);
   }
   public function testGetHooksValuesAreCallableArrays(): void {
       foreach (Plugin::getHooks() as $key => $value) {
           $this->assertIsArray($value);
           $this->assertCount(2, $value);
           $this->assertSame(Plugin::class, $value[0]);
           $this->assertIsString($value[1]);
       }
   }
   ```
   Verify that each hook method name (`getRequirements`, `getAddon`, `getSettings`) matches `$hooks['function.requirements'][1]` etc.

4. **Assert method signatures via Reflection** — for every hook method and `doEnable`/`doDisable`:
   ```php
   public function testGetRequirementsMethodIsStatic(): void {
       $method = $this->reflection->getMethod('getRequirements');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
   }
   public function testGetRequirementsAcceptsGenericEvent(): void {
       $params = $this->reflection->getMethod('getRequirements')->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   // doEnable/doDisable: assertCount(3, $params), names: serviceOrder, repeatInvoiceId, regexMatch
   // regexMatch must have default value false: $params[2]->getDefaultValue() === false
   ```

5. **Assert public method count and namespace:**
   ```php
   public function testPublicMethodCount(): void {
       // __construct, getHooks, getRequirements, getAddon, doEnable, doDisable, getSettings
       $this->assertCount(7, $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC));
   }
   public function testClassNamespace(): void {
       $this->assertSame('Detain\\MyAdminVpsHd', $this->reflection->getNamespaceName());
   }
   ```

### Testing `src/vps_hdspace.php` → `tests/VpsHdspaceFileTest.php`

6. **Scaffold the file-content test class** — load file contents in `setUp()`:
   ```php
   class VpsHdspaceFileTest extends TestCase {
       private string $filePath;
       private string $fileContents;
       protected function setUp(): void {
           $this->filePath = dirname(__DIR__) . '/src/vps_hdspace.php';
           $this->fileContents = file_get_contents($this->filePath);
       }
   }
   ```

7. **Assert structural strings** — use `assertStringContainsString` for every required pattern:
   ```php
   // Function definition
   $this->assertStringContainsString('function vps_hdspace()', $this->fileContents);
   // Module assignment
   $this->assertStringContainsString("\$module = 'vps'", $this->fileContents);
   // Required helper calls
   $this->assertStringContainsString('get_module_settings($module)', $this->fileContents);
   $this->assertStringContainsString('get_module_db($module)', $this->fileContents);
   // CSRF (both directions)
   $this->assertStringContainsString("csrf('additional_hd')", $this->fileContents);
   $this->assertStringContainsString("verify_csrf('additional_hd')", $this->fileContents);
   // ORM classes
   $this->assertStringContainsString('\\MyAdmin\\Orm\\Repeat_Invoice', $this->fileContents);
   $this->assertStringContainsString('\\MyAdmin\\Orm\\Invoice', $this->fileContents);
   // Logging
   $this->assertStringContainsString("myadmin_log('vps'", $this->fileContents);
   // Docblock tags
   $this->assertStringContainsString('@author', $this->fileContents);
   $this->assertStringContainsString('@return', $this->fileContents);
   $this->assertStringContainsString('@throws', $this->fileContents);
   // No merge conflicts
   $this->assertStringNotContainsString('<<<<<<', $this->fileContents);
   ```

## Examples

**User says:** "Add tests for a new `getVersion()` static method on Plugin that returns a string."

**Actions taken:**
1. Read `src/Plugin.php` to confirm `getVersion()` exists as `public static`.
2. Add to `tests/PluginTest.php`:
   ```php
   public function testGetVersionMethodIsStatic(): void {
       $method = $this->reflection->getMethod('getVersion');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
   }
   public function testGetVersionReturnsString(): void {
       $this->assertIsString(Plugin::getVersion());
   }
   ```
3. Update `testPublicMethodCount` to `assertCount(8, ...)` since a new public method was added.
4. Run `vendor/bin/phpunit tests/ -v` to confirm green.

## Common Issues

- **`Class 'Detain\MyAdminVpsHd\Plugin' not found`** — Composer autoload isn't built. Run `composer install` then retry.
- **`Call to undefined function get_module_db()`** — A test is calling a hook method at runtime. Use `ReflectionClass` to inspect the method signature instead of invoking it.
- **`assertCount(5, $staticProperties)` fails with 6** — A new static property was added to `Plugin.php`. Update the count assertion to match.
- **`assertCount(7, $publicMethods)` fails** — A method was added or removed. Count methods in `src/Plugin.php` manually: `__construct` + `getHooks` + hook methods + callbacks.
- **`file_get_contents` returns false`** — `dirname(__DIR__)` resolves relative to `tests/`, so `dirname(__DIR__) . '/src/vps_hdspace.php'` must point to `src/`. Confirm with `realpath($this->filePath)` in a debug test.
- **`assertSame(GenericEvent::class, $type->getName())` fails with null** — The parameter has no type hint in source. Add `GenericEvent $event` typehint to the method in `src/Plugin.php`.
