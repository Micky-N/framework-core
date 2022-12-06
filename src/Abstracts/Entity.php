<?php

namespace MkyCore\Abstracts;

use Exception;
use ReflectionClass;
use ReflectionException;
use MkyCore\Annotation\Annotation;
use MkyCore\Annotation\ParamAnnotation;
use MkyCore\Database;

abstract class Entity
{

    /**
     * @throws ReflectionException
     */
    public function __construct(array $donnees = [])
    {
        if ($donnees) {
            $this->hydrate($donnees);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(array $donnees): void
    {
        foreach ($donnees as $key => $donnee) {
            $key = $this->camelize($key);
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($donnee);
            } else {
                $this->relation[$key] = $donnee;
            }
        }
    }

    /**
     * @param string $input
     * @return string
     */
    private function camelize(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function delete(): array
    {
        return $this->getManager()->delete($this);
    }

    /**
     * @throws ReflectionException
     */
    public function getManager(): Manager
    {
        $annotation = (new Annotation($this))->getClassAnnotation('Manager');
        $manager = $annotation->getProperty();
        if ($manager) {
            $manager = new $manager();
        }
        return $manager;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function hasOne(Entity|string $entityRelation, string $foreignKey = '')
    {
        if (is_string($entityRelation)) {
            $entityRelation = new $entityRelation();
        }
        if (!($entityRelation instanceof Entity)) {
            throw new Exception("Must be a instance of MkyCore\Abstract\Entity");
        }
        $manager = $this->getManager();
        $managerRelation = $entityRelation->getManager();
        $preForeignKey = strtolower((new ReflectionClass($entityRelation))->getShortName());
        $table = $manager->getTable();
        $tableRelate = $managerRelation->getTable();
        $primaryKey = $entityRelation->getPrimaryKey();
        $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        return $this->{$preForeignKey} = Database::prepare(
            "
		SELECT {$tableRelate}.*
		FROM {$tableRelate}
		LEFT JOIN {$table}
		ON {$table}.{$foreignKey} = " . $tableRelate . '.' . $primaryKey .
        ' WHERE ' . $table . '.' . $this->getPrimaryKey() . " = :{$this->getPrimaryKey()} LIMIT 1",
            [$this->getPrimaryKey() => $this->{$this->getPrimaryKey()}()],
            get_class($entityRelation),
            true
        );
    }

    /**
     * @return string|null
     * @throws ReflectionException
     */
    public function getPrimaryKey(): string|null
    {
        $annotations = (new Annotation($this))->getPropertiesAnnotations();
        foreach ($annotations as $name => $annotation) {
            if ($column = $annotation->getParam('Column')) {
                return $column->getProperty();
            }
        }
        return null;
    }

    /**
     * Create new record in the relation table
     *
     * @param Entity $entity
     * @param string $foreignKey
     * @return bool|$this
     * @throws ReflectionException
     */
    public function add(Entity $entity, string $foreignKey = ''): bool|Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        $preForeignKey = strtolower((new ReflectionClass($this))->getShortName());
        $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        $entity->{'set' . ucfirst($this->camelize($foreignKey))}($this->{$primaryKey}());
        return $entity->getManager()->save($entity);
    }

    /**
     * @throws ReflectionException
     */
    public function attach(Entity $entity, string $foreignKey = ''): bool|Entity
    {
        $primaryKey = $entity->getPrimaryKey();
        $preForeignKey = strtolower((new ReflectionClass($entity))->getShortName());
        $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        $this->{'set' . ucfirst($this->camelize($foreignKey))}($entity->{$primaryKey}());
        return $this->getManager()->update($this);
    }

    /**
     * @throws ReflectionException
     */
    public function update(): Entity|bool
    {
        return $this->getManager()->update($this);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function attachOnPivot(Entity $entityRelation, array $options = [], string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): bool|Entity
    {
        $preForeignKeyOne = strtolower((new ReflectionClass($this))->getShortName());
        $preForeignKeyTwo = strtolower((new ReflectionClass($entityRelation))->getShortName());
        $primaryKeyOne = $this->getPrimaryKey();
        $primaryKeyTwo = $entityRelation->getPrimaryKey();
        $foreignKeyOne = $foreignKeyOne ?: $preForeignKeyOne . '_' . $primaryKeyOne;
        $foreignKeyTwo = $foreignKeyTwo ?: $preForeignKeyTwo . '_' . $primaryKeyTwo;
        $all = Database::query(
            'SHOW TABLES FROM ' . config('database.connections.mysql.name', 'database')
        );
        if (empty($pivot)) {
            foreach ($all as $a) {
                if (str_contains($a['Tables_in_' . config('database.connections.mysql.name', 'database')], '_')) {
                    $test = explode('_', $a['Tables_in_' . config('database.connections.mysql.name', 'database')]);
                    if (in_array($preForeignKeyOne, $test) && in_array($preForeignKeyTwo, $test)) {
                        $pivot = $a['Tables_in_' . config('database.connections.mysql.name', 'database')];
                    }
                }
            }
        }
        $data = [];
        $data[$foreignKeyOne] = $this->{$primaryKeyOne}();
        $data[$foreignKeyTwo] = $this->{$primaryKeyTwo}();
        if ($options) {
            foreach ($options as $key => $option) {
                $data[$key] = $option;
            }
        }
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement = 'INSERT INTO ' . $pivot . ' (' . implode(', ', $keys) . ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        return Database::prepare($statement, $values);
    }

    /**
     * Get record from the foreign table
     *
     * @param Entity|string $entityRelation
     * @param string $foreignKey
     * @return array|bool|mixed
     * @throws ReflectionException
     * @throws Exception
     * @example One to Many
     */
    protected function hasMany(Entity|string $entityRelation, string $foreignKey = ''): mixed
    {
        if (is_string($entityRelation)) {
            $entityRelation = new $entityRelation();
        }
        if (!($entityRelation instanceof Entity)) {
            throw new Exception("Must be a instance of MkyCore\Abstract\Entity");
        }
        $manager = $this->getManager();
        $managerRelation = $entityRelation->getManager();
        $preForeignKey = strtolower((new ReflectionClass($this))->getShortName());
        $table = $manager->getTable();
        $tableRelate = $managerRelation->getTable();
        $primaryKey = $this->getPrimaryKey();
        $foreignKey = $foreignKey ?: $preForeignKey . '_' . $primaryKey;
        return $this->{$tableRelate} = Database::prepare(
            "
		SELECT {$tableRelate}.* FROM {$tableRelate}
		LEFT JOIN " . $table . " ON " . $table . '.' . $primaryKey . ' = ' . $tableRelate . '.' . $foreignKey . "
		WHERE " . $table . '.' . $primaryKey . " = :" . $primaryKey, [$primaryKey => $this->$primaryKey()], get_class($entityRelation));
    }

    /**
     * Get all records from the foreign table
     *
     * @param Entity|string $entityRelation
     * @param string $pivot
     * @param string $foreignKeyOne
     * @param string $foreignKeyTwo
     * @return array|bool|mixed
     * @throws ReflectionException
     * @throws Exception
     * @example Many to Many
     */
    protected function manyToMany(Entity|string $entityRelation, string $pivot = '', string $foreignKeyOne = '', string $foreignKeyTwo = ''): mixed
    {
        if (is_string($entityRelation)) {
            $entityRelation = new $entityRelation();
        }
        if (!($entityRelation instanceof Entity)) {
            throw new Exception("Must be a instance of MkyCore\Abstract\Entity");
        }
        $managerRelation = $entityRelation->getManager();
        $preForeignKeyOne = strtolower((new ReflectionClass($this))->getShortName());
        $preForeignKeyTwo = strtolower((new ReflectionClass($entityRelation))->getShortName());
        $tableRelate = $managerRelation->getTable();
        $primaryKeyOne = $this->getPrimaryKey();
        $primaryKeyTwo = $entityRelation->getPrimaryKey();
        $foreignKeyOne = $foreignKeyOne ?: $preForeignKeyOne . '_' . $primaryKeyOne;
        $foreignKeyTwo = $foreignKeyTwo ?: $preForeignKeyTwo . '_' . $primaryKeyTwo;
        $all = Database::query(
            'SHOW TABLES FROM ' . config('database.connections.mysql.name', 'database')
        );
        if (empty($pivot)) {
            foreach ($all as $a) {
                if (str_contains($a['Tables_in_' . config('database.connections.mysql.name', 'database')], '_')) {
                    $test = explode('_', $a['Tables_in_' . config('database.connections.mysql.name', 'database')]);
                    if (in_array($preForeignKeyOne, $test) && in_array($preForeignKeyTwo, $test)) {
                        $pivot = $a['Tables_in_' . config('database.connections.mysql.name', 'database')];
                    }
                }
            }
        }
        return Database::prepare(
            "
		SELECT {$tableRelate}.*, {$pivot}.*
		FROM {$tableRelate}
		LEFT JOIN {$pivot}
		ON {$pivot}.{$foreignKeyTwo} = {$tableRelate}.{$primaryKeyTwo}
		WHERE {$pivot}.{$foreignKeyOne} = :$primaryKeyOne",
            ["$primaryKeyOne" => $this->{$this->getPrimaryKey()}],
            get_class($entityRelation)
        );
    }

    /**
     * @throws ReflectionException
     */
    private function querySet()
    {
        $annotations = (new Annotation($this))->getPropertiesAnnotations();
        foreach ($annotations as $name => $annotation) {
            $getTypes = $annotation->getParams();
            $name = $this->camelize($name);
            foreach ($getTypes as $type => $param) {
                $value = $this->handleType($type, $param);
                if (method_exists($this, 'set' . ucfirst($name)) && $value) {
                    $this->{'set' . ucfirst($name)}($value);
                }
            }
        }
    }

    private function handleType(int|string $type, mixed $param)
    {
        $value = $this->handleColumn($param);
        return $value;
    }

    private function handleColumn(ParamAnnotation $param)
    {

    }
}