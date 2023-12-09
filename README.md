# InitORM

Manage your database with or without abstraction. This library is built on the PHP PDO plugin and is mainly used to build and execute SQL queries.

[![Latest Stable Version](http://poser.pugx.org/initorm/orm/v)](https://packagist.org/packages/initorm/orm) [![Total Downloads](http://poser.pugx.org/initorm/orm/downloads)](https://packagist.org/packages/initorm/orm) [![Latest Unstable Version](http://poser.pugx.org/initorm/orm/v/unstable)](https://packagist.org/packages/initorm/orm) [![License](http://poser.pugx.org/initorm/orm/license)](https://packagist.org/packages/initorm/orm) [![PHP Version Require](http://poser.pugx.org/initorm/orm/require/php)](https://packagist.org/packages/initorm/orm)

## Requirements

- PHP 8.0 and later.
- PHP PDO extension.

## Supported Databases

This library should work correctly in almost any database that uses basic SQL syntax.
Databases supported by PDO and suitable drivers are available at [https://www.php.net/manual/en/pdo.drivers.php](https://www.php.net/manual/en/pdo.drivers.php).

## Installation

```
composer require initorm/orm
```

## Usage

### Model and Entity

Model and Entity; are two common concepts used in database abstraction. To explain these two concepts in the roughest way;

- **Model :** Each model is a class that represents a table in the database.
- **Entity :** Entity is a class that represents a single row of data.

The most basic example of a model class would look like this.

```php
namespace App\Model;

class Posts extends \InitORM\ORM\Model
{

    /**
    * If your model will use a connection other than your global connection, provide connection information.
    * @var array|null <p>Default : NULL</p> 
    */
    protected array $credentials = [
        'dsn'               => '',
        'username'          => 'root',
        'password'          => '',
        'charset'           => 'utf8mb4',
        'collation'         => 'utf8mb4_unicode_ci',
    ];

    /**
     * If not specified, \InitORM\ORM\Entity::class is used by default.
     * 
     * @var string<\InitORM\ORM\Entity>
     */
    protected $entity = \App\Entities\PostEntity::class;

    /**
     * If not specified, the name of your model class is used.
     * 
     * @var string
     */
    protected string $schema = 'posts';

    /**
     * The name of the PRIMARY KEY column.
     * 
     * @var string
     */
    protected string $schemaId = 'id';

    /**
     * Specify FALSE if you want the data to be permanently deleted.
     * 
     * @var bool
     */
    protected bool $useSoftDeletes = true;

    /**
     * Column name to hold the creation time of the data.
     * 
     * @var string|null
     */
    protected ?string $createdField = 'created_at';

    /**
     * The column name to hold the last time the data was updated.
     * 
     * @var string|null
     */
    protected ?string $updatedField = 'updated_at';

    /**
     * Column name to keep deletion time if $useSoftDeletes is active.
     * 
     * @var string|null
     */
    protected ?string $deletedField = 'deleted_at';

    protected bool $readable = true;

    protected bool $writable = true;

    protected bool $deletable = true;

    protected bool $updatable = true;
    
}
```

The most basic example of a entity class would look like this.

```php
namespace App\Entities;

class PostEntity extends \InitORM\ORM\Entity 
{
    /**
     * An example of a getter method for the "post_title" column.
     * 
     * Usage : 
     * echo $entity->post_title;
     */
    public function getPostTitleAttribute($title)
    {
        return strtoupper($title);
    }
    
    /**
     * An example of a setter method for the "post_title" column.
     * 
     * Usage : 
     * $entity->post_title = 'New Post Title';
     */
    public function setPostTitleAttribute($title)
    {
        $this->post_title = strtolower($title);
    }
    
}
```

## Getting Help

If you have questions, concerns, bug reports, etc, please file an issue in this repository's Issue Tracker.

## Getting Involved

> All contributions to this project will be published under the MIT License. By submitting a pull request or filing a bug, issue, or feature request, you are agreeing to comply with this waiver of copyright interest.

There are two primary ways to help:

- Using the issue tracker, and
- Changing the code-base.

### Using the issue tracker

Use the issue tracker to suggest feature requests, report bugs, and ask questions. This is also a great way to connect with the developers of the project as well as others who are interested in this solution.

Use the issue tracker to find ways to contribute. Find a bug or a feature, mention in the issue that you will take on that effort, then follow the Changing the code-base guidance below.

### Changing the code-base

Generally speaking, you should fork this repository, make changes in your own fork, and then submit a pull request. All new code should have associated unit tests that validate implemented features and the presence or lack of defects. Additionally, the code should follow any stylistic and architectural guidelines prescribed by the project. In the absence of such guidelines, mimic the styles and patterns in the existing code-base.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2023 [MIT License](./LICENSE)