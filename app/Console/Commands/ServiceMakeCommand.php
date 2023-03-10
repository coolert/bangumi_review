<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:service')]
class ServiceMakeCommand extends GeneratorCommand
{
    /** The console command name.
     *
     * @var string
     */
    protected $name = 'make:service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service class';

    /** The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Service';

    /** Get the stub name.
     *
     * @return string
     */
    protected function getStub() : string
    {
        return __DIR__ . '/stubs/service.plain.stub';
    }

    /** Get the default namespace for the class.
     *
     * @param $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) : string
    {
        return $rootNamespace . '\Http\Services';
    }

    /** Execute the console command.
     * @return bool|null
     * @throws FileNotFoundException
     */
    public function handle(): bool|null
    {
        $serviceDirPath = app_path('/Http/Services');
        File::isDirectory($serviceDirPath) or File::makeDirectory($serviceDirPath, 0777, true, true);
        $baseServicePath = app_path('/Http/Services/BaseService.php');
        if (!file_exists($baseServicePath)) {
            $baseServiceTemplate = file_get_contents(app_path('/Console/Commands/stubs/service.base.stub'));
            file_put_contents($baseServicePath, $baseServiceTemplate);
        }
        return parent::handle(); // TODO: Change the autogenerated stub
    }
}
