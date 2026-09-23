<?php

$fontPath = base_path('vendor/dompdf/dompdf/lib/fonts');
$cachePath = storage_path('framework/cache');
$publicPath = public_path();

return [
    'show_warnings' => false,
    'public_path' => $publicPath,
    'convert_entities' => true,

    'options' => [
        'font_dir' => $fontPath,
        'font_cache' => $cachePath,
        'temp_dir' => $cachePath,
        'chroot' => [
            base_path(),
            storage_path(),
            $publicPath,
        ],
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],
        'artifactPathValidation' => null,
        'log_output_file' => null,
        'enable_font_subsetting' => false,
        'pdf_backend' => 'CPDF',
        'default_media_type' => 'screen',
        'default_paper_size' => 'letter',
        'default_paper_orientation' => 'portrait',
        'default_font' => 'serif',
        'dpi' => 96,
        'enable_php' => false,
        'enable_javascript' => true,
        'enable_remote' => false,
        'allowed_remote_hosts' => null,
        'font_height_ratio' => 1.1,
        'enable_html5_parser' => true,
    ],
];
