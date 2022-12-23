<?php

namespace MkyCore\Interfaces;

use MkyCore\Abstracts\Entity;
use MkyCore\QueryBuilderMysql;

interface RelationEntityInterface
{

    /**
     * Get relation value in database table
     *
     * @return array|Entity|false
     * @see QueryBuilderMysql
     */
    public function get(): array|Entity|false;
}