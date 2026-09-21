<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

#region use

use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Exception;
use PDO;
use function count;
use function get_class;
use function in_array;
use function is_array;

#endregion

/**
 * Loads an entity's relationships: one-to-many, many-to-one, one-to-one and
 * many-to-many, lazily for a single record and eagerly for a collection.
 *
 * Lifted out of ActiveRecord as a body-for-body move. Only four kinds of
 * access had to change, because they no longer resolve from here:
 *
 *   $this[...]                 -> $this->entity[...]
 *   self::$db                  -> ActiveRecord::getConnection()
 *   self::$reflectionCache[X]  -> ActiveRecord::metadataFor(X)
 *   $this->getMetadata() and friends -> $this->entity->...
 *
 * Every SQL string, every branch and every message is otherwise the one that
 * was there before.
 *
 * The eager-loading-for-a-collection methods are static because they were:
 * they take the collection by reference and never touch an owning entity.
 *
 * @package Clover\Classes\Database
 */
class RelationLoader
{
    /**
     * @param ActiveRecord $entity The record whose relationships are being loaded.
     */
    public function __construct(private readonly ActiveRecord $entity)
    {
    }

    /**
     * Load relationship data lazily
     * 
     * Dispatches to appropriate loader based on relationship type.
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship configuration
     * 
     * @return void
     * @throws Exception When database connection is not set
     */
    public function load(string $property, array $relationshipData): void
    {
        if (!ActiveRecord::getConnection()) {
            throw new Exception("Database connection not set");
        }

        $type = $relationshipData['type'];

        switch ($type) {
            case 'oneToMany':
                // Load related entities
                $this->loadOneToManyRelationship($property, $relationshipData);
                break;

            case 'manyToOne':
                // Load parent entity
                $this->loadManyToOneRelationship($property, $relationshipData);
                break;

            case 'oneToOne':
                // Load related entity
                $this->loadOneToOneRelationship($property, $relationshipData);
                break;

            case 'manyToMany':
                // Load many-to-many relationship
                $this->loadManyToManyRelationship($property, $relationshipData);
                break;
        }
    }

    /**
     * Load one-to-many relationship data
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship configuration
     * 
     * @return void
     */
    public function loadOneToManyRelationship(string $property, array $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        if (empty($pkValue)) {
            // Can't load related entities without a primary key
            $this->entity[$property] = [];
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'];

        // We need to find the foreign key in the target entity
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Find the column that corresponds to the mappedBy property
        $foreignKeyColumn = null;
        if (isset($targetMetadata['columns'][$mappedBy])) {
            $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
        } else {
            // Try to find a ManyToOne relationship with a join column
            foreach ($targetMetadata['relationships'] as $targetProperty => $targetRelationship) {
                if (
                    $targetProperty === $mappedBy &&
                    $targetRelationship['type'] === 'manyToOne' &&
                    isset($targetRelationship['joinColumn'])
                ) {
                    $foreignKeyColumn = $targetRelationship['joinColumn']['name'];
                    break;
                }
            }
        }

        if (!$foreignKeyColumn) {
            // Could not determine foreign key column
            $this->entity[$property] = [];
            return;
        }

        $targetTable = $targetMetadata['table'];
        $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} = ?";
        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$pkValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $targetClass();

            // Map database columns to entity properties
            foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                $columnName = $columnInfo['name'];

                if (isset($row[$columnName])) {
                    $entity[$targetProperty] = $this->entity->castValueToType($row[$columnName], $columnInfo['type'], $metadata);
                }
            }

            $results[] = $entity;
        }

        $this->entity[$property] = $results;
    }

    /**
     * Load many-to-one relationship
     * 
     * @param string $property
     * @param array $relationshipData
     * 
     * @return void
     */
    public function loadManyToOneRelationship($property, $relationshipData): void
    {
        // Hoisted: the last line passes $metadata to mapRowToEntity(), which
        // declares it `array`. It used to be assigned only inside the nested
        // branch below, so a relationship that names its joinColumn - or an
        // entity that already has the derived key - reached that call with the
        // variable undefined and raised a TypeError under strict_types. The
        // value is the one the branch assigned, so the working path is unchanged.
        $metadata = $this->entity->getMetadata();

        if (!isset($relationshipData['joinColumn'])) {
            // Try to determine join column from property name
            $foreignKey = $property . '_id';
            if (!isset($this->entity[$foreignKey])) {
                foreach ($metadata['columns'] as $colProperty => $columnInfo) {
                    if (strtolower($colProperty) === strtolower($foreignKey)) {
                        $foreignKey = $colProperty;
                        break;
                    }
                }
            }
        } else {
            $foreignKey = $relationshipData['joinColumn']['name'];
        }

        // Get foreign key value
        $foreignKeyValue = $this->entity[$foreignKey] ?? null;

        if (!$foreignKeyValue) {
            $this->entity[$property] = null;
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        $sql = "SELECT * FROM {$targetTable} WHERE {$targetPkColumn} = ? LIMIT 1";
        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$foreignKeyValue]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->entity[$property] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $metadata);
    }

    /**
     * Load one-to-one relationship data
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship configuration
     * 
     * @return void
     */
    public function loadOneToOneRelationship(string $property, array $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        if (empty($pkValue)) {
            $this->entity[$property] = null;
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'];

        if ($mappedBy) {
            // The foreign key is in the target entity (inverse side)
            $targetInstance = new $targetClass();
            $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));
            $targetTable = $targetMetadata['table'];

            // Find the column that corresponds to the mappedBy property
            $foreignKeyColumn = null;
            if (isset($targetMetadata['columns'][$mappedBy])) {
                $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
            } elseif (isset($targetMetadata['relationships'][$mappedBy]['joinColumn'])) {
                $foreignKeyColumn = $targetMetadata['relationships'][$mappedBy]['joinColumn']['name'];
            }

            if (!$foreignKeyColumn) {
                // Try with a default naming convention
                $foreignKeyColumn = strtolower(ReflectionHandler::getClassShortName($this->entity)) . '_id';
            }

            $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} = ? LIMIT 1";

            $stmt = ActiveRecord::getConnection()->prepare($sql);
            $stmt->execute([$pkValue]);
        } else {
            // The foreign key is in this entity (owning side)
            if (!isset($relationshipData['joinColumn'])) {
                // Try to determine join column from property name
                $foreignKey = $property . '_id';
            } else {
                $foreignKey = $relationshipData['joinColumn']['name'];
            }

            $foreignKeyValue = $this->entity[$foreignKey] ?? null;

            if (!$foreignKeyValue) {
                $this->entity[$property] = null;
                return;
            }

            $targetInstance = new $targetClass();
            $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));
            $targetTable = $targetMetadata['table'];
            $targetPkName = $targetMetadata['primaryKey'];
            $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

            $sql = "SELECT * FROM `{$targetTable}` WHERE {$targetPkColumn} = ? LIMIT 1";
            $stmt = ActiveRecord::getConnection()->prepare($sql);
            $stmt->execute([$foreignKeyValue]);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $entity = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($entity));
        $this->entity[$property] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $metadata);
    }

    /**
     * Load many-to-many relationship data via join table
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship configuration
     * 
     * @return void
     */
    public function loadManyToManyRelationship(string $property, array $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        if (empty($pkValue)) {
            $this->entity[$property] = [];
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Get join table information
        $joinTableName = null;
        $sourceColumn = null;
        $targetColumn = null;

        if (isset($relationshipData['joinTable'])) {
            $joinTableName = $relationshipData['joinTable']['name'];
            $sourceColumn = $relationshipData['joinTable']['joinColumn']['name'];
            $targetColumn = $relationshipData['joinTable']['inverseJoinColumn']['name'];
        } else {
            // Try to infer join table and columns
            $thisClass = ReflectionHandler::getClassShortName($this->entity);
            $targetClass = ReflectionHandler::getClassShortName($targetInstance);

            $tables = [$thisClass, $targetClass];
            sort($tables);
            $joinTableName = strtolower(implode('_', $tables));

            $sourceColumn = strtolower($thisClass) . '_id';
            $targetColumn = strtolower($targetClass) . '_id';
        }

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        // Join query to get related entities
        $sql = "SELECT t.* FROM `{$targetTable}` t 
                INNER JOIN {$joinTableName} j ON t.{$targetPkColumn} = j.{$targetColumn} 
                WHERE j.{$sourceColumn} = ?";

        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$pkValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $metadata);
        }

        $this->entity[$property] = $results;
    }

    /**
     * Eager load relationships for a collection of entities (batch loading).
     *
     * Performs N+1 query prevention by batching relationship queries across
     * all provided entities. Supports oneToMany, manyToOne, oneToOne, and
     * manyToMany relationship types.
     * 
     * @param array $entities Collection of entities
     * @param array $relations Relations to eager load
     * 
     * @return array|object Entities with relationships populated
     * @phpstan-param list<ActiveRecord> $entities
     * @phpstan-param list<string> $relations
     * @phpstan-return list<ActiveRecord>
     */
    public static function eagerLoadForCollection(array $entities, array $relations): array|object
    {
        if (empty($entities) || empty($relations) || !ActiveRecord::getConnection()) {
            return $entities;
        }

        $firstEntity = reset($entities);
        $metadata = ActiveRecord::metadataFor(get_class($firstEntity)) ?? null;

        if (!$metadata) {
            return $entities;
        }

        $pkName = $metadata['primaryKey'];
        $pkValues = array_map(function ($entity) use ($pkName) {
            return $entity[$pkName] ?? null;
        }, $entities);
        $pkValues = array_filter($pkValues);

        if (empty($pkValues)) {
            return $entities;
        }

        foreach ($relations as $relation) {
            if (!isset($metadata['relationships'][$relation])) {
                continue;
            }

            $relationshipData = $metadata['relationships'][$relation];
            $type = $relationshipData['type'];

            switch ($type) {
                case 'oneToMany':
                    self::eagerLoadOneToManyForCollection($entities, $relation, $relationshipData, $pkValues, $pkName);
                    break;
                case 'manyToOne':
                    self::eagerLoadManyToOneForCollection($entities, $relation, $relationshipData);
                    break;
                case 'oneToOne':
                    self::eagerLoadOneToOneForCollection($entities, $relation, $relationshipData, $pkValues, $pkName);
                    break;
                case 'manyToMany':
                    self::eagerLoadManyToManyForCollection($entities, $relation, $relationshipData, $pkValues, $pkName);
                    break;
            }
        }

        return $entities;
    }

    /**
     * Eager load one-to-many relationship
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * 
     * @return void
     */
    public function eagerLoadOneToMany($property, $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'];

        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Find the column that corresponds to the mappedBy property
        $foreignKeyColumn = null;
        if (isset($targetMetadata['columns'][$mappedBy])) {
            $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
        } else {
            // Try to find a ManyToOne relationship with a join column
            foreach ($targetMetadata['relationships'] as $targetProperty => $targetRelationship) {
                if ($targetProperty === $mappedBy && $targetRelationship['type'] === 'manyToOne' && isset($targetRelationship['joinColumn'])) {
                    $foreignKeyColumn = $targetRelationship['joinColumn']['name'];
                    break;
                }
            }
        }

        if (!$foreignKeyColumn) {
            // Could not determine foreign key column
            $this->entity[$property] = [];
            return;
        }

        $targetTable = $targetMetadata['table'];
        $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} = ?";
        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$pkValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $targetClass();

            // Map database columns to entity properties
            foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                $columnName = $columnInfo['name'];

                if (isset($row[$columnName])) {
                    $entity[$targetProperty] = $this->entity->castValueToType($row[$columnName], $columnInfo['type'], $metadata);
                }
            }

            $results[] = $entity;
        }

        $this->entity[$property] = $results;
    }

    /**
     * Eager load one-to-many relationship for multiple entities
     * 
     * @param array $entities Collection of entities
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * @param array $pkValues Primary key values of the entities
     * @param string $pkName Primary key property name
     * 
     * @return void
     */
    public static function eagerLoadOneToManyForCollection(array &$entities, $property, $relationshipData, $pkValues, $pkName): void
    {
        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'];

        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Find the column that corresponds to the mappedBy property
        $foreignKeyColumn = null;
        if (isset($targetMetadata['columns'][$mappedBy])) {
            $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
        } else {
            // Try to find a ManyToOne relationship with a join column
            foreach ($targetMetadata['relationships'] as $targetProperty => $targetRelationship) {
                if ($targetProperty === $mappedBy && $targetRelationship['type'] === 'manyToOne' && isset($targetRelationship['joinColumn'])) {
                    $foreignKeyColumn = $targetRelationship['joinColumn']['name'];
                    break;
                }
            }
        }

        if (!$foreignKeyColumn) {
            // Could not determine foreign key column
            return;
        }

        $targetTable = $targetMetadata['table'];
        $placeholders = implode(',', array_fill(0, count($pkValues), '?'));
        $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} IN ({$placeholders})";

        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute($pkValues);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $targetClass();
            $foreignKeyValue = null;

            // Map database columns to entity properties
            foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                $columnName = $columnInfo['name'];

                if (isset($row[$columnName])) {
                    $entity[$targetProperty] = $row[$columnName]; // No need to cast for bulk loading

                    if ($columnName === $foreignKeyColumn) {
                        $foreignKeyValue = $row[$columnName];
                    }
                }
            }

            if ($foreignKeyValue !== null) {
                if (!isset($results[$foreignKeyValue])) {
                    $results[$foreignKeyValue] = [];
                }

                $results[$foreignKeyValue][] = $entity;
            }
        }

        // Assign results to each entity
        foreach ($entities as &$entity) {
            $pkValue = $entity[$pkName] ?? null;
            $entity[$property] = $results[$pkValue] ?? [];
        }
    }

    /**
     * Eager load many-to-one relationship
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * 
     * @return void
     */
    public function eagerLoadManyToOne($property, $relationshipData): void
    {
        if (!isset($relationshipData['joinColumn'])) {
            // Try to determine join column from property name
            $foreignKey = $property . '_id';
            if (!isset($this->entity[$foreignKey])) {
                $metadata = $this->entity->getMetadata();
                foreach ($metadata['columns'] as $colProperty => $columnInfo) {
                    if (strtolower($colProperty) === strtolower($foreignKey)) {
                        $foreignKey = $colProperty;
                        break;
                    }
                }
            }
        } else {
            $foreignKey = $relationshipData['joinColumn']['name'];
        }

        // Get foreign key value
        $foreignKeyValue = $this->entity[$foreignKey] ?? null;

        if (!$foreignKeyValue) {
            $this->entity[$property] = null;
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        $sql = "SELECT * FROM `{$targetTable}` WHERE {$targetPkColumn} = ? LIMIT 1";
        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$foreignKeyValue]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->entity[$property] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $this->entity->getMetadata());
    }

    /**
     * Eager load many-to-one relationship for multiple entities
     * 
     * @param array $entities Collection of entities
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * 
     * @return void
     */
    public static function eagerLoadManyToOneForCollection(array &$entities, $property, $relationshipData): void
    {
        $foreignKey = null;

        if (!isset($relationshipData['joinColumn'])) {
            // Try to determine join column from property name
            $foreignKey = $property . '_id';
        } else {
            $foreignKey = $relationshipData['joinColumn']['name'];
        }

        // Get all foreign key values
        $foreignKeyValues = [];
        foreach ($entities as $entity) {
            $fkValue = $entity[$foreignKey] ?? null;
            if ($fkValue !== null) {
                $foreignKeyValues[] = $fkValue;
            }
        }

        if (empty($foreignKeyValues)) {
            return;
        }

        // Remove duplicates
        $foreignKeyValues = array_unique($foreignKeyValues);

        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        $placeholders = implode(',', array_fill(0, count($foreignKeyValues), '?'));
        $sql = "SELECT * FROM `{$targetTable}` WHERE {$targetPkColumn} IN ({$placeholders})";
        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute($foreignKeyValues);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $targetClass();
            $primaryKeyValue = null;

            // Map database columns to entity properties
            foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                $columnName = $columnInfo['name'];

                if (isset($row[$columnName])) {
                    $entity[$targetProperty] = $row[$columnName];

                    if ($targetProperty === $targetPkName) {
                        $primaryKeyValue = $row[$columnName];
                    }
                }
            }

            if ($primaryKeyValue !== null) {
                $results[$primaryKeyValue] = $entity;
            }
        }

        // Assign results to each entity
        foreach ($entities as &$entity) {
            $fkValue = $entity[$foreignKey] ?? null;
            $entity[$property] = $results[$fkValue] ?? null;
        }
    }

    /**
     * Eager load one-to-one relationship
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * 
     * @return void
     */
    public function eagerLoadOneToOne($property, $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        if (empty($pkValue)) {
            $this->entity[$property] = null;
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'] ?? null;

        if ($mappedBy) {
            // The foreign key is in the target entity (inverse side)
            $targetInstance = new $targetClass();
            $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));
            $targetTable = $targetMetadata['table'];

            // Find the column that corresponds to the mappedBy property
            $foreignKeyColumn = null;
            if (isset($targetMetadata['columns'][$mappedBy])) {
                $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
            } elseif (isset($targetMetadata['relationships'][$mappedBy]['joinColumn'])) {
                $foreignKeyColumn = $targetMetadata['relationships'][$mappedBy]['joinColumn']['name'];
            }

            if (!$foreignKeyColumn) {
                // Try with a default naming convention
                $foreignKeyColumn = strtolower(ReflectionHandler::getClassShortName($this->entity)) . '_id';
            }

            $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} = ? LIMIT 1";
            $stmt = ActiveRecord::getConnection()->prepare($sql);
            $stmt->execute([$pkValue]);
        } else {
            // The foreign key is in this entity (owning side)
            if (!isset($relationshipData['joinColumn'])) {
                // Try to determine join column from property name
                $foreignKey = $property . '_id';
            } else {
                $foreignKey = $relationshipData['joinColumn']['name'];
            }

            $foreignKeyValue = $this->entity[$foreignKey] ?? null;

            if (!$foreignKeyValue) {
                $this->entity[$property] = null;
                return;
            }

            $targetInstance = new $targetClass();
            $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));
            $targetTable = $targetMetadata['table'];
            $targetPkName = $targetMetadata['primaryKey'];
            $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

            $sql = "SELECT * FROM `{$targetTable}` WHERE {$targetPkColumn} = ? LIMIT 1";
            $stmt = ActiveRecord::getConnection()->prepare($sql);
            $stmt->execute([$foreignKeyValue]);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->entity[$property] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $metadata);
    }

    /**
     * Eager load one-to-one relationship for multiple entities
     * 
     * @param array $entities Collection of entities
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * @param array $pkValues Primary key values of the entities
     * @param string $pkName Primary key property name
     * 
     * @return void
     */
    public static function eagerLoadOneToOneForCollection(array &$entities, $property, $relationshipData, $pkValues, $pkName): void
    {
        $targetClass = $relationshipData['targetEntity'];
        $mappedBy = $relationshipData['mappedBy'] ?? null;

        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));
        $targetTable = $targetMetadata['table'];

        if ($mappedBy) {
            // The foreign key is in the target entity (inverse side)

            // Find the column that corresponds to the mappedBy property
            $foreignKeyColumn = null;
            if (isset($targetMetadata['columns'][$mappedBy])) {
                $foreignKeyColumn = $targetMetadata['columns'][$mappedBy]['name'];
            } elseif (isset($targetMetadata['relationships'][$mappedBy]['joinColumn'])) {
                $foreignKeyColumn = $targetMetadata['relationships'][$mappedBy]['joinColumn']['name'];
            }

            if (!$foreignKeyColumn) {
                // Try with a default naming convention
                $className = ReflectionHandler::getClassShortName(reset($entities));
                $foreignKeyColumn = strtolower($className) . '_id';
            }

            $placeholders = implode(',', array_fill(0, count($pkValues), '?'));
            $sql = "SELECT * FROM `{$targetTable}` WHERE {$foreignKeyColumn} IN ({$placeholders})";
            $stmt = ActiveRecord::getConnection()->prepare($sql);
            $stmt->execute($pkValues);

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entity = new $targetClass();
                $foreignKeyValue = null;

                // Map database columns to entity properties
                foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                    $columnName = $columnInfo['name'];

                    if (isset($row[$columnName])) {
                        $entity[$targetProperty] = $row[$columnName];

                        if ($columnName === $foreignKeyColumn) {
                            $foreignKeyValue = $row[$columnName];
                        }
                    }
                }

                if ($foreignKeyValue !== null) {
                    $results[$foreignKeyValue] = $entity;
                }
            }

            // Assign results to each entity
            foreach ($entities as &$entity) {
                $pkValue = $entity[$pkName] ?? null;
                $entity[$property] = $results[$pkValue] ?? null;
            }
        } else {
            // The foreign key is in this entity (owning side)
            // Find the foreign key column
            if (!isset($relationshipData['joinColumn'])) {
                // Try to determine join column from property name
                $foreignKey = $property . '_id';
            } else {
                $foreignKey = $relationshipData['joinColumn']['name'];
            }

            // Get all foreign key values
            $foreignKeyValues = [];
            foreach ($entities as $entity) {
                $fkValue = $entity[$foreignKey] ?? null;
                if ($fkValue !== null) {
                    $foreignKeyValues[] = $fkValue;
                }
            }

            if (empty($foreignKeyValues)) {
                return;
            }

            // Remove duplicates
            $foreignKeyValues = array_unique($foreignKeyValues);

            $targetPkName = $targetMetadata['primaryKey'];
            $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

            $placeholders = implode(',', array_fill(0, count($foreignKeyValues), '?'));
            $sql = "SELECT * FROM `{$targetTable}` WHERE {$targetPkColumn} IN ({$placeholders})";
            $stmt = ActiveRecord::getConnection()->prepare($sql);

            $stmt->execute(array_values($foreignKeyValues));

            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $entity = new $targetClass();
                $primaryKeyValue = null;

                // Map database columns to entity properties
                foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                    $columnName = $columnInfo['name'];

                    if (isset($row[$columnName])) {
                        $entity[$targetProperty] = $row[$columnName];

                        if ($targetProperty === $targetPkName) {
                            $primaryKeyValue = $row[$columnName];
                        }
                    }
                }

                if ($primaryKeyValue !== null) {
                    $results[$primaryKeyValue] = $entity;
                }
            }

            // Assign results to each entity
            foreach ($entities as &$entity) {
                $fkValue = $entity[$foreignKey] ?? null;
                $entity[$property] = $results[$fkValue] ?? null;
            }
        }
    }

    /**
     * Eager load many-to-many relationship
     * 
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * 
     * @return void
     */
    public function eagerLoadManyToMany($property, $relationshipData): void
    {
        $metadata = $this->entity->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this->entity[$pkName];

        if (empty($pkValue)) {
            $this->entity[$property] = [];
            return;
        }

        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Get join table information
        $joinTableName = null;
        $sourceColumn = null;
        $targetColumn = null;

        if (isset($relationshipData['joinTable'])) {
            $joinTableName = $relationshipData['joinTable']['name'];
            $sourceColumn = $relationshipData['joinTable']['joinColumn']['name'];
            $targetColumn = $relationshipData['joinTable']['inverseJoinColumn']['name'];
        } else {
            // Try to infer join table and columns
            $thisClass = ReflectionHandler::getClassShortName($this->entity);
            $targetClass = ReflectionHandler::getClassShortName($targetInstance);

            $tables = [$thisClass, $targetClass];
            sort($tables);
            $joinTableName = strtolower(implode('_', $tables));

            $sourceColumn = strtolower($thisClass) . '_id';
            $targetColumn = strtolower($targetClass) . '_id';
        }

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        // Join query to get related entities
        $sql = "SELECT t.* FROM `{$targetTable}` t 
            INNER JOIN {$joinTableName} j ON t.{$targetPkColumn} = j.{$targetColumn} 
            WHERE j.{$sourceColumn} = ?";

        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute([$pkValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->entity->mapRowToEntity($targetClass, $targetMetadata, $row, $metadata);
        }

        $this->entity[$property] = $results;
    }

    /**
     * Eager load many-to-many relationship for multiple entities
     * 
     * @param array $entities Collection of entities
     * @param string $property Relationship property name
     * @param array $relationshipData Relationship metadata
     * @param array $pkValues Primary key values of the source entities
     * @param string $pkName Primary key property name of the source entities
     * 
     * @return void
     */
    public static function eagerLoadManyToManyForCollection(array &$entities, $property, $relationshipData, $pkValues, $pkName): void
    {
        $firstEntity = reset($entities);
        $targetClass = $relationshipData['targetEntity'];
        $targetInstance = new $targetClass();
        $targetMetadata = ActiveRecord::metadataFor(get_class($targetInstance));

        // Get join table information
        $joinTableName = null;
        $sourceColumn = null;
        $targetColumn = null;

        if (isset($relationshipData['joinTable'])) {
            $joinTableName = $relationshipData['joinTable']['name'];
            $sourceColumn = $relationshipData['joinTable']['joinColumn']['name'];
            $targetColumn = $relationshipData['joinTable']['inverseJoinColumn']['name'];
        } else {
            // Try to infer join table and columns
            $thisClass = ReflectionHandler::getClassShortName($firstEntity);
            $targetClass = ReflectionHandler::getClassShortName($targetInstance);

            $tables = [$thisClass, $targetClass];
            sort($tables);
            $joinTableName = strtolower(implode('_', $tables));

            $sourceColumn = strtolower($thisClass) . '_id';
            $targetColumn = strtolower($targetClass) . '_id';
        }

        $targetTable = $targetMetadata['table'];
        $targetPkName = $targetMetadata['primaryKey'];
        $targetPkColumn = $targetMetadata['columns'][$targetPkName]['name'];

        // Join query to get related entities
        $placeholders = implode(',', array_fill(0, count($pkValues), '?'));
        $sql = "SELECT j.{$sourceColumn} as source_id, t.* FROM `{$targetTable}` t 
            INNER JOIN {$joinTableName} j ON t.{$targetPkColumn} = j.{$targetColumn} 
            WHERE j.{$sourceColumn} IN ({$placeholders})";

        $stmt = ActiveRecord::getConnection()->prepare($sql);
        $stmt->execute($pkValues);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sourceId = $row['source_id'];
            if (!isset($results[$sourceId])) {
                $results[$sourceId] = [];
            }

            $entity = new $targetClass();

            // Map database columns to entity properties
            foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
                $columnName = $columnInfo['name'];
                if (isset($row[$columnName])) {
                    $entity[$targetProperty] = $row[$columnName];
                }
            }

            $results[$sourceId][] = $entity;
        }

        // Assign results to each entity
        foreach ($entities as &$entity) {
            $pkValue = $entity[$pkName] ?? null;
            $entity[$property] = $results[$pkValue] ?? [];
        }
    }
}
