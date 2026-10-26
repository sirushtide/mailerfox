<?php

use App\Models\PublicTemplate;
use App\Models\PublicTemplateCategory;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$records = json_decode(
    file_get_contents(__DIR__ . '/templates.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);

DB::transaction(function () use ($records): void {
    $category = PublicTemplateCategory::firstOrCreate(
        ['slug' => 'gorealo-business'],
        ['name' => 'GoRealo Business', 'is_active' => true]
    );

    foreach ($records as $record) {
        $record['category_id'] = $category->id;
        PublicTemplate::updateOrCreate(
            ['slug' => $record['slug']],
            $record
        );
    }
});

fwrite(STDOUT, 'Imported or updated ' . count($records) . " GoRealo public templates.\n");
