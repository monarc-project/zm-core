<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator;

use Laminas\I18n\Translator\Resources;
use Laminas\I18n\Translator\Translator;
use Laminas\Validator\Translator\TranslatorInterface;
use Monarc\Core\Service\ConnectedUserService;

class InputValidationTranslator extends Translator implements TranslatorInterface
{
    public function __construct(ConnectedUserService $connectedUserService, array $config)
    {
        $this->addTranslationFilePattern('phpArray', Resources::getBasePath(), Resources::getPatternForValidator())
            ->addTranslationFilePattern(
                'phpArray',
                __DIR__ . '/../../../locale/languages/',
                '%s/validation_messages.php'
            );

        if ($connectedUserService->getConnectedUser() !== null) {
            $availableUiLanguages = isset($config['languages'])
                ? array_keys($config['languages'])
                : ['fr', 'en', 'de', 'nl'];
            $this->setLocale($availableUiLanguages[$connectedUserService->getConnectedUser()->getLanguage() - 1]);
        }
    }
}
