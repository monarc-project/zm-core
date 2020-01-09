MONARC Core project
===================


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

	'\Monarc\Core\Model\Entity\MyEntity' => '\Monarc\Core\Model\Entity\MyEntity',

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

	'\Monarc\Core\Model\Table\MyEntityTable' => function($sm){
        return new Model\Table\MyEntityTable($sm->get('\Monarc\Core\Model\Db'));
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

	'controller' => '\Monarc\Core\Controller\MyIndex',


Controller Factory
------------------

Create Controller Factory file & class in Controller folder and extend it with `AbstractControllerFactory`.

Define `protected $serviceName = '\Monarc\Core\Service\MyService';`.

In `Module.php:getControllerConfig()` add in `factories`:

	'\Monarc\Core\Controller\MyIndex' => '\Monarc\Core\Controller\MyIndexControllerFactory',


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
		'ressource1'=> '\Monarc\Core\Model\Table\EntityTable',
		'ressource2'=> '\Monarc\Core\Model\Entity\Entity',
	);

Or

	protected $ressources = '\Monarc\Core\Model\Table\EntityTable';

In `Module.php:getServiceConfig()` add in `factories`:

	'\Monarc\Core\Service\MyIndexService' => '\Monarc\Core\Service\MyIndexServiceFactory',


License
-------

This software is licensed under
[GNU Affero General Public License version 3](http://www.gnu.org/licenses/agpl-3.0.html)

- Copyright (C) 2016-2020 Jérôme Lombardi - https://github.com/jerolomb
- Copyright (C) 2016-2020 Juan Rocha - https://github.com/jfrocha
- Copyright (C) 2016-2020 SMILE gie securitymadein.lu
- Copyright (C) 2017-2020 Cédric Bonhomme - https://www.cedricbonhomme.org
- Copyright (C) 2016-2017 Guillaume Lesniak
- Copyright (C) 2016-2017 Thomas Metois
- Copyright (C) 2016-2017 Jérôme De Almeida

For more information, [the list of authors and contributors](AUTHORS) is available.

Disclaimer: This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.



[^1]: https://stevenwilliamalexander.wordpress.com/2013/09/25/zf2-restful-api-example/
[^2]: https://www.youtube.com/watch?v=CGEDNMzWoFk
[^3]: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html

[1]: http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html
