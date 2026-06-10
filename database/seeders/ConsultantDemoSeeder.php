<?php

namespace Database\Seeders;

use App\Data\ConsultantOptions;
use App\Models\Consultant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ConsultantDemoSeeder extends Seeder
{
    private const DEMO_EMAIL_DOMAIN = '@menetzero.demo';

    public function run(): void
    {
        if (Consultant::query()->where('email', 'like', '%' . self::DEMO_EMAIL_DOMAIN)->exists()) {
            $this->command?->warn('Demo consultants already exist — skipping. Delete *@menetzero.demo to re-seed.');

            return;
        }

        $people = [
            ['name' => 'Fatima Al Mazrouei', 'female' => true],
            ['name' => 'Omar Al Ketbi', 'female' => false],
            ['name' => 'Sarah Mitchell', 'female' => true],
            ['name' => 'Rajesh Nair', 'female' => false],
            ['name' => 'Aisha Al Nuaimi', 'female' => true],
            ['name' => 'James Thornton', 'female' => false],
            ['name' => 'Priya Sharma', 'female' => true],
            ['name' => 'Khalid Al Mansoori', 'female' => false],
            ['name' => 'Layla Hassan', 'female' => true],
            ['name' => 'Michael Chen', 'female' => false],
            ['name' => 'Noor Al Shamsi', 'female' => true],
            ['name' => 'David Okonkwo', 'female' => false],
            ['name' => 'Mariam Al Falasi', 'female' => true],
            ['name' => 'Ahmed Farooq', 'female' => false],
            ['name' => 'Elena Popov', 'female' => true],
            ['name' => 'Youssef Benali', 'female' => false],
            ['name' => 'Hannah Williams', 'female' => true],
            ['name' => 'Vikram Patel', 'female' => false],
            ['name' => 'Zainab Al Qasimi', 'female' => true],
            ['name' => 'Thomas Berger', 'female' => false],
            ['name' => 'Amira Al Suwaidi', 'female' => true],
            ['name' => 'Carlos Mendez', 'female' => false],
            ['name' => 'Nadia Rahman', 'female' => true],
            ['name' => 'Faisal Al Zaabi', 'female' => false],
            ['name' => 'Sophie Laurent', 'female' => true],
            ['name' => 'Imran Siddiqui', 'female' => false],
            ['name' => 'Rania Al Dhaheri', 'female' => true],
            ['name' => 'Peter van der Berg', 'female' => false],
            ['name' => 'Deepa Krishnan', 'female' => true],
            ['name' => 'Hamad Al Rumaithi', 'female' => false],
            ['name' => 'Chloe Anderson', 'female' => true],
            ['name' => 'Sanjay Mehta', 'female' => false],
            ['name' => 'Hessa Al Muhairi', 'female' => true],
            ['name' => 'Luca Romano', 'female' => false],
            ['name' => 'Meera Iyer', 'female' => true],
            ['name' => 'Tariq Al Blooshi', 'female' => false],
            ['name' => 'Isabelle Dubois', 'female' => true],
            ['name' => 'Arjun Desai', 'female' => false],
            ['name' => 'Salma Al Kaabi', 'female' => true],
            ['name' => 'Robert Hughes', 'female' => false],
            ['name' => 'Ananya Reddy', 'female' => true],
            ['name' => 'Saeed Al Marri', 'female' => false],
            ['name' => 'Emily Foster', 'female' => true],
            ['name' => 'Hassan Al Ameri', 'female' => false],
            ['name' => 'Kavita Menon', 'female' => true],
            ['name' => 'Daniel O\'Brien', 'female' => false],
            ['name' => 'Mouza Al Shamsi', 'female' => true],
            ['name' => 'Bilal Chaudhry', 'female' => false],
            ['name' => 'Grace Nakamura', 'female' => true],
            ['name' => 'Mohammed Al Hosani', 'female' => false],
        ];

        $companyPrefixes = [
            'Green', 'Carbon', 'Sustain', 'Eco', 'Climate', 'NetZero', 'Emirates', 'Gulf', 'MENA', 'Verdant',
            'Apex', 'Horizon', 'Pinnacle', 'Summit', 'Atlas', 'Nexus', 'Vertex', 'Prime', 'Clear', 'True',
        ];

        $companySuffixes = [
            'Advisory', 'Consulting', 'Solutions', 'Associates', 'Group', 'Experts', 'Services', 'Labs', 'Studio',
        ];

        $emirateKeys = array_keys(ConsultantOptions::EMIRATES);
        $specialtyKeys = array_keys(ConsultantOptions::SPECIALTIES);
        $languageOptions = [['en'], ['en', 'ar'], ['en', 'hi'], ['en', 'ar', 'hi'], ['en', 'ur'], ['en', 'fr']];

        $bios = [
            'UAE-based sustainability consultant supporting SMEs with Scope 1 & 2 inventories and MOCCAE-aligned reporting.',
            'Former Big Four climate practice lead, now focused on GHG Protocol implementations for manufacturing and logistics.',
            'Helps family businesses across the Emirates prepare investor-ready IFRS S2 and GRI disclosure drafts.',
            'Specialises in IEQT / mrv.ae exports and federal inventory quality checks for mid-market clients.',
            'Provides pragmatic Scope 3 screening and supplier engagement playbooks for UAE distributors.',
            'Bilingual advisor (English/Arabic) for ADX-path companies building first-year carbon disclosures.',
            'Freelance verifier supporting third-party review workflows — not a substitute for MOCCAE legal verification.',
        ];

        $now = now();
        $password = Hash::make('DemoConsultant1!');

        foreach ($people as $i => $person) {
            $n = $i + 1;
            $emirate = $emirateKeys[$i % count($emirateKeys)];
            $extraEmirate = $emirateKeys[($i + 3) % count($emirateKeys)];
            $emirates = $i % 4 === 0 ? [$emirate, $extraEmirate] : [$emirate];

            shuffle($specialtyKeys);
            $specialties = array_slice($specialtyKeys, 0, rand(2, 4));

            $companyName = $companyPrefixes[$i % count($companyPrefixes)] . ' '
                . $companySuffixes[intdiv($i, 2) % count($companySuffixes)];

            Consultant::create([
                'name' => $person['name'],
                'email' => 'consultant-demo-' . $n . self::DEMO_EMAIL_DOMAIN,
                'password' => $password,
                'phone' => '+9715' . str_pad((string) (10000000 + $n * 173), 8, '0', STR_PAD_LEFT),
                'company_name' => $companyName . ' LLC',
                'trade_license_number' => 'TL-' . (100000 + $n),
                'bio' => $bios[$i % count($bios)],
                'emirates' => $emirates,
                'languages' => $languageOptions[$i % count($languageOptions)],
                'specialties' => $specialties,
                'experience_years' => rand(3, 22),
                'website' => $n % 3 === 0 ? 'https://example-' . $n . '.ae' : null,
                'linkedin' => $n % 2 === 0 ? 'https://linkedin.com/in/demo-consultant-' . $n : null,
                'has_moccae_experience' => $i % 3 !== 2,
                'is_featured' => $n <= 6,
                'status' => 'approved',
                'submitted_at' => $now->copy()->subDays(rand(5, 60)),
                'reviewed_at' => $now->copy()->subDays(rand(1, 4)),
                'is_active' => true,
            ]);
        }

        $this->command?->info('✅ Seeded 50 demo consultants (*@menetzero.demo, password: DemoConsultant1!)');
    }
}
