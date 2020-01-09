<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

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
     * @var \Monarc\Core\Model\Entity\Model
     *
     * @ORM\ManyToMany(targetEntity="Monarc\Core\Model\Entity\Model", inversedBy="threats", cascade={"persist"})
     * @ORM\JoinTable(name="threats_models",
     *  joinColumns={@ORM\JoinColumn(name="threat_id", referencedColumnName="uuid")},
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
