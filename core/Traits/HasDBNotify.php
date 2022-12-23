<?php

namespace MkyCore\Traits;

use MkyCore\Abstracts\Entity;
use MkyCore\Database;
use MkyCore\Notification\Database\HasManyNotification;
use MkyCore\Notification\Database\HasManyUnReadNotification;
use MkyCore\Notification\Database\Notification;
use MkyCore\QueryBuilderMysql;
use MkyCore\RelationEntity\HasMany;

trait HasDbNotify
{

    public function notifications(): HasManyNotification|false
    {
        /** @var Entity $this */
        return $this->hasManyNotification(Notification::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) {
            return $queryBuilderMysql->where('entity', Database::stringifyEntity($this));
        });
    }

    public function unreadNotifications(): HasManyUnReadNotification|false
    {
        /** @var Entity $this */
        return $this->hasManyUnReadNotification(Notification::class, 'entity_id')->queryBuilder(function (QueryBuilderMysql $queryBuilderMysql) {
            return $queryBuilderMysql->where('entity', Database::stringifyEntity($this))
                ->whereNull('read_at', 'NULL');
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