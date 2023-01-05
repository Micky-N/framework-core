<?php

namespace MkyCore\Traits;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Database;
use MkyCore\EventNotification\HasManyNotification;
use MkyCore\EventNotification\HasManyUnReadNotification;
use MkyCore\EventNotification\Notification;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;

trait HasDbNotify
{

    public function notifications(): HasManyNotification|false
    {
        return $this->hasManyNotification(Notification::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) {
            return $queryBuilderMysql->where('entity', Database::stringifyEntity($this));
        });
    }

    public function unreadNotifications(): HasManyUnReadNotification|false
    {
        return $this->hasManyUnReadNotification(Notification::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) {
            return $queryBuilderMysql->where('entity', Database::stringifyEntity($this))
                ->whereNull('read_at');
        });
    }

    private function hasManyNotification(Entity|string $entityRelation, string $foreignKey = ''): HasMany|false
    {
        try {
            $entityRelation = $this->getEntity($entityRelation);
            $relation = new HasManyNotification($this, $entityRelation, $foreignKey);
            return $this->relations['notification'] = $relation;
        } catch (Exception $ex) {
            return false;
        }
    }

    private function hasManyUnReadNotification(Entity|string $entityRelation, string $foreignKey = ''): HasMany|false
    {
        try {
            $entityRelation = $this->getEntity($entityRelation);
            $relation = new HasManyUnReadNotification($this, $entityRelation, $foreignKey);
            return $this->relations['notification'] = $relation;
        } catch (Exception $ex) {
            return false;
        }
    }
}