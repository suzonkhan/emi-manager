<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Token;
use App\Models\Upazilla;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Customer Data for EMI Management System...');

        // Get used tokens and their users
        $usedTokens = Token::with(['usedBy'])
            ->where('status', 'used')
            ->whereNotNull('used_by')
            ->get();

        if ($usedTokens->isEmpty()) {
            $this->command->error('No used tokens found! Please run TokenManagementSeeder first.');

            return;
        }

        $createdCount = 0;

        foreach ($usedTokens as $token) {
            $salesman = $token->usedBy;

            if (! $salesman || ! $salesman->hasRole('salesman')) {
                continue;
            }

            // Create customer with realistic data
            $customer = $this->createCustomerForToken($token, $salesman);

            if ($customer) {
                $createdCount++;

                if ($createdCount % 50 === 0) {
                    $this->command->info("Created {$createdCount} customers...");
                }
            }
        }

        $this->command->info('Customer data seeded successfully!');
        $this->printCustomerSummary();
    }

    private function createCustomerForToken(Token $token, User $salesman): ?Customer
    {
        try {
            // Get realistic address for customer (near salesman's territory)
            $salesmanUpazilla = $salesman->presentAddress->upazilla;
            $customerUpazillas = $this->getNearbyUpazillas($salesmanUpazilla);
            $customerUpazilla = $customerUpazillas[array_rand($customerUpazillas)];

            // Generate customer data
            $customerData = $this->generateCustomerData();
            $productData = $this->generateProductData();
            $emiData = $this->calculateEMIData($productData);

            // Create addresses
            $presentAddress = $this->createCustomerAddress($customerUpazilla, 'present');
            $permanentAddress = $this->createCustomerAddress($customerUpazilla, 'permanent');

            // Create customer
            $customer = Customer::create([
                'nid_no' => $this->generateNIDNumber(),
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'mobile' => $customerData['mobile'],
                'present_address_id' => $presentAddress->id,
                'permanent_address_id' => $permanentAddress->id,
                'token_id' => $token->id,
                'emi_duration_months' => $emiData['duration'],
                'product_type' => $productData['type'],
                'product_model' => $productData['model'],
                'product_price' => $productData['price'],
                'emi_per_month' => $emiData['monthly_amount'],
                'imei_1' => $this->generateIMEI(),
                'imei_2' => $this->generateIMEI(),
                'created_by' => $salesman->id,
                'documents' => $this->generateDocuments(),
                'status' => $this->assignCustomerStatus(),
                'created_at' => $token->used_at ?? now()->subDays(rand(1, 15)),
                'updated_at' => now()->subDays(rand(0, 5)),
            ]);

            return $customer;

        } catch (\Exception $e) {
            $this->command->error("Failed to create customer for token {$token->code}: ".$e->getMessage());

            return null;
        }
    }

    private function generateCustomerData(): array
    {
        $names = [
            'Abdul Rahman Khan', 'Fatima Begum', 'Mohammad Ali Sheikh', 'Rashida Khatun',
            'Aminul Islam', 'Nasreen Akter', 'Karim Uddin', 'Salma Begum',
            'Rafiq Ahmed', 'Rahela Khatun', 'Jamal Hossain', 'Ruma Akter',
            'Nasir Uddin', 'Shirin Begum', 'Habib Rahman', 'Kulsum Khatun',
            'Fazlur Rahman', 'Marium Begum', 'Delwar Hossain', 'Rubina Akter',
            'Siraj Uddin', 'Maksuda Begum', 'Nurul Islam', 'Rashida Begum',
            'Abdul Karim', 'Shahida Khatun', 'Mizanur Rahman', 'Nazma Begum',
            'Golam Mostafa', 'Sahera Khatun', 'Shahjahan Ali', 'Ruma Khatun',
        ];

        $name = $names[array_rand($names)];
        $email = $this->generateEmail($name);
        $mobile = $this->generateMobileNumber();

        return [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
        ];
    }

    private function generateProductData(): array
    {
        $products = [
            // Smartphones
            ['type' => 'smartphone', 'model' => 'Samsung Galaxy A54', 'price' => 42000],
            ['type' => 'smartphone', 'model' => 'iPhone 13', 'price' => 85000],
            ['type' => 'smartphone', 'model' => 'Xiaomi Redmi Note 12', 'price' => 28000],
            ['type' => 'smartphone', 'model' => 'Realme 10 Pro', 'price' => 35000],
            ['type' => 'smartphone', 'model' => 'Oppo A78', 'price' => 32000],
            ['type' => 'smartphone', 'model' => 'Vivo V27', 'price' => 48000],
            ['type' => 'smartphone', 'model' => 'OnePlus Nord CE 3', 'price' => 45000],

            // Motorcycles
            ['type' => 'motorcycle', 'model' => 'Hero Splendor Plus', 'price' => 125000],
            ['type' => 'motorcycle', 'model' => 'Bajaj Pulsar 150', 'price' => 180000],
            ['type' => 'motorcycle', 'model' => 'Honda CB Shine', 'price' => 145000],
            ['type' => 'motorcycle', 'model' => 'TVS Apache RTR 160', 'price' => 165000],
            ['type' => 'motorcycle', 'model' => 'Yamaha FZ-S V3', 'price' => 155000],

            // Home Appliances
            ['type' => 'refrigerator', 'model' => 'Walton WFC-2A4-GDEL', 'price' => 45000],
            ['type' => 'refrigerator', 'model' => 'Samsung RT28T3722S8', 'price' => 55000],
            ['type' => 'washing_machine', 'model' => 'LG T7288NDDL', 'price' => 38000],
            ['type' => 'air_conditioner', 'model' => 'General ASGA18FMTA', 'price' => 65000],
            ['type' => 'television', 'model' => 'Sony Bravia 43W66F', 'price' => 52000],

            // Furniture
            ['type' => 'sofa_set', 'model' => '5 Seater L-Shape Sofa', 'price' => 75000],
            ['type' => 'dining_set', 'model' => '6 Chair Dining Table', 'price' => 45000],
            ['type' => 'bedroom_set', 'model' => 'King Size Bed Set', 'price' => 85000],
        ];

        return $products[array_rand($products)];
    }

    private function calculateEMIData(array $productData): array
    {
        $price = $productData['price'];

        // Different EMI durations based on product type and price
        $durationOptions = [];

        if ($price <= 30000) {
            $durationOptions = [6, 9, 12];
        } elseif ($price <= 60000) {
            $durationOptions = [12, 18, 24];
        } elseif ($price <= 100000) {
            $durationOptions = [18, 24, 36];
        } else {
            $durationOptions = [24, 36, 48];
        }

        $duration = $durationOptions[array_rand($durationOptions)];

        // Calculate EMI with interest (10-18% annual)
        $interestRate = rand(10, 18) / 100; // Annual interest rate
        $monthlyRate = $interestRate / 12;

        // EMI calculation using formula: P * r * (1+r)^n / ((1+r)^n - 1)
        $emi = ($price * $monthlyRate * pow(1 + $monthlyRate, $duration)) /
               (pow(1 + $monthlyRate, $duration) - 1);

        return [
            'duration' => $duration,
            'monthly_amount' => round($emi, 2),
        ];
    }

    private function createCustomerAddress(array $upazilla, string $type = 'present'): Address
    {
        $streets = [
            'Main Road', 'Station Road', 'Shaheed Minar Road', 'College Road',
            'Hospital Road', 'Bazar Road', 'School Road', 'Mosque Road',
            'Village Road', 'Railway Road', 'Post Office Road', 'Bank Road',
        ];

        $landmarks = [
            'Near Central Mosque', 'Opposite to Primary School', 'Behind Community Center',
            'Near Bus Stand', 'Beside Medical Center', 'Near Market Area',
            'Close to Police Station', 'Next to Post Office', 'Near High School',
            'Beside Union Parishad', 'Near Pond', 'Close to Playground',
        ];

        $houseNumber = rand(1, 200);
        $street = $streets[array_rand($streets)];
        $landmark = $landmarks[array_rand($landmarks)];

        return Address::create([
            'street_address' => "House #{$houseNumber}, {$street}",
            'landmark' => $landmark,
            'postal_code' => $upazilla['postal_code'],
            'division_id' => $upazilla['district']['division_id'],
            'district_id' => $upazilla['district_id'],
            'upazilla_id' => $upazilla['id'],
        ]);
    }

    private function getNearbyUpazillas(object $salesmanUpazilla): array
    {
        // Get upazillas in the same district or nearby districts
        return Upazilla::with(['district.division'])
            ->where(function ($query) use ($salesmanUpazilla) {
                $query->where('district_id', $salesmanUpazilla->district_id)
                    ->orWhereHas('district', function ($q) use ($salesmanUpazilla) {
                        $q->where('division_id', $salesmanUpazilla->district->division_id);
                    });
            })
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function generateEmail(string $name): string
    {
        $domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
        $cleanName = strtolower(str_replace(' ', '.', $name));
        $domain = $domains[array_rand($domains)];
        $number = rand(10, 99);

        return "{$cleanName}{$number}@{$domain}";
    }

    private function generateMobileNumber(): string
    {
        $operators = ['017', '019', '015', '016', '018', '013'];
        $operator = $operators[array_rand($operators)];
        $number = $operator.rand(10000000, 99999999);

        return "0{$number}";
    }

    private function generateNIDNumber(): string
    {
        // Generate 10 or 13 digit NID number
        $isOldFormat = rand(0, 1);

        if ($isOldFormat) {
            // 10 digit old format
            return (string) rand(1000000000, 9999999999);
        } else {
            // 13 digit new format
            return (string) rand(1000000000000, 9999999999999);
        }
    }

    private function generateIMEI(): string
    {
        // Generate 15-digit IMEI
        $imei = '';
        for ($i = 0; $i < 15; $i++) {
            $imei .= rand(0, 9);
        }

        return $imei;
    }

    private function generateDocuments(): array
    {
        $documents = [];

        // Required documents
        $requiredDocs = ['nid_copy', 'photo', 'salary_certificate'];

        foreach ($requiredDocs as $doc) {
            $documents[] = [
                'type' => $doc,
                'filename' => $doc.'_'.now()->timestamp.'_'.rand(1000, 9999).'.pdf',
                'uploaded_at' => now()->subDays(rand(1, 10))->toDateTimeString(),
                'status' => 'verified',
            ];
        }

        // Optional documents (50% chance)
        $optionalDocs = ['bank_statement', 'utility_bill', 'reference_letter'];

        foreach ($optionalDocs as $doc) {
            if (rand(0, 1)) {
                $documents[] = [
                    'type' => $doc,
                    'filename' => $doc.'_'.now()->timestamp.'_'.rand(1000, 9999).'.pdf',
                    'uploaded_at' => now()->subDays(rand(1, 8))->toDateTimeString(),
                    'status' => 'verified',
                ];
            }
        }

        return $documents;
    }

    private function assignCustomerStatus(): string
    {
        $statuses = [
            'active' => 70,      // 70% active customers
            'completed' => 15,   // 15% completed EMI
            'defaulted' => 10,   // 10% defaulted
            'cancelled' => 5,    // 5% cancelled
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return 'active'; // Default fallback
    }

    private function printCustomerSummary(): void
    {
        $total = Customer::count();
        $byStatus = [
            'Active' => Customer::where('status', 'active')->count(),
            'Completed' => Customer::where('status', 'completed')->count(),
            'Defaulted' => Customer::where('status', 'defaulted')->count(),
            'Cancelled' => Customer::where('status', 'cancelled')->count(),
        ];

        $byProduct = Customer::select('product_type')
            ->selectRaw('count(*) as count')
            ->groupBy('product_type')
            ->get()
            ->pluck('count', 'product_type')
            ->toArray();

        $emiStats = [
            'Total EMI Value' => '৳'.number_format(Customer::sum('product_price'), 2),
            'Monthly EMI Collection' => '৳'.number_format(Customer::where('status', 'active')->sum('emi_per_month'), 2),
            'Average EMI Duration' => round(Customer::avg('emi_duration_months'), 1).' months',
            'Average Product Price' => '৳'.number_format(Customer::avg('product_price'), 2),
        ];

        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', $total],
                ['---', '---'],
                ['Active Customers', $byStatus['Active']],
                ['Completed EMIs', $byStatus['Completed']],
                ['Defaulted Customers', $byStatus['Defaulted']],
                ['Cancelled EMIs', $byStatus['Cancelled']],
            ]
        );

        $this->command->info("\nProduct Distribution:");
        foreach ($byProduct as $product => $count) {
            $percentage = round(($count / $total) * 100, 1);
            $this->command->line("  {$product}: {$count} ({$percentage}%)");
        }

        $this->command->info("\nEMI Statistics:");
        foreach ($emiStats as $metric => $value) {
            $this->command->line("  {$metric}: {$value}");
        }
    }
}
