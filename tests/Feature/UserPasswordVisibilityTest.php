<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'dealer']);
    
    // Create test users
    $this->superAdmin = User::factory()->create([
        'email' => 'superadmin@test.com',
        'plain_password' => 'SuperAdminPass123'
    ]);
    $this->superAdmin->assignRole('super_admin');
    
    $this->dealer = User::factory()->create([
        'email' => 'dealer@test.com',
        'plain_password' => 'DealerPass123'
    ]);
    $this->dealer->assignRole('dealer');
    
    $this->regularUser = User::factory()->create([
        'email' => 'user@test.com',
        'plain_password' => 'UserPass123'
    ]);
    $this->regularUser->assignRole('dealer');
});

test('super admin can view user passwords in user list', function () {
    $response = $this->actingAs($this->superAdmin, 'sanctum')
        ->getJson('/api/users');
    
    $response->assertSuccessful();
    
    // Check that plain_password is included in the response
    $users = $response->json('data.users');
    expect($users)->toHaveCount(3);
    
    foreach ($users as $user) {
        expect($user)->toHaveKey('plain_password');
        expect($user['plain_password'])->not->toBeNull();
    }
});

test('non super admin cannot view user passwords in user list', function () {
    $response = $this->actingAs($this->dealer, 'sanctum')
        ->getJson('/api/users');
    
    $response->assertSuccessful();
    
    // Check that plain_password is not included or null
    $users = $response->json('data.users');
    
    foreach ($users as $user) {
        if (isset($user['plain_password'])) {
            expect($user['plain_password'])->toBeNull();
        }
    }
});

test('super admin can get individual user password', function () {
    $response = $this->actingAs($this->superAdmin, 'sanctum')
        ->getJson("/api/users/{$this->dealer->id}/password");
    
    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'password',
                'user_id',
                'user_name',
                'message'
            ]
        ]);
    
    expect($response->json('data.password'))->toBe('DealerPass123');
    expect($response->json('data.user_id'))->toBe($this->dealer->id);
});

test('non super admin cannot get individual user password', function () {
    $response = $this->actingAs($this->dealer, 'sanctum')
        ->getJson("/api/users/{$this->regularUser->id}/password");
    
    $response->assertForbidden();
});

test('unauthenticated user cannot get user password', function () {
    $response = $this->getJson("/api/users/{$this->dealer->id}/password");
    
    $response->assertUnauthorized();
});

test('password is stored correctly when creating user', function () {
    $userData = [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'phone' => '+8801700000001',
        'password' => 'NewUserPass123',
        'role' => 'dealer',
        'present_address' => [
            'division_id' => 1,
            'district_id' => 1,
            'upazilla_id' => 1,
            'address' => '123 Test St'
        ],
        'permanent_address' => [
            'division_id' => 1,
            'district_id' => 1,
            'upazilla_id' => 1,
            'address' => '123 Test St'
        ]
    ];
    
    $response = $this->actingAs($this->superAdmin, 'sanctum')
        ->postJson('/api/users', $userData);
    
    $response->assertSuccessful();
    
    $user = User::find($response->json('data.user.id'));
    expect($user->plain_password)->toBe('NewUserPass123');
    expect(\Hash::check('NewUserPass123', $user->password))->toBeTrue();
});

test('password is updated correctly when updating user', function () {
    $updateData = [
        'password' => 'UpdatedPassword123'
    ];
    
    $response = $this->actingAs($this->superAdmin, 'sanctum')
        ->putJson("/api/users/{$this->dealer->id}", $updateData);
    
    $response->assertSuccessful();
    
    $this->dealer->refresh();
    expect($this->dealer->plain_password)->toBe('UpdatedPassword123');
    expect(\Hash::check('UpdatedPassword123', $this->dealer->password))->toBeTrue();
});
