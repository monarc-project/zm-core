<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Threat
 *
 * @ORM\Table(name="threats", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id","code"}),
 *      @ORM\Index(name="anr_id2", columns={"anr_id"}),
 *      @ORM\Index(name="theme_id", columns={"theme_id"})
 * })
 * @ORM\Entity
 */
class Threat extends ThreatSuperClass
{

    /**
     * @var \MonarcCore\Model\Entity\Model
     *
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Model", inversedBy="threats", cascade={"persist"})
     * @ORM\JoinTable(name="threats_models",
     *  joinColumns={@ORM\JoinColumn(name="threat_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="model_id", referencedColumnName="id")}
     * )
     */
    protected $models;

    /**
     * Set model
     *
     * @param key
     * @param Model $model
     */
    public function setModel($id, Model $model)
    {
        $this->models[$id] = $model;
    }

    /**
     * @return Model
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @param Model $models
     * @return Threat
     */
    public function setModels($models)
    {
        $this->models = $models;
        return $this;
    }

    /**
     * Add model
     *
     * @param Model $model
     */
    public function addModel(Model $model)
    {
        $this->models->add($model);
    }

    public function __construct($obj = null)
    {
        $this->models = new ArrayCollection();
        parent::__construct($obj);
    }
}
