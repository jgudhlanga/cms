<?php

use App\Models\Users\User;
use App\Services\DepartmentEnrolmentService;
use Illuminate\Http\Request;

it('returns pagination max limit for page_size=all', function (): void {
    config(['custom.system.pagination_items_per_page' => 15]);
    config(['custom.system.pagination_max_limit' => 200]);

    $request = Request::create('/', 'GET', ['page_size' => 'all']);
    app()->instance('request', $request);

    expect((new User)->getPerPage())->toBe(200);
});

it('clamps oversized page_size to pagination max limit', function (): void {
    config(['custom.system.pagination_items_per_page' => 15]);
    config(['custom.system.pagination_max_limit' => 200]);

    $request = Request::create('/', 'GET', ['page_size' => 500]);
    app()->instance('request', $request);

    expect((new User)->getPerPage())->toBe(200);
});

it('uses configured default page size when page_size is missing', function (): void {
    config(['custom.system.pagination_items_per_page' => 15]);
    config(['custom.system.pagination_max_limit' => 200]);

    $request = Request::create('/', 'GET');
    app()->instance('request', $request);

    expect((new User)->getPerPage())->toBe(15);
});

it('department enrolment service defaults to class_list_page_size', function (): void {
    config(['custom.system.class_list_page_size' => 200]);

    $method = new ReflectionMethod(DepartmentEnrolmentService::class, 'queryEnrolments');
    $parameter = $method->getParameters()[5];

    expect($parameter->getName())->toBe('perPage')
        ->and($parameter->isDefaultValueAvailable())->toBeTrue()
        ->and($parameter->getDefaultValue())->toBeNull();

    $classListsMethod = new ReflectionMethod(DepartmentEnrolmentService::class, 'queryClassLists');
    $classListsParameter = $classListsMethod->getParameters()[5];

    expect($classListsParameter->getName())->toBe('perPage')
        ->and($classListsParameter->getDefaultValue())->toBeNull();
});
