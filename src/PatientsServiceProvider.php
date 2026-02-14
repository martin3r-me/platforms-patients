<?php

namespace Platform\Patients;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Platform\Patients\Models\PatientsAnamnesisBoardBlockText;
use Platform\Patients\Models\PatientsProgressBoard;
use Platform\Patients\Models\PatientsProgressCard;
use Platform\Patients\Models\PatientsKanbanBoard;
use Platform\Patients\Models\PatientsKanbanCard;
use Platform\Patients\Policies\PatientPolicy;
use Platform\Patients\Policies\AnamnesisBoardPolicy;
use Platform\Patients\Policies\ProgressBoardPolicy;
use Platform\Patients\Policies\ProgressCardPolicy;
use Platform\Patients\Policies\KanbanBoardPolicy;
use Platform\Patients\Policies\KanbanCardPolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PatientsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Publish & merge config (MUST be before registerModule!)
        $this->publishes([
            __DIR__.'/../config/patients.php' => config_path('patients.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/patients.php', 'patients');

        // Module registration only if config & table exist
        if (
            config()->has('patients.routing') &&
            config()->has('patients.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'patients',
                'title'      => 'Patients',
                'routing'    => config('patients.routing'),
                'guard'      => config('patients.guard'),
                'navigation' => config('patients.navigation'),
                'sidebar'    => config('patients.sidebar'),
                'billables'  => config('patients.billables', []),
            ]);
        }

        // Only load routes if the module has been registered
        if (PlatformCore::getModule('patients')) {
            ModuleRouter::group('patients', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrations, Views, Livewire components
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'patients');
        $this->registerLivewireComponents();

        // Register policies
        $this->registerPolicies();

        // Register morph map for Anamnesis Board Block types
        $this->registerMorphMap();

        // Register tools
        $this->registerTools();
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Patients\\Livewire';
        $prefix = 'patients';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    /**
     * Registers policies for the Patients module
     */
    protected function registerPolicies(): void
    {
        $policies = [
            PatientsPatient::class => PatientPolicy::class,
            PatientsAnamnesisBoard::class => AnamnesisBoardPolicy::class,
            PatientsProgressBoard::class => ProgressBoardPolicy::class,
            PatientsProgressCard::class => ProgressCardPolicy::class,
            PatientsKanbanBoard::class => KanbanBoardPolicy::class,
            PatientsKanbanCard::class => KanbanCardPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Registers morph map for Anamnesis Board Block types
     */
    protected function registerMorphMap(): void
    {
        Relation::morphMap([
            'text' => PatientsAnamnesisBoardBlockText::class,
        ]);
    }

    /**
     * Registers tools for the Patients module
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Patient tools
            $registry->register(new \Platform\Patients\Tools\CreatePatientTool());
            $registry->register(new \Platform\Patients\Tools\ListPatientsTool());
            $registry->register(new \Platform\Patients\Tools\GetPatientTool());
            $registry->register(new \Platform\Patients\Tools\UpdatePatientTool());
            $registry->register(new \Platform\Patients\Tools\DeletePatientTool());

            // CRM links
            $registry->register(new \Platform\Patients\Tools\LinkPatientCompanyTool());
            $registry->register(new \Platform\Patients\Tools\LinkPatientContactTool());

            // AnamnesisBoard-Tools
            $registry->register(new \Platform\Patients\Tools\CreateAnamnesisBoardTool());
            $registry->register(new \Platform\Patients\Tools\ListAnamnesisBoardsTool());
            $registry->register(new \Platform\Patients\Tools\GetAnamnesisBoardTool());
            $registry->register(new \Platform\Patients\Tools\UpdateAnamnesisBoardTool());
            $registry->register(new \Platform\Patients\Tools\DeleteAnamnesisBoardTool());

            // AnamnesisBoardBlock-Tools
            $registry->register(new \Platform\Patients\Tools\CreateAnamnesisBoardBlockTool());
            $registry->register(new \Platform\Patients\Tools\ListAnamnesisBoardBlocksTool());
            $registry->register(new \Platform\Patients\Tools\GetAnamnesisBoardBlockTool());
            $registry->register(new \Platform\Patients\Tools\UpdateAnamnesisBoardBlockTool());
            $registry->register(new \Platform\Patients\Tools\DeleteAnamnesisBoardBlockTool());

            // AnamnesisBoardBlock Bulk-Tools
            $registry->register(new \Platform\Patients\Tools\BulkCreateAnamnesisBoardBlocksTool());
            $registry->register(new \Platform\Patients\Tools\BulkUpdateAnamnesisBoardBlocksTool());

            // AnamnesisBoardBlockText Tools (CRUD)
            $registry->register(new \Platform\Patients\Tools\CreateAnamnesisBoardBlockTextTool());
            $registry->register(new \Platform\Patients\Tools\UpdateAnamnesisBoardBlockTextTool());
            $registry->register(new \Platform\Patients\Tools\GetAnamnesisBoardBlockTextTool());
            $registry->register(new \Platform\Patients\Tools\DeleteAnamnesisBoardBlockTextTool());

            // KanbanBoard-Tools
            $registry->register(new \Platform\Patients\Tools\CreateKanbanBoardTool());
            $registry->register(new \Platform\Patients\Tools\ListKanbanBoardsTool());
            $registry->register(new \Platform\Patients\Tools\GetKanbanBoardTool());
            $registry->register(new \Platform\Patients\Tools\UpdateKanbanBoardTool());
            $registry->register(new \Platform\Patients\Tools\DeleteKanbanBoardTool());

            // KanbanCard-Tools
            $registry->register(new \Platform\Patients\Tools\CreateKanbanCardTool());
            $registry->register(new \Platform\Patients\Tools\ListKanbanCardsTool());
            $registry->register(new \Platform\Patients\Tools\GetKanbanCardTool());
            $registry->register(new \Platform\Patients\Tools\UpdateKanbanCardTool());
            $registry->register(new \Platform\Patients\Tools\DeleteKanbanCardTool());

            // ProgressBoard-Tools
            $registry->register(new \Platform\Patients\Tools\CreateProgressBoardTool());
            $registry->register(new \Platform\Patients\Tools\ListProgressBoardsTool());
            $registry->register(new \Platform\Patients\Tools\GetProgressBoardTool());
            $registry->register(new \Platform\Patients\Tools\UpdateProgressBoardTool());
            $registry->register(new \Platform\Patients\Tools\DeleteProgressBoardTool());

            // ProgressCard-Tools
            $registry->register(new \Platform\Patients\Tools\CreateProgressCardTool());
            $registry->register(new \Platform\Patients\Tools\ListProgressCardsTool());
            $registry->register(new \Platform\Patients\Tools\GetProgressCardTool());
            $registry->register(new \Platform\Patients\Tools\UpdateProgressCardTool());
            $registry->register(new \Platform\Patients\Tools\DeleteProgressCardTool());

            // ProgressCard Bulk-Tools
            $registry->register(new \Platform\Patients\Tools\BulkCreateProgressCardsTool());
            $registry->register(new \Platform\Patients\Tools\BulkUpdateProgressCardsTool());
        } catch (\Throwable $e) {
            // Silent fail - Tool registry may not be available
        }
    }
}
