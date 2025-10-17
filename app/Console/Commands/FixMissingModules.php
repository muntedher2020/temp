<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ModuleRestoreHelper;

class FixMissingModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:fix-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إصلاح الوحدات المختفية من القائمة الجانبية';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 جاري البحث عن الوحدات المختفية...');

        $result = ModuleRestoreHelper::fixMissingModules();

        if ($result['success']) {
            if (!empty($result['fixed'])) {
                $this->info('✅ تم إصلاح الوحدات التالية:');
                foreach ($result['fixed'] as $module) {
                    $this->line("   - {$module}");
                }
            } else {
                $this->info('✅ جميع الوحدات موجودة في القائمة');
            }
        } else {
            $this->error('❌ ' . $result['message']);
        }

        return 0;
    }
}
