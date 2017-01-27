<?php

namespace MonarcCore\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Asset
 *
 * @ORM\Table(name="assets", indexes={
 *      @ORM\Index(name="anr_id", columns={"anr_id","code"}),
 *      @ORM\Index(name="anr_id2", columns={"anr_id"})
 * })
 * @ORM\Entity
 */
class Asset extends AssetSuperClass
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     * @ORM\ManyToMany(targetEntity="MonarcCore\Model\Entity\Model", inversedBy="assets", cascade={"persist"})
     * @ORM\JoinTable(name="assets_models",
     *  joinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="model_id", referencedColumnName="id")}
     * )
     */
    protected $models;

    /**
     * @return Model
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * @return Model
     */
    public function getModel($id)
    {
        return $this->models[$id];
    }

    /**
     * @param Model $models
     * @return Asset
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
        $this->models[] = $model;
    }

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

    public function __construct($obj = null)
    {
        $this->models = new ArrayCollection();
        parent::__construct($obj);
    }
}

