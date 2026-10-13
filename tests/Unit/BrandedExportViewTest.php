<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;

class BrandedExportViewTest extends TestCase
{
    public function test_print_unit_identifiers_compile_to_valid_php(): void
    {
        $blade = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
        $source = file_get_contents(__DIR__ . '/../../resources/views/admin/partials/print-unit-identifiers.blade.php');
        $compiled = $blade->compileString($source);

        // TOKEN_PARSE raises ParseError for malformed PHP, including unmatched endif.
        $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
    }

    public function test_the_branded_export_partial_compiles_without_a_parse_error(): void
    {
        $blade = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
        $source = file_get_contents(__DIR__ . '/../../resources/views/admin/reports/partials/branded-export.blade.php');

        $compiled = $blade->compileString($source);

        $this->assertStringContainsString('json_encode($exportCompanyData', $compiled);
    }

    public function test_the_production_edit_impact_modal_compiles_without_a_parse_error(): void
    {
        $blade = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
        $source = file_get_contents(__DIR__ . '/../../resources/views/admin/production/edit.blade.php');

        $compiled = $blade->compileString($source);

        $this->assertStringContainsString('identifierImpactModal', $compiled);
        $this->assertStringContainsString('propagation_targets[]', $compiled);
    }
}
