<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity\Interfaces;

interface PositionedEntityInterface
{
    public function getPosition(): int;

    public function setPosition(int $position): self;

    /**
     * Returns the relation fields names and their values to be used for positioning of the entity.
     * Is used to be able to position the entity based on its related entity(ies)' field value(s).
     * example:
     * [
     *  'relationFiledName1' => 'value1',
     *  'relationFiledName2' => 'value2',
     * ]
     */
    public function getImplicitPositionRelationsValues(): array;

    public function getCreator(): string;

    public function getUpdater(): string;

    public function setUpdater(string $updater): self;
}
