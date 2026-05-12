<?php declare(strict_types=1);

namespace Unit\InputFormatter\ReassessmentTrigger;

use Doctrine\Common\Collections\Criteria;
use Monarc\Core\InputFormatter\ReassessmentTrigger\GetReassessmentTriggersInputFormatter;
use PHPUnit\Framework\TestCase;

class GetReassessmentTriggersInputFormatterTest extends TestCase
{
    /**
     * @covers \Monarc\Core\InputFormatter\ReassessmentTrigger\GetReassessmentTriggersInputFormatter
     */
    public function testFormatAddsDefaultOrderingWithoutStatusFilter(): void
    {
        $formatter = new GetReassessmentTriggersInputFormatter();

        $formattedParams = $formatter->format([]);

        self::assertSame(Criteria::ASC, $formattedParams->getOrder()['position']);
        self::assertSame(Criteria::ASC, $formattedParams->getOrder()['id']);
        self::assertFalse($formattedParams->hasFilterFor('isActive'));
    }

    /**
     * @covers \Monarc\Core\InputFormatter\ReassessmentTrigger\GetReassessmentTriggersInputFormatter
     */
    public function testFormatConvertsStatusAliasAndPassesAnrFilter(): void
    {
        $formatter = new GetReassessmentTriggersInputFormatter();
        $anr = new \stdClass();

        $formattedParams = $formatter->format([
            'anr' => $anr,
            'status' => 'active',
        ]);

        self::assertSame($anr, $formattedParams->getFilterFor('anr')['value']);
        self::assertSame(true, $formattedParams->getFilterFor('isActive')['value']);
    }

    /**
     * @covers \Monarc\Core\InputFormatter\ReassessmentTrigger\GetReassessmentTriggersInputFormatter
     */
    public function testFormatIgnoresAllStatusAndFormatsSearch(): void
    {
        $formatter = new GetReassessmentTriggersInputFormatter();

        $formattedParams = $formatter->format([
            'filter' => 'incident',
            'status' => 'all',
        ]);

        self::assertSame('incident', $formattedParams->getSearch()['string']);
        self::assertFalse($formattedParams->hasFilterFor('isActive'));
    }
}
