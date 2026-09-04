<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContentTypesConfigTest extends TestCase
{
    public function test_every_content_type_is_complete(): void
    {
        foreach (config('content-types') as $key => $type) {
            $this->assertArrayHasKey('label', $type, "{$key} has no label");
            $this->assertArrayHasKey('help', $type, "{$key} has no help text");
            $this->assertArrayHasKey('tag', $type, "{$key} has no page-type tag");
        }
    }

    public function test_campaign_is_distinct_from_the_announcement_it_used_to_be_folded_into(): void
    {
        $types = config('content-types');

        $this->assertArrayHasKey('campaign', $types);
        $this->assertSame('Latest', $types['campaign']['tag']);
        $this->assertSame('Latest', $types['announcement']['tag']);

        // Both are Latest, but the announcement must no longer claim campaigns
        // or the two options overlap and the choice becomes a coin toss.
        $this->assertStringNotContainsStringIgnoringCase('campaign', $types['announcement']['help']);
        $this->assertStringContainsStringIgnoringCase('campaign', $types['campaign']['help']);
    }

    public function test_the_wizard_offers_every_configured_type(): void
    {
        $response = $this->get('/')->assertSuccessful();

        foreach (config('content-types') as $type) {
            $response->assertSee($type['label']);
        }
    }
}
