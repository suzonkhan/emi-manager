<?php

namespace Database\Seeders;

use App\Models\Token;
use App\Models\TokenAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TokenManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Token Management System Data...');

        // Get users for token workflow
        $superAdmin = User::role('super_admin')->first();
        $dealers = User::role('dealer')->get();
        $subDealers = User::role('sub_dealer')->get();
        $salesmen = User::role('salesman')->get();

        if (! $superAdmin) {
            $this->command->error('Super Admin not found! Please run UserHierarchySeeder first.');

            return;
        }

        // Super Admin generates tokens
        $this->command->info('Super Admin generating tokens...');
        $totalTokens = 1000; // Large batch for system launch
        $generatedTokens = $this->generateTokens($superAdmin, $totalTokens);

        // Assign tokens to dealers (Super Admin → Dealers)
        $this->command->info('Assigning tokens to dealers...');
        $dealerTokens = $this->assignTokensToDealers($superAdmin, $dealers, $generatedTokens);

        // Assign tokens to sub-dealers (Dealers → Sub-Dealers)
        $this->command->info('Assigning tokens to sub-dealers...');
        $subDealerTokens = $this->assignTokensToSubDealers($dealerTokens);

        // Assign tokens to salesmen (Sub-Dealers → Salesmen)
        $this->command->info('Assigning tokens to salesmen...');
        $salesmenTokens = $this->assignTokensToSalesmen($subDealerTokens);

        // Some tokens used by salesmen (for customers that will be created later)
        $this->command->info('Marking some tokens as used by salesmen...');
        $this->markSomeTokensAsUsed($salesmenTokens);

        $this->command->info('Token management system seeded successfully!');
        $this->printTokenSummary();
    }

    private function generateTokens(User $superAdmin, int $count): array
    {
        $tokens = [];

        for ($i = 0; $i < $count; $i++) {
            $token = Token::create([
                'created_by' => $superAdmin->id,
                'status' => 'available',
            ]);

            // Record generation in token assignments
            TokenAssignment::create([
                'token_id' => $token->id,
                'from_user_id' => $superAdmin->id,
                'from_role' => 'super_admin',
                'to_user_id' => null,
                'to_role' => null,
                'assignment_type' => 'generation',
                'status' => 'completed',
                'metadata' => [
                    'batch_info' => 'System launch batch',
                    'generation_reason' => 'Initial system setup',
                ],
            ]);

            $tokens[] = $token;

            if (($i + 1) % 100 === 0) {
                $this->command->info('Generated '.($i + 1).' tokens...');
            }
        }

        return $tokens;
    }

    private function assignTokensToDealers(User $superAdmin, $dealers, array $tokens): array
    {
        $dealerTokens = [];
        $tokensPerDealer = intval(count($tokens) / $dealers->count());

        foreach ($dealers as $index => $dealer) {
            $startIndex = $index * $tokensPerDealer;
            $endIndex = min($startIndex + $tokensPerDealer, count($tokens));
            $dealerTokenCount = $endIndex - $startIndex;

            $dealerTokens[$dealer->id] = [];

            for ($i = $startIndex; $i < $endIndex; $i++) {
                $token = $tokens[$i];

                // Assign token to dealer
                $token->update([
                    'assigned_to' => $dealer->id,
                    'status' => 'assigned',
                    'assigned_at' => now()->subDays(rand(1, 30)),
                ]);

                // Record assignment
                TokenAssignment::create([
                    'token_id' => $token->id,
                    'from_user_id' => $superAdmin->id,
                    'from_role' => 'super_admin',
                    'to_user_id' => $dealer->id,
                    'to_role' => 'dealer',
                    'assignment_type' => 'assignment',
                    'status' => 'completed',
                    'assigned_at' => $token->assigned_at,
                    'metadata' => [
                        'assignment_reason' => 'Regional distribution',
                        'territory' => $this->getDealerTerritory($dealer),
                    ],
                ]);

                $dealerTokens[$dealer->id][] = $token;
            }

            $this->command->info("Assigned {$dealerTokenCount} tokens to dealer: {$dealer->name}");
        }

        return $dealerTokens;
    }

    private function assignTokensToSubDealers(array $dealerTokens): array
    {
        $subDealerTokens = [];

        foreach ($dealerTokens as $dealerId => $tokens) {
            $dealer = User::find($dealerId);
            $subDealers = User::role('sub_dealer')->where('parent_id', $dealerId)->get();

            if ($subDealers->isEmpty()) {
                continue;
            }

            $tokensPerSubDealer = intval(count($tokens) / $subDealers->count());

            foreach ($subDealers as $index => $subDealer) {
                $startIndex = $index * $tokensPerSubDealer;
                $endIndex = min($startIndex + $tokensPerSubDealer, count($tokens));

                $subDealerTokens[$subDealer->id] = [];

                for ($i = $startIndex; $i < $endIndex; $i++) {
                    $token = $tokens[$i];

                    // Assign token to sub-dealer
                    $token->update([
                        'assigned_to' => $subDealer->id,
                        'assigned_at' => now()->subDays(rand(1, 25)),
                    ]);

                    // Record assignment
                    TokenAssignment::create([
                        'token_id' => $token->id,
                        'from_user_id' => $dealer->id,
                        'from_role' => 'dealer',
                        'to_user_id' => $subDealer->id,
                        'to_role' => 'sub_dealer',
                        'assignment_type' => 'assignment',
                        'status' => 'completed',
                        'assigned_at' => $token->assigned_at,
                        'metadata' => [
                            'assignment_reason' => 'Zone distribution',
                            'zone' => $this->getSubDealerZone($subDealer),
                        ],
                    ]);

                    $subDealerTokens[$subDealer->id][] = $token;
                }

                $assignedCount = $endIndex - $startIndex;
                $this->command->info("Assigned {$assignedCount} tokens to sub-dealer: {$subDealer->name}");
            }
        }

        return $subDealerTokens;
    }

    private function assignTokensToSalesmen(array $subDealerTokens): array
    {
        $salesmenTokens = [];

        foreach ($subDealerTokens as $subDealerId => $tokens) {
            $subDealer = User::find($subDealerId);
            $salesmen = User::role('salesman')->where('parent_id', $subDealerId)->get();

            if ($salesmen->isEmpty()) {
                continue;
            }

            $tokensPerSalesman = intval(count($tokens) / $salesmen->count());

            foreach ($salesmen as $index => $salesman) {
                $startIndex = $index * $tokensPerSalesman;
                $endIndex = min($startIndex + $tokensPerSalesman, count($tokens));

                $salesmenTokens[$salesman->id] = [];

                for ($i = $startIndex; $i < $endIndex; $i++) {
                    $token = $tokens[$i];

                    // Assign token to salesman
                    $token->update([
                        'assigned_to' => $salesman->id,
                        'assigned_at' => now()->subDays(rand(1, 20)),
                    ]);

                    // Record assignment
                    TokenAssignment::create([
                        'token_id' => $token->id,
                        'from_user_id' => $subDealer->id,
                        'from_role' => 'sub_dealer',
                        'to_user_id' => $salesman->id,
                        'to_role' => 'salesman',
                        'assignment_type' => 'assignment',
                        'status' => 'completed',
                        'assigned_at' => $token->assigned_at,
                        'metadata' => [
                            'assignment_reason' => 'Field sales distribution',
                            'territory' => $this->getSalesmanTerritory($salesman),
                        ],
                    ]);

                    $salesmenTokens[$salesman->id][] = $token;
                }

                $assignedCount = $endIndex - $startIndex;
                $this->command->info("Assigned {$assignedCount} tokens to salesman: {$salesman->name}");
            }
        }

        return $salesmenTokens;
    }

    private function markSomeTokensAsUsed(array $salesmenTokens): void
    {
        $usedCount = 0;

        foreach ($salesmenTokens as $salesmanId => $tokens) {
            $salesman = User::find($salesmanId);

            // Use 20-40% of each salesman's tokens
            $tokensToUse = intval(count($tokens) * (rand(20, 40) / 100));

            for ($i = 0; $i < $tokensToUse; $i++) {
                $token = $tokens[$i];

                // Mark token as used
                $token->update([
                    'used_by' => $salesman->id,
                    'status' => 'used',
                    'used_at' => now()->subDays(rand(1, 15)),
                ]);

                // Record usage
                TokenAssignment::create([
                    'token_id' => $token->id,
                    'from_user_id' => $salesman->id,
                    'from_role' => 'salesman',
                    'to_user_id' => null,
                    'to_role' => null,
                    'assignment_type' => 'usage',
                    'status' => 'completed',
                    'assigned_at' => $token->used_at,
                    'metadata' => [
                        'usage_reason' => 'Customer onboarding',
                        'note' => 'Token used for EMI customer registration',
                    ],
                ]);

                $usedCount++;
            }
        }

        $this->command->info("Marked {$usedCount} tokens as used for customer registrations");
    }

    private function getDealerTerritory(User $dealer): string
    {
        $address = $dealer->presentAddress;
        $division = $address->division->name ?? 'Unknown';

        return "{$division} Division Territory";
    }

    private function getSubDealerZone(User $subDealer): string
    {
        $zones = ['North Zone', 'South Zone', 'Central Zone', 'Commercial Zone'];

        return $zones[($subDealer->id - 1) % count($zones)];
    }

    private function getSalesmanTerritory(User $salesman): string
    {
        $address = $salesman->presentAddress;
        $upazilla = $address->upazilla->name ?? 'Unknown';

        return "{$upazilla} Area Territory";
    }

    private function printTokenSummary(): void
    {
        $summary = [
            'total' => Token::count(),
            'available' => Token::where('status', 'available')->count(),
            'assigned' => Token::where('status', 'assigned')->count(),
            'used' => Token::where('status', 'used')->count(),
        ];

        $assignments = [
            'generations' => TokenAssignment::where('assignment_type', 'generation')->count(),
            'assignments' => TokenAssignment::where('assignment_type', 'assignment')->count(),
            'usages' => TokenAssignment::where('assignment_type', 'usage')->count(),
        ];

        $this->command->table(
            ['Status', 'Count'],
            [
                ['Total Tokens', $summary['total']],
                ['Available', $summary['available']],
                ['Assigned', $summary['assigned']],
                ['Used', $summary['used']],
                ['---', '---'],
                ['Token Generations', $assignments['generations']],
                ['Token Assignments', $assignments['assignments']],
                ['Token Usages', $assignments['usages']],
                ['Total Assignments', array_sum($assignments)],
            ]
        );

        // Role distribution
        $roleDistribution = [
            'Super Admin Created' => Token::where('created_by', User::role('super_admin')->first()?->id)->count(),
            'Assigned to Dealers' => Token::whereIn('assigned_to', User::role('dealer')->pluck('id'))->count(),
            'Assigned to Sub-Dealers' => Token::whereIn('assigned_to', User::role('sub_dealer')->pluck('id'))->count(),
            'Assigned to Salesmen' => Token::whereIn('assigned_to', User::role('salesman')->pluck('id'))->count(),
        ];

        $this->command->info("\nToken Distribution by Role:");
        foreach ($roleDistribution as $role => $count) {
            $this->command->line("  {$role}: {$count}");
        }
    }
}
