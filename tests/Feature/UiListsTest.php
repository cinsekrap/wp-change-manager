<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Eleven index pages were built five ways: three opened with a sentence saying
 * what they held and eight with a bare title, and column padding varied by page.
 * They share one header, one table and one empty state now.
 */
class UiListsTest extends TestCase
{
    /** Every admin list, and the words it should open with. */
    private const LISTS = [
        'admin.requests.index' => 'Everything asked for across all sites',
        'admin.funding' => 'Content that needs hours agreed',
        'admin.sites.index' => 'The websites requests can be made against',
        'admin.cpts.index' => 'The kinds of page each site has',
        'admin.questions.index' => 'Asked before a request is submitted',
        'admin.users.index' => 'People who can sign in',
        'admin.clinical-approvers.index' => 'sign off content as clinically safe',
        'admin.funding-approvers.index' => 'agree to spend content design hours',
        'admin.audit-log' => 'Every change made in the admin',
    ];

    public function test_every_list_says_what_it_holds(): void
    {
        $this->loginAsAdmin();

        foreach (self::LISTS as $route => $lede) {
            $this->get(route($route))
                ->assertSuccessful()
                ->assertSee($lede, false);
        }
    }

    public function test_every_list_uses_the_shared_header(): void
    {
        $missing = [];

        foreach (array_keys(self::LISTS) as $route) {
            $view = $this->viewFor($route);
            if ($view && ! str_contains(file_get_contents($view), '<x-admin.page-header')) {
                $missing[] = basename($view);
            }
        }

        $this->assertSame([], $missing,
            'These lists build their own header: '.implode(', ', $missing));
    }

    private function viewFor(string $route): ?string
    {
        $map = [
            'admin.requests.index' => 'admin/requests/index',
            'admin.funding' => 'admin/funding',
            'admin.sites.index' => 'admin/sites/index',
            'admin.cpts.index' => 'admin/cpts/index',
            'admin.questions.index' => 'admin/questions/index',
            'admin.users.index' => 'admin/users/index',
            'admin.clinical-approvers.index' => 'admin/clinical-approvers/index',
            'admin.funding-approvers.index' => 'admin/funding-approvers/index',
            'admin.audit-log' => 'admin/audit-log/index',
        ];

        return isset($map[$route]) ? resource_path("views/{$map[$route]}.blade.php") : null;
    }

    public function test_no_list_styles_its_own_table_cells(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/admin/**/*.blade.php')) as $path) {
            $html = file_get_contents($path);
            // Padding on a cell means the table is not using the shared style.
            if (preg_match('/<t[hd] class="[^"]*\bpx-\d/', $html)) {
                $offenders[] = basename($path);
            }
        }

        $this->assertSame([], $offenders,
            'These style their own cells instead of using .table: '.implode(', ', $offenders));
    }

    public function test_the_words_on_the_page_match_the_words_in_the_menu(): void
    {
        $this->loginAsAdmin();

        // "CPT Types" and "Admin Users" were database and role jargon.
        $html = $this->get(route('admin.dashboard'))->assertSuccessful()->getContent();

        foreach (['CPT Types', 'Admin Users', 'Change Requests'] as $old) {
            $this->assertStringNotContainsString($old, $html, "The menu still says '{$old}'");
        }
        foreach (['Content types', 'Admins'] as $new) {
            $this->assertStringContainsString($new, $html);
        }
    }
}
