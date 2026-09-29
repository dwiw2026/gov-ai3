<?php

namespace App\Services;

use App\Models\TelegramSession;
use App\Services\ComplaintService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TelegramService — handles the Telegram Bot citizen intake channel.
 *
 * Flow:
 *   Citizen → Telegram → Laravel Webhook → TelegramService
 *              → LangflowService (AI analysis)
 *              → ComplaintService (create ticket)
 *              → Telegram (send confirmation)
 *
 * This is the "WhatsApp / Telegram intake channel" described in the architecture.
 * IBM Bob is the separate operator-facing interface (via MCP).
 */
class TelegramService
{
    protected string $token;
    protected string $apiBase;

    public function __construct()
    {
        $this->token   = config('civicai.telegram.token', '');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    // ─── Incoming update dispatcher ────────────────────────────────────────

    public function handleUpdate(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    protected function handleMessage(array $message): void
    {
        $chatId   = $message['chat']['id'];
        $userId   = (string) $message['from']['id'];
        $text     = $message['text'] ?? '';
        $photo    = $message['photo'] ?? null;
        $location = $message['location'] ?? null;

        $session = TelegramSession::firstOrCreate(
            ['telegram_user_id' => $userId],
            [
                'telegram_username' => $message['from']['username'] ?? null,
                'first_name'        => $message['from']['first_name'] ?? null,
                'last_name'         => $message['from']['last_name'] ?? null,
                'state'             => 'idle',
            ]
        );

        $session->update(['last_activity_at' => now()]);

        // Commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $userId, $text, $session);
            return;
        }

        // State machine
        match ($session->state) {
            'idle'                 => $this->handleIdle($chatId, $userId, $text, $photo, $session),
            'awaiting_description' => $this->handleDescription($chatId, $userId, $text, $session),
            'awaiting_location'    => $this->handleLocation($chatId, $userId, $text, $location, $session),
            'awaiting_photo'       => $this->handlePhoto($chatId, $userId, $text, $photo, $session),
            'confirming'           => $this->handleConfirm($chatId, $userId, $text, $session),
            default                => $this->handleIdle($chatId, $userId, $text, $photo, $session),
        };
    }

    protected function handleCommand(string $chatId, string $userId, string $command, TelegramSession $session): void
    {
        switch (strtolower(explode('@', $command)[0])) {
            case '/start':
                $session->clearSession();
                $this->sendMessage($chatId,
                    "👋 *Selamat datang di CivicAI!*\n\n"
                    . "Saya adalah asisten pengaduan masyarakat yang didukung AI.\n\n"
                    . "Ketik pengaduan Anda secara langsung, atau gunakan:\n"
                    . "• /lapor — Buat laporan baru\n"
                    . "• /status — Cek status laporan\n"
                    . "• /bantuan — Bantuan\n\n"
                    . "_\"Dari laporan menjadi tindakan.\"_ 🏛️"
                );
                break;

            case '/lapor':
                $session->update(['state' => 'awaiting_description', 'session_data' => []]);
                $this->sendMessage($chatId,
                    "📝 *Buat Laporan Baru*\n\n"
                    . "Ceritakan masalah yang Anda laporkan. Sertakan:\n"
                    . "• Deskripsi kejadian\n"
                    . "• Lokasi (jalan, kecamatan, atau kirim lokasi)\n\n"
                    . "Contoh: _\"Ada pengamen di Jalan A. Yani mengganggu pengguna jalan\"_"
                );
                break;

            case '/status':
                $this->sendMessage($chatId, "Masukkan nomor tiket Anda (contoh: CA-2026-00001):");
                $session->update(['state' => 'idle']);
                break;

            case '/bantuan':
                $this->sendMessage($chatId,
                    "ℹ️ *Bantuan CivicAI*\n\n"
                    . "Anda bisa melaporkan:\n"
                    . "🔥 Kebakaran → Damkar\n"
                    . "🚔 Ketertiban umum → Satpol PP\n"
                    . "🗑️ Sampah/lingkungan → DLH\n"
                    . "🚦 Lalu lintas → Dishub\n"
                    . "🏗️ Infrastruktur jalan → DPUPR\n\n"
                    . "AI kami akan menentukan OPD yang tepat secara otomatis."
                );
                break;

            default:
                $this->sendMessage($chatId, "Perintah tidak dikenal. Ketik /bantuan untuk daftar perintah.");
        }
    }

    protected function handleIdle(string $chatId, string $userId, string $text, ?array $photo, TelegramSession $session): void
    {
        // Check if user is querying ticket status
        if (preg_match('/^CA-\d{4}-\d{5}$/', trim($text))) {
            $this->queryTicketStatus($chatId, trim($text));
            return;
        }

        // Treat as direct complaint
        if (strlen($text) > 10) {
            $session->update([
                'state'        => 'awaiting_location',
                'session_data' => ['description' => $text],
            ]);
            $this->sendMessage($chatId,
                "📍 Di mana lokasi kejadian tersebut?\n\n"
                . "Anda bisa mengetik nama jalan/lokasi, atau kirim lokasi GPS."
            );
        } else {
            $this->sendMessage($chatId,
                "Halo! Ketik /lapor untuk membuat laporan pengaduan, atau ceritakan langsung masalah yang Anda hadapi."
            );
        }
    }

    protected function handleDescription(string $chatId, string $userId, string $text, TelegramSession $session): void
    {
        $session->setSessionValue('description', $text);
        $session->update(['state' => 'awaiting_location']);
        $this->sendMessage($chatId,
            "📍 Sekarang, di mana lokasi kejadian?\n\n"
            . "Ketik nama jalan/lokasi, atau kirim lokasi GPS dari Telegram."
        );
    }

    protected function handleLocation(
        string $chatId, string $userId, string $text, ?array $location, TelegramSession $session
    ): void {
        if ($location) {
            $session->setSessionValue('latitude', $location['latitude']);
            $session->setSessionValue('longitude', $location['longitude']);
            $session->setSessionValue('location_text', "GPS: {$location['latitude']},{$location['longitude']}");
        } else {
            $session->setSessionValue('location_text', $text);
        }

        $session->update(['state' => 'confirming']);

        $desc = $session->getSessionValue('description');
        $loc  = $session->getSessionValue('location_text');

        $this->sendMessage($chatId,
            "📋 *Konfirmasi Laporan*\n\n"
            . "Deskripsi: {$desc}\n"
            . "Lokasi: {$loc}\n\n"
            . "Kirim laporan ini? Balas *YA* untuk mengkonfirmasi atau *TIDAK* untuk membatalkan."
        );
    }

    protected function handlePhoto(
        string $chatId, string $userId, string $text, ?array $photo, TelegramSession $session
    ): void {
        if ($photo) {
            $session->setSessionValue('has_photo', true);
        }
        $session->update(['state' => 'confirming']);
        $this->handleConfirm($chatId, $userId, 'ya', $session);
    }

    protected function handleConfirm(string $chatId, string $userId, string $text, TelegramSession $session): void
    {
        if (!in_array(strtolower(trim($text)), ['ya', 'yes', 'y', '1', 'ok'])) {
            $session->clearSession();
            $this->sendMessage($chatId, "Laporan dibatalkan. Ketik /lapor untuk memulai laporan baru.");
            return;
        }

        $this->sendMessage($chatId, "⏳ Sedang menganalisis laporan Anda...");

        $desc     = $session->getSessionValue('description');
        $locText  = $session->getSessionValue('location_text');
        $lat      = $session->getSessionValue('latitude');
        $lng      = $session->getSessionValue('longitude');

        // Resolve ComplaintService from container
        /** @var ComplaintService $svc */
        $svc      = app(ComplaintService::class);
        $analysis = $svc->analyzeComplaint($desc, $locText, null, 'telegram', $userId, $session->first_name);

        $complaint = $svc->createComplaint(array_merge($analysis, [
            'location_text'       => $locText,
            'latitude'            => $lat,
            'longitude'           => $lng,
            'citizen_identifier'  => $userId,
            'citizen_name'        => trim("{$session->first_name} {$session->last_name}"),
        ]));

        $session->update(['last_complaint_id' => $complaint->id]);
        $session->clearSession();

        $priorityEmoji = match ($complaint->priority) {
            'critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', default => '🟢',
        };
        $confidence = round(($complaint->ai_confidence ?? 0) * 100);

        $this->sendMessage($chatId,
            "✅ *Laporan Berhasil Dibuat!*\n\n"
            . "📋 Nomor: `{$complaint->ticket_number}`\n"
            . "🏷️ Kategori: " . ucfirst($complaint->category ?? 'Umum') . "\n"
            . "🏛️ Ditangani: " . ($complaint->agency?->name ?? 'Menunggu routing') . "\n"
            . "{$priorityEmoji} Prioritas: " . ucfirst($complaint->priority) . "\n"
            . "🤖 AI Confidence: {$confidence}%\n\n"
            . ($complaint->requires_human_review
                ? "⚠️ _Laporan memerlukan review manual oleh operator._\n\n"
                : "")
            . "Kami akan memberitahu Anda saat ada perkembangan. Terima kasih! 🙏"
        );
    }

    protected function handleCallbackQuery(array $callback): void
    {
        // Handle inline keyboard callbacks (future feature)
        $this->answerCallbackQuery($callback['id']);
    }

    protected function queryTicketStatus(string $chatId, string $ticketNumber): void
    {
        /** @var ComplaintService $svc */
        $svc    = app(ComplaintService::class);
        $status = $svc->getComplaintStatus($ticketNumber);

        if (!$status) {
            $this->sendMessage($chatId, "Nomor tiket `{$ticketNumber}` tidak ditemukan.");
            return;
        }

        $slaInfo = $status['sla_breached']
            ? "⚠️ SLA Terlewat"
            : ($status['sla_remaining'] !== null
                ? ($status['sla_remaining'] > 0
                    ? "⏰ Sisa SLA: {$status['sla_remaining']} menit"
                    : "✅ Selesai tepat waktu")
                : "");

        $this->sendMessage($chatId,
            "📋 *Status Laporan*\n\n"
            . "Nomor: `{$ticketNumber}`\n"
            . "Status: *{$status['status_label']}*\n"
            . "Kategori: " . ucfirst($status['category'] ?? '-') . "\n"
            . "Instansi: " . ($status['agency'] ?? '-') . "\n"
            . ($status['officer'] ? "Petugas: {$status['officer']}\n" : '')
            . ($slaInfo ? "{$slaInfo}\n" : '')
        );
    }

    // ─── Telegram API wrappers ──────────────────────────────────────────────

    public function sendMessage(string $chatId, string $text, array $extra = []): bool
    {
        if (empty($this->token)) {
            Log::info("Telegram [MOCK] to {$chatId}: {$text}");
            return false;
        }

        try {
            $response = Http::post("{$this->apiBase}/sendMessage", array_merge([
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ], $extra));

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Telegram sendMessage error: " . $e->getMessage());
            return false;
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        if (empty($this->token)) {
            return;
        }
        Http::post("{$this->apiBase}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
        ]);
    }

    public function setWebhook(string $url, string $secret = ''): array
    {
        $response = Http::post("{$this->apiBase}/setWebhook", [
            'url'            => $url,
            'secret_token'   => $secret,
            'allowed_updates'=> ['message', 'callback_query'],
        ]);
        return $response->json();
    }
}
