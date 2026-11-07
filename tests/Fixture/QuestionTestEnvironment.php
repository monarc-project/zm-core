<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\QuestionService;
use Monarc\Core\Service\QuestionChoiceService;
use Monarc\Core\Table\AbstractTable;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use Monarc\Core\Validator\InputValidator\Question\QuestionDataInputValidator;
use Monarc\Core\Validator\InputValidator\Question\QuestionChoiceDataInputValidator;
use Monarc\FrontOffice\Validator\InputValidator\Question\QuestionDataInputValidator as AnrQuestionValidator;

class QuestionTestEnvironment
{
    public EntityManager $manager;
    public AbstractTable $questions;
    public AbstractTable $choices;
    public QuestionService|\Monarc\FrontOffice\Service\AnrQuestionService $service;
    public QuestionChoiceService $choiceService;
    public QuestionDataInputValidator|AnrQuestionValidator $validator;
    public QuestionDataInputValidator $definitionValidator;
    public QuestionChoiceDataInputValidator $choiceValidator;

    public function __construct(public ConnectedUserService $identity, bool $analysis = false)
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [__DIR__ . '/../../src/Entity']));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Php84QuestionTestProxies');
        $config->setAutoGenerateProxyClasses(true);
        $this->manager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $namespace = $analysis ? 'Monarc\\FrontOffice' : 'Monarc\\Core';
        $metadata = array_map(
            fn ($name) => $this->manager->getClassMetadata($namespace . '\\Entity\\' . $name),
            ['Question', 'QuestionChoice']
        );
        foreach ((new SchemaTool($this->manager))->getCreateSchemaSql($metadata) as $sql) {
            $sql = preg_replace('/^(CREATE INDEX) (\w+) ON (\w+)/', '$1 $3_$2 ON $3', $sql);
            $this->manager->getConnection()->executeStatement($sql);
        }
        $questionTable = $namespace . '\\Table\\QuestionTable';
        $choiceTable = $namespace . '\\Table\\QuestionChoiceTable';
        $this->questions = new $questionTable($this->manager);
        $this->choices = new $choiceTable($this->manager);
        $translator = new InputValidationTranslator($identity, []);
        $this->definitionValidator = new QuestionDataInputValidator([], $translator);
        $this->validator = $analysis
            ? new AnrQuestionValidator([], $translator)
            : $this->definitionValidator;
        $this->choiceValidator = new QuestionChoiceDataInputValidator([], $translator);
        $service = $namespace . '\\Service\\' . ($analysis ? 'Anr' : '') . 'QuestionService';
        $this->service = new $service($this->questions, $this->choices, $identity);
        if (!$analysis) {
            $this->choiceService = new QuestionChoiceService($this->choices, $this->questions, $identity);
        }
    }

    public function analysis(int $id): \Monarc\FrontOffice\Entity\Anr
    {
        $anr = new \Monarc\FrontOffice\Entity\Anr();
        (new \ReflectionProperty($anr, 'id'))->setValue($anr, $id);
        $anr->setLanguage(3);
        $this->manager->getUnitOfWork()->registerManaged($anr, ['id' => $id], []);
        return $anr;
    }
}
