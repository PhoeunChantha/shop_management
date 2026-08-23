<?php

use App\Services\Admin\EnvService;

beforeEach(function () {
    $this->envPath = storage_path('framework/testing-env-'.uniqid().'.env');
    file_put_contents($this->envPath, "APP_NAME=Shop\nGOOGLE_CLIENT_ID=abc\n");
    // Point the app at our scratch .env so the real one is never touched.
    app()->loadEnvironmentFrom(basename($this->envPath));
    app()->useEnvironmentPath(dirname($this->envPath));
});

afterEach(function () {
    @unlink($this->envPath);
});

it('does not rewrite .env when the values are unchanged', function () {
    $before = filemtime($this->envPath);
    $content = file_get_contents($this->envPath);
    touch($this->envPath, $before - 10);
    clearstatcache();
    $mtime = filemtime($this->envPath);

    app(EnvService::class)->set(['GOOGLE_CLIENT_ID' => 'abc']);
    clearstatcache();

    expect(filemtime($this->envPath))->toBe($mtime)
        ->and(file_get_contents($this->envPath))->toBe($content);
});

it('writes .env when a value changes', function () {
    app(EnvService::class)->set(['GOOGLE_CLIENT_ID' => 'new-id', 'GOOGLE_CLIENT_SECRET' => 's3cret']);

    $content = file_get_contents($this->envPath);
    expect($content)->toContain("GOOGLE_CLIENT_ID=new-id\n")
        ->and($content)->toContain('GOOGLE_CLIENT_SECRET=s3cret')
        ->and(app(EnvService::class)->get('GOOGLE_CLIENT_ID'))->toBe('new-id');
});
