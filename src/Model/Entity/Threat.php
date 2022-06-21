<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
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
     * @var Model[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Model", inversedBy="threats", cascade={"persist"})
     * @ORM\JoinTable(name="threats_models",
     *  joinColumns={@ORM\JoinColumn(name="threat_id", referencedColumnName="uuid")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="model_id", referencedColumnName="id")}
     * )
     */
    protected $models;

    public function __construct()
    {
        $this->models = new ArrayCollection();
    }

    public function getModels()
    {
        return $this->models;
    }

    public function addModel(Model $model): self
    {
        if (!$this->models->contains($model)) {
            $this->models->add($model);
            $model->addThreat($this);
        }

        return $this;
    }

    public function removeModel(Model $model): self
    {
        if ($this->models->contains($model)) {
            $this->models->removeElement($model);
            $model->removeThreat($this);
        }

        return $this;
    }

    public function unlinkModels(): self
    {
        foreach ($this->models as $model) {
            $this->models->removeElement($model);
            $model->removeThreat($this);
        }

        return $this;
    }
}
