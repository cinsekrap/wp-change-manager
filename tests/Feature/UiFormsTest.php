<?php

namespace Tests\Feature;

use App\Models\Site;
use Tests\TestCase;

/**
 * Every admin form was a stack of white cards with its own idea of where help
 * text went and where the save button lived. They share one layout now: the
 * section name and its explanation beside the fields, and actions in a footer
 * attached to the form.
 */
class UiFormsTest extends TestCase
{
    private const FORMS = [
        'admin.requests.content.create',
        'admin.sites.create',
        'admin.questions.create',
        'admin.users.create',
        'admin.clinical-approvers.create',
        'admin.funding-approvers.create',
    ];

    public function test_every_form_renders(): void
    {
        $this->loginAsAdmin();
        Site::firstOrCreate(['domain' => 'hcrg.test'], ['name' => 'HCRG', 'is_active' => true]);

        foreach (self::FORMS as $route) {
            $this->get(route($route))->assertSuccessful();
        }
    }

    public function test_every_form_uses_the_shared_section_layout(): void
    {
        $views = [
            'admin/requests/create-content', 'admin/sites/form', 'admin/questions/form',
            'admin/users/form', 'admin/clinical-approvers/form', 'admin/funding-approvers/form',
        ];

        $missing = [];
        foreach ($views as $view) {
            $html = file_get_contents(resource_path("views/{$view}.blade.php"));
            if (! str_contains($html, '<x-admin.form-section')) {
                $missing[] = $view;
            }
        }

        $this->assertSame([], $missing,
            'These build their own section layout: '.implode(', ', $missing));
    }

    public function test_every_form_puts_its_actions_in_the_footer(): void
    {
        $views = [
            'admin/requests/create-content', 'admin/sites/form', 'admin/questions/form',
            'admin/users/form', 'admin/clinical-approvers/form', 'admin/funding-approvers/form',
        ];

        $missing = [];
        foreach ($views as $view) {
            $html = file_get_contents(resource_path("views/{$view}.blade.php"));
            if (! str_contains($html, '<x-admin.form-actions')) {
                $missing[] = $view;
            }
        }

        $this->assertSame([], $missing,
            'Save floats loose on these: '.implode(', ', $missing));
    }

    public function test_sections_are_closed(): void
    {
        foreach (glob(resource_path('views/admin/**/*.blade.php')) as $path) {
            $html = file_get_contents($path);
            $this->assertSame(
                substr_count($html, '<x-admin.form-section'),
                substr_count($html, '</x-admin.form-section>'),
                basename($path).' has an unclosed form section'
            );
        }
    }

    public function test_required_fields_are_marked(): void
    {
        $this->loginAsAdmin();

        // Two forms that previously marked nothing.
        foreach (['admin.users.create', 'admin.questions.create'] as $route) {
            $this->get(route($route))
                ->assertSuccessful()
                ->assertSee('text-status-error', false);
        }
    }
}
