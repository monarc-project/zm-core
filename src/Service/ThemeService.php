<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity\Theme;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\ThemeTable;

class ThemeService
{
    private UserSuperClass $connectedUser;

    public function __construct(private ThemeTable $themeTable, ConnectedUserService $connectedUserService)
    {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];
        /** @var Theme $theme */
        foreach ($this->themeTable->findByParams($params) as $theme) {
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
        /** @var Theme $theme */
        $theme = (new Theme())
            ->setLabels($data)
            ->setCreator($this->connectedUser->getEmail());

        $this->themeTable->save($theme, $saveInDb);

        return $theme;
    }

    public function update(int $id, array $data): void
    {
        /** @var Theme $theme */
        $theme = $this->themeTable->findById($id);

        $theme->setLabels($data)
            ->setUpdater($this->connectedUser->getEmail());

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
        return array_merge(['id' => $theme->getId()], $theme->getLabels());
    }
}
