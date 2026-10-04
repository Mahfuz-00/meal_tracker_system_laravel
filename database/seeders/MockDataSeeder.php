<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\Department;
use App\Models\Deposit;
use App\Models\Institution;
use App\Models\MealEntry;
use App\Models\MealExpense;
use App\Models\MealMenu;
use App\Models\MealMenuOption;
use App\Models\MealMenuVote;
use App\Models\Student;
use App\Models\Subsidy;
use App\Models\SubsidySource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * ==========================================================================
 * MOCK DATA SEEDER - LOCAL / DEMO ONLY. NEVER RUN THIS IN PRODUCTION.
 * ==========================================================================
 *
 * THIS FILE IS GIT-IGNORED (see .gitignore -> `/database/seeders/MockDataSeeder.php`).
 *
 * It exists so a developer or a demo environment can spin up a rich, realistic
 * dataset spanning FIVE institutions - members, deposits, meals, expenses,
 * subsidies, vendors, menu votes, subscriptions and claims - without that sample
 * data ever reaching version control, and therefore without any chance of it
 * being deployed to production.
 *
 * WHY A SEPARATE, IGNORED FILE (and not a flag on DatabaseSeeder)
 * ---------------------------------------------------------------
 * A `--demo` flag inside the committed `DatabaseSeeder` would ship the sample
 * data (names, phone numbers, fake ledgers) in the repository. Anyone running
 * `php artisan db:seed` in production would then have the data available to
 * create. Keeping the file out of git makes deployment of it IMPOSSIBLE rather
 * than merely discouraged - the strongest available guarantee.
 *
 * HOW TO RUN
 * ----------
 *     php artisan db:seed --class=MockDataSeeder
 *     php artisan db:seed --class=MockDataSeeder --force     # non-interactive
 *
 * To wipe it again, use the built-in reset:
 *
 *     php artisan db:clear-dummy --force
 *
 * WHICH TARGET DATABASE?
 * ----------------------
 * It refuses to run when the app environment is `production` unless
 * `--force` is ALSO passed with `MOCK_DATA_ALLOW_PRODUCTION=true`. That double
 * opt-in exists so a single stray command cannot pollute a live system.
 *
 * IDEMPOTENCE
 * -----------
 * Every row is created through `firstOrCreate` / `updateOrCreate` keyed on a
 * natural identifier (slug, code, email, roll+institution, date+member). Running
 * the seeder twice does NOT duplicate anything and does NOT touch the platform
 * owner's account. Members' linked user accounts are created ONLY when missing,
 * so their passwords are never overwritten.
 *
 * THE FIVE INSTITUTIONS
 * ----------------------
 *   1. North South University Hall   - university_dorm  (Students, BDT)
 *   2. Touch and Solve Ltd.          - company          (Employees, BDT)
 *   3. Rajshahi College Hostel       - college_dorm     (Boarders, BDT)
 *   4. Greenfield Corporate Canteen  - company          (Employees, USD)
 *   5. Mirpur General Mess           - general_mess     (Members, BDT)
 *
 * The spread is deliberate: two institution TYPES that share the same label
 * (company) but different currencies, one dorm, one hostel and one generic mess,
 * so every terminology preset, currency format and subsidy mode gets exercised.
 */
class MockDataSeeder extends Seeder
{
    /**
     * The shared password every mock account is created with.
     *
     * Only ever applied to accounts this seeder CREATES - an existing account's
     * credential is left untouched, so a developer who changed their local
     * password does not have it reset by a re-run.
     */
    protected const MOCK_PASSWORD = 'password';

    /** Guard against a re-run multiplying the fixture volume. */
    protected const MONTHS_OF_HISTORY = 2;

    public function run(): void
    {
        if (! $this->environmentAllowsSeeding()) {
            return;
        }

        $this->command?->info('Seeding mock data for 5 institutions (local/demo only)...');

        foreach ($this->institutionBlueprints() as $blueprint) {
            $this->seedInstitution($blueprint);
        }

        $this->command?->newLine();
        $this->command?->info('Mock data ready. Every mock account signs in with the password: ' . self::MOCK_PASSWORD);
        $this->command?->line('  Remove it again with: php artisan db:clear-dummy --force');
    }

    /* ------------------------------------------------------------------ *
     * Safety
     * ------------------------------------------------------------------ */

    /**
     * Refuse to run in production unless the operator opts in TWICE.
     *
     * `--force` alone is not enough: it is routinely passed by deploy scripts
     * and CI, so relying on it would defeat the purpose. The env var is a
     * deliberate, explicit acknowledgement that this machine is a demo box.
     */
    protected function environmentAllowsSeeding(): bool
    {
        if (! app()->environment('production')) {
            return true;
        }

        $allowed = $this->command?->option('force')
            && filter_var(env('MOCK_DATA_ALLOW_PRODUCTION', false), FILTER_VALIDATE_BOOL);

        if (! $allowed) {
            $this->command?->warn(
                'Refusing to seed mock data in the production environment. '
                . 'Set MOCK_DATA_ALLOW_PRODUCTION=true and pass --force if this really is a demo box.'
            );

            return false;
        }

        return true;
    }

    /* ------------------------------------------------------------------ *
     * The blueprints - the whole dataset is declared here
     * ------------------------------------------------------------------ */

    /**
     * Five institutions, each with its own type, currency, roster, vendors,
     * subsidy sources and subscription state.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function institutionBlueprints(): array
    {
        return [
            [
                'slug' => 'north-south-university-hall',
                'name' => 'North South University Hall',
                'subtitle' => 'Residential hall meal programme',
                'type' => 'university_dorm',
                'currency_code' => 'BDT',
                'currency_settings' => ['symbol' => '৳', 'symbol_position' => 'before'],
                'timezone' => 'Asia/Dhaka',
                'accent' => 'indigo',
                'subsidy_mode' => 'pool',
                'subscription_plan' => 'growth',
                'subscription_status' => 'paid',
                'member_limit' => 250,
                'member_label' => 'CS',
                'staff' => [
                    ['name' => 'Dr. Ashraful Islam', 'email' => 'admin@nsu-hall.test', 'role' => 'Institution Admin', 'designation' => 'Provost'],
                    ['name' => 'Rakib Hasan', 'email' => 'manager@nsu-hall.test', 'role' => 'Meal Manager', 'designation' => 'Hall Manager'],
                ],
                'departments' => ['Computer Science', 'Electrical Engineering', 'Business Administration', 'Architecture'],
                'vendors' => [
                    ['name' => 'Karwan Bazar Grocery', 'category' => 'groceries', 'recurrence' => 'daily'],
                    ['name' => 'Mirpur Fresh Vegetables', 'category' => 'vegetables', 'recurrence' => 'daily'],
                    ['name' => 'Rahim Meat House', 'category' => 'meat_fish', 'recurrence' => 'weekly'],
                    ['name' => 'Titas Gas Supply', 'category' => 'utilities', 'recurrence' => 'monthly'],
                ],
                'subsidy_sources' => [
                    ['name' => 'NSU Authority Grant', 'key' => 'university_authority', 'percentage' => 60],
                    ['name' => 'Alumni Donation Fund', 'key' => 'donation', 'percentage' => 40],
                ],
                'subsidies' => [
                    ['source' => 'university_authority', 'amount' => 85000, 'apply_mode' => 'pool', 'percentage' => 100],
                ],
                'members' => [
                    ['name' => 'Farhan Hossain', 'roll' => 'CS-2021-045', 'department' => 'Computer Science', 'meals' => 'regular'],
                    ['name' => 'Nusrat Jahan', 'roll' => 'CS-2021-051', 'department' => 'Computer Science', 'meals' => 'light'],
                    ['name' => 'Tanvir Ahmed', 'roll' => 'EEE-2021-012', 'department' => 'Electrical Engineering', 'meals' => 'heavy'],
                    ['name' => 'Sadia Islam', 'roll' => 'BBA-2022-088', 'department' => 'Business Administration', 'meals' => 'regular'],
                    ['name' => 'Imran Kabir', 'roll' => 'ARC-2022-033', 'department' => 'Architecture', 'meals' => 'regular'],
                    ['name' => 'Mehedi Hasan', 'roll' => 'CS-2022-102', 'department' => 'Computer Science', 'meals' => 'light'],
                    ['name' => 'Jannatul Ferdous', 'roll' => 'EEE-2022-019', 'department' => 'Electrical Engineering', 'meals' => 'regular'],
                    ['name' => 'Arif Chowdhury', 'roll' => 'BBA-2021-070', 'department' => 'Business Administration', 'meals' => 'heavy'],
                ],
            ],

            [
                'slug' => 'touch-and-solve-ltd',
                'name' => 'Touch and Solve Ltd.',
                'subtitle' => 'Staff cafeteria programme',
                'type' => 'company',
                'currency_code' => 'BDT',
                'currency_settings' => ['symbol' => '৳', 'symbol_position' => 'before'],
                'timezone' => 'Asia/Dhaka',
                'accent' => 'emerald',
                'subsidy_mode' => 'per_member',
                'subscription_plan' => 'enterprise',
                'subscription_status' => 'paid',
                'member_limit' => 120,
                'member_label' => 'EMP',
                'staff' => [
                    ['name' => 'Mahfuzur Rahman', 'email' => 'admin@touchandsolve.test', 'role' => 'Institution Admin', 'designation' => 'Operations Director'],
                    ['name' => 'Shakil Mia', 'email' => 'manager@touchandsolve.test', 'role' => 'Meal Manager', 'designation' => 'Canteen Manager'],
                ],
                'departments' => ['Engineering', 'Sales', 'Human Resources', 'Finance'],
                'vendors' => [
                    ['name' => 'Agora Superstore', 'category' => 'groceries', 'recurrence' => 'weekly'],
                    ['name' => 'Bengal Meat Supply', 'category' => 'meat_fish', 'recurrence' => 'weekly'],
                    ['name' => 'Fresh Farm Produce', 'category' => 'vegetables', 'recurrence' => 'daily'],
                    ['name' => 'Office Kitchen Supplies', 'category' => 'kitchen_supplies', 'recurrence' => 'monthly'],
                ],
                'subsidy_sources' => [
                    ['name' => 'Company Management', 'key' => 'company_management', 'percentage' => 100],
                ],
                'subsidies' => [
                    ['source' => 'company_management', 'amount' => 60000, 'apply_mode' => 'per_member', 'percentage' => 100],
                ],
                'members' => [
                    ['name' => 'Rezaul Karim', 'roll' => 'EMP-1001', 'department' => 'Engineering', 'meals' => 'regular'],
                    ['name' => 'Fahmida Akter', 'roll' => 'EMP-1002', 'department' => 'Engineering', 'meals' => 'light'],
                    ['name' => 'Sabbir Rahman', 'roll' => 'EMP-1003', 'department' => 'Sales', 'meals' => 'heavy'],
                    ['name' => 'Nazia Sultana', 'roll' => 'EMP-1004', 'department' => 'Human Resources', 'meals' => 'regular'],
                    ['name' => 'Kamrul Hasan', 'roll' => 'EMP-1005', 'department' => 'Finance', 'meals' => 'regular'],
                    ['name' => 'Rumana Begum', 'roll' => 'EMP-1006', 'department' => 'Sales', 'meals' => 'light'],
                ],
            ],

            [
                'slug' => 'rajshahi-college-hostel',
                'name' => 'Rajshahi College Hostel',
                'subtitle' => 'Hostel mess, two blocks',
                'type' => 'college_dorm',
                'currency_code' => 'BDT',
                'currency_settings' => ['symbol' => '৳', 'symbol_position' => 'before'],
                'timezone' => 'Asia/Dhaka',
                'accent' => 'sky',
                'subsidy_mode' => 'credit_behind',
                'subscription_plan' => 'starter',
                'subscription_status' => 'trial',
                'onboarding_mode' => 'trial',
                'trial_days_left' => 4,
                'member_limit' => 80,
                'member_label' => 'BRD',
                'staff' => [
                    ['name' => 'Professor Nazrul Islam', 'email' => 'admin@rc-hostel.test', 'role' => 'Institution Admin', 'designation' => 'Hostel Superintendent'],
                    ['name' => 'Jamal Uddin', 'email' => 'manager@rc-hostel.test', 'role' => 'Meal Manager', 'designation' => 'Mess Manager'],
                ],
                'departments' => ['Science', 'Arts', 'Commerce'],
                'vendors' => [
                    ['name' => 'Shaheb Bazar Bazar', 'category' => 'groceries', 'recurrence' => 'daily'],
                    ['name' => 'Padma Fish Depot', 'category' => 'meat_fish', 'recurrence' => 'weekly'],
                    ['name' => 'Local Vegetable Growers', 'category' => 'vegetables', 'recurrence' => 'daily'],
                ],
                'subsidy_sources' => [
                    ['name' => 'College Administration', 'key' => 'college_administration', 'percentage' => 100],
                ],
                'subsidies' => [
                    ['source' => 'college_administration', 'amount' => 30000, 'apply_mode' => 'credit_behind', 'percentage' => 100],
                ],
                'members' => [
                    ['name' => 'Sujon Ahmed', 'roll' => 'BRD-3001', 'department' => 'Science', 'meals' => 'regular'],
                    ['name' => 'Rashed Khan', 'roll' => 'BRD-3002', 'department' => 'Science', 'meals' => 'heavy'],
                    ['name' => 'Mizanur Rahman', 'roll' => 'BRD-3003', 'department' => 'Arts', 'meals' => 'light'],
                    ['name' => 'Shahin Alam', 'roll' => 'BRD-3004', 'department' => 'Commerce', 'meals' => 'regular'],
                    ['name' => 'Rubel Hossain', 'roll' => 'BRD-3005', 'department' => 'Science', 'meals' => 'regular'],
                ],
            ],

            [
                'slug' => 'greenfield-corporate-canteen',
                'name' => 'Greenfield Corporate Canteen',
                'subtitle' => 'Subsidised employee dining',
                'type' => 'company',
                // A DIFFERENT currency from the other company, so the
                // per-institution currency format is genuinely exercised.
                'currency_code' => 'USD',
                'currency_settings' => [
                    'symbol' => '$',
                    'symbol_position' => 'before',
                    'decimal_precision' => 2,
                    'abbreviation_threshold' => 1000,
                ],
                'timezone' => 'America/New_York',
                'accent' => 'violet',
                'subsidy_mode' => 'pool',
                'subscription_plan' => 'growth',
                'subscription_status' => 'pending',
                'member_limit' => 150,
                'member_label' => 'STF',
                'staff' => [
                    ['name' => 'Daniel Whitmore', 'email' => 'admin@greenfield.test', 'role' => 'Institution Admin', 'designation' => 'Facilities Lead'],
                    ['name' => 'Maria Gonzalez', 'email' => 'manager@greenfield.test', 'role' => 'Meal Manager', 'designation' => 'Canteen Supervisor'],
                ],
                'departments' => ['Product', 'Design', 'Marketing'],
                'vendors' => [
                    ['name' => 'Greenfield Wholesale', 'category' => 'groceries', 'recurrence' => 'weekly'],
                    ['name' => 'Farmers Market Co-op', 'category' => 'vegetables', 'recurrence' => 'daily'],
                    ['name' => 'City Power & Utilities', 'category' => 'utilities', 'recurrence' => 'monthly'],
                ],
                'subsidy_sources' => [
                    ['name' => 'Greenfield Management', 'key' => 'company_management', 'percentage' => 70],
                    ['name' => 'Government Grant', 'key' => 'government_grant', 'percentage' => 30],
                ],
                'subsidies' => [
                    ['source' => 'company_management', 'amount' => 2400, 'apply_mode' => 'pool', 'percentage' => 100],
                ],
                'members' => [
                    ['name' => 'James Carter', 'roll' => 'STF-2001', 'department' => 'Product', 'meals' => 'regular'],
                    ['name' => 'Emily Novak', 'roll' => 'STF-2002', 'department' => 'Design', 'meals' => 'light'],
                    ['name' => 'Robert Chen', 'roll' => 'STF-2003', 'department' => 'Marketing', 'meals' => 'regular'],
                    ['name' => 'Priya Raman', 'roll' => 'STF-2004', 'department' => 'Product', 'meals' => 'heavy'],
                ],
            ],

            [
                'slug' => 'mirpur-general-mess',
                'name' => 'Mirpur General Mess',
                'subtitle' => 'Shared mess, no institutional structure',
                'type' => 'general_mess',
                'currency_code' => 'BDT',
                'currency_settings' => ['symbol' => '৳', 'symbol_position' => 'before'],
                'timezone' => 'Asia/Dhaka',
                'accent' => 'amber',
                'subsidy_mode' => 'pool',
                'subscription_plan' => 'starter',
                'subscription_status' => 'overdue',
                'member_limit' => 40,
                'member_label' => 'MBR',
                'staff' => [
                    ['name' => 'Abdul Karim', 'email' => 'admin@mirpur-mess.test', 'role' => 'Institution Admin', 'designation' => 'Mess Owner'],
                ],
                'departments' => ['Ground Floor', 'First Floor'],
                'vendors' => [
                    ['name' => 'Mirpur Bazar Grocery', 'category' => 'groceries', 'recurrence' => 'daily'],
                    ['name' => 'Kazi Farm Chicken', 'category' => 'meat_fish', 'recurrence' => 'weekly'],
                ],
                'subsidy_sources' => [],
                'subsidies' => [],
                'members' => [
                    ['name' => 'Sohel Rana', 'roll' => 'MBR-4001', 'department' => 'Ground Floor', 'meals' => 'regular'],
                    ['name' => 'Habibur Rahman', 'roll' => 'MBR-4002', 'department' => 'Ground Floor', 'meals' => 'heavy'],
                    ['name' => 'Nayeem Islam', 'roll' => 'MBR-4003', 'department' => 'First Floor', 'meals' => 'light'],
                    ['name' => 'Tofazzal Hossain', 'roll' => 'MBR-4004', 'department' => 'First Floor', 'meals' => 'regular'],
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------ *
     * One institution
     * ------------------------------------------------------------------ */

    /**
     * @param  array<string, mixed>  $blueprint
     */
    protected function seedInstitution(array $blueprint): void
    {
        $this->command?->line('  -> ' . $blueprint['name']);

        $institution = $this->upsertInstitution($blueprint);

        // Seeding must act INSIDE this institution, because every tenant-owned
        // model carries a global scope that reads the active tenant. Forcing the
        // tenant here means the models stamp the right `institution_id` and the
        // subsequent lookups resolve the right rows.
        app(\App\Support\TenantManager::class)->force($institution->id);

        try {
            $staff = $this->seedStaff($institution, $blueprint);
            $departments = $this->seedDepartments($institution, $blueprint);
            $vendors = $this->seedVendors($institution, $blueprint);
            $members = $this->seedMembers($institution, $blueprint, $departments, $staff);
            $this->seedSubsidySources($institution, $blueprint);

            $this->seedLedger($institution, $members, $vendors, $staff);
            $this->seedSubsidies($institution, $blueprint, $staff, $departments, $members);
            $this->seedMenus($institution, $members, $staff);
            $this->seedClaims($institution, $members, $staff);
        } finally {
            // Always release the pin, even on failure, so the next institution
            // (and the rest of the request) is not silently scoped to this one.
            app(\App\Support\TenantManager::class)->force(null);
        }
    }

    /** @param array<string, mixed> $blueprint */
    protected function upsertInstitution(array $blueprint): Institution
    {
        $attributes = [
            'name' => $blueprint['name'],
            'subtitle' => $blueprint['subtitle'],
            'type' => $blueprint['type'],
            'currency_code' => $blueprint['currency_code'],
            'currency_settings' => $blueprint['currency_settings'],
            'timezone' => $blueprint['timezone'],
            'is_active' => true,
            'theme' => ['accent' => $blueprint['accent'], 'mode' => 'light'],
            'subsidy_mode' => $blueprint['subsidy_mode'],
            'subscription_plan' => $blueprint['subscription_plan'],
            'subscription_status' => $blueprint['subscription_status'],
            'subscription_amount' => $blueprint['subscription_plan'] === 'enterprise' ? 9990 : 2990,
            'member_limit' => $blueprint['member_limit'],
            'contact_email' => 'hello@' . $blueprint['slug'] . '.test',
            'contact_phone' => '+8801700000000',
            'onboarding_mode' => $blueprint['onboarding_mode'] ?? 'subscription',
        ];

        // Trial windows are only set when the blueprint asks for one.
        if (($blueprint['onboarding_mode'] ?? null) === 'trial') {
            $attributes['trial_started_at'] = now()->subDays(
                Institution::TRIAL_DAYS - ($blueprint['trial_days_left'] ?? 0)
            );
            $attributes['trial_ends_at'] = now()->addDays($blueprint['trial_days_left'] ?? 3);
        }

        // firstOrCreate (not updateOrCreate) on purpose: a developer who has
        // edited a workspace's name/theme/logo in the UI keeps their changes.
        return Institution::firstOrCreate(['slug' => $blueprint['slug']], $attributes);
    }

    /**
     * Institution staff (admin + manager) and their accounts.
     *
     * @param  array<string, mixed>  $blueprint
     * @return array<string, User>  keyed by role name
     */
    protected function seedStaff(Institution $institution, array $blueprint): array
    {
        $staff = [];

        foreach ($blueprint['staff'] as $spec) {
            // CREATE ONLY IF MISSING - the password of an existing account is
            // never overwritten (the same rule SoftwareSuperAdminSeeder follows).
            $user = User::where('email', $spec['email'])->first();

            if (! $user) {
                $user = new User([
                    'institution_id' => $institution->id,
                    'name' => $spec['name'],
                    'email' => $spec['email'],
                    'password' => Hash::make(self::MOCK_PASSWORD),
                    'designation' => $spec['designation'],
                    'status' => 'active',
                    'setup_completed_at' => now(),
                    'onboarding_completed_at' => now(),
                    // Staff who eat in the mess may vote on menus.
                    'is_meal_participant' => true,
                ]);
                // Authorise the initial credential write (see User::booted()).
                $user->passwordWriteAuthorised = true;
                $user->save();
            }

            if (! $user->hasRole($spec['role'])) {
                $user->assignRole($spec['role']);
            }

            $staff[$spec['role']] = $user;
        }

        return $staff;
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @return array<string, Department>  keyed by name
     */
    protected function seedDepartments(Institution $institution, array $blueprint): array
    {
        $departments = [];

        foreach ($blueprint['departments'] as $name) {
            $departments[$name] = Department::firstOrCreate(
                ['institution_id' => $institution->id, 'name' => $name],
                ['slug' => \Illuminate\Support\Str::slug($institution->slug . '-' . $name)],
            );
        }

        return $departments;
    }

    /**
     * @param  array<string, mixed>  $blueprint
     * @return array<int, Vendor>
     */
    protected function seedVendors(Institution $institution, array $blueprint): array
    {
        $vendors = [];

        foreach ($blueprint['vendors'] as $spec) {
            $vendors[] = Vendor::firstOrCreate(
                ['institution_id' => $institution->id, 'slug' => \Illuminate\Support\Str::slug($institution->slug . '-' . $spec['name'])],
                [
                    'name' => $spec['name'],
                    'category' => $spec['category'],
                    'recurrence' => $spec['recurrence'],
                    'contact_person' => 'Procurement Desk',
                    'phone' => '+8801712345678',
                    'status' => 'active',
                    'opening_balance' => 0,
                ],
            );
        }

        return $vendors;
    }

    /**
     * The roster: a Student row per member, plus a linked login account for most
     * of them (so the member-facing mobile screens have real data to render).
     *
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, Department>  $departments
     * @param  array<string, User>  $staff
     * @return array<int, Student>
     */
    protected function seedMembers(Institution $institution, array $blueprint, array $departments, array $staff): array
    {
        $members = [];
        $manager = $staff['Meal Manager'] ?? $staff['Institution Admin'] ?? null;
        $index = 0;

        foreach ($blueprint['members'] as $spec) {
            $index++;

            // Give roughly two thirds of the roster a login, so both the
            // "has_account" and "no account yet" states appear in the UI.
            $withAccount = $index % 3 !== 0;

            $user = null;

            if ($withAccount) {
                $email = strtolower(str_replace([' ', '.'], ['.', ''], $spec['name']))
                    . '@' . $blueprint['slug'] . '.test';

                $user = User::where('email', $email)->first();

                if (! $user) {
                    $user = new User([
                        'institution_id' => $institution->id,
                        'name' => $spec['name'],
                        'email' => $email,
                        'password' => Hash::make(self::MOCK_PASSWORD),
                        'status' => 'active',
                        'setup_completed_at' => now(),
                    ]);
                    $user->passwordWriteAuthorised = true;
                    $user->save();
                    $user->assignInstitutionRole('Member');
                }
            }

            // Keyed on institution + roll: the natural identity of a member.
            $student = Student::firstOrCreate(
                ['institution_id' => $institution->id, 'roll' => $spec['roll']],
                [
                    'user_id' => $user?->id,
                    'manager_id' => $manager?->id,
                    'name' => $spec['name'],
                    'department_id' => $departments[$spec['department']]->id ?? null,
                    'join_date' => now()->subMonths(self::MONTHS_OF_HISTORY + 2)->toDateString(),
                    'status' => 'active',
                ],
            );

            // Backfill the link if the member row existed but had no account.
            if ($user && ! $student->user_id) {
                $student->forceFill(['user_id' => $user->id])->save();
            }

            // Remember the appetite profile so the ledger generator can vary
            // meal counts per member (heavy eaters, light eaters, regulars).
            $student->setAttribute('_meal_profile', $spec['meals']);

            $members[] = $student;
        }

        return $members;
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    protected function seedSubsidySources(Institution $institution, array $blueprint): void
    {
        foreach ($blueprint['subsidy_sources'] as $spec) {
            SubsidySource::firstOrCreate(
                ['institution_id' => $institution->id, 'key' => $spec['key']],
                [
                    'name' => $spec['name'],
                    'percentage' => $spec['percentage'],
                    'is_active' => true,
                ],
            );
        }
    }

    /* ------------------------------------------------------------------ *
     * The ledger: meals, deposits, expenses
     * ------------------------------------------------------------------ */

    /**
     * Generate MONTHS_OF_HISTORY months of coherent meal entries, member
     * deposits and kitchen expenses.
     *
     * The volumes are tuned so the derived per-meal rate lands in a realistic
     * band: expenses are sized from the generated meal COUNT rather than picked
     * at random, so reports and balances do not show absurd ratios.
     *
     * @param  array<int, Student>  $members
     * @param  array<int, Vendor>  $vendors
     * @param  array<string, User>  $staff
     */
    protected function seedLedger(Institution $institution, array $members, array $vendors, array $staff): void
    {
        $recorder = $staff['Meal Manager'] ?? $staff['Institution Admin'] ?? null;
        $currency = $institution->currencySettings();

        // A meal costs roughly this much; expenses are derived from it so the
        // resulting per-meal rate is believable for the workspace's currency.
        $costPerMeal = $currency['symbol'] === '$' ? 6.5 : 45.0;

        for ($monthOffset = self::MONTHS_OF_HISTORY - 1; $monthOffset >= 0; $monthOffset--) {
            $month = now()->subMonths($monthOffset);
            $isCurrentMonth = $monthOffset === 0;
            $daysInMonth = $isCurrentMonth ? (int) now()->day : $month->daysInMonth;

            $monthMeals = 0;

            // ---- Meal entries -------------------------------------------------
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $month->copy()->day($day);

                // Nothing before the roster joined, and a small chance of a
                // missing day (holidays / unrecorded) for realism.
                if ($date->isFuture() || $date->dayOfWeek === Carbon::FRIDAY && $day % 7 === 0) {
                    continue;
                }

                foreach ($members as $member) {
                    $entry = $this->mealEntryFor($member, $date, $recorder);

                    if ($entry) {
                        $monthMeals += $entry->breakfast + $entry->lunch + $entry->dinner;
                    }
                }
            }

            // ---- Kitchen expenses ---------------------------------------------
            // Sized from the real meal volume, with a little noise, so the
            // per-meal rate is derived rather than invented.
            $targetExpense = round($monthMeals * $costPerMeal * (0.92 + (mt_rand(0, 16) / 100)), 2);

            $this->seedMonthlyExpenses($institution, $month, $targetExpense, $vendors, $recorder);

            // ---- Member deposits ----------------------------------------------
            // Most members top up generously; a couple deliberately under-pay so
            // the "dues" / negative-balance states appear in the UI.
            $this->seedMonthlyDeposits($institution, $month, $members, $recorder, $currency);
        }
    }

    /**
     * One member's meal row for one day, varied by their appetite profile.
     */
    protected function mealEntryFor(Student $member, Carbon $date, ?User $recorder): ?MealEntry
    {
        $profile = $member->getAttribute('_meal_profile') ?? 'regular';

        // Profile -> the probability of eating each of the three meals.
        [$breakfastChance, $lunchChance, $dinnerChance] = match ($profile) {
            'heavy' => [70, 97, 95],
            'light' => [20, 75, 45],
            default => [45, 92, 80],
        };

        $breakfast = mt_rand(1, 100) <= $breakfastChance ? 1 : 0;
        $lunch = mt_rand(1, 100) <= $lunchChance ? 1 : 0;
        $dinner = mt_rand(1, 100) <= $dinnerChance ? 1 : 0;

        // A row of all zeros is not stored (the module deletes it instead), so
        // skip rather than write a meaningless row.
        if ($breakfast + $lunch + $dinner === 0) {
            return null;
        }

        /*
         * KEYED ON (student_id, date) - deliberately NOT including
         * institution_id. The table's unique index is
         * `unique(['student_id', 'date'])`, so adding institution_id to the
         * lookup would make it MISS the existing row and then hit the
         * constraint on insert. The student already implies the institution.
         *
         * The lookup uses whereDate (not a raw `date =` binding) because the
         * `date` cast writes a full datetime into the column; comparing it to a
         * plain 'Y-m-d' string would never match.
         */
        $existing = MealEntry::query()
            ->where('student_id', $member->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        return MealEntry::create([
            'institution_id' => $member->institution_id,
            'student_id' => $member->id,
            'date' => $date->toDateString(),
            'breakfast' => $breakfast,
            'lunch' => $lunch,
            'dinner' => $dinner,
            'recorded_by' => $recorder?->id,
        ]);
    }

    /**
     * Split the month's target expense across several vendor purchases, writing
     * the mirroring ledger transaction for each.
     *
     * @param  array<int, Vendor>  $vendors
     */
    protected function seedMonthlyExpenses(
        Institution $institution,
        Carbon $month,
        float $targetExpense,
        array $vendors,
        ?User $recorder,
    ): void {
        if ($targetExpense <= 0 || $vendors === []) {
            return;
        }

        // Four purchases a month: two grocery runs, one protein buy, one misc.
        $splits = [0.38, 0.27, 0.23, 0.12];
        $descriptions = ['Monthly grocery restock', 'Vegetable and produce supply', 'Protein purchase (meat/fish)', 'Kitchen consumables'];

        foreach ($splits as $index => $share) {
            $amount = round($targetExpense * $share, 2);

            if ($amount <= 0) {
                continue;
            }

            $vendor = $vendors[$index % count($vendors)];
            $date = $month->copy()->day(min(28, 4 + ($index * 7)));

            // Idempotent on (institution, item, vendor, created_at date): a
            // re-run does not double the month's spend.
            $existing = Transaction::where('institution_id', $institution->id)
                ->where('vendor_id', $vendor->id)
                ->where('item', $descriptions[$index])
                ->whereDate('created_at', $date->toDateString())
                ->first();

            if ($existing) {
                continue;
            }

            $transaction = Transaction::create([
                'institution_id' => $institution->id,
                'user_id' => $recorder?->id,
                'vendor_id' => $vendor->id,
                'item' => $descriptions[$index],
                'type' => 'out',
                'amount' => $amount,
                'category' => 'meal_expense',
                'payment_method' => $index === 3 ? 'Cash' : 'Bank',
                'payee' => $vendor->name,
                'reason' => 'Meal expense for ' . $month->format('F Y'),
                'source' => 'meal_module',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            MealExpense::create([
                'institution_id' => $institution->id,
                'transaction_id' => $transaction->id,
                'vendor_id' => $vendor->id,
                'description' => $descriptions[$index],
                'category' => $vendor->category ?? 'other',
                'amount' => $amount,
                'payment_status' => 'paid',
                'recorded_by' => $recorder?->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }

    /**
     * Member top-ups for the month, with a deliberate spread of payment
     * behaviour so dues / credit states are represented.
     *
     * @param  array<int, Student>  $members
     * @param  array<string, mixed>  $currency
     */
    protected function seedMonthlyDeposits(
        Institution $institution,
        Carbon $month,
        array $members,
        ?User $recorder,
        array $currency,
    ): void {
        $methods = ['bKash', 'Nagad', 'Bank Transfer', 'Cash'];
        $symbol = $currency['symbol'] ?? '৳';
        $base = $symbol === '$' ? 220 : 3000;

        foreach ($members as $index => $member) {
            // Every third member under-deposits, so their balance goes negative
            // and the "with dues" reporting path is exercised.
            $underPays = $index % 3 === 1;
            $amount = $underPays ? $base * 0.55 : $base * (1 + (mt_rand(0, 30) / 100));
            $amount = round($amount, 2);

            $date = $month->copy()->day(min(26, 3 + ($index % 20)));

            $existing = Deposit::where('institution_id', $institution->id)
                ->where('student_id', $member->id)
                ->whereDate('created_at', $date->toDateString())
                ->first();

            if ($existing) {
                continue;
            }

            $transaction = Transaction::create([
                'institution_id' => $institution->id,
                'user_id' => $recorder?->id,
                'student_id' => $member->id,
                'item' => 'Meal deposit for ' . $member->name,
                'type' => 'in',
                'amount' => $amount,
                'category' => 'Meal Deposit',
                'payment_method' => $methods[$index % count($methods)],
                'by_whom' => $member->name,
                'source' => 'meal_module',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            Deposit::create([
                'institution_id' => $institution->id,
                'student_id' => $member->id,
                'amount' => $amount,
                'kind' => 'personal',
                'payment_method' => $methods[$index % count($methods)],
                'recorded_by' => $recorder?->id,
                'transaction_id' => $transaction->id,
                'notes' => 'Monthly ' . $month->format('F Y') . ' contribution',
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }

    /* ------------------------------------------------------------------ *
     * Subsidies, menus, claims
     * ------------------------------------------------------------------ */

    /**
     * @param  array<string, mixed>  $blueprint
     * @param  array<string, User>  $staff
     * @param  array<string, Department>  $departments
     * @param  array<int, Student>  $members
     */
    protected function seedSubsidies(
        Institution $institution,
        array $blueprint,
        array $staff,
        array $departments,
        array $members,
    ): void {
        $recorder = $staff['Institution Admin'] ?? $staff['Meal Manager'] ?? null;

        foreach ($blueprint['subsidies'] as $spec) {
            $period = now()->startOfMonth();

            $existing = Subsidy::where('institution_id', $institution->id)
                ->where('source', $spec['source'])
                ->whereDate('period_start', $period->toDateString())
                ->first();

            if ($existing) {
                continue;
            }

            $transaction = Transaction::create([
                'institution_id' => $institution->id,
                'user_id' => $recorder?->id,
                'item' => 'Institutional subsidy - ' . ($spec['source'] ?? 'other'),
                'type' => 'in',
                'amount' => $spec['amount'],
                'category' => 'Subsidy',
                'payment_method' => 'Bank',
                'by_whom' => 'Institution Authority',
                'source' => 'meal_module',
            ]);

            Subsidy::create([
                'institution_id' => $institution->id,
                'source' => $spec['source'],
                'source_label' => $spec['source'] === 'university_authority' ? 'NSU Authority Grant' : null,
                // A department-scoped subsidy for the dorm, a whole-body one
                // elsewhere - so the `scope` resolution has both shapes.
                'department_id' => $institution->type === 'university_dorm'
                    ? ($departments['Computer Science']->id ?? null)
                    : null,
                'apply_mode' => $spec['apply_mode'],
                'amount' => $spec['amount'],
                'period_month' => $period->format('Y-m'),
                'percentage' => $spec['percentage'],
                'period_start' => $period->toDateString(),
                'period_end' => $period->copy()->endOfMonth()->toDateString(),
                'recorded_by' => $recorder?->id,
                'transaction_id' => $transaction->id,
                'status' => 'active',
                'notes' => 'Seeded institutional funding for ' . $period->format('F Y'),
            ]);
        }
    }

    /**
     * A menu in each lifecycle state, with options and member votes.
     *
     * @param  array<int, Student>  $members
     * @param  array<string, User>  $staff
     */
    protected function seedMenus(Institution $institution, array $members, array $staff): void
    {
        $proposer = $staff['Meal Manager'] ?? $staff['Institution Admin'] ?? null;
        $approver = $staff['Institution Admin'] ?? $proposer;

        // One OPEN menu (members can vote) and one APPROVED menu (the active one).
        $definitions = [
            [
                'meal_type' => 'lunch',
                'menu_date' => now()->addDay()->toDateString(),
                'title' => 'Tomorrow\'s Lunch Options',
                'status' => 'voting',
                'options' => [
                    ['name' => 'Chicken Biryani', 'cost' => 120, 'recommended' => true],
                    ['name' => 'Beef Tehari', 'cost' => 135, 'recommended' => false],
                    ['name' => 'Vegetable Khichuri', 'cost' => 70, 'recommended' => false],
                ],
            ],
            [
                'meal_type' => 'dinner',
                'menu_date' => now()->toDateString(),
                'title' => 'Tonight\'s Dinner (Approved)',
                'status' => 'approved',
                'options' => [
                    ['name' => 'Rice, Dal & Chicken Curry', 'cost' => 110, 'recommended' => true],
                    ['name' => 'Roti & Mixed Vegetables', 'cost' => 65, 'recommended' => false],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $menu = MealMenu::firstOrCreate(
                [
                    'institution_id' => $institution->id,
                    'meal_type' => $definition['meal_type'],
                    'menu_date' => $definition['menu_date'],
                ],
                [
                    'title' => $definition['title'],
                    'description' => 'Seeded menu for demo purposes.',
                    'status' => $definition['status'],
                    'voting_opens_at' => now()->subDay(),
                    'voting_closes_at' => now()->addDay(),
                    'allow_vote_changes' => true,
                    'created_by' => $proposer?->id,
                    'approved_by' => $definition['status'] === 'approved' ? $approver?->id : null,
                    'approved_at' => $definition['status'] === 'approved' ? now() : null,
                ],
            );

            $options = [];

            foreach ($definition['options'] as $sortOrder => $optionSpec) {
                $options[] = MealMenuOption::firstOrCreate(
                    ['meal_menu_id' => $menu->id, 'name' => $optionSpec['name']],
                    [
                        'estimated_cost' => $optionSpec['cost'],
                        'is_recommended' => $optionSpec['recommended'],
                        'sort_order' => $sortOrder,
                    ],
                );
            }

            // Votes only make sense on a menu that is open or already approved.
            if (in_array($menu->status, ['voting', 'approved'], true) && $options !== []) {
                foreach ($members as $index => $member) {
                    if (! $member->user_id) {
                        continue; // no login => cannot vote
                    }

                    // One vote per (menu, voter) is a DB unique constraint, so
                    // updateOrCreate is the only safe way to re-run this.
                    MealMenuVote::updateOrCreate(
                        ['meal_menu_id' => $menu->id, 'user_id' => $member->user_id],
                        [
                            'meal_menu_option_id' => $options[$index % count($options)]->id,
                            'student_id' => $member->id,
                            'comment' => $index % 4 === 0 ? 'Preferred option for the price.' : null,
                        ],
                    );
                }
            }
        }
    }

    /**
     * A spread of claims: one pending, one approved, one rejected - so the
     * review queue and the member status tracker both have content.
     *
     * @param  array<int, Student>  $members
     * @param  array<string, User>  $staff
     */
    protected function seedClaims(Institution $institution, array $members, array $staff): void
    {
        $reviewer = $staff['Institution Admin'] ?? $staff['Meal Manager'] ?? null;

        $definitions = [
            [
                'index' => 0,
                'kind' => 'dispute',
                'title' => 'Deposit not showing on my balance',
                'description' => 'I paid in cash on the 5th but my balance was not credited.',
                'amount' => 3000,
                'status' => 'pending',
            ],
            [
                'index' => 1,
                'kind' => 'expense',
                'title' => 'Reimbursement for kitchen gas refill',
                'description' => 'I paid for the LPG cylinder from my own pocket.',
                'amount' => 1450,
                'status' => 'approved',
            ],
            [
                'index' => 2,
                'kind' => 'dispute',
                'title' => 'Wrong meal count for last Tuesday',
                'description' => 'I was away but two meals were recorded against my name.',
                'amount' => 90,
                'status' => 'rejected',
            ],
        ];

        foreach ($definitions as $definition) {
            $member = $members[$definition['index']] ?? null;

            if (! $member) {
                continue;
            }

            $existing = Claim::where('institution_id', $institution->id)
                ->where('student_id', $member->id)
                ->where('title', $definition['title'])
                ->first();

            if ($existing) {
                continue;
            }

            $reviewed = $definition['status'] !== 'pending';

            Claim::create([
                'institution_id' => $institution->id,
                'student_id' => $member->id,
                'kind' => $definition['kind'],
                'subject' => $definition['kind'],
                'title' => $definition['title'],
                'description' => $definition['description'],
                'amount' => $definition['amount'],
                'claim_date' => now()->subDays(5)->toDateString(),
                'status' => $definition['status'],
                'reviewed_by' => $reviewed ? $reviewer?->id : null,
                'reviewed_at' => $reviewed ? now()->subDays(2) : null,
                'review_notes' => match ($definition['status']) {
                    'approved' => 'Verified against the cash book. Credited.',
                    'rejected' => 'No supporting record found for this date.',
                    default => null,
                },
            ]);
        }
    }
}