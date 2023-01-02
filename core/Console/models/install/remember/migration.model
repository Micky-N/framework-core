<?php


use MkyCore\Abstracts\Migration;
use MkyCore\Migration\MigrationTable;
use MkyCore\Migration\Schema;

class CreateRememberTokensTable extends Migration
{

    public function up()
    {
        Schema::create('remember_tokens', function(MigrationTable $table){
            $table->id()->autoIncrement();
            $table->string('entity', 40)->notNull();
            $table->integer('entity_id')->notNull();
            $table->string('provider')->notNull();
            $table->string('selector')->notNull();
            $table->string('validator')->notNull();
            $table->createdAt();
        });
    }

    public function down()
    {
        Schema::dropTable('remember_tokens');
    }

}