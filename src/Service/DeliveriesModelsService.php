<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\DeliveriesModels;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Helper\FileUploadHelperTrait;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Table\DeliveriesModelsTable;
use Throwable;

class DeliveriesModelsService
{
    use FileUploadHelperTrait;

    private const MAX_TEMPLATES_PER_CATEGORY = 5;
    private const LANGUAGE_DIRECTORIES = [1 => 'FR', 2 => 'EN', 3 => 'DE', 4 => 'NE'];

    private UserSuperClass $connectedUser;

    public function __construct(
        private DeliveriesModelsTable $deliveriesModelsTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        return array_map($this->serialize(...), $this->deliveriesModelsTable->findByParams($params));
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->deliveriesModelsTable->countByParams($params);
    }

    public function getDeliveryModelData(int $id): array
    {
        return $this->serialize($this->deliveriesModelsTable->findById($id));
    }

    public function create(array $data): DeliveriesModels
    {
        $this->assertCategoryAvailable($data['category']);
        $deliveryModel = (new DeliveriesModels())
            ->setCategory($data['category'])
            ->setEditable((bool)($data['editable'] ?? true))
            ->setDescriptions($data)
            ->setCreator($this->connectedUser->getEmail());
        $newPaths = $this->storeUploads($data['file'] ?? [], $deliveryModel->getCategory());
        try {
            $deliveryModel->setPath($newPaths);
            $this->deliveriesModelsTable->save($deliveryModel);
        } catch (Throwable $error) {
            $this->removeFiles($newPaths);
            throw $error;
        }

        return $deliveryModel;
    }

    public function update(int $id, array $data): void
    {
        $deliveryModel = $this->deliveriesModelsTable->findById($id);
        if (isset($data['category']) && $data['category'] !== $deliveryModel->getCategory()) {
            $this->assertCategoryAvailable($data['category']);
        }
        $oldPaths = $deliveryModel->getPaths();
        $oldDescriptions = $deliveryModel->getDescriptions();
        $oldCategory = $deliveryModel->getCategory();
        $oldEditable = $deliveryModel->isEditable();
        $oldUpdater = $deliveryModel->getUpdater();
        $replacedPaths = [];
        foreach ($data['file'] ?? [] as $language => $file) {
            $path = $oldPaths['path' . $language];
            if ($path !== null && $path !== '' && is_file($this->resolvePath($path))) {
                $this->assertRemovable($path);
                $replacedPaths[] = $path;
            }
        }
        $newPaths = $this->storeUploads($data['file'] ?? [], $data['category'] ?? $oldCategory);
        try {
            if (isset($data['category'])) {
                $deliveryModel->setCategory($data['category']);
            }
            if (isset($data['editable'])) {
                $deliveryModel->setEditable((bool)$data['editable']);
            }
            $deliveryModel->setPath($newPaths)
                ->setDescriptions($data)
                ->setUpdater($this->connectedUser->getEmail());
            $this->deliveriesModelsTable->save($deliveryModel);
        } catch (Throwable $error) {
            $deliveryModel->setPath($oldPaths)
                ->setDescriptions($oldDescriptions)
                ->setCategory($oldCategory)
                ->setEditable($oldEditable)
                ->setUpdater($oldUpdater);
            $this->removeFiles($newPaths);
            throw $error;
        }
        $this->removeFiles($replacedPaths);
    }

    public function delete(int $id): void
    {
        $deliveryModel = $this->deliveriesModelsTable->findById($id);
        $backups = [];
        try {
            foreach (array_unique($deliveryModel->getPaths()) as $path) {
                if ($path === null || $path === '') {
                    continue;
                }
                $this->assertRemovable($path);
                $absolutePath = $this->resolvePath($path);
                $backup = $absolutePath . '.' . bin2hex(random_bytes(8)) . '.deleted';
                if (!rename($absolutePath, $backup)) {
                    throw new Exception('Document template could not be removed', 412);
                }
                $backups[$absolutePath] = $backup;
            }
            $this->deliveriesModelsTable->remove($deliveryModel);
        } catch (Throwable $error) {
            foreach ($backups as $path => $backup) {
                if (!rename($backup, $path)) {
                    throw new Exception('Document template could not be restored', 412, $error);
                }
            }
            throw $error;
        }
        foreach ($backups as $backup) {
            if (!unlink($backup)) {
                throw new Exception('Document template file could not be removed', 412);
            }
        }
    }

    public function getDownload(int $id, int $language): array
    {
        $deliveryModel = $this->deliveriesModelsTable->findById($id);
        $path = $this->readablePath($deliveryModel->getPath($language));
        if ($path === null) {
            throw new Exception('Document template not found', 404);
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new Exception('Document template could not be read', 412);
        }

        return ['filename' => basename($path), 'contents' => $contents];
    }

    public function getGenerationPath(int $id, int $language, int $category): string
    {
        $deliveryModel = $this->deliveriesModelsTable->findById($id);
        $path = $this->readablePath($deliveryModel->getPath($language));
        if ($path === null) {
            $defaultTemplate = $this->deliveriesModelsTable->findDefaultEnglishTemplate($category);
            $path = $this->readablePath($defaultTemplate?->getPath(2));
        }
        if ($path === null) {
            throw new Exception('Document template not found', 404);
        }

        return $path;
    }

    private function serialize(DeliveriesModels $deliveryModel): array
    {
        $deliveryModelData = array_merge([
            'id' => $deliveryModel->getId(),
            'category' => $deliveryModel->getCategory(),
            'editable' => $deliveryModel->isEditable(),
            'creator' => $deliveryModel->getCreator(),
            'createdAt' => $deliveryModel->getCreatedAt(),
            'updater' => $deliveryModel->getUpdater(),
            'updatedAt' => $deliveryModel->getUpdatedAt(),
        ], $deliveryModel->getDescriptions(), $deliveryModel->getPaths());
        foreach (self::LANGUAGE_DIRECTORIES as $language => $directory) {
            $path = $deliveryModel->getPath($language);
            $exists = $this->readablePath($path) !== null;
            $deliveryModelData['filename' . $language] = $exists ? basename($path) : '';
            $deliveryModelData['path' . $language] = $exists
                ? './api/deliveriesmodels/' . $deliveryModel->getId() . '?lang=' . $language
                : '';
        }

        return $deliveryModelData;
    }

    private function assertCategoryAvailable(int $category): void
    {
        if ($this->deliveriesModelsTable->countForCategory($category) >= self::MAX_TEMPLATES_PER_CATEGORY) {
            throw new Exception('Maximum number of templates reached for this category.', 412);
        }
    }

    private function resolvePath(string $path): string
    {
        return rtrim(getenv('APP_CONF_DIR') ?: '.', '/') . '/' . ltrim($path, './');
    }

    private function readablePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $absolutePath = $this->resolvePath($path);

        return is_file($absolutePath) && is_readable($absolutePath) ? $absolutePath : null;
    }

    private function assertRemovable(string $path): void
    {
        $absolutePath = $this->readablePath($path);
        if ($absolutePath === null || !is_writable($absolutePath) || !is_writable(dirname($absolutePath))) {
            throw new Exception('Document template file is missing or not writable', 412);
        }
    }

    private function storeUploads(array $files, int $category): array
    {
        $newPaths = [];
        try {
            foreach ($files as $language => $file) {
                $relativeDirectory = './data/monarc/models/' . self::LANGUAGE_DIRECTORIES[$language];
                $directory = $this->resolvePath($relativeDirectory);
                $existingParent = $directory;
                while (!file_exists($existingParent)) {
                    $existingParent = dirname($existingParent);
                }
                if (!is_dir($existingParent) || !is_writable($existingParent)) {
                    throw new Exception('Document template directory is not writable', 412);
                }
                if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
                    throw new Exception('Document template directory could not be created', 412);
                }
                $filename = bin2hex(random_bytes(16)) . '_' . $category . '.docx';
                $newPaths['path' . $language] = $relativeDirectory . '/' . $filename;
                $this->moveTmpFile($file, $directory, $filename);
            }
        } catch (Throwable $error) {
            $this->removeFiles($newPaths);
            throw $error;
        }

        return $newPaths;
    }

    private function removeFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $absolutePath = $this->resolvePath($path);
            if (is_file($absolutePath) && !unlink($absolutePath)) {
                throw new Exception('Document template file could not be removed', 412);
            }
        }
    }
}
