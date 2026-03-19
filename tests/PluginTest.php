<?php

declare(strict_types=1);

namespace Detain\MyAdminVpsHd\Tests;

use Detain\MyAdminVpsHd\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the $name static property is set correctly.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('HD Space VPS Addon', Plugin::$name);
    }

    /**
     * Test that the $description static property is set correctly.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertSame(
            'Allows selling of HD Space Upgrades to a VPS.',
            Plugin::$description
        );
    }

    /**
     * Test that the $help static property is an empty string.
     */
    public function testHelpPropertyIsEmpty(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that the $module static property is 'vps'.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Test that the $type static property is 'addon'.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('addon', Plugin::$type);
    }

    /**
     * Test that the class has exactly five static properties.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProperties = $this->reflection->getStaticProperties();
        $this->assertCount(5, $staticProperties);
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains the expected hook keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('vps.load_addons', $hooks);
        $this->assertArrayHasKey('vps.settings', $hooks);
    }

    /**
     * Test that getHooks returns exactly three hooks.
     */
    public function testGetHooksReturnsThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Test that each hook value is a callable array referencing the Plugin class.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $key => $value) {
            $this->assertIsArray($value, "Hook '$key' value should be an array");
            $this->assertCount(2, $value, "Hook '$key' should have two elements");
            $this->assertSame(Plugin::class, $value[0], "Hook '$key' should reference Plugin class");
            $this->assertIsString($value[1], "Hook '$key' method name should be a string");
        }
    }

    /**
     * Test that the function.requirements hook points to getRequirements.
     */
    public function testRequirementsHookMethod(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getRequirements', $hooks['function.requirements'][1]);
    }

    /**
     * Test that the vps.load_addons hook points to getAddon.
     */
    public function testLoadAddonsHookMethod(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getAddon', $hooks['vps.load_addons'][1]);
    }

    /**
     * Test that the vps.settings hook points to getSettings.
     */
    public function testSettingsHookMethod(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getSettings', $hooks['vps.settings'][1]);
    }

    /**
     * Test that hook keys use the module property value.
     */
    public function testHookKeysUseModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;
        $this->assertArrayHasKey($module . '.load_addons', $hooks);
        $this->assertArrayHasKey($module . '.settings', $hooks);
    }

    /**
     * Test that the getRequirements method exists and is static.
     */
    public function testGetRequirementsMethodIsStatic(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that the getAddon method exists and is static.
     */
    public function testGetAddonMethodIsStatic(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that the doEnable method exists and is static.
     */
    public function testDoEnableMethodIsStatic(): void
    {
        $method = $this->reflection->getMethod('doEnable');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that the doDisable method exists and is static.
     */
    public function testDoDisableMethodIsStatic(): void
    {
        $method = $this->reflection->getMethod('doDisable');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that the getSettings method exists and is static.
     */
    public function testGetSettingsMethodIsStatic(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test the getRequirements method signature accepts GenericEvent.
     */
    public function testGetRequirementsAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test the getAddon method signature accepts GenericEvent.
     */
    public function testGetAddonAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test the getSettings method signature accepts GenericEvent.
     */
    public function testGetSettingsAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Test the doEnable method signature has correct parameters.
     */
    public function testDoEnableMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doEnable');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Test the doDisable method signature has correct parameters.
     */
    public function testDoDisableMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doDisable');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Test that the constructor has no required parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that the class is in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminVpsHd', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the class is not an interface.
     */
    public function testClassIsNotInterface(): void
    {
        $this->assertFalse($this->reflection->isInterface());
    }

    /**
     * Test that all hook methods listed in getHooks exist on the class.
     */
    public function testAllHookMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $hookName => $callable) {
            $this->assertTrue(
                $this->reflection->hasMethod($callable[1]),
                "Method {$callable[1]} referenced by hook '$hookName' should exist"
            );
        }
    }

    /**
     * Test that the class has the expected number of public methods.
     */
    public function testPublicMethodCount(): void
    {
        $publicMethods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        // __construct, getHooks, getRequirements, getAddon, doEnable, doDisable, getSettings
        $this->assertCount(7, $publicMethods);
    }

    /**
     * Test that the module property value is used in the load_addons hook key.
     */
    public function testModulePropertyDrivesHookKeys(): void
    {
        $originalModule = Plugin::$module;
        $hooks = Plugin::getHooks();

        $this->assertArrayHasKey($originalModule . '.load_addons', $hooks);
        $this->assertArrayHasKey($originalModule . '.settings', $hooks);
    }

    /**
     * Test that the vps_hdspace.php file exists alongside Plugin.php.
     */
    public function testVpsHdspaceFileExists(): void
    {
        $pluginFile = $this->reflection->getFileName();
        $this->assertNotFalse($pluginFile);
        $hdspaceFile = dirname($pluginFile) . DIRECTORY_SEPARATOR . 'vps_hdspace.php';
        $this->assertFileExists($hdspaceFile);
    }
}
