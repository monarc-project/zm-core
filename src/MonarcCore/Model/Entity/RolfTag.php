<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Thme
 *
 * @ORM\Table(name="rolf_tags", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class RolfTag extends RolfTagSuperclass
{
    /**
     * @var \MonarcCore\Model\Entity\RolfRisk
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\RolfRisk", mappedBy="tags", cascade={"persist"})
     */
    protected $risks;
}

