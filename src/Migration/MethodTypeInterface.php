<?php

namespace MkyCore\Migration;

interface MethodTypeInterface
{
    public function id();

    public function bigInt(string $name);

    public function string(string $name,int $limit = 255);

    public function integer(string $name);

    public function text(string $name);

    public function float(string $name);

    public function datetime(string $name);

    public function timestamp(string $name);

    public function notNull();

    public function autoIncrement();

    public function unique();

    public function default(mixed $value = null);

    public function unsigned();

    public function foreignKey(string $name);

    public function references(string $name,string $row = 'id');

    public function cascade();

    public function cascadeDelete();

    public function cascadeUpdate();

    public function noAction();

    public function noActionDelete();

    public function noActionUpdate();

    public function createAt();

    public function updateAt();
}