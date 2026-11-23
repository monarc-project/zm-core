<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\DeliveryModel;

use Monarc\Core\Entity\DeliveriesModels;
use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetDeliveryModelsInputFormatter extends AbstractInputFormatter
{
    private const ALLOWED_CATEGORIES = [
        DeliveriesModels::MODEL_CONTEXT_VALIDATION,
        DeliveriesModels::MODEL_ASSETS_AND_MODELS_VALIDATION,
        DeliveriesModels::MODEL_RISK_ANALYSIS,
        DeliveriesModels::MODEL_IMPLEMENTATION_PLAN,
        DeliveriesModels::MODEL_STATEMENT_OF_APPLICABILITY,
        DeliveriesModels::MODEL_RECORD_OF_PROCESSING_ACTIVITIES,
        DeliveriesModels::MODEL_ALL_RECORD_OF_PROCESSING_ACTIVITIES,
    ];
    private const ALLOWED_EDITABLE_VALUES = [0, 1];

    protected static array $allowedSearchFields = ['description1', 'description2', 'description3', 'description4'];

    protected static array $allowedFilterFields = [
        'category' => ['type' => 'int', 'inArray' => self::ALLOWED_CATEGORIES],
        'editable' => ['type' => 'int', 'inArray' => self::ALLOWED_EDITABLE_VALUES],
    ];
}
