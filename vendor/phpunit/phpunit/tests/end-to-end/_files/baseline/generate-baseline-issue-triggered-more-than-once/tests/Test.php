<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TestFixture\Baseline;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Test extends TestCase
{
    /**
     * @return array<string, array{int}>
     */
    public static function provider(): array
    {
        return [
            'first data set'  => [1],
            'second data set' => [2],
        ];
    }

    public function testTriggersTheSameDeprecationThreeTimes(): void
    {
        $source = new Source;

        for ($i = 0; $i < 3; $i++) {
            $source->triggerDeprecation();
        }

        $this->assertTrue(true);
    }

    #[DataProvider('provider')]
    public function testTriggersTheSameDeprecationForEachDataSet(int $value): void
    {
        (new Source)->triggerDeprecation();

        $this->assertGreaterThan(0, $value);
    }

    public function testTriggersADeprecationWithTheSameMessageOnAnotherLine(): void
    {
        (new Source)->triggerDeprecationWithTheSameMessageOnAnotherLine();

        $this->assertTrue(true);
    }
}
