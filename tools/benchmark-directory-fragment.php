<?php

ob_start();
require __DIR__ . '/benchmark-admin-list.php';
ob_end_clean(); // Shared bootstrap enforces SQLite :memory: before migrations.
foreach ([false, true] as $partial) {
    if ($partial) $request->headers->set('X-Directory-Fragment', '1');
    else $request->headers->remove('X-Directory-Fragment');
    for ($sample = -1; $sample < 3; $sample++) {
        $start = hrtime(true);
        $result = app(App\Http\Controllers\AdminUserController::class)->index($request);
        $html = $result instanceof Illuminate\Contracts\View\View ? $result->render() : $result->getContent();
        if ($sample >= 0) echo json_encode(['partial' => $partial, 'ms' => round((hrtime(true) - $start) / 1e6, 2), 'html_bytes' => strlen($html)]) . PHP_EOL;
    }
}
