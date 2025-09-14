<?php

use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'dealer']);
    Role::create(['name' => 'sub_dealer']);
    Role::create(['name' => 'salesman']);
    Role::create(['name' => 'customer']);
});

it('allows super admin to assign any lower role', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $roleService = new RoleHierarchyService;

    expect($roleService->canAssignRole($superAdmin, 'dealer'))->toBeTrue();
    expect($roleService->canAssignRole($superAdmin, 'sub_dealer'))->toBeTrue();
    expect($roleService->canAssignRole($superAdmin, 'salesman'))->toBeTrue();
    expect($roleService->canAssignRole($superAdmin, 'customer'))->toBeTrue();
});

it('prevents dealer from assigning super_admin role', function () {
    $dealer = User::factory()->create();
    $dealer->assignRole('dealer');

    $roleService = new RoleHierarchyService;

    expect($roleService->canAssignRole($dealer, 'super_admin'))->toBeFalse();
    expect($roleService->canAssignRole($dealer, 'dealer'))->toBeFalse();
});

it('allows dealer to assign only lower roles', function () {
    $dealer = User::factory()->create();
    $dealer->assignRole('dealer');

    $roleService = new RoleHierarchyService;

    expect($roleService->canAssignRole($dealer, 'sub_dealer'))->toBeTrue();
    expect($roleService->canAssignRole($dealer, 'salesman'))->toBeTrue();
    expect($roleService->canAssignRole($dealer, 'customer'))->toBeTrue();
});

it('prevents customer from assigning any role', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $roleService = new RoleHierarchyService;

    expect($roleService->canAssignRole($customer, 'super_admin'))->toBeFalse();
    expect($roleService->canAssignRole($customer, 'dealer'))->toBeFalse();
    expect($roleService->canAssignRole($customer, 'sub_dealer'))->toBeFalse();
    expect($roleService->canAssignRole($customer, 'salesman'))->toBeFalse();
    expect($roleService->canAssignRole($customer, 'customer'))->toBeFalse();
});

it('validates role hierarchy on user creation via API', function () {
    $dealer = User::factory()->create();
    $dealer->assignRole('dealer');

    // Create required location data
    $division = \App\Models\Division::factory()->create();
    $district = \App\Models\District::factory()->create(['division_id' => $division->id]);
    $upazilla = \App\Models\Upazilla::factory()->create(['district_id' => $district->id]);

    $userData = [
        'name' => 'Test User',
        'phone' => '01234567890',
        'password' => 'password123',
        'role' => 'super_admin', // Dealer trying to create super_admin
        'present_address' => [
            'street_address' => '123 Test St',
            'division_id' => $division->id,
            'district_id' => $district->id,
            'upazilla_id' => $upazilla->id,
        ],
        'permanent_address' => [
            'street_address' => '123 Test St',
            'division_id' => $division->id,
            'district_id' => $district->id,
            'upazilla_id' => $upazilla->id,
        ],
    ];

    $response = $this->actingAs($dealer)->postJson('/api/users', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('role');
});

it('allows valid role assignment via API', function () {
    $dealer = User::factory()->create();
    $dealer->assignRole('dealer');

    // Create required location data
    $division = \App\Models\Division::factory()->create();
    $district = \App\Models\District::factory()->create(['division_id' => $division->id]);
    $upazilla = \App\Models\Upazilla::factory()->create(['district_id' => $district->id]);

    $userData = [
        'name' => 'Test User',
        'phone' => '01234567890',
        'password' => 'password123',
        'role' => 'sub_dealer', // Dealer creating sub_dealer (allowed)
        'present_address' => [
            'street_address' => '123 Test St',
            'division_id' => $division->id,
            'district_id' => $district->id,
            'upazilla_id' => $upazilla->id,
        ],
        'permanent_address' => [
            'street_address' => '123 Test St',
            'division_id' => $division->id,
            'district_id' => $district->id,
            'upazilla_id' => $upazilla->id,
        ],
    ];

    $response = $this->actingAs($dealer)->postJson('/api/users', $userData);

    $response->assertStatus(201)
        ->assertJsonPath('data.user.roles.0.name', 'sub_dealer');
});
