<?php

namespace Tests\Unit;

use App\Support\WordDiff;
use PHPUnit\Framework\TestCase;

class WordDiffTest extends TestCase
{
    public function test_identical_text_has_no_markup(): void
    {
        $html = WordDiff::toHtml('opens at 9am', 'opens at 9am')->toHtml();

        $this->assertStringNotContainsString('<del', $html);
        $this->assertStringNotContainsString('<ins', $html);
        $this->assertStringContainsString('opens at 9am', $html);
    }

    public function test_pure_insertion(): void
    {
        $html = WordDiff::toHtml('opens at 9am', 'opens at 9am on weekdays')->toHtml();

        $this->assertStringContainsString('<ins', $html);
        $this->assertStringNotContainsString('<del', $html);
        $this->assertStringContainsString('weekdays', $html);
    }

    public function test_pure_deletion(): void
    {
        $html = WordDiff::toHtml('opens at 9am on weekdays', 'opens at 9am')->toHtml();

        $this->assertStringContainsString('<del', $html);
        $this->assertStringNotContainsString('<ins', $html);
    }

    public function test_mixed_edit_marks_both_sides(): void
    {
        $html = WordDiff::toHtml('opens at 9am on weekdays', 'opens at 8am Monday to Friday')->toHtml();

        // "9am"/"weekdays" removed, "8am"/"Monday to Friday" added; "opens at" stays.
        $this->assertStringContainsString('<del', $html);
        $this->assertStringContainsString('<ins', $html);
        $this->assertStringContainsString('opens at', $html);
        $this->assertStringContainsString('8am', $html);
    }

    public function test_null_and_empty_inputs(): void
    {
        $this->assertSame('', WordDiff::toHtml(null, null)->toHtml());
        $this->assertSame('', WordDiff::toHtml('', '')->toHtml());

        // All-new content when there's no prior text.
        $addOnly = WordDiff::toHtml(null, 'brand new')->toHtml();
        $this->assertStringContainsString('<ins', $addOnly);
        $this->assertStringNotContainsString('<del', $addOnly);
    }

    public function test_html_in_content_is_escaped(): void
    {
        $html = WordDiff::toHtml('hello <b>world</b>', 'hello <script>alert(1)</script>')->toHtml();

        // No raw tags from the content survive — only our own <del>/<ins> wrappers.
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }
}
