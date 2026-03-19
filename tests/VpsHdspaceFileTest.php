<?php

declare(strict_types=1);

namespace Detain\MyAdminVpsHd\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Static analysis tests for the vps_hdspace.php file.
 */
class VpsHdspaceFileTest extends TestCase
{
    /**
     * @var string
     */
    private string $filePath;

    /**
     * @var string
     */
    private string $fileContents;

    protected function setUp(): void
    {
        $this->filePath = dirname(__DIR__) . '/src/vps_hdspace.php';
        $this->fileContents = file_get_contents($this->filePath);
    }

    /**
     * Test that the vps_hdspace.php file exists.
     */
    public function testFileExists(): void
    {
        $this->assertFileExists($this->filePath);
    }

    /**
     * Test that the file defines the vps_hdspace function.
     */
    public function testDefinesVpsHdspaceFunction(): void
    {
        $this->assertStringContainsString('function vps_hdspace()', $this->fileContents);
    }

    /**
     * Test that the file starts with a PHP opening tag.
     */
    public function testStartsWithPhpTag(): void
    {
        $this->assertStringStartsWith('<?php', $this->fileContents);
    }

    /**
     * Test that the file has a docblock header.
     */
    public function testFileHasDocblock(): void
    {
        $this->assertStringContainsString('@author', $this->fileContents);
        $this->assertStringContainsString('@package', $this->fileContents);
    }

    /**
     * Test that the function references the vps module.
     */
    public function testFunctionUsesVpsModule(): void
    {
        $this->assertStringContainsString("\$module = 'vps'", $this->fileContents);
    }

    /**
     * Test that the function calls get_module_settings.
     */
    public function testFunctionCallsGetModuleSettings(): void
    {
        $this->assertStringContainsString('get_module_settings($module)', $this->fileContents);
    }

    /**
     * Test that the function calls get_module_db.
     */
    public function testFunctionCallsGetModuleDb(): void
    {
        $this->assertStringContainsString('get_module_db($module)', $this->fileContents);
    }

    /**
     * Test that the function sets the page title.
     */
    public function testFunctionSetsPageTitle(): void
    {
        $this->assertStringContainsString("page_title('Purchase Additional VPS HD Space')", $this->fileContents);
    }

    /**
     * Test that the function performs CSRF verification.
     */
    public function testFunctionUsesCsrf(): void
    {
        $this->assertStringContainsString("csrf('additional_hd')", $this->fileContents);
        $this->assertStringContainsString("verify_csrf('additional_hd')", $this->fileContents);
    }

    /**
     * Test that the function validates VPS status.
     */
    public function testFunctionValidatesVpsStatus(): void
    {
        $this->assertStringContainsString("'_status'] != 'active'", $this->fileContents);
    }

    /**
     * Test that the function handles invalid VPS IDs.
     */
    public function testFunctionHandlesInvalidVps(): void
    {
        $this->assertStringContainsString('Invalid VPS', $this->fileContents);
        $this->assertStringContainsString('Invalid VPS ID Passed', $this->fileContents);
    }

    /**
     * Test that the function validates size range (1-100).
     */
    public function testFunctionValidatesSizeRange(): void
    {
        $this->assertStringContainsString('$size >= 1 && $size <= 100', $this->fileContents);
    }

    /**
     * Test that the function references the Repeat_Invoice ORM class.
     */
    public function testFunctionUsesRepeatInvoiceOrm(): void
    {
        $this->assertStringContainsString('\\MyAdmin\\Orm\\Repeat_Invoice', $this->fileContents);
    }

    /**
     * Test that the function references the Invoice ORM class.
     */
    public function testFunctionUsesInvoiceOrm(): void
    {
        $this->assertStringContainsString('\\MyAdmin\\Orm\\Invoice', $this->fileContents);
    }

    /**
     * Test that the function uses myadmin_log for logging.
     */
    public function testFunctionUsesMyadminLog(): void
    {
        $this->assertStringContainsString("myadmin_log('vps'", $this->fileContents);
    }

    /**
     * Test that the function uses the VPS_HD_COST constant.
     */
    public function testFunctionUsesVpsHdCostConstant(): void
    {
        $this->assertStringContainsString('VPS_HD_COST', $this->fileContents);
    }

    /**
     * Test that the function handles iframe context.
     */
    public function testFunctionHandlesIframeContext(): void
    {
        $this->assertStringContainsString('iframe.php', $this->fileContents);
    }

    /**
     * Test that the file does not contain merge conflict markers.
     */
    public function testNoMergeConflictMarkers(): void
    {
        $this->assertStringNotContainsString('<<<<<<', $this->fileContents);
        $this->assertStringNotContainsString('>>>>>>', $this->fileContents);
    }

    /**
     * Test that the function checks for disk usage info in extras.
     */
    public function testFunctionChecksDiskUsage(): void
    {
        $this->assertStringContainsString('diskused', $this->fileContents);
        $this->assertStringContainsString('diskmax', $this->fileContents);
    }

    /**
     * Test that the function handles the no-change scenario.
     */
    public function testFunctionHandlesNoChange(): void
    {
        $this->assertStringContainsString('No Change Made, Size The Same', $this->fileContents);
    }

    /**
     * Test that the function handles invalid size.
     */
    public function testFunctionHandlesInvalidSize(): void
    {
        $this->assertStringContainsString('Invalid Size Specified', $this->fileContents);
    }

    /**
     * Test that the function return type is documented.
     */
    public function testFunctionReturnTypeDocumented(): void
    {
        $this->assertStringContainsString('@return', $this->fileContents);
    }

    /**
     * Test that the function documents throwable exceptions.
     */
    public function testFunctionDocumentsExceptions(): void
    {
        $this->assertStringContainsString('@throws', $this->fileContents);
    }
}
