MONARC Core project
===================


Development notes concerning the following:

* Entity
* Table
* Controller
* Service
* Container configuration in module.config.php
* Tests Coverage

Entity
------

Creation of entities should be performed as a simple object instantiation, e.g.

    $myEntityClass = (new MyEntityClass())
        ->setName('name')
        ->setCode('codde');

The entity should have a single responsibility and do not perform any database related operations itself. 

`Note!` The `AbstractEntity` inheritance is going to be removed and the entities filters methods cleaned uo.  


For generating migrating file & migrate DB with adding/deleting/changing column:

	php ./vendor/bin/doctrine-module migrations:diff
	php ./vendor/bin/doctrine-module migrations:migrate


Entity Table
------------

Implementation of table (repositories) classes are done in Model/Table folder and extend `AbstractTable`.

In the table constructor there is mandatory to pass an entity class name that the table is responsible to manage, e.g. 

    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, \MyEntityNamespace\MyEntityClass::class);
    }

The table methods, responsible for fetching data from the DB should start from `findBy` prefix. 
AbstractTable has methods, which help with the basic entities operations. 

* save
* remove
* and so on.

In the module config file there is required to define an way of the table class creation.
In most of the cases it works well with `Laminas\Di\Container\AutowireFactory`:

    \MyEntityNamespace\MyEntityClass::class => AutowireFactory::class,


Controller
----------

Controller should extend `Laminas\Mvc\Controller\AbstractRestfulController`.

Restful application methods to be defined:

* getList()
* get($id)
* create($data)
* update($id, $data)
* delete($id)

In `module.config.php`, controllers are usually defined in the factories container like:

    ControllerNameSpace\MyController::class => AutowireFactory::class,


Service
-------

`Note` New created services classes should not extend `AbstractService`, is going to be be removed in the future.


In the module config file the services container looks like:  

    ServiceNamescpace\MyService::class => Laminas\Di\Container\AutowireFactory::class,

or, in case if config needs to be injected:

    ServiceNamescpace\MyService::class => Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,


Input Formatters
-------

The input formatters classes aim to perform the [GET] requests data filtering, validating and transforming into a format that can be used directly to prepare the database queries.
The formatters classes are enriched with possibilities of handling and preparing the data for search by text, filter by params, ordering and pagination.

Tha basic classes are `InputFormatter\AbstractInputFormatter`, `InputFormatter\FormattedInputParams`.
The instantiated objects inherited the basic classes are used in the Controllers and the formatted results are passed to the services and to the table classes methods.
The tables' classes, inherited the `Table\AbstractTable` have a certain method `findByParams` where the `FormattedInputParams` object is accepted and processed to generate the query.  

The trait's method `Controller\Handler\ControllerRequestResponseHandlerTrait::getFormattedInputParams` helps in the process.


Validators
-------

The validators classes are responsible to validate the post, put and patch requests' data. 
Every child class of `Validator\InputValidator\AbstractInputValidator` have to describe a set rules that are used to validate the incoming data.  
The validated data can be obtained with use of the methods `getValidData`, `getValidDataSets` if `isValid` result is `true`.

The trait's method `Controller\Handler\ControllerRequestResponseHandlerTrait::validatePostParams` helps in the process.
It can also handle the batch data requests, that are used for the import operations.


Tests Coverage
--------------

The implementation is partially done on MonarcAppFO side, because integration and functional tests should cover the both Core and FrontOffice modules of the MONARC application.

Unit tests can be implemented at a particular projects side.

We might move from the current Core/FrontOffice modules approach to a libraries/responsibility specific and the tests will be moved as well. 


License
-------

This software is licensed under
[GNU Affero General Public License version 3](http://www.gnu.org/licenses/agpl-3.0.html)

- Copyright (C) 2016-2020 Jérôme Lombardi - https://github.com/jerolomb
- Copyright (C) 2016-2020 Juan Rocha - https://github.com/jfrocha
- Copyright (C) 2016-2020 SMILE gie securitymadein.lu
- Copyright (C) 2017-2020 Cédric Bonhomme - https://www.cedricbonhomme.org
- Copyright (C) 2019-2021 Ruslan Baidan
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
