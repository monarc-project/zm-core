<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Model\JsonModel;
use LogicException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Psr\Http\Server\RequestHandlerInterface;

trait ControllerRequestResponseHandlerTrait
{
    protected function getFormattedInputParams(AbstractInputFormatter $inputFormatter): FormattedInputParams
    {
        $this->validateInstance();

        $params = $this->params()->fromQuery();
        if ($this instanceof RequestHandlerInterface) {
            /** @var AnrSuperClass|null $anr */
            $anr = $this->getRequest()->getAttribute('anr');
            if ($anr !== null) {
                $params = array_merge($params, ['anr' => $anr]);
                if (method_exists($anr, 'getLanguage')) {
                    $inputFormatter->setDefaultLanguageIndex($anr->getLanguage());
                }
            }
        }

        return $inputFormatter->format($params);
    }

    protected function validatePostParams(
        AbstractInputValidator $inputValidator,
        array $params,
        bool $isBatchData = false
    ): void {
        $this->validateInstance();

        if ($this instanceof RequestHandlerInterface) {
            /** @var AnrSuperClass|null $anr */
            $anr = $this->getRequest()->getAttribute('anr');
            if ($anr !== null && method_exists($anr, 'getLanguage')) {
                $inputValidator->setDefaultLanguageIndex($anr->getLanguage());
            }
        }

        $paramsSets = $isBatchData ? $params : [$params];
        foreach ($paramsSets as $rowNum => $paramsSet) {
            if (!$inputValidator->isValid($paramsSet)) {
                throw new Exception(sprintf(
                    'Data validation errors %s: [ %s ]',
                    $isBatchData ? '(row number "' . ($rowNum + 1) . '") ' : '',
                    json_encode($inputValidator->getErrorMessages(), JSON_THROW_ON_ERROR)
                ), 412);
            }
        }
    }

    /**
     * @return JsonModel|JsonResponse
     */
    protected function getPreparedJsonResponse(array $responseData): object
    {
        if ($this instanceof RequestHandlerInterface) {
            return new JsonResponse($responseData);
        }

        return new JsonModel($responseData);
    }

    /**
     * @return JsonModel|JsonResponse
     */
    protected function getSuccessfulJsonResponse(array $responseData = []): object
    {
        return $this->getPreparedJsonResponse(array_merge(['status' => 'ok'], $responseData));
    }

    /**
     * Validates if there is a batch of data.
     */
    protected function isBatchData(array $data): bool
    {
        return array_keys($data) === range(0, \count($data) - 1);
    }

    private function validateInstance(): void
    {
        if (!($this instanceof AbstractController)) {
            throw new LogicException(
                'Getting query params in only possible for instance of class "' . AbstractController::class . '"'
            );
        }
    }
}
