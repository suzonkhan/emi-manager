<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Upazilla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating EMI Management System User Hierarchy...');

        // Get locations for realistic addresses
        $dhakaUpazillas = $this->getDhakaUpazillas();
        $chittagongUpazillas = $this->getChittagongUpazillas();
        $rajshahiUpazillas = $this->getRajshahiUpazillas();

        // Create Super Admin (already exists from UserSeeder, but we'll ensure proper setup)
        $superAdmin = $this->createSuperAdmin($dhakaUpazillas[0]);

        // Create Dealers in major cities
        $dhakaDealers = $this->createDealers($superAdmin, $dhakaUpazillas, 'Dhaka');
        $chittagongDealers = $this->createDealers($superAdmin, $chittagongUpazillas, 'Chittagong');
        $rajshahiDealers = $this->createDealers($superAdmin, $rajshahiUpazillas, 'Rajshahi');

        // Create Sub Dealers under each Dealer
        $allSubDealers = [];
        foreach ([$dhakaDealers, $chittagongDealers, $rajshahiDealers] as $regionDealers) {
            foreach ($regionDealers as $dealer) {
                $subDealers = $this->createSubDealers($dealer);
                $allSubDealers = array_merge($allSubDealers, $subDealers);
            }
        }

        // Create Salesmen under each Sub Dealer
        foreach ($allSubDealers as $subDealer) {
            $this->createSalesmen($subDealer);
        }

        $this->command->info('User hierarchy created successfully!');
        $this->printHierarchySummary();
    }

    private function getDhakaUpazillas(): array
    {
        return Upazilla::with('district.division')
            ->whereHas('district', function ($query) {
                $query->whereHas('division', function ($query) {
                    $query->where('name', 'Dhaka');
                });
            })->limit(10)->get()->toArray();
    }

    private function getChittagongUpazillas(): array
    {
        return Upazilla::with('district.division')
            ->whereHas('district', function ($query) {
                $query->whereHas('division', function ($query) {
                    $query->where('name', 'Chittagong');
                });
            })->limit(8)->get()->toArray();
    }

    private function getRajshahiUpazillas(): array
    {
        return Upazilla::with('district.division')
            ->whereHas('district', function ($query) {
                $query->whereHas('division', function ($query) {
                    $query->where('name', 'Rajshahi');
                });
            })->limit(6)->get()->toArray();
    }

    private function createSuperAdmin(array $upazilla): User
    {
        // Check if super admin already exists
        $superAdmin = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first();

        if ($superAdmin) {
            $this->command->info('Super Admin already exists: '.$superAdmin->email);

            return $superAdmin;
        }

        $address = $this->createAddress([
            'street_address' => 'EMI Manager Head Office, Gulshan Avenue',
            'landmark' => 'Opposite to Gulshan 2 Circle',
            'postal_code' => '1212',
            'upazilla' => $upazilla,
        ]);

        $superAdmin = User::create([
            'name' => 'EMI System Administrator',
            'email' => 'admin@emimanager.com',
            'phone' => '+8801700000001',
            'password' => Hash::make('Admin@123'),
            'present_address_id' => $address->id,
            'permanent_address_id' => $address->id,
            'can_change_password' => true,
            'is_active' => true,
            'bkash_merchant_number' => '01700000001',
            'nagad_merchant_number' => '01700000001',
        ]);

        $superAdmin->assignRole('super_admin');
        $this->command->info('Super Admin created: '.$superAdmin->email);

        return $superAdmin;
    }

    private function createDealers(User $superAdmin, array $upazillas, string $region): array
    {
        $dealers = [];
        $dealerData = $this->getDealerData($region);

        foreach ($dealerData as $index => $data) {
            $upazilla = $upazillas[$index % count($upazillas)];

            $address = $this->createAddress([
                'street_address' => $data['address'],
                'landmark' => $data['landmark'],
                'postal_code' => $data['postal_code'],
                'upazilla' => $upazilla,
            ]);

            $dealer = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make('Dealer@123'),
                'parent_id' => $superAdmin->id,
                'present_address_id' => $address->id,
                'permanent_address_id' => $address->id,
                'can_change_password' => true,
                'is_active' => true,
                'bkash_merchant_number' => $data['bkash'],
                'nagad_merchant_number' => $data['nagad'],
            ]);

            $dealer->assignRole('dealer');
            $dealers[] = $dealer;

            $this->command->info("Dealer created: {$dealer->name} in {$region}");
        }

        return $dealers;
    }

    private function createSubDealers(User $dealer): array
    {
        $subDealers = [];
        $region = $this->getRegionFromAddress($dealer->presentAddress);
        $upazillas = $this->getUpazillasForRegion($region);

        $subDealerNames = [
            'North Zone Distribution',
            'South Zone Distribution',
            'Central Zone Distribution',
            'Commercial Zone Distribution',
        ];

        foreach ($subDealerNames as $index => $zoneName) {
            $upazilla = $upazillas[$index % count($upazillas)];

            $shopNumber = $index + 101;
            $address = $this->createAddress([
                'street_address' => "{$zoneName} Office, Shop {$shopNumber}",
                'landmark' => "Near {$upazilla['name']} Market",
                'postal_code' => substr($upazilla['postal_code'], 0, 4),
                'upazilla' => $upazilla,
            ]);

            $subDealer = User::create([
                'name' => "Manager - {$zoneName}",
                'email' => strtolower(str_replace(' ', '.', $zoneName)).".{$dealer->id}@emimanager.com",
                'phone' => '+880171'.str_pad($dealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
                'password' => Hash::make('SubDealer@123'),
                'parent_id' => $dealer->id,
                'present_address_id' => $address->id,
                'permanent_address_id' => $address->id,
                'can_change_password' => true,
                'is_active' => true,
                'bkash_merchant_number' => '0171'.str_pad($dealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
                'nagad_merchant_number' => '0171'.str_pad($dealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
            ]);

            $subDealer->assignRole('sub_dealer');
            $subDealers[] = $subDealer;

            $this->command->info("Sub Dealer created: {$subDealer->name} under {$dealer->name}");
        }

        return $subDealers;
    }

    private function createSalesmen(User $subDealer): array
    {
        $salesmen = [];
        $region = $this->getRegionFromAddress($subDealer->presentAddress);
        $upazillas = $this->getUpazillasForRegion($region);

        $salesmanNames = [
            'Rashid Ahmed', 'Karim Khan', 'Nasir Uddin',
            'Jamal Hossain', 'Faruk Rahman', 'Salam Sheikh',
        ];

        foreach ($salesmanNames as $index => $name) {
            $upazilla = $upazillas[$index % count($upazillas)];

            $houseNumber = $index + 10;
            $roadNumber = $index + 2;
            $address = $this->createAddress([
                'street_address' => "House #{$houseNumber}, Road #{$roadNumber}",
                'landmark' => "Near {$upazilla['name']} School",
                'postal_code' => $upazilla['postal_code'],
                'upazilla' => $upazilla,
            ]);

            $salesman = User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)).".{$subDealer->id}@emimanager.com",
                'phone' => '+880172'.str_pad($subDealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
                'password' => Hash::make('Salesman@123'),
                'parent_id' => $subDealer->id,
                'present_address_id' => $address->id,
                'permanent_address_id' => $address->id,
                'can_change_password' => true,
                'is_active' => true,
                'bkash_merchant_number' => '0172'.str_pad($subDealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
                'nagad_merchant_number' => '0172'.str_pad($subDealer->id * 10 + $index, 7, '0', STR_PAD_LEFT),
            ]);

            $salesman->assignRole('salesman');
            $salesmen[] = $salesman;

            $this->command->info("Salesman created: {$salesman->name} under {$subDealer->name}");
        }

        return $salesmen;
    }

    private function createAddress(array $data): Address
    {
        $upazilla = $data['upazilla'];

        return Address::create([
            'street_address' => $data['street_address'],
            'landmark' => $data['landmark'],
            'postal_code' => $data['postal_code'],
            'division_id' => $upazilla['district']['division_id'],
            'district_id' => $upazilla['district_id'],
            'upazilla_id' => $upazilla['id'],
        ]);
    }

    private function getDealerData(string $region): array
    {
        $data = [
            'Dhaka' => [
                [
                    'name' => 'Dhaka Central Dealer - Zahir Raihan',
                    'email' => 'zahir.dhaka@emimanager.com',
                    'phone' => '+8801710000001',
                    'address' => 'Motijheel Commercial Area, Block C',
                    'landmark' => 'Near Shapla Chattar',
                    'postal_code' => '1000',
                    'bkash' => '01710000001',
                    'nagad' => '01710000001',
                ],
                [
                    'name' => 'Dhaka North Dealer - Aminul Islam',
                    'email' => 'aminul.dhaka@emimanager.com',
                    'phone' => '+8801710000002',
                    'address' => 'Uttara Sector 7, House 15',
                    'landmark' => 'Near Uttara Town Center',
                    'postal_code' => '1230',
                    'bkash' => '01710000002',
                    'nagad' => '01710000002',
                ],
            ],
            'Chittagong' => [
                [
                    'name' => 'Chittagong Port Dealer - Rafiq Ahmed',
                    'email' => 'rafiq.chittagong@emimanager.com',
                    'phone' => '+8801710000003',
                    'address' => 'Agrabad Commercial Area',
                    'landmark' => 'Near Port City International University',
                    'postal_code' => '4100',
                    'bkash' => '01710000003',
                    'nagad' => '01710000003',
                ],
            ],
            'Rajshahi' => [
                [
                    'name' => 'Rajshahi Silk Dealer - Habibur Rahman',
                    'email' => 'habib.rajshahi@emimanager.com',
                    'phone' => '+8801710000004',
                    'address' => 'New Market Area, Shop 45',
                    'landmark' => 'Near Rajshahi University',
                    'postal_code' => '6000',
                    'bkash' => '01710000004',
                    'nagad' => '01710000004',
                ],
            ],
        ];

        return $data[$region] ?? [];
    }

    private function getRegionFromAddress(Address $address): string
    {
        $divisionName = $address->division->name ?? '';

        if (str_contains($divisionName, 'Dhaka')) {
            return 'Dhaka';
        }
        if (str_contains($divisionName, 'Chittagong')) {
            return 'Chittagong';
        }
        if (str_contains($divisionName, 'Rajshahi')) {
            return 'Rajshahi';
        }

        return 'Dhaka'; // Default fallback
    }

    private function getUpazillasForRegion(string $region): array
    {
        switch ($region) {
            case 'Chittagong':
                return $this->getChittagongUpazillas();
            case 'Rajshahi':
                return $this->getRajshahiUpazillas();
            default:
                return $this->getDhakaUpazillas();
        }
    }

    private function printHierarchySummary(): void
    {
        $counts = [
            'super_admin' => User::role('super_admin')->count(),
            'dealer' => User::role('dealer')->count(),
            'sub_dealer' => User::role('sub_dealer')->count(),
            'salesman' => User::role('salesman')->count(),
        ];

        $this->command->table(
            ['Role', 'Count'],
            [
                ['Super Admin', $counts['super_admin']],
                ['Dealer', $counts['dealer']],
                ['Sub Dealer', $counts['sub_dealer']],
                ['Salesman', $counts['salesman']],
                ['Total Users', array_sum($counts)],
            ]
        );
    }
}
