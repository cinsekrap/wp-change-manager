<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Configuration held eleven items in one flat list, ordered by when each was
 * built, split by a divider that marked where super-admin-only began. Permission
 * is not what somebody looking for a setting is thinking about.
 */
class UiNavigationTest extends TestCase
{
    private function menu(): string
    {
        $html = $this->get(route('admin.dashboard'))->assertSuccessful()->getContent();
        $start = strpos($html, 'id="configMenu"');

        return substr($html, $start, strpos($html, 'id="userDropdown"') - $start);
    }

    public function test_configuration_is_grouped_by_subject(): void
    {
        $this->loginAsAdmin();
        $menu = $this->menu();

        foreach (['Content', 'People', 'System'] as $group) {
            $this->assertStringContainsString($group, $menu, "The '{$group}' group is missing");
        }

        // Each group holds what its name says.
        $this->assertLessThan(strpos($menu, 'People'), strpos($menu, 'Sites'));
        $this->assertLessThan(strpos($menu, 'System'), strpos($menu, 'Clinical approvers'));
        $this->assertGreaterThan(strpos($menu, 'System'), strpos($menu, 'Audit log'));
    }

    public function test_an_editor_sees_only_what_they_can_reach(): void
    {
        $this->loginAsAdmin(['role' => 'editor']);
        $menu = $this->menu();

        // Grouping must not have widened anyone's access.
        $this->assertStringContainsString('Sites', $menu);
        $this->assertStringContainsString('Check questions', $menu);

        foreach (['Admins', 'Clinical approvers', 'Funding approvers', 'Sign-in', 'Audit log', 'Import / export'] as $restricted) {
            $this->assertStringNotContainsString($restricted, $menu,
                "An editor can see '{$restricted}'");
        }
    }

    public function test_an_editor_is_not_shown_empty_groups(): void
    {
        $this->loginAsAdmin(['role' => 'editor']);
        $menu = $this->menu();

        // A heading with nothing under it is worse than no heading.
        $this->assertStringNotContainsString('>People<', $menu);
        $this->assertStringNotContainsString('>System<', $menu);
    }

    public function test_every_configuration_page_is_still_reachable(): void
    {
        $this->loginAsAdmin();
        $menu = $this->menu();

        foreach ([
            'admin.sites.index', 'admin.cpts.index', 'admin.questions.index',
            'admin.users.index', 'admin.clinical-approvers.index', 'admin.funding-approvers.index',
            'admin.settings.mail', 'admin.settings.notifications', 'admin.settings.entra',
            'admin.settings.updates', 'admin.audit-log', 'admin.settings.config',
        ] as $route) {
            $this->assertStringContainsString(route($route), $menu,
                "'{$route}' fell out of the menu");
        }
    }
}
