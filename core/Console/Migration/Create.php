<?php

namespace MkyCore\Console\Migration;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\File;
use MkyCore\Migration\DB;
use MkyCore\Str;

class Create extends AbstractCommand
{

    protected string $description = 'Create a new migration file';

    public function __construct(private readonly Application $application, private readonly array $variables = [])
    {
        $this->migrationDB = $application->get(DB::class);
    }

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the migration file');
    }

    public function execute(Input $input, Output $output): int|string
    {
        $outputDir = File::makePath([$this->application->get('path:database'), 'migrations']);
        $name = $input->argument('name');
        $nameSnaked = Str::toSnake($name);
        $nameFile = time() . "_$nameSnaked";
        $final = $outputDir . DIRECTORY_SEPARATOR . $nameFile . '.php';
        if (file_exists($final)) {
            $output->error('File already exists', 'migrations' . DIRECTORY_SEPARATOR . "$nameFile.php");
            return self::ERROR;
        }
        $parsedModel = file_get_contents(dirname(__DIR__) . 'models/migration.model');
        $parsedModel = str_replace('!name', Str::classify($name), $parsedModel);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }
        file_put_contents($final, $parsedModel);
        if (count($this->variables) > 0) {
            return $final;
        }
        $output->success("Migration file created", $final);
        return self::SUCCESS;
    }
}