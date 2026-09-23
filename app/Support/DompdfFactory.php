<?php

namespace App\Support;

use Dompdf\Dompdf;

class DompdfFactory
{
    public static function make(): Dompdf
    {
        $fontPath = storage_path('fonts');
        $tempPath = storage_path('framework/cache/dompdf');
        $publicPath = public_path();

        foreach ([$fontPath, $tempPath, $publicPath] as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0775, true);
            }
        }

        return new Dompdf([
            'chroot' => [base_path(), storage_path(), $publicPath],
            'fontDir' => $fontPath,
            'fontCache' => $fontPath,
            'tempDir' => $tempPath,
            'logOutputFile' => null,
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);
    }
}
