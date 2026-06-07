<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Generate a service class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");

        if (File::exists($path)) {
            $this->error("Service sudah ada!");
            return Command::FAILURE;
        }

        $namespace = 'App\Services';
        File::ensureDirectoryExists(app_path('Services'));
        File::put($path, "<?php

namespace {$namespace};

class {$name}
{
    //
}
");

        $this->info("Service {$name} berhasil dibuat.");

        $this->registerServiceInProvider($name);

        return Command::SUCCESS;
    }

    protected function registerServiceInProvider(string $name): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($providerPath)) {
            $this->warn("AppServiceProvider tidak ditemukan, lewati pendaftaran.");
            return;
        }

        $serviceClass = "App\\Services\\{$name}";
        $providerContent = File::get($providerPath);

        if (str_contains($providerContent, $serviceClass)) {
            $this->info("Service {$name} sudah terdaftar di AppServiceProvider.");
            return;
        }

        $registrationCode = "\$this->app->singleton({$serviceClass}::class, function (\$app) {\n            return new {$serviceClass}();\n        });\n        ";

        // Laravel 11-13: register(): void (dengan explicit return type)
        // Laravel 8-10:  register() (tanpa return type)
        $patterns = [
            '/(public function register\(\)\s*:\s*void\s*{)/',
            '/(public function register\(\)\s*{)/',
        ];

        $replaced = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $providerContent)) {
                $providerContent = preg_replace(
                    $pattern,
                    "$1\n        {$registrationCode}",
                    $providerContent
                );
                $replaced = true;
                break;
            }
        }

        // Fallback: method register() tidak ada, inject sebelum penutup class
        if (! $replaced) {
            $registerMethod = "\n    public function register(): void\n    {\n        {$registrationCode}\n    }\n";
            $providerContent = preg_replace('/}(\s*)$/', "{$registerMethod}}\$1", $providerContent);
        }

        File::put($providerPath, $providerContent);
        $this->info("Service {$name} berhasil didaftarkan di AppServiceProvider.");
    }
}
