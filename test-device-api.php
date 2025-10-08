#!/usr/bin/env php
<?php

/**
 * Quick FCM Token Tester
 * 
 * This script helps you test your device control APIs without needing a real Android device.
 * It simulates a device registration and command sending.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║         FCM Device Control API Tester                     ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Configuration
$baseUrl = 'http://localhost:8000/api';
$token = null;

// Helper functions
function generateTestFCMToken() {
    $prefix = 'eXXX';
    $suffix = 'XXXe';
    $middle = bin2hex(random_bytes(64));
    return $prefix . $middle . $suffix;
}

function makeRequest($method, $endpoint, $data = [], $requiresAuth = false) {
    global $baseUrl, $token;
    
    $url = $baseUrl . $endpoint;
    $headers = ['Accept' => 'application/json'];
    
    if ($requiresAuth && $token) {
        $headers['Authorization'] = 'Bearer ' . $token;
    }
    
    try {
        $response = match($method) {
            'GET' => Http::withHeaders($headers)->get($url),
            'POST' => Http::withHeaders($headers)->post($url, $data),
            'PATCH' => Http::withHeaders($headers)->patch($url, $data),
            default => throw new Exception("Unsupported method: $method"),
        };
        
        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'data' => $response->json(),
            'body' => $response->body(),
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

function printResponse($response) {
    echo "\n";
    echo "Status: " . ($response['status'] ?? 'N/A') . "\n";
    echo "Response:\n";
    echo json_encode($response['data'] ?? $response, JSON_PRETTY_PRINT) . "\n";
    echo "\n";
}

// Main Menu
function showMenu() {
    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  MAIN MENU\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  [1] Login (Get Auth Token)\n";
    echo "  [2] Register Test Device\n";
    echo "  [3] Get Device Info\n";
    echo "  [4] Send Lock Device Command\n";
    echo "  [5] Send Unlock Device Command\n";
    echo "  [6] Send Show Message Command\n";
    echo "  [7] Get Command History\n";
    echo "  [8] Get Available Commands\n";
    echo "  [9] Test Firebase Connection\n";
    echo "  [0] Exit\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "\n";
}

// Test Functions
function testLogin() {
    echo "🔐 Testing Login...\n";
    
    $email = readline("Enter email (default: admin@emimanager.com): ") ?: 'admin@emimanager.com';
    $password = readline("Enter password (default: Admin@123): ") ?: 'Admin@123';
    
    $response = makeRequest('POST', '/auth/login', [
        'login' => $email,
        'password' => $password,
    ]);
    
    printResponse($response);
    
    if ($response['success'] && isset($response['data']['data']['token'])) {
        global $token;
        $token = $response['data']['data']['token'];
        echo "✅ Token saved! You can now make authenticated requests.\n";
    }
}

function testRegisterDevice() {
    echo "📱 Registering Test Device...\n\n";
    
    // Check if we have customers
    $customer = \App\Models\Customer::first();
    
    if (!$customer) {
        echo "❌ No customers found in database.\n";
        echo "Please create a customer first or run: php artisan db:seed\n";
        return;
    }
    
    // Check if customer has IMEI
    if (!$customer->imei_1) {
        echo "❌ Customer (ID: {$customer->id}) doesn't have IMEI1 set.\n";
        echo "Updating customer with test IMEI...\n";
        $customer->update(['imei_1' => '356740000000000']);
        $customer = $customer->fresh();
    }
    
    $serialNumber = 'TEST_' . strtoupper(substr(md5(time()), 0, 10));
    $imei1 = $customer->imei_1;
    $fcmToken = generateTestFCMToken();
    
    echo "Customer ID: {$customer->id}\n";
    echo "Customer Name: {$customer->name}\n";
    echo "Serial Number: {$serialNumber}\n";
    echo "IMEI1: {$imei1}\n";
    echo "FCM Token: " . substr($fcmToken, 0, 40) . "...\n\n";
    
    $response = makeRequest('POST', '/devices/register', [
        'serial_number' => $serialNumber,
        'imei1' => $imei1,
        'fcm_token' => $fcmToken,
    ]);
    
    printResponse($response);
    
    if ($response['success']) {
        echo "✅ Device registered successfully!\n";
        echo "💾 Saved customer ID for future commands: {$customer->id}\n";
        
        // Save customer ID for later use
        file_put_contents(__DIR__ . '/.last_customer_id', $customer->id);
    }
}

function testGetDeviceInfo() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    $customerId = getLastCustomerId();
    if (!$customerId) {
        echo "❌ Please register a device first (option 2)\n";
        return;
    }
    
    echo "📱 Getting Device Info (Customer ID: {$customerId})...\n";
    
    $response = makeRequest('GET', "/devices/{$customerId}", [], true);
    printResponse($response);
}

function testLockDevice() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    $customerId = getLastCustomerId();
    if (!$customerId) {
        echo "❌ Please register a device first (option 2)\n";
        return;
    }
    
    echo "🔒 Sending Lock Device Command...\n";
    
    $response = makeRequest('POST', '/devices/command/lock', [
        'customer_id' => $customerId,
    ], true);
    
    printResponse($response);
}

function testUnlockDevice() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    $customerId = getLastCustomerId();
    if (!$customerId) {
        echo "❌ Please register a device first (option 2)\n";
        return;
    }
    
    echo "🔓 Sending Unlock Device Command...\n";
    
    $response = makeRequest('POST', '/devices/command/unlock', [
        'customer_id' => $customerId,
    ], true);
    
    printResponse($response);
}

function testShowMessage() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    $customerId = getLastCustomerId();
    if (!$customerId) {
        echo "❌ Please register a device first (option 2)\n";
        return;
    }
    
    echo "💬 Sending Show Message Command...\n\n";
    
    $title = readline("Message title (default: Payment Reminder): ") ?: 'Payment Reminder';
    $message = readline("Message text (default: Please pay your EMI): ") ?: 'Please pay your EMI installment.';
    
    $response = makeRequest('POST', '/devices/command/show-message', [
        'customer_id' => $customerId,
        'title' => $title,
        'message' => $message,
    ], true);
    
    printResponse($response);
}

function testCommandHistory() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    $customerId = getLastCustomerId();
    if (!$customerId) {
        echo "❌ Please register a device first (option 2)\n";
        return;
    }
    
    echo "📜 Getting Command History (Customer ID: {$customerId})...\n";
    
    $response = makeRequest('GET', "/devices/{$customerId}/history", [], true);
    printResponse($response);
}

function testAvailableCommands() {
    global $token;
    
    if (!$token) {
        echo "❌ Please login first (option 1)\n";
        return;
    }
    
    echo "📋 Getting Available Commands...\n";
    
    $response = makeRequest('GET', '/devices/commands', [], true);
    printResponse($response);
}

function testFirebaseConnection() {
    echo "🔥 Testing Firebase Connection...\n\n";
    system('php artisan firebase:test');
}

function getLastCustomerId() {
    $file = __DIR__ . '/.last_customer_id';
    if (file_exists($file)) {
        return (int) file_get_contents($file);
    }
    
    // Try to get first customer
    $customer = \App\Models\Customer::first();
    return $customer ? $customer->id : null;
}

// Main Loop
while (true) {
    showMenu();
    
    $choice = readline("Select option (0-9): ");
    
    switch ($choice) {
        case '1':
            testLogin();
            break;
        case '2':
            testRegisterDevice();
            break;
        case '3':
            testGetDeviceInfo();
            break;
        case '4':
            testLockDevice();
            break;
        case '5':
            testUnlockDevice();
            break;
        case '6':
            testShowMessage();
            break;
        case '7':
            testCommandHistory();
            break;
        case '8':
            testAvailableCommands();
            break;
        case '9':
            testFirebaseConnection();
            break;
        case '0':
            echo "\n👋 Goodbye!\n\n";
            exit(0);
        default:
            echo "\n❌ Invalid choice. Please try again.\n";
    }
    
    readline("\nPress Enter to continue...");
}
