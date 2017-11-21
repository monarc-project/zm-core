<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Translation
 *
 * @ORM\Table(name="translations")
 * @ORM\Entity
 */
class Translation extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="fr", type="string", length=255, nullable=true)
     */
    protected $fr;

    /**
     * @var string
     *
     * @ORM\Column(name="en", type="string", length=255, nullable=true)
     */
    protected $en;

    /**
     * @var string
     *
     * @ORM\Column(name="de", type="string", length=255, nullable=true)
     */
    protected $de;

    /**
     * @var string
     *
     * @ORM\Column(name="nl", type="string", length=255, nullable=true)
     */
    protected $nl;
}

