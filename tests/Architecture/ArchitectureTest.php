<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/** T-19: constitution articles I, II and III. */
final class ArchitectureTest extends TestCase
{
    private const ROOT = __DIR__.'/../../app/Library';

    /** Article I: the domain depends on nothing but the PHP standard library. */
    public function test_domain_depends_on_nothing(): void
    {
        $this->assertNoImports('Domain', '/^App\\\\Library\\\\Domain\\\\/', 'el dominio solo puede usar el dominio');
    }

    /** Article II: application only knows the domain; no framework types. */
    public function test_application_does_not_import_adapters_nor_framework(): void
    {
        $this->assertNoImports('Application', '/^App\\\\Library\\\\(Domain|Application)\\\\/', 'la aplicación solo conoce dominio y aplicación');
    }

    /** Article III: adapters are only instantiated in Bootstrap. */
    public function test_adapters_are_only_instantiated_in_bootstrap(): void
    {
        $adapters = array_map(fn ($f) => basename($f, '.php'), $this->files('Adapters/Outbound'));
        $pattern = '/new\s+('.implode('|', $adapters).')\s*\(/';

        foreach (['Domain', 'Application', 'Adapters'] as $layer) {
            foreach ($this->files($layer) as $file) {
                $this->assertDoesNotMatchRegularExpression($pattern, (string) file_get_contents($file),
                    "Artículo III: {$file} instancia un adaptador fuera de Bootstrap");
            }
        }
    }

    private function assertNoImports(string $layer, string $allowed, string $rule): void
    {
        foreach ($this->files($layer) as $file) {
            preg_match_all('/^use\s+([^;\s]+)/m', (string) file_get_contents($file), $m);
            foreach ($m[1] as $import) {
                $isStdLib = ! str_contains($import, '\\');
                $this->assertTrue($isStdLib || preg_match($allowed, $import) === 1,
                    "{$rule}: {$file} importa {$import}");
            }
        }
    }

    /** @return list<string> */
    private function files(string $layer): array
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT.'/'.$layer));
        $files = [];
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }
}
