MONARC Core project
===================

*Disclaimer: This is a work in progress and software is still in alpha stage.*

See example repository for create:

* Entity
* Entity Table
* Controller
* Controller Factory
* Service
* Service Factory
* and configure Module.php & module.config.php

Entity
------

Create Entity file & class in Model/Entity folder and extend it whith `AbstractEntity`.

Define `protected` attributes and use [DoctrineOrm][1] for define table & columns.

In `Module.php:getServiceConfig()` add in `invokables`:

	'\MonarcCore\Model\Entity\MyEntity' => '\MonarcCore\Model\Entity\MyEntity',

For generating migrating file & migrate DB avec adding/deleting/changing column:

	php ./vendor/bin/doctrine-module migrations:diff
	php ./vendor/bin/doctrine-module migrations:migrate


Entity Table
------------

Create EntityTable file & class in Model/Table folder and extend it whith `AbstractEntityTable`.

Define your own functions fo loading entities datas from database.
AbstractEntityTable has already functions:

* getDb: return DB object
* fetchAll: return all data for entity
* get: return entity
* save
* delete

In `Module.php:getServiceConfig()` add in `factories`:

	'\MonarcCore\Model\Table\MyEntityTable' => function($sm){
        return new Model\Table\MyEntityTable($sm->get('\MonarcCore\Model\Db'));
    },


Controller
----------

Create Controller file & class in Controller folder and extend it with `AbstractController`.

Adding function:

* getList()
* get($id)
* create($data)
* update($id, $data)
* delete($id)

In `module.config.php`, define route & controller:

	'controller' => '\MonarcCore\Controller\MyIndex',


Controller Factory
------------------

Create Controller Factory file & class in Controller folder and extend it with `AbstractControllerFactory`.

Define `protected $serviceName = '\MonarcCore\Service\MyService';`.

In `Module.php:getControllerConfig()` add in `factories`:

	'\MonarcCore\Controller\MyIndex' => '\MonarcCore\Controller\MyIndexControllerFactory',


Service
-------

Create Service file & class in Service folder and extend it with `AbstractService`.

Define attributes ressources used in this service:

	protected $ressource1;
	protected $ressource2;

And business functions.

For accessing ressource:

	$this->get('ressource1');

Or

	$this->getServiceFactory();


Service Factory
---------------

Create Service file & class in Service Factory folder and extend it with `AbstractServiceFactory`.

Define ressources to load in Service:

	protected $ressources = array(
		'ressource1'=> '\MonarcCore\Model\Table\EntityTable',
		'ressource2'=> '\MonarcCore\Model\Entity\Entity',
	);

Or

	protected $ressources = '\MonarcCore\Model\Table\EntityTable';

In `Module.php:getServiceConfig()` add in `factories`:

	'\MonarcCore\Service\MyIndexService' => '\MonarcCore\Service\MyIndexServiceFactory',

License
-------

This software is licensed under [GNU Affero General Public License version 3](http://www.gnu.org/licenses/agpl-3.0.html)

Copyright (C) 2016-2017 SMILE gie securitymadein.lu

[^1]: https://stevenwilliamalexander.wordpress.com/2013/09/25/zf2-restful-api-example/
[^2]: https://www.youtube.com/watch?v=CGEDNMzWoFk
[^3]: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html

[1]: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html
