<?php

use App\Models\Customer;
use App\Models\DeviceCommandLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('device can send command response successfully', function () {
    // Create a customer with device
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
        'fcm_token' => 'test_fcm_token',
    ]);

    // Create a sent command log
    $commandLog = DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'REQUEST_LOCATION',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
        'sent_at' => now(),
    ]);

    // Device sends response
    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'REQUEST_LOCATION',
        'data' => [
            'latitude' => 23.8103,
            'longitude' => 90.4125,
            'accuracy' => 12.5,
            'timestamp' => '2025-10-16T10:30:00Z',
        ],
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'message' => 'Command response received successfully',
                'command_log_id' => $commandLog->id,
            ],
        ]);

    // Verify command log was updated
    $commandLog->refresh();
    expect($commandLog->status)->toBe('delivered');
    expect($commandLog->metadata)->toBeArray();
    expect($commandLog->metadata['latitude'])->toBe(23.8103);
    expect($commandLog->metadata['longitude'])->toBe(90.4125);
});

test('command response requires valid device_id', function () {
    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'INVALID_DEVICE',
        'command' => 'REQUEST_LOCATION',
        'data' => [],
    ]);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Device not found',
        ]);
});

test('command response finds device by serial number', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'SERIAL123',
    ]);

    DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'SERIAL123',
        'command' => 'LOCK_DEVICE',
        'data' => ['locked' => true],
    ]);

    $response->assertSuccessful();
});

test('command response finds device by imei_1', function () {
    $customer = Customer::factory()->create([
        'imei_1' => 'IMEI111111',
    ]);

    DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'IMEI111111',
        'command' => 'LOCK_DEVICE',
        'data' => ['locked' => true],
    ]);

    $response->assertSuccessful();
});

test('command response finds device by imei_2', function () {
    $customer = Customer::factory()->create([
        'imei_2' => 'IMEI222222',
    ]);

    DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'IMEI222222',
        'command' => 'LOCK_DEVICE',
        'data' => ['locked' => true],
    ]);

    $response->assertSuccessful();
});

test('command response requires matching command', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
    ]);

    DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    // Try to respond to a different command
    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'UNLOCK_DEVICE',
        'data' => [],
    ]);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Command log not found',
        ]);
});

test('command response only updates sent status commands', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
    ]);

    // Create a delivered command (should not be updated)
    DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'delivered',
        'sent_by' => User::factory()->create()->id,
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'LOCK_DEVICE',
        'data' => [],
    ]);

    $response->assertNotFound();
});

test('command response works without data field', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
    ]);

    $commandLog = DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'LOCK_DEVICE',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'LOCK_DEVICE',
    ]);

    $response->assertSuccessful();

    $commandLog->refresh();
    expect($commandLog->status)->toBe('delivered');
    expect($commandLog->metadata)->toBeArray();
    expect($commandLog->metadata)->toBeEmpty();
});

test('location data can be extracted from metadata', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
    ]);

    $commandLog = DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'REQUEST_LOCATION',
        'status' => 'sent',
        'sent_by' => User::factory()->create()->id,
    ]);

    $locationData = [
        'latitude' => 23.8103,
        'longitude' => 90.4125,
        'accuracy' => 12.5,
        'timestamp' => '2025-10-16T10:30:00Z',
    ];

    $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'REQUEST_LOCATION',
        'data' => $locationData,
    ]);

    $commandLog->refresh();

    // Test helper methods
    expect($commandLog->hasLocationResponse())->toBeTrue();
    expect($commandLog->getLocationData())->toBe($locationData);
});

test('command response finds latest sent command', function () {
    $customer = Customer::factory()->create([
        'serial_number' => 'TEST123',
    ]);

    $user = User::factory()->create();

    // Create multiple commands
    $oldCommand = DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'REQUEST_LOCATION',
        'status' => 'sent',
        'sent_by' => $user->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $newCommand = DeviceCommandLog::create([
        'customer_id' => $customer->id,
        'command' => 'REQUEST_LOCATION',
        'status' => 'sent',
        'sent_by' => $user->id,
        'created_at' => now()->subMinutes(5),
    ]);

    $response = $this->postJson('/api/devices/command-response', [
        'device_id' => 'TEST123',
        'command' => 'REQUEST_LOCATION',
        'data' => ['latitude' => 23.8103, 'longitude' => 90.4125],
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'data' => [
                'command_log_id' => $newCommand->id, // Should be the newer one
            ],
        ]);

    // Verify only the new command was updated
    $oldCommand->refresh();
    $newCommand->refresh();

    expect($oldCommand->status)->toBe('sent'); // Still sent
    expect($newCommand->status)->toBe('delivered'); // Updated
});
