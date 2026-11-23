<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Unit\DeliveryModel;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\SchemaValidator;
use Monarc\Core\Entity\DeliveriesModels;
use Monarc\Core\Entity\User;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\DeliveryModel\GetDeliveryModelsInputFormatter;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\ConfigService;
use Monarc\Core\Service\DeliveriesModelsService;
use Monarc\Core\Tests\Fixture\DeliveryModelTestEnvironment;
use Monarc\Core\Validator\InputValidator\DeliveryModel\DeliveryModelDataInputValidator;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/** @coversNothing */
class DeliveryModelWorkflowTest extends TestCase
{
    private DeliveryModelTestEnvironment $env;

    private ConnectedUserService $connectedUserService;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../Fixture/DeliveryModelTestEnvironment.php';
        $this->connectedUserService = $this->createMock(ConnectedUserService::class);
        $this->connectedUserService->method('getConnectedUser')->willReturn(new User([
            'firstname' => 'Editor',
            'lastname' => 'Templates',
            'email' => 'editor@example.invalid',
            'language' => 2,
            'role' => [],
            'creator' => 'test',
        ]));
        $this->env = new DeliveryModelTestEnvironment($this->connectedUserService);
    }

    protected function tearDown(): void
    {
        $this->env->close();
    }

    public function testMappingAndCreationKeepGlobalStateNullabilityAndAudits(): void
    {
        $metadata = $this->env->manager->getClassMetadata(DeliveriesModels::class);
        self::assertSame([], (new SchemaValidator($this->env->manager))->validateClass($metadata));
        self::assertFalse($metadata->hasAssociation('anr'));
        $template = $this->create();
        $id = $template->getId();
        $this->env->manager->clear();
        $template = $this->env->table->findById($id);
        self::assertSame(1, $template->getCategory());
        self::assertTrue($template->isEditable());
        self::assertSame('editor@example.invalid', $template->getCreator());
        self::assertNotNull($template->getCreatedAt());
        self::assertNull($template->getUpdatedAt());
        self::assertNull($template->getPath(2));
        self::assertNull($template->getDescriptions()['description2']);
        self::assertSame('  Original  ', $template->getDescription(1));
    }

    public function testPartialReplacementPreservesIdOtherLanguagesAndOmittedMetadata(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('old'), 2 => $this->env->upload('English')]]);
        $oldPath = $this->env->directory . '/' . ltrim($template->getPath(1), './');
        $englishPath = $template->getPath(2);
        $this->env->service->update($template->getId(), $this->env->validate([
            'file' => [1 => $this->env->upload('new')],
            'path2' => '/untrusted',
        ], true));
        self::assertFalse(file_exists($oldPath));
        self::assertSame($englishPath, $template->getPath(2));
        self::assertSame('  Original  ', $template->getDescription(1));
        self::assertStringContainsString('new', $this->env->service->getDownload($template->getId(), 1)['contents']);
        self::assertSame(1, $this->env->table->countAll());
        self::assertSame('editor@example.invalid', $template->getUpdater());
        $this->env->service->update($template->getId(), $this->env->validate([
            'description1' => null,
            'description2' => '',
            'editable' => '0',
        ], true));
        self::assertNull($template->getDescriptions()['description1']);
        self::assertSame('', $template->getDescription(2));
        self::assertFalse($template->isEditable());
    }

    public function testAReplacementCanRepairAMissingStoredFileWithoutChangingTheId(): void
    {
        $template = $this->create();
        $id = $template->getId();
        $template->setPath(['path1' => './missing-template.docx']);
        $this->env->table->save($template);

        $this->env->service->update($id, $this->env->validate([
            'file' => [1 => $this->env->upload('Restored template')],
        ], true));

        self::assertSame($id, $template->getId());
        self::assertSame(1, $this->env->table->countAll());
        self::assertStringContainsString('Restored template', $this->env->service->getDownload($id, 1)['contents']);
    }

    public function testFullCategoryAllowsUnchangedUpdatesButRejectsCreationAndCategoryChanges(): void
    {
        $templates = [];
        for ($i = 0; $i < 5; ++$i) {
            $templates[] = $this->create();
        }
        $template = $this->create(['category' => 2]);
        $this->reject(fn () => $this->create());
        $this->reject(fn () => $this->env->service->update($template->getId(), ['category' => 1]));
        $this->env->service->update($templates[0]->getId(), ['category' => 1, 'description1' => 'Allowed']);
        self::assertSame('Allowed', $templates[0]->getDescription(1));
        self::assertSame(2, $template->getCategory());
    }

    public function testListCountFilteringAndPaginationAgree(): void
    {
        $this->create();
        $this->create();
        $this->create(['category' => 2]);
        $params = (new GetDeliveryModelsInputFormatter())->format(['category' => 1, 'limit' => 1, 'page' => 1]);
        self::assertSame(2, $this->env->service->getCount($params));
        self::assertCount(1, $this->env->service->getList($params));
        $data = $this->env->service->getList($params)[0];
        self::assertSame('', $data['filename1']);
        self::assertSame('', $data['path1']);
        self::assertArrayNotHasKey('anr', $data);
    }

    /** @dataProvider invalidData */
    public function testValidationRejectsInvalidInputsBeforeMutation(array $data): void
    {
        self::assertFalse($this->env->validator->isValid($data));
        self::assertSame(0, $this->env->table->countAll());
    }

    public static function invalidData(): array
    {
        return [
            [[]],
            [['category' => 8]],
            [['category' => 1, 'description1' => ['invalid']]],
            [['category' => 1, 'file' => 'invalid']],
            [['category' => 1, 'file' => [1 => ['error' => UPLOAD_ERR_PARTIAL]]]],
        ];
    }

    public function testRejectedContentDoesNotMoveAnyLanguageOrChangeExistingData(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('old')]]);
        $paths = $template->getPaths();
        $good = $this->env->upload('replacement');
        $bad = $this->env->upload();
        file_put_contents($bad['tmp_name'], 'not a document');
        clearstatcache(true, $bad['tmp_name']);
        $bad['size'] = filesize($bad['tmp_name']);
        self::assertFalse($this->env->validator->isValid(['file' => [1 => $good, 2 => $bad]], true));
        self::assertSame($paths, $template->getPaths());
        self::assertFileExists($good['tmp_name']);
        self::assertStringContainsString('old', $this->env->service->getDownload($template->getId(), 1)['contents']);
    }

    public function testUploadMetadataAndLanguageKeysAreValidatedBeforeFileMoves(): void
    {
        $upload = $this->env->upload();
        $emptyUpload = $this->env->upload();
        file_put_contents($emptyUpload['tmp_name'], '');
        clearstatcache(true, $emptyUpload['tmp_name']);
        foreach ([
            [1 => $emptyUpload],
            [1 => array_replace($upload, ['name' => 'template.php'])],
            [1 => array_replace($upload, ['error' => UPLOAD_ERR_INI_SIZE])],
            [1 => [$upload]],
            [5 => $upload],
        ] as $files) {
            $this->env->validator->resetValidation();
            self::assertFalse($this->env->validator->isValid(['file' => $files], true));
            self::assertFileExists($upload['tmp_name']);
        }
        self::assertSame(0, $this->env->table->countAll());
    }

    public function testUploadsUseOnlyConfiguredDataLanguagesAndPreserveTheirIndexes(): void
    {
        $environment = new DeliveryModelTestEnvironment($this->connectedUserService, [1, 3]);
        try {
            $file = $environment->upload();
            self::assertTrue($environment->validator->isValid(['category' => 1, 'file' => [3 => $file]]));
            self::assertSame([3 => $file], $environment->validator->getValidData()['file']);
            $environment->validator->resetValidation();
            self::assertFalse($environment->validator->isValid(['category' => 1, 'file' => [2 => $file]]));
            self::assertArrayHasKey('file', $environment->validator->getErrorMessages());
            self::assertFileExists($file['tmp_name']);
        } finally {
            $environment->close();
        }
    }

    public function testProductionUploadRulesRejectFilesThatWereNotUploadedThroughHttp(): void
    {
        $validator = new DeliveryModelDataInputValidator(
            ['languages' => ['fr' => ['index' => 1]]],
            new InputValidationTranslator($this->connectedUserService, [])
        );
        $file = $this->env->upload();

        self::assertFalse($validator->isValid(['category' => 1, 'file' => [1 => $file]]));
        self::assertArrayHasKey('file', $validator->getErrorMessages());
        self::assertFileExists($file['tmp_name']);
    }

    public function testDocxPackageRequiresBothContentTypesAndWordDocument(): void
    {
        $file = $this->env->upload();
        $zip = new ZipArchive();
        $zip->open($file['tmp_name']);
        $zip->deleteName('word/document.xml');
        $zip->close();
        clearstatcache(true, $file['tmp_name']);

        self::assertFalse($this->env->validator->isValid(['category' => 1, 'file' => [1 => $file]]));
        self::assertFileExists($file['tmp_name']);
        self::assertSame(0, $this->env->table->countAll());
    }

    public function testLaterMoveFailureRemovesOnlyNewFiles(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('old')]]);
        $paths = $template->getPaths();
        $this->env->service->failMove = 3;
        $this->reject(fn () => $this->env->service->update($template->getId(), [
            'file' => [1 => $this->env->upload('new'), 2 => $this->env->upload('fail')],
        ]), RuntimeException::class);
        self::assertSame($paths, $template->getPaths());
        self::assertCount(1, glob($this->env->directory . '/data/monarc/models/*/*.docx'));
        self::assertStringContainsString('old', $this->env->service->getDownload($template->getId(), 1)['contents']);
    }

    public function testSaveFailureRestoresMetadataAndKeepsOldFile(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('old')]]);
        $paths = $template->getPaths();
        $this->env->table->failSave = true;
        $this->reject(fn () => $this->env->service->update($template->getId(), [
            'description1' => 'Changed',
            'file' => [1 => $this->env->upload('new')],
        ]), RuntimeException::class);
        self::assertSame($paths, $template->getPaths());
        self::assertSame('  Original  ', $template->getDescription(1));
        self::assertCount(1, glob($this->env->directory . '/data/monarc/models/*/*.docx'));
        self::assertStringContainsString('old', $this->env->service->getDownload($template->getId(), 1)['contents']);
    }

    public function testUnavailableUploadDirectoryDoesNotChangeTheExistingTemplate(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('old')]]);
        $paths = $template->getPaths();
        file_put_contents($this->env->directory . '/data/monarc/models/EN', 'not a directory');
        $this->reject(fn () => $this->env->service->update($template->getId(), [
            'file' => [1 => $this->env->upload('new'), 2 => $this->env->upload('blocked')],
        ]));
        self::assertSame($paths, $template->getPaths());
        self::assertCount(1, glob($this->env->directory . '/data/monarc/models/*/*.docx'));
        self::assertStringContainsString('old', $this->env->service->getDownload($template->getId(), 1)['contents']);
    }

    public function testCreateFailureCleansNewFiles(): void
    {
        $this->env->table->failSave = true;
        $this->reject(fn () => $this->create(['file' => [1 => $this->env->upload()]]), RuntimeException::class);
        self::assertSame([], glob($this->env->directory . '/data/monarc/models/*/*.docx'));
        self::assertSame(0, $this->env->table->countAll());
    }

    public function testDeleteFailureRestoresFilesThenSuccessfulDeleteRemovesThem(): void
    {
        $template = $this->create(['file' => [1 => $this->env->upload('keep')]]);
        $this->env->table->failRemove = true;
        $this->reject(fn () => $this->env->service->delete($template->getId()), RuntimeException::class);
        self::assertStringContainsString('keep', $this->env->service->getDownload($template->getId(), 1)['contents']);
        self::assertSame(1, $this->env->table->countAll());
        $this->env->table->failRemove = false;
        $this->env->service->delete($template->getId());
        self::assertSame(0, $this->env->table->countAll());
        self::assertSame([], glob($this->env->directory . '/data/monarc/models/*/*'));
    }

    public function testMissingFilesFailDownloadDeleteAndFallbackWithoutDiagnostics(): void
    {
        $template = $this->create();
        $id = $template->getId();
        $this->reject(fn () => $this->env->service->getDownload($id, 1));
        $this->reject(fn () => $this->env->service->getDownload($id, 99));
        $this->reject(fn () => $this->env->service->getGenerationPath($id, 1, 1));
        $template->setPath(['path1' => './missing.docx']);
        $this->env->table->save($template);
        $this->reject(fn () => $this->env->service->delete($id));
        self::assertSame(1, $this->env->table->countAll());
        $this->reject(fn () => $this->env->service->getDeliveryModelData(999), EntityNotFoundException::class);
    }

    /** @dataProvider defaultLanguages */
    public function testGenerationUsesSelectedLanguageThenConfiguredDefaultInSameCategory(
        int $defaultLanguageIndex,
        int $missingLanguageIndex
    ): void {
        $service = new DeliveriesModelsService(
            $this->env->table,
            $this->connectedUserService,
            new ConfigService(['defaultLanguageIndex' => $defaultLanguageIndex])
        );
        $selected = $this->create(['file' => [4 => $this->env->upload('Selected')]]);
        $default = $this->create([
            'editable' => 0,
            'file' => [$defaultLanguageIndex => $this->env->upload('Default')],
        ]);
        $path = $service->getGenerationPath($selected->getId(), 4, 1);
        self::assertSame(basename($selected->getPath(4)), basename($path));
        $path = $service->getGenerationPath($selected->getId(), $missingLanguageIndex, 1);
        self::assertSame(basename($default->getPath($defaultLanguageIndex)), basename($path));
        $this->reject(fn () => $service->getGenerationPath($selected->getId(), $missingLanguageIndex, 2));
    }

    public static function defaultLanguages(): array
    {
        return [
            'French' => [1, 3],
            'English' => [2, 3],
            'German' => [3, 1],
            'Dutch' => [4, 3],
        ];
    }

    public function testMissingDefaultLanguageDoesNotFallBackToEnglish(): void
    {
        $selected = $this->create(['file' => [4 => $this->env->upload('Selected')]]);
        $this->create(['editable' => 0, 'file' => [2 => $this->env->upload('English')]]);

        $this->reject(fn () => $this->env->service->getGenerationPath($selected->getId(), 3, 1));
    }

    private function create(array $data = []): DeliveriesModels
    {
        return $this->env->service->create($this->env->validate(array_replace([
            'category' => 1,
            'description1' => '  Original  ',
        ], $data)));
    }

    private function reject(callable $action, string $class = Exception::class): void
    {
        try {
            $action();
            self::fail('Expected operation to fail');
        } catch (\Exception $error) {
            self::assertInstanceOf($class, $error);
        }
    }
}
