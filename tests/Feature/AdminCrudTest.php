<?php

namespace Tests\Feature;

use App\Models\ChangeRequest;
use App\Models\CheckQuestion;
use App\Models\CptType;
use App\Models\Site;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    public function test_can_create_site(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('admin.sites.store'), [
            'name' => 'New Site',
            'domain' => 'newsite.com',
            'sitemap_url' => '',
            'is_active' => true,
            'default_approvers' => [],
        ]);

        $response->assertRedirect(route('admin.sites.index'));
        $this->assertDatabaseHas('sites', [
            'name' => 'New Site',
            'domain' => 'newsite.com',
        ]);
    }

    public function test_can_update_site(): void
    {
        $this->loginAsAdmin();

        $site = Site::create([
            'name' => 'Old Name',
            'domain' => 'old.com',
            'is_active' => true,
        ]);

        $response = $this->put(route('admin.sites.update', $site), [
            'name' => 'Updated Name',
            'domain' => 'updated.com',
            'is_active' => true,
            'default_approvers' => [],
        ]);

        $response->assertRedirect(route('admin.sites.index'));
        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Updated Name',
            'domain' => 'updated.com',
        ]);
    }

    public function test_cannot_delete_site_with_requests(): void
    {
        $this->loginAsAdmin();

        $site = Site::create([
            'name' => 'Busy Site',
            'domain' => 'busy.com',
            'is_active' => true,
        ]);

        ChangeRequest::create([
            'reference' => 'WCR-20260327-001',
            'site_id' => $site->id,
            'page_url' => 'https://busy.com/page',
            'cpt_slug' => 'page',
            'status' => 'requested',
            'requester_name' => 'Tester',
            'requester_email' => 'tester@example.com',
        ]);

        $response = $this->delete(route('admin.sites.destroy', $site));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('sites', ['id' => $site->id]);
    }

    public function test_can_create_cpt(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('admin.cpts.store'), [
            'slug' => 'news',
            'name' => 'News Articles',
            'description' => 'News content type',
            'sort_order' => 1,
            'is_active' => true,
            'is_blocked' => false,
            'content_areas' => [],
        ]);

        $response->assertRedirect(route('admin.cpts.index'));
        $this->assertDatabaseHas('cpt_types', [
            'slug' => 'news',
            'name' => 'News Articles',
        ]);
    }

    public function test_can_create_question(): void
    {
        $this->loginAsAdmin();

        $response = $this->post(route('admin.questions.store'), [
            'question_text' => 'Has this been reviewed?',
            'options' => [
                ['label' => 'Yes', 'pass' => true],
                ['label' => 'No', 'pass' => false],
            ],
            'sort_order' => 0,
            'is_active' => true,
            'is_required' => true,
        ]);

        $response->assertRedirect(route('admin.questions.index'));
        $this->assertDatabaseHas('check_questions', [
            'question_text' => 'Has this been reviewed?',
        ]);
    }
}
