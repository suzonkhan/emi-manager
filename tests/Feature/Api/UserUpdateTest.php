<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'dealer']);
        Role::create(['name' => 'sub_dealer']);
        Role::create(['name' => 'salesman']);
    }

    public function test_user_update_basic_data()
    {
        // Create a super admin user
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'phone' => '01700000000',
        ]);
        $superAdmin->assignRole('super_admin');
        $superAdmin = $superAdmin->fresh(); // Ensure we have a fresh instance

        // Create a user to update
        $userToUpdate = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'phone' => '01700000001',
            'parent_id' => $superAdmin->id,
        ]);
        $userToUpdate->assignRole('dealer');

        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@test.com',
            'phone' => '01700000002',
            'bkash_merchant_number' => '01700000003',
            'nagad_merchant_number' => '01700000004',
        ];

        $response = $this->actingAs($superAdmin)
            ->putJson("/api/users/{$userToUpdate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'bkash_merchant_number',
                        'nagad_merchant_number',
                    ],
                ],
            ]);

        // Verify the user was updated
        $updatedUser = User::find($userToUpdate->id);
        $this->assertEquals('Updated User Name', $updatedUser->name);
        $this->assertEquals('updated@test.com', $updatedUser->email);
        $this->assertEquals('01700000002', $updatedUser->phone);
        $this->assertEquals('01700000003', $updatedUser->bkash_merchant_number);
        $this->assertEquals('01700000004', $updatedUser->nagad_merchant_number);
    }

    public function test_users_list_excludes_current_user()
    {
        // Create a super admin user
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'phone' => '01700000000',
        ]);
        $superAdmin->assignRole('super_admin');
        $superAdmin = $superAdmin->fresh(); // Ensure we have a fresh instance

        // Create other users
        $user1 = User::factory()->create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'phone' => '01700000001',
            'parent_id' => $superAdmin->id,
        ]);
        $user1->assignRole('dealer');

        $user2 = User::factory()->create([
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'phone' => '01700000002',
            'parent_id' => $superAdmin->id,
        ]);
        $user2->assignRole('dealer');

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/users');

        $response->assertStatus(200);

        $users = $response->json('data.users');

        // Verify current user is not in the list
        $userIds = collect($users)->pluck('id')->toArray();
        $this->assertNotContains($superAdmin->id, $userIds);

        // Verify other users are in the list
        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
    }
}
