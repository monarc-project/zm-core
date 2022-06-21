<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\Theme;
use Monarc\Core\Table\ThemeTable;

class ThemeService
{
    private ThemeTable $themeTable;

    private ConnectedUserService $connectedUserService;

    public function __construct(ThemeTable $themeTable, ConnectedUserService $connectedUserService)
    {
        $this->themeTable = $themeTable;
        $this->connectedUserService = $connectedUserService;
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];

        /** @var Theme[] $themes */
        $themes = $this->themeTable->findByParams($params);
        foreach ($themes as $theme) {
            $result[] = $this->prepareThemeDataResult($theme);
        }

        return $result;
    }

    public function getThemeData(int $id): array
    {
        /** @var Theme $theme */
        $theme = $this->themeTable->findById($id);

        return $this->prepareThemeDataResult($theme);
    }

    public function create(array $data, bool $saveInDb = true): Theme
    {
        $theme = (new Theme())
            ->setLabels($data)
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());

        $this->themeTable->save($theme, $saveInDb);

        return $theme;
    }

    public function update(int $id, array $data): void
    {
        /** @var Theme $theme */
        $theme = $this->themeTable->findById($id);

        $theme->setLabels($data);

        $this->themeTable->save($theme);
    }

    public function delete(int $id): void
    {
        /** @var Theme $theme */
        $theme = $this->themeTable->findById($id);

        $this->themeTable->remove($theme);
    }

    private function prepareThemeDataResult(Theme $theme): array
    {
        return [
            'id' => $theme->getId(),
            'label1' => $theme->getLabel(1),
            'label2' => $theme->getLabel(2),
            'label3' => $theme->getLabel(3),
            'label4' => $theme->getLabel(4),
        ];
    }
}
