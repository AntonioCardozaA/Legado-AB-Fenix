<?php

ob_start();
require __DIR__ . '/benchmark-admin-list.php';
ob_end_clean();
$root = sys_get_temp_dir() . '/legado-browser-directory-' . bin2hex(random_bytes(5));
mkdir($root);
$variants = ['initial' => [], 'page2' => ['page' => 2], 'first' => ['search' => 'Fixture 00001'], 'second' => ['search' => 'Fixture 00002'], 'third' => ['search' => 'Fixture 00003']];
foreach ($variants as $name => $filters) {
    $request = Illuminate\Http\Request::create(route('admin.users.index', [], false), 'GET', $filters);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session);
    $request->setRouteResolver(fn () => $app['router']->getRoutes()->match($request));
    $app->instance('request', $request);
    file_put_contents($root . '/' . $name . '.html', app(App\Http\Controllers\AdminUserController::class)->index($request)->render());
    $request->headers->set('X-Directory-Fragment', '1');
    file_put_contents($root . '/' . $name . '-fragment.html', app(App\Http\Controllers\AdminUserController::class)->index($request)->getContent());
}
file_put_contents($root . '/meta.json', json_encode(['route' => route('admin.users.index', [], false)]));
echo json_encode(['fixture_directory' => $root]) . PHP_EOL;
