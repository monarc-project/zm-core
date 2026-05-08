<?php declare(strict_types=1);

namespace Unit\InputFormatter\RiskSource;

use Doctrine\Common\Collections\Criteria;
use Monarc\Core\InputFormatter\RiskSource\GetRiskSourcesInputFormatter;
use PHPUnit\Framework\TestCase;

class GetRiskSourcesInputFormatterTest extends TestCase
{
    /**
     * @covers \Monarc\Core\InputFormatter\RiskSource\GetRiskSourcesInputFormatter
     */
    public function testFormatAddsDefaultStatusAndOrdering(): void
    {
        $formatter = new GetRiskSourcesInputFormatter();

        $formattedParams = $formatter->format([]);

        self::assertSame(true, $formattedParams->getFilterFor('isActive')['value']);
        self::assertSame(Criteria::DESC, $formattedParams->getOrder()['isDefault']);
        self::assertSame(Criteria::ASC, $formattedParams->getOrder()['label']);
    }

    /**
     * @covers \Monarc\Core\InputFormatter\RiskSource\GetRiskSourcesInputFormatter
     */
    public function testFormatIgnoresAllStatusAndFormatsSearch(): void
    {
        $formatter = new GetRiskSourcesInputFormatter();

        $formattedParams = $formatter->format([
            'filter' => 'cloud',
            'status' => 'all',
        ]);

        self::assertSame('cloud', $formattedParams->getSearch()['string']);
        self::assertFalse($formattedParams->hasFilterFor('isActive'));
    }

    /**
     * @covers \Monarc\Core\InputFormatter\RiskSource\GetRiskSourcesInputFormatter
     */
    public function testFormatConvertsActiveStatusAliasAndPassesAnrFilter(): void
    {
        $formatter = new GetRiskSourcesInputFormatter();
        $anr = new \stdClass();

        $formattedParams = $formatter->format([
            'anr' => $anr,
            'status' => 'active',
        ]);

        self::assertSame($anr, $formattedParams->getFilterFor('anr')['value']);
        self::assertSame(true, $formattedParams->getFilterFor('isActive')['value']);
    }
}
