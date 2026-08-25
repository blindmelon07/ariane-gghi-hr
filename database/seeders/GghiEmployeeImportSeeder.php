<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Seeder;

/**
 * Imports the full GGHI employee roster (from "GGHI EMPLOYEES (1).xlsx",
 * sheet "LIST OF EMPLOYEES") and creates/updates the Department master list
 * to match.
 *
 * Safe to re-run: departments are firstOrCreate'd, and employees are matched
 * to existing records by normalized name before falling back to creating a
 * new one — running this twice will not create duplicates.
 *
 * Usage (e.g. on Hostinger):
 *   php artisan db:seed --class=GghiEmployeeImportSeeder
 */
class GghiEmployeeImportSeeder extends Seeder
{
    /**
     * Sheet SECTION/DEPARTMENT -> system Department name (direct 1:1 cases).
     */
    private const SECTION_MAP = [
        'NURSING'            => 'Nursing',
        'LABORATORY'         => 'Laboratory',
        'PHARMACY'           => 'Pharmacy',
        'COMMUNITY PHARMACY' => 'Community Pharmacy',
        'DIETARY'            => 'Dietary',
        'HEMODIALYSIS'       => 'Hemodialysis',
        'RADIOLOGY'          => 'Radiology',
    ];

    /**
     * ADMIN section positions -> system Department name.
     * Anything under ADMIN not listed here falls back to "Administration".
     */
    private const ADMIN_POSITION_MAP = [
        'ACCOUNTING CLERK'            => 'Accounting',
        'BOOKKEEPER'                  => 'Accounting',
        'JUNIOR BOOKKEEPER'           => 'Accounting',
        'CASHIER'                     => 'Accounting',
        'HEAD CASHIER'                => 'Accounting',
        'BILLING CLERK'               => 'Accounting',
        'CLAIMS CLERK'                => 'Accounting',
        'CLAIMS OFFICER'              => 'Accounting',
        'CREDIT AND COLLECTION STAFF' => 'Accounting',
        'GSAC SCHOLAR-CLAIMS ENDODER' => 'Accounting',
        'INTERNAL AUDIT STAFF'        => 'Accounting',
        'INTERNAL AUDITOR'            => 'Accounting',

        'PROCUREMENT STAFF'           => 'Procurement',
        'SUPPLY AND INVENTORY CLERK'  => 'Procurement',
        'PROPERTY CUSTODIAN'          => 'Procurement',

        'HD CLERK'                    => 'Housekeeping Department',
        'HD TECHNICIAN/ELECTRICIAN'   => 'Housekeeping Department',
        'JANITORIAL STAFF'            => 'Housekeeping Department',
        'LINEN/LAUNDRY STAFF'         => 'Housekeeping Department',
        'UTILITY STAFF'               => 'Housekeeping Department',
        'UTILITY/MAINTENANCE STAFF'   => 'Housekeeping Department',

        'IT HEAD'                     => 'Information Technology',
        'IT STAFF'                    => 'Information Technology',
        'PROJECT BASED IT'            => 'Information Technology',

        'HR OIC'                      => 'Human Resources',
        'HR STAFF'                    => 'Human Resources',
    ];

    /**
     * All departments this seeder can assign someone to, with a short code.
     * "Information Technology" is intentionally omitted — it's expected to
     * already exist, and firstOrCreate below won't touch it if so.
     */
    private const DEPARTMENTS = [
        'Information Technology'  => 'IT',
        'Nursing'                 => 'NURS',
        'Laboratory'              => 'LAB',
        'Pharmacy'                => 'PHARM',
        'Community Pharmacy'      => 'CPHARM',
        'Dietary'                 => 'DIET',
        'Hemodialysis'            => 'HEMO',
        'Radiology'               => 'RAD',
        'Human Resources'         => 'HR',
        'Administration'          => 'ADMN',
        'Accounting'              => 'ACCT',
        'Procurement'             => 'PROC',
        'Housekeeping Department' => 'HD',
    ];

    public function run(): void
    {
        $deptIds = $this->ensureDepartments();

        $path = __DIR__ . '/data/gghi_employees.json';
        if (! file_exists($path)) {
            $this->command?->error("Data file not found: {$path}");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);

        $existingByNormName = [];
        foreach (Employee::all() as $emp) {
            $key = $this->normalizeName(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? ''));
            $existingByNormName[$key] = $emp;
        }

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach ($rows as $row) {
            $deptName = $this->resolveDeptName($row);
            $deptId   = $deptIds[$deptName] ?? null;

            $match = $this->findExisting($row, $existingByNormName);

            if ($match) {
                $match->update([
                    'department_id' => $deptId,
                    'position'      => ucwords(mb_strtolower($row['position'])),
                ]);
                $updated++;
                continue;
            }

            $empCode = 'GGHI-' . str_pad((string) $row['row'], 3, '0', STR_PAD_LEFT);

            if (Employee::where('emp_code', $empCode)->exists()) {
                // Already imported in a previous run of this seeder — update, don't duplicate.
                Employee::where('emp_code', $empCode)->update([
                    'department_id' => $deptId,
                    'position'      => ucwords(mb_strtolower($row['position'])),
                ]);
                $updated++;
                continue;
            }

            Employee::create([
                'emp_code'        => $empCode,
                'first_name'      => ucwords(mb_strtolower($row['first'])),
                'last_name'       => ucwords(mb_strtolower($row['last'])),
                'position'        => ucwords(mb_strtolower($row['position'])),
                'department_id'   => $deptId,
                'is_active'       => true,
                'employment_type' => 'regular',
            ]);
            $created++;
        }

        $this->command?->info("GGHI employee import: {$created} created, {$updated} matched/updated.");

        if ($skipped) {
            $this->command?->warn('Skipped (needs manual review): ' . implode(', ', $skipped));
        }
    }

    /**
     * @return array<string,int> our canonical department name => id
     */
    private function ensureDepartments(): array
    {
        $deptIds = [];

        foreach (self::DEPARTMENTS as $name => $code) {
            // Match by name OR code: a department created ad hoc through the
            // admin UI before this seeder existed (e.g. "IT Department") may
            // already occupy that code under a different display name.
            // firstOrCreate() alone only checks name, so it would try to
            // insert a second row with the same code and hit the unique
            // constraint — look it up manually and only create if truly new.
            $dept = Department::where('name', $name)
                ->orWhere('code', $code)
                ->first();

            if (! $dept) {
                $dept = Department::create(['name' => $name, 'code' => $code, 'is_active' => true]);
            } elseif (! $dept->is_active) {
                $dept->update(['is_active' => true]);
            }

            // Keep our canonical label as the lookup key regardless of
            // whatever the department is actually named in the database.
            $deptIds[$name] = $dept->id;
        }

        // Earlier ad-hoc setup may have created a bare "HD" placeholder before
        // its meaning (Housekeeping Department) was confirmed — fold it in.
        $hd = Department::where('name', 'HD')->first();
        if ($hd && $hd->name !== 'Housekeeping Department') {
            $hd->update(['name' => 'Housekeeping Department']);
            $deptIds['Housekeeping Department'] = $hd->id;
        }

        return $deptIds;
    }

    private function resolveDeptName(array $row): string
    {
        $section  = strtoupper($row['dept']);
        $position = strtoupper($row['position']);

        if ($section === 'ADMIN') {
            return self::ADMIN_POSITION_MAP[$position] ?? 'Administration';
        }

        return self::SECTION_MAP[$section] ?? 'Administration';
    }

    private function findExisting(array $row, array $existingByNormName): ?Employee
    {
        $candidates = [
            $this->normalizeName($row['first'] . ' ' . $row['last']),
            $this->normalizeName($row['last'] . ' ' . $row['first']),
            $this->normalizeName($row['last'] . ' ' . $row['first'] . ' ' . $row['middle']),
        ];

        foreach ($candidates as $c) {
            if (isset($existingByNormName[$c])) {
                return $existingByNormName[$c];
            }
        }

        return null;
    }

    private function normalizeName(string $s): string
    {
        $s = str_replace(',', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return mb_strtolower(trim($s));
    }
}
