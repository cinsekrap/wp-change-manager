<?php

namespace Tests\Unit;

use App\Support\ReadingAge;
use PHPUnit\Framework\TestCase;

class ReadingAgeTest extends TestCase
{
    private string $simple;
    private string $complex;

    protected function setUp(): void
    {
        parent::setUp();
        // Same meaning, both >= 30 words; the second is deliberately more formal.
        $this->simple = 'If your child is not going to come to school you must let us know. '
            . 'Call the office before nine in the morning. Tell us why they are not coming. '
            . 'If they are ill, say what is wrong so we can help you and keep them safe.';
        $this->complex = 'In the event that your child will fail to attend their education setting, '
            . 'you are required to notify the establishment accordingly. Please telephone the '
            . 'administrative office prior to the commencement of the school day. Communicate the '
            . 'rationale for the non-attendance so appropriate safeguarding measures may be undertaken.';
    }

    public function test_short_text_is_not_scored(): void
    {
        $this->assertNull(ReadingAge::grade('Do not go to school.'));
        $this->assertNull(ReadingAge::grade(''));
        $this->assertNull(ReadingAge::grade(null));
    }

    public function test_long_text_scores_an_age(): void
    {
        $age = ReadingAge::grade($this->simple);
        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(5, $age);
    }

    public function test_more_formal_wording_scores_higher(): void
    {
        $this->assertGreaterThan(ReadingAge::grade($this->simple), ReadingAge::grade($this->complex));
    }

    public function test_increase_detected_only_when_updated_reads_older(): void
    {
        $up = ReadingAge::increase($this->simple, $this->complex);
        $this->assertNotNull($up);
        $this->assertSame(ReadingAge::grade($this->simple), $up['from']);
        $this->assertSame(ReadingAge::grade($this->complex), $up['to']);

        // No increase when it gets simpler, or when either side is too short to score.
        $this->assertNull(ReadingAge::increase($this->complex, $this->simple));
        $this->assertNull(ReadingAge::increase('too short', $this->complex));
    }

    public function test_increase_uses_a_lower_floor_than_absolute_grade(): void
    {
        // ~15-word passages: below the 30-word grade() floor, but the increase
        // comparison scores from 10 words, so a wordier reword is still caught.
        $current = 'Do not come to school if you are ill. Just let us know today.';
        $updated = 'Please refrain from attending the educational establishment should you be experiencing illness; notify administration.';

        $this->assertNull(ReadingAge::grade($current));            // too short for the absolute grade
        $this->assertNotNull(ReadingAge::increase($current, $updated)); // but the comparison still flags it
    }
}
