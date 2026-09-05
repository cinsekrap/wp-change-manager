<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The admin UI came to have seven sizes of the same button and three card
 * paddings because every screen re-decided them at the call site. These are now
 * component classes in resources/css/app.css, and this fails if a template goes
 * back to spelling one out by hand.
 */
class UiFoundationsTest extends TestCase
{
    /** @return string[] */
    private function views(): array
    {
        $files = [];
        $dir = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($dir as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function offenders(string $pattern, callable $isReal): array
    {
        $found = [];
        foreach ($this->views() as $path) {
            preg_match_all('/class="([^"]*)"/', file_get_contents($path), $matches);
            foreach ($matches[1] as $class) {
                // A class list holding a Blade expression is a conditional state
                // — a toggle or a selected chip — not a static component.
                if (str_contains($class, '{{')) {
                    continue;
                }

                // A variant (hover:, peer-checked:, focus:, md:) says what a
                // thing does in some state, not what it is. Only the unprefixed
                // classes describe the component.
                $base = implode(' ', array_filter(
                    explode(' ', $class),
                    fn ($token) => ! str_contains($token, ':')
                ));

                if (preg_match($pattern, $base) && $isReal($base)) {
                    $found[] = str_replace(resource_path('views').'/', '', $path).': '.$class;
                }
            }
        }

        return array_unique($found);
    }

    public function test_no_template_spells_out_a_button(): void
    {
        // A pill with a burgundy fill or outline is a button. Progress bars and
        // pills that are not buttons have neither.
        $offenders = $this->offenders(
            '/rounded-full/',
            fn ($class) => ! str_contains($class, 'btn')
                && (str_contains($class, 'bg-hcrg-burgundy') && str_contains($class, 'text-white')
                    || (str_contains($class, 'border-hcrg-burgundy') && str_contains($class, 'text-hcrg-burgundy')))
        );

        $this->assertSame([], array_values($offenders),
            "Use .btn with a variant instead:\n".implode("\n", $offenders));
    }

    public function test_no_template_spells_out_a_card(): void
    {
        // Dropdowns and modals use shadow-lg or shadow-xl and are not cards.
        $offenders = $this->offenders(
            '/shadow(?![-\w])/',
            fn ($class) => str_contains($class, 'bg-white') && str_contains($class, 'rounded-lg')
        );

        $this->assertSame([], array_values($offenders),
            "Use .card, with .card-body when it needs padding:\n".implode("\n", $offenders));
    }

    public function test_no_template_spells_out_a_form_input(): void
    {
        $offenders = $this->offenders(
            '/focus:ring-hcrg-burgundy/',
            fn ($class) => ! str_contains($class, 'field-input')
                && str_contains($class, 'rounded-lg')
                && str_contains($class, 'border-gray-300')
        );

        $this->assertSame([], array_values($offenders),
            "Use .field-input:\n".implode("\n", $offenders));
    }

    public function test_the_component_layer_defines_what_the_templates_use(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        foreach ([
            '.btn', '.btn-primary', '.btn-secondary', '.btn-danger', '.btn-quiet', '.btn-sm',
            '.card', '.card-body', '.card-header',
            '.page-title', '.page-lede', '.card-title', '.group-label',
            '.field-label', '.field-help', '.field-input', '.field-error',
            '.empty-state',
        ] as $component) {
            $this->assertMatchesRegularExpression('/\\'.$component.'\s*[{,]/', $css,
                "{$component} is used by templates but not defined");
        }
    }

    public function test_the_admin_pages_still_render(): void
    {
        $this->loginAsAdmin();

        foreach ([
            'admin.dashboard', 'admin.requests.index', 'admin.funding',
            'admin.requests.content.create', 'admin.sites.index', 'admin.users.index',
            'admin.clinical-approvers.index', 'admin.funding-approvers.index',
            'admin.questions.index', 'admin.cpts.index', 'admin.audit-log',
        ] as $route) {
            $this->get(route($route))->assertSuccessful();
        }
    }
}
