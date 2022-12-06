<?php

/*
 * -------------------------------------------------------------
 *  Database config
 * -------------------------------------------------------------
 *
 * Database configuration for app
 *
 */
return [
    /*
     * -------------------------------------------------------------
     *  Default
     * -------------------------------------------------------------
     *
     * return the default database system
     *
     */
    'default' => 'mysql',

    /*
     * -------------------------------------------------------------
     *  Connections
     * -------------------------------------------------------------
     *
     * return the connection params for your database system
     *
     */
    'connections' => [
        /*
         * -------------------------------------------------------------
         *  Mysql
         * -------------------------------------------------------------
         *
         * return the PDO connection params you can change this in your
         * .env file
         *
         */
        'mysql' => [
            'user' => env('DB_USER', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'name' => env('DB_NAME', 'mkyframework'),
            'host' => env('DB_HOST', 'localhost')
        ]
    ]
];