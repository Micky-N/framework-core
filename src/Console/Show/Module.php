<?php

namespace MkyCore\Console\Show;

use MkyCore\Console\Color;

class Module extends Show
{

    const HEADERS = [
        'getAlias' => 'Alias',
        'getModuleKernel' => 'Kernel',
    ];

    use Color;


    public function process()
    {
        $print = in_array('--print', $this->params);
        $table = new ConsoleTable();
        $table->setHeaders(array_map(fn($header) => $this->getColoredString($header, 'green'), array_values(self::HEADERS)));
        $modules = array_keys($this->app->getModules());
        $headers = array_keys(self::HEADERS);
        for ($i = 0; $i < count($modules); $i++) {
            $module = $this->app->getModuleKernel($modules[$i]);
            $array = [];
            foreach ($headers as $header) {
                if ($header == 'getModuleKernel') {
                    $array[] = get_class($module);
                } else {
                    $array[] = $module->{$header}();
                }
            }
            $table->addRow($array);
        }
        if ($print) {
            echo "List of modules:\n";
        }

        $table->setPadding(2)
            ->setIndent(2)
            ->showAllBorders()
            ->display();
    }
}