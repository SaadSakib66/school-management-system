<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    'show_warnings' => false,
    'public_path'   => null,

    // Dejavu Sans misses some glyphs; keep this true unless you need legacy entities conversion off
    'convert_entities' => true,

    'options' => [

        /*
         | Fonts: keep your TTFs here and make this folder writable
         | storage/fonts/SolaimanLipi.ttf
         | storage/fonts/SolaimanLipi-Bold.ttf
         */
        'font_dir'   => storage_path('fonts'),
        'font_cache' => storage_path('fonts'),

        // Temp files
        'temp_dir' => sys_get_temp_dir(),

        // Chroot (security)
        'chroot' => realpath(base_path()),

        // Allow data:/file:/http(s): resources if needed
        'allowed_protocols' => [
            'data://'  => ['rules' => []],
            'file://'  => ['rules' => []],
            'http://'  => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,
        'log_output_file'        => null,

        // ✅ Important for embedding only the used glyphs
        'enable_font_subsetting' => true,

        // Backend
        'pdf_backend' => 'CPDF',

        // Media / paper
        'default_media_type'       => 'screen',
        'default_paper_size'       => 'a4',
        'default_paper_orientation' => 'portrait',

        // ✅ Make Bangla font the default
        'default_font' => 'solaimanlipi',

        // Rendering
        'dpi'                   => 96,
        'enable_php'            => false,
        'enable_javascript'     => true,

        // Enable remote images/CSS if you ever reference http(s) assets
        'enable_remote'         => true,

        'allowed_remote_hosts'  => null,
        'font_height_ratio'     => 1.1,

        // Always on in dompdf 2.x; keep true for older integrations
        'enable_html5_parser'   => true,

        // ✅ Register SolaimanLipi family
        'font_family' => [
            'solaimanlipi' => [
                'normal'      => 'SolaimanLipi.ttf',
                'bold'        => 'SolaimanLipi-Bold.ttf',
                'italic'      => 'SolaimanLipi.ttf',
                'bold_italic' => 'SolaimanLipi-Bold.ttf',
            ],
        ],
    ],
];
