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
use Monarc\Core\Entity\DeliveriesModels;
use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Service\DeliveriesModelsService;
use Monarc\Core\Table\DeliveriesModelsTable;
use Monarc\Core\Validator\InputValidator\DeliveryModel\DeliveryModelDataInputValidator;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use RuntimeException;
use ZipArchive;

class DeliveryModelTestEnvironment
{
    public EntityManager $manager;
    public DeliveriesModelsTable $table;
    public DeliveriesModelsService $service;
    public DeliveryModelDataInputValidator $validator;
    public string $directory;
    private string|false $previousDirectory;

    public function __construct(ConnectedUserService $identity)
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('DeliveryModelTestProxies');
        $this->manager = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        (new SchemaTool($this->manager))->createSchema([$this->manager->getClassMetadata(DeliveriesModels::class)]);
        $this->table = new class ($this->manager) extends DeliveriesModelsTable {
            public bool $failSave = false;
            public bool $failRemove = false;

            public function save(object $entity, bool $flushAll = true): void
            {
                if ($this->failSave) {
                    throw new RuntimeException('Simulated persistence failure');
                }
                parent::save($entity, $flushAll);
            }

            public function remove(object $entity, bool $flushAll = true): void
            {
                if ($this->failRemove) {
                    throw new RuntimeException('Simulated removal failure');
                }
                parent::remove($entity, $flushAll);
            }
        };
        $this->service = new class ($this->table, $identity) extends DeliveriesModelsService {
            public int $failMove = 0;
            private int $moves = 0;

            // Unit fixtures are local files, not SAPI uploads. Runtime probes use the real mover.
            public function moveTmpFile(array $tmpFile, $destinationPath, $filename): string
            {
                if (++$this->moves === $this->failMove) {
                    throw new RuntimeException('Simulated move failure');
                }
                $path = $destinationPath . '/' . $filename;
                if (!rename($tmpFile['tmp_name'], $path)) {
                    throw new RuntimeException('Fixture move failed');
                }

                return $path;
            }
        };
        $this->validator = new DeliveryModelDataInputValidator([], new InputValidationTranslator($identity, []));
        $this->directory = sys_get_temp_dir() . '/php84-deliveries-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0775, true);
        $this->previousDirectory = getenv('APP_CONF_DIR');
        putenv('APP_CONF_DIR=' . $this->directory);
    }

    public function upload(string $text = 'Template'): array
    {
        $path = $this->directory . '/' . bin2hex(random_bytes(8)) . '.docx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<Types/>');
        $zip->addFromString('word/document.xml', '<document>' . $text . '</document>');
        $zip->setCompressionName('word/document.xml', ZipArchive::CM_STORE);
        $zip->close();

        return [
            'tmp_name' => $path,
            'name' => 'template.docx',
            'size' => filesize($path),
            'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    public function validate(array $data, bool $partial = false): array
    {
        $this->validator->resetValidation();
        if (!$this->validator->isValid($data, $partial)) {
            throw new RuntimeException(json_encode($this->validator->getErrorMessages(), JSON_THROW_ON_ERROR));
        }

        return $this->validator->getValidData();
    }

    public function close(): void
    {
        $this->manager->close();
        putenv($this->previousDirectory === false ? 'APP_CONF_DIR' : 'APP_CONF_DIR=' . $this->previousDirectory);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }
}
