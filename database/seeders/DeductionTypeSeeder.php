<?php

namespace Database\Seeders;

use App\Models\DeductionType;
use Illuminate\Database\Seeder;

class DeductionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Government
            ['code' => 'SSS',        'name' => 'SSS Contribution',        'category' => 'government'],
            ['code' => 'PHIC',       'name' => 'PhilHealth Contribution', 'category' => 'government'],
            ['code' => 'HDMF',       'name' => 'Pag-IBIG Contribution',   'category' => 'government'],
            ['code' => 'TAX',        'name' => 'Withholding Tax',         'category' => 'government'],

            // Loans
            ['code' => 'SSS_LOAN',   'name' => 'SSS Loan',               'category' => 'loan'],
            ['code' => 'HDMF_LOAN',  'name' => 'Pag-IBIG Loan',          'category' => 'loan'],
            ['code' => 'HDMF_MP2',   'name' => 'Pag-IBIG MP2',           'category' => 'loan'],
            ['code' => 'CASH_ADV',   'name' => 'Cash Advance',           'category' => 'loan'],
            ['code' => 'COMPANY_LOAN', 'name' => 'Company Loan',         'category' => 'loan'],

            // Benefits
            ['code' => 'HMO',        'name' => 'HMO Premium',            'category' => 'benefit'],
            ['code' => 'UNIFORM',    'name' => 'Uniform',                'category' => 'benefit'],
            ['code' => 'RICE',       'name' => 'Rice Subsidy Deduction', 'category' => 'benefit'],

            // Other
            ['code' => 'OTHER',      'name' => 'Other Deduction',        'category' => 'other'],
        ];

        foreach ($types as $type) {
            DeductionType::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
