<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Amv
 *
 * @ORM\Table(name="amvs", indexes={
 *      @ORM\Index(name="anr", columns={"anr_id"}),
 *      @ORM\Index(name="asset", columns={"asset_id"}),
 *      @ORM\Index(name="threat", columns={"threat_id"}),
 *      @ORM\Index(name="vulnerability", columns={"vulnerability_id"}),
 * })
 * @ORM\Entity
 */
class Amv extends AmvSuperclass
{
  public function getInputFilter($partial = false)
  {
      if (!$this->inputFilter) {
          parent::getInputFilter($partial);

          $texts = ['vulnerability', 'asset'];

          foreach ($texts as $text) {
              $this->inputFilter->add(array(
                  'name' => $text,
                  'required' => ($partial) ? false : true,
                  'allow_empty' => false,
                  'filters' => array(
                      array(
                          'name' => 'Digits',
                      ),
                  ),
                  'validators' => array(),
              ));
          }

          $this->inputFilter->add(array(
              'name' => 'threat',
              'required' => ($partial) ? false : true,
              'allow_empty' => false,
              'filters' => array(
                  array(
                      'name' => 'Digits',
                  ),
              ),
              'validators' => array(
                  array(
                      'name' => 'Callback',//'\MonarcCore\Validator\UniqueAMV',
                      'options' => array(
                          'messages' => array(
                              \Zend\Validator\Callback::INVALID_VALUE => 'This AMV link is already used',
                          ),
                          'callback' => function ($value, $context = array()) use ($partial) {
                              if (!$partial) {
                                  $adapter = $this->getDbAdapter();
                                  if (empty($adapter)) {
                                      return false;
                                  } else {
                                      $res = $adapter->getRepository(get_class($this))->createQueryBuilder('a')
                                          ->select(array('a.id'))
                                          ->where(' a.vulnerability = :vulnerability ')
                                          ->andWhere(' a.asset = :asset ')
                                          ->andWhere(' a.threat = :threat ')
                                          ->setParameter(':vulnerability', $context['vulnerability'])
                                          ->setParameter(':threat', $context['threat'])
                                          ->setParameter(':asset', $context['asset']);
                                      if (empty($context['anr'])) {
                                          $res = $res->andWhere(' a.anr IS NULL ');
                                      } else {
                                          $res = $res->andWhere(' a.anr = :anr ')
                                              ->setParameter(':anr', $context['anr']);
                                      }
                                      $res = $res->getQuery()
                                          ->getResult();
                                      $context['id'] = empty($context['id']) ? $this->get('id') : $context['id'];
                                      if (!empty($res) && $context['id'] != $res[0]['id']) {
                                          return false;
                                      }
                                  }
                                  return true;
                              } else {
                                  return true;
                              }
                          },
                      ),
                  ),
              ),
          ));
      }
      return $this->inputFilter;
  }
}
