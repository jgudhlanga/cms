<?php

use App\Models\Institution\DocumentTemplate;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

test('guests are redirected when visiting document templates page', function () {
    $this->get(route('document-templates.index'))->assertRedirect('/login');
});

test('authenticated users with permission can view document templates page', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('viewAny:document-templates', 'web');
    $user->givePermissionTo('viewAny:document-templates');

    $this->actingAs($user)
        ->get(route('document-templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/document-templates/Index')
            ->has('documentTemplates')
            ->has('filters')
            ->has('trashedCount'));
});

test('document templates index eager loads relations without extra shared props', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('viewAny:document-templates', 'web');
    $user->givePermissionTo('viewAny:document-templates');

    DocumentTemplate::factory()->count(3)->create(['tenant_id' => $user->tenant_id]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('document-templates.index'))
        ->assertSuccessful();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $documentTemplateSelects = collect($queries)->filter(
        fn (array $query): bool => str_contains(strtolower($query['query']), 'from "document_templates"')
            || str_contains(strtolower($query['query']), 'from `document_templates`'),
    );

    expect($documentTemplateSelects->count())->toBeLessThanOrEqual(3);
});
