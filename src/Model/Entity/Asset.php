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
     * @var Model[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="Model", inversedBy="assets", cascade={"persist"})
     * @ORM\JoinTable(name="assets_models",
     *  joinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="model_id", referencedColumnName="id")}
     * )
     */
    protected $models;

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

    public function addModel(Model $model): self
    {
        if (!$this->models->contains($model)) {
            $this->models->add($model);
            $model->addAsset($this);
        }

        return $this;
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
