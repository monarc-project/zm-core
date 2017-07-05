<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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

