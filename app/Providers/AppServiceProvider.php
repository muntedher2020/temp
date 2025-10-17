<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use App\Http\Livewire\ModuleManager\ModuleManagerSimple;
use App\Helpers\DynamicMenuHelper;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    // تحميل Helper classes
    $this->loadHelpers();
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Livewire::component('module-manager.module-manager-simple', ModuleManagerSimple::class);

    // Register custom Blade directive for dynamic menu
    Blade::directive('dynamicMenu', function () {
      return "<?php echo view('partials.dynamic-menu')->render(); ?>";
    });
  }

  /**
   * تحميل ملفات Helper
   */
  private function loadHelpers()
  {
    $helpersPath = app_path('Helpers');

    if (is_dir($helpersPath)) {
      foreach (glob($helpersPath . '/*.php') as $file) {
        if (file_exists($file)) {
          require_once $file;
        }
      }
    }
  }
}
