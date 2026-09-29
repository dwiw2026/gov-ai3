<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Officer;
use App\Models\RoutingRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin user ───────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@civicai.id'],
            [
                'name'     => 'Admin CivicAI',
                'password' => Hash::make('password'),
            ]
        );

        // ─── Government Agencies (OPD) ────────────────────────────────────────
        $agencies = [
            [
                'code'        => 'DAMKAR',
                'name'        => 'Dinas Pemadam Kebakaran',
                'phone'       => '113',
                'email'       => 'damkar@banjarmasin.go.id',
                'description' => 'Menangani kebakaran dan bencana',
                'sla_minutes' => 15,
            ],
            [
                'code'        => 'SATPOL_PP',
                'name'        => 'Satuan Polisi Pamong Praja',
                'phone'       => '1500040',
                'email'       => 'satpolpp@banjarmasin.go.id',
                'description' => 'Penegakan ketertiban umum',
                'sla_minutes' => 30,
            ],
            [
                'code'        => 'DISHUB',
                'name'        => 'Dinas Perhubungan',
                'phone'       => '(0511) 3363-xxx',
                'email'       => 'dishub@banjarmasin.go.id',
                'description' => 'Pengaturan lalu lintas dan transportasi',
                'sla_minutes' => 60,
            ],
            [
                'code'        => 'DLH',
                'name'        => 'Dinas Lingkungan Hidup',
                'phone'       => '(0511) 3360-xxx',
                'email'       => 'dlh@banjarmasin.go.id',
                'description' => 'Pengelolaan sampah dan lingkungan',
                'sla_minutes' => 120,
            ],
            [
                'code'        => 'DPUPR',
                'name'        => 'Dinas Pekerjaan Umum dan Penataan Ruang',
                'phone'       => '(0511) 3355-xxx',
                'email'       => 'dpupr@banjarmasin.go.id',
                'description' => 'Infrastruktur jalan, drainase, dan bangunan',
                'sla_minutes' => 240,
            ],
            [
                'code'        => 'PDAM',
                'name'        => 'Perusahaan Daerah Air Minum',
                'phone'       => '(0511) 3253-xxx',
                'email'       => 'pdam@banjarmasin.go.id',
                'description' => 'Pengelolaan air bersih dan banjir',
                'sla_minutes' => 120,
            ],
            [
                'code'        => 'UMUM',
                'name'        => 'Pusat Pengaduan Umum',
                'phone'       => '1500-XXX',
                'email'       => 'pengaduan@banjarmasin.go.id',
                'description' => 'Pengaduan umum / belum terklasifikasi',
                'sla_minutes' => 480,
            ],
        ];

        foreach ($agencies as $a) {
            Agency::firstOrCreate(['code' => $a['code']], $a);
        }

        // ─── Officers ─────────────────────────────────────────────────────────
        $damkar   = Agency::where('code', 'DAMKAR')->first();
        $satpol   = Agency::where('code', 'SATPOL_PP')->first();
        $dishub   = Agency::where('code', 'DISHUB')->first();
        $dlh      = Agency::where('code', 'DLH')->first();
        $dpupr    = Agency::where('code', 'DPUPR')->first();

        $officers = [
            ['agency_id' => $damkar->id,  'name' => 'Sgt. Ahmad Rizki',     'employee_id' => 'DMK-001', 'phone' => '08111-001-001', 'status' => 'available'],
            ['agency_id' => $damkar->id,  'name' => 'Sgt. Budi Santoso',    'employee_id' => 'DMK-002', 'phone' => '08111-001-002', 'status' => 'available'],
            ['agency_id' => $satpol->id,  'name' => 'Psl. Hendra Wijaya',   'employee_id' => 'SPP-001', 'phone' => '08111-002-001', 'status' => 'available'],
            ['agency_id' => $satpol->id,  'name' => 'Psl. Dewi Anggraini',  'employee_id' => 'SPP-002', 'phone' => '08111-002-002', 'status' => 'available'],
            ['agency_id' => $dishub->id,  'name' => 'Opr. Fauzan Akbar',    'employee_id' => 'DHB-001', 'phone' => '08111-003-001', 'status' => 'available'],
            ['agency_id' => $dlh->id,     'name' => 'Opr. Sri Mulyani',     'employee_id' => 'DLH-001', 'phone' => '08111-004-001', 'status' => 'available'],
            ['agency_id' => $dpupr->id,   'name' => 'Eng. Rahmat Hidayat',  'employee_id' => 'DPU-001', 'phone' => '08111-005-001', 'status' => 'available'],
        ];

        foreach ($officers as $o) {
            Officer::firstOrCreate(['employee_id' => $o['employee_id']], $o);
        }

        // ─── Routing Rules ────────────────────────────────────────────────────
        $rules = [
            // Critical — Damkar
            ['agency_id' => $damkar->id, 'category' => 'kebakaran',    'subcategory' => null, 'priority_override' => 'critical', 'sla_minutes' => 10,  'sort_order' => 1],
            // High — DPUPR for banjir
            ['agency_id' => $dpupr->id,  'category' => 'banjir',       'subcategory' => null, 'priority_override' => 'high',     'sla_minutes' => 60,  'sort_order' => 2],
            // Medium — Satpol PP
            ['agency_id' => $satpol->id, 'category' => 'ketertiban',   'subcategory' => null, 'priority_override' => null,       'sla_minutes' => 30,  'sort_order' => 3],
            ['agency_id' => $satpol->id, 'category' => 'ketertiban',   'subcategory' => 'pengamen', 'priority_override' => null, 'sla_minutes' => 30,  'sort_order' => 4],
            // Dishub
            ['agency_id' => $dishub->id, 'category' => 'lalu_lintas',  'subcategory' => null, 'priority_override' => 'medium',   'sla_minutes' => 60,  'sort_order' => 5],
            // DLH
            ['agency_id' => $dlh->id,    'category' => 'sampah',       'subcategory' => null, 'priority_override' => 'medium',   'sla_minutes' => 120, 'sort_order' => 6],
            ['agency_id' => $dlh->id,    'category' => 'taman',        'subcategory' => null, 'priority_override' => 'low',      'sla_minutes' => 240, 'sort_order' => 7],
            ['agency_id' => $dlh->id,    'category' => 'pohon',        'subcategory' => null, 'priority_override' => 'medium',   'sla_minutes' => 120, 'sort_order' => 8],
            // DPUPR
            ['agency_id' => $dpupr->id,  'category' => 'infrastruktur','subcategory' => null, 'priority_override' => 'high',     'sla_minutes' => 240, 'sort_order' => 9],
            ['agency_id' => $dpupr->id,  'category' => 'jalan',        'subcategory' => null, 'priority_override' => 'medium',   'sla_minutes' => 240, 'sort_order' => 10],
        ];

        foreach ($rules as $r) {
            RoutingRule::firstOrCreate(
                ['agency_id' => $r['agency_id'], 'category' => $r['category'], 'subcategory' => $r['subcategory']],
                $r
            );
        }
    }
}
