<?php

namespace App\Jobs;

use App\Models\ProductCatalog\Product3DReconstruction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class Run3DReconstructionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $reconstructionId)
    {
    }

    public function handle(): void
    {
        $reconstruction = Product3DReconstruction::with('images')->find($this->reconstructionId);

        if (!$reconstruction) {
            Log::warning('3D reconstruction job skipped: record not found', [
                'reconstruction_id' => $this->reconstructionId,
            ]);
            return;
        }

        $reconstruction->update([
            'status' => 'processing',
            'progress' => max(1, (int) $reconstruction->progress),
            'started_at' => $reconstruction->started_at ?? now(),
            'error_message' => null,
        ]);

        if ($reconstruction->images->isEmpty()) {
            $reconstruction->update([
                'status' => 'failed',
                'error_message' => 'No images attached to reconstruction.',
                'finished_at' => now(),
                'progress' => 100,
            ]);
            return;
        }

        $config = config('three_d');
        $command = $config['command'] ?? null;
        $enabled = (bool) ($config['enabled'] ?? false);

        if (!$enabled || empty($command)) {
            $reconstruction->update([
                'status' => 'failed',
                'error_message' => '3D pipeline is not configured. Set three_d.enabled=true and three_d.command.',
                'finished_at' => now(),
                'progress' => 100,
            ]);
            return;
        }

        $inputDisk = $config['input_disk'] ?? 'local';
        $outputDisk = $config['output_disk'] ?? 'public';
        $outputFormat = $config['output_format'] ?? 'glb';
        $engine = $reconstruction->options['engine'] ?? 'colmap';

        $firstImage = $reconstruction->images->first();
        $inputDirRelative = dirname($firstImage->file_path);
        $inputDir = Storage::disk($inputDisk)->path($inputDirRelative);

        $outputDirRelative = sprintf(
            '3d/%d/products/%d/reconstructions/%d/result',
            $reconstruction->store_id,
            $reconstruction->product_id,
            $reconstruction->id
        );

        $outputDir = Storage::disk($outputDisk)->path($outputDirRelative);
        Storage::disk($outputDisk)->makeDirectory($outputDirRelative);

        $outputFileName = 'model.' . $outputFormat;
        $outputRelativePath = $outputDirRelative . '/' . $outputFileName;
        $outputFile = $outputDir . DIRECTORY_SEPARATOR . $outputFileName;

        $reconstruction->update(['progress' => 10]);

        try {
            $process = $this->buildProcess(
                $command,
                [
                    '{input_dir}' => $inputDir,
                    '{output_dir}' => $outputDir,
                    '{output_file}' => $outputFile,
                    '{reconstruction_id}' => (string) $reconstruction->id,
                    '{store_id}' => (string) $reconstruction->store_id,
                    '{product_id}' => (string) $reconstruction->product_id,
                    '{engine}' => (string) $engine,
                ],
                (int) ($config['timeout_seconds'] ?? 7200)
            );

            $reconstruction->update(['progress' => 30]);
            $process->run();

            $reconstruction->update(['progress' => 80]);

            if (!$process->isSuccessful()) {
                $reconstruction->update([
                    'status' => 'failed',
                    'error_message' => trim($process->getErrorOutput() ?: $process->getOutput()),
                    'finished_at' => now(),
                    'progress' => 100,
                ]);
                return;
            }

            if (!Storage::disk($outputDisk)->exists($outputRelativePath)) {
                $reconstruction->update([
                    'status' => 'failed',
                    'error_message' => 'Pipeline completed but output model not found.',
                    'finished_at' => now(),
                    'progress' => 100,
                ]);
                return;
            }

            $reconstruction->update([
                'status' => 'ready',
                'output_path' => $outputRelativePath,
                'output_format' => $outputFormat,
                'finished_at' => now(),
                'progress' => 100,
            ]);
        } catch (\Throwable $e) {
            $reconstruction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'progress' => 100,
            ]);

            Log::error('3D reconstruction failed', [
                'reconstruction_id' => $reconstruction->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildProcess($command, array $replacements, int $timeoutSeconds): Process
    {
        if (is_string($command)) {
            $command = strtr($command, $replacements);
            $process = Process::fromShellCommandline($command);
        } else {
            $command = array_map(fn ($part) => strtr((string) $part, $replacements), $command);
            $process = new Process($command);
        }

        $process->setTimeout($timeoutSeconds);
        return $process;
    }
}
