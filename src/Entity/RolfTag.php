<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Thme
 *
 * @ORM\Table(name="rolf_tags", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class RolfTag extends RolfTagSuperClass
{
    /**
     * @var \Monarc\Core\Entity\RolfRisk
     *
     * @ORM\ManyToMany(targetEntity="Monarc\Core\Entity\RolfRisk", mappedBy="tags", cascade={"persist"})
     */
    protected $risks;
}
