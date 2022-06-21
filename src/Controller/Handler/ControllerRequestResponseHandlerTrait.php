<?php declare(strict_types=1);

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
                $params = array_merge(['anr' => $anr], $params);
            }
        }

        return $inputFormatter->format($params);
    }

    protected function validatePostParams(AbstractInputValidator $inputValidator, array $params): void
    {
        $this->validateInstance();

        if ($this instanceof RequestHandlerInterface) {
            /** @var AnrSuperClass|null $anr */
            $anr = $this->getRequest()->getAttribute('anr');
            if ($anr !== null && method_exists($anr, 'getLanguage')) {
                $inputValidator->setLanguageIndex($anr->getLanguage());
            }
        }

        if (!$inputValidator->isValid($params)) {
            throw new Exception('Data validation errors: [ ' . json_encode($inputValidator->getErrorMessages()), 412);
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
     * Validates if the request contains batch of data.
     */
    protected function isBatchDataRequest(array $data): bool
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
