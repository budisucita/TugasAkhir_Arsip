<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class DiagnoseS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose S3 configuration and connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting S3 Diagnosis...');

        // 1. Check Dependency
        $this->info('1. Checking Dependencies...');
        if (!class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            $this->error('❌ Critical: `league/flysystem-aws-s3-v3` package is MISSING.');
            $this->line('   Please run: composer require league/flysystem-aws-s3-v3');
            return 1;
        } else {
            $this->info('✅ Package `league/flysystem-aws-s3-v3` is installed.');
        }

        // 2. Check Configuration
        $this->info('2. Checking Configuration...');
        $config = Config::get('filesystems.disks.s3');
        $this->table(
            ['Config Key', 'Value (Masked)'],
            [
                ['Driver', $config['driver'] ?? 'NULL'],
                ['Region', $config['region'] ?? 'NULL'],
                ['Bucket', $config['bucket'] ?? 'NULL'],
                ['Endpoint', $config['endpoint'] ?? 'NULL'],
                ['Use Path Style', ($config['use_path_style_endpoint'] ?? false) ? 'true' : 'false'],
            ]
        );

        if (empty($config['key']) || empty($config['secret']) || empty($config['bucket']) || empty($config['region'])) {
            $this->warn('⚠️  Some required S3 configuration values are missing in .env');
        } else {
            $this->info('✅ S3 configuration keys are present.');
        }

        // 3. Test Database Connection
        $this->info('3. Testing Database Connection...');
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $this->info('✅ Database connection successful.');
            
            // Check metadata table
            if (\Illuminate\Support\Facades\Schema::hasTable('dokumentasi')) {
                $this->info('✅ Table `dokumentasi` exists.');
            } else {
                $this->warn('⚠️  Table `dokumentasi` is missing.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Database Connection Failed: ' . $e->getMessage());
        }

        // 4. Test S3 Connection
        $this->info('4. Testing S3 Connection...');
        try {
            $disk = Storage::disk('s3');
            $filename = 'diagnose_' . time() . '.txt';
            
            $this->line('   Attempting to write file: ' . $filename);
            $disk->put($filename, 'S3 Connection Test Successful');
            $this->info('✅ Write successful.');

            $this->line('   Attempting to read file...');
            $content = $disk->get($filename);
            if ($content === 'S3 Connection Test Successful') {
                $this->info('✅ Read successful.');
            } else {
                $this->error('❌ Read content mismatch.');
            }

            $this->line('   Attempting to delete file...');
            $disk->delete($filename);
            $this->info('✅ Delete successful.');

            $this->info('🎉 S3 Connection is FULLY OPERATIONAL.');
        } catch (\Exception $e) {
            $this->error('❌ Connection Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
