<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 3D Reconstruction Pipeline
    |--------------------------------------------------------------------------
    | Enable this when COLMAP/OpenMVG/OpenMVS pipeline is installed and ready.
    | The command can be a string (shell) or array (Process).
    | Available placeholders:
    |  {input_dir} {output_dir} {output_file} {reconstruction_id} {store_id} {product_id}
    */

    'enabled' => env('THREED_RECON_ENABLED', false),

    // Example:
    // 'command' => 'python scripts/3d/reconstruct.py --input {input_dir} --output {output_file}',
    'command' => env('THREED_RECON_COMMAND') ?: null,

    'input_disk' => env('THREED_RECON_INPUT_DISK', 'local'),
    'output_disk' => env('THREED_RECON_OUTPUT_DISK', 'public'),
    'output_format' => env('THREED_RECON_OUTPUT_FORMAT', 'glb'),
    'timeout_seconds' => env('THREED_RECON_TIMEOUT', 7200),
];
