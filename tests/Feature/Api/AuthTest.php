<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    $this->artisan('db:seed', ['--class' => 'BangladeshLocationSeeder']);
    $this->artisan('db:seed', ['--class' => 'UserSeeder']);
});

it('can login with valid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'login' => 'superadmin@emimanager.com',
        'password' => 'SuperAdmin@123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'unique_id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'permissions',
                    'can_change_password',
                    'is_active',
                ],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'email' => 'superadmin@emimanager.com',
                    'role' => 'super_admin',
                ],
                'token_type' => 'Bearer',
            ],
        ]);
});

it('cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'login' => 'superadmin@emimanager.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'login' => ['The provided credentials are incorrect.'],
            ],
        ]);
});

it('can get user profile when authenticated', function () {
    $user = User::where('email', 'superadmin@emimanager.com')->first();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/auth/profile');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'unique_id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'permissions',
                    'present_address',
                    'permanent_address',
                    'can_change_password',
                    'is_active',
                    'last_login_at',
                    'hierarchy_level',
                    'created_at',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Profile retrieved successfully',
        ]);
});

it('can logout successfully', function () {
    $user = User::where('email', 'superadmin@emimanager.com')->first();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/auth/logout');

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
});
