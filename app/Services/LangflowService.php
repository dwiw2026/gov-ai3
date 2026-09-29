<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LangflowService — calls the Langflow Complaint Intelligence Agent workflow.
 *
 * Langflow is the AI Agent layer: it runs LLM + RAG + Tools to classify
 * a complaint and return structured JSON for routing decisions.
 */
class LangflowService
{
    protected string $baseUrl;
    protected string $flowId;
    protected ?string $apiKey;
    protected float  $confidenceThreshold;

    public function __construct()
    {
        $this->baseUrl             = rtrim(config('civicai.langflow.base_url'), '/');
        $this->flowId              = config('civicai.langflow.flow_id', '');
        $this->apiKey              = config('civicai.langflow.api_key');
        $this->confidenceThreshold = (float) config('civicai.ai.confidence_threshold', 0.70);
    }

    /**
     * Analyze a complaint through the Langflow Intelligence Agent.
     *
     * Returns a structured array:
     * [
     *   category, subcategory, location, priority,
     *   responsible_agency, confidence, reason,
     *   recommended_action, requires_human_review,
     *   raw_output
     * ]
     */
    public function analyzeComplaint(
        string  $message,
        ?string $locationText = null,
        ?string $imageBase64 = null,
        string  $channel = 'bot'
    ): array {
        if (empty($this->flowId)) {
            Log::warning('LangflowService: LANGFLOW_FLOW_ID not configured — using fallback classifier');
            return $this->fallbackClassify($message, $locationText);
        }

        $inputPayload = [
            'message'  => $message,
            'location' => $locationText ?? '',
            'channel'  => $channel,
        ];

        if ($imageBase64) {
            $inputPayload['image'] = $imageBase64;
        }

        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(30)
                ->post("{$this->baseUrl}/api/v1/run/{$this->flowId}", [
                    'input_value'  => json_encode($inputPayload),
                    'input_type'   => 'chat',
                    'output_type'  => 'chat',
                    'tweaks'       => [],
                ]);

            if ($response->failed()) {
                Log::error('Langflow API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fallbackClassify($message, $locationText);
            }

            $rawOutput = $response->json();
            return $this->parseOutput($rawOutput, $message, $locationText);

        } catch (\Throwable $e) {
            Log::error('Langflow connection failed: ' . $e->getMessage());
            return $this->fallbackClassify($message, $locationText);
        }
    }

    /**
     * Parse Langflow response into CivicAI standard format.
     */
    protected function parseOutput(array $rawOutput, string $message, ?string $location): array
    {
        // Langflow v1 API returns outputs[0].outputs[0].results.message.text
        $text = data_get($rawOutput, 'outputs.0.outputs.0.results.message.text', '');

        // Try to extract JSON from text (Langflow might wrap it)
        $jsonString = $this->extractJson($text);
        $parsed     = json_decode($jsonString, true) ?? [];

        $confidence = (float) ($parsed['confidence'] ?? 0.5);

        return [
            'category'             => $parsed['category'] ?? 'umum',
            'subcategory'          => $parsed['subcategory'] ?? null,
            'location'             => $parsed['location'] ?? $location,
            'priority'             => $parsed['priority'] ?? 'medium',
            'responsible_agency'   => $parsed['responsible_agency'] ?? 'UMUM',
            'confidence'           => $confidence,
            'reason'               => $parsed['reason'] ?? 'Dianalisis oleh AI',
            'recommended_action'   => $parsed['recommended_action'] ?? null,
            'requires_human_review'=> $confidence < $this->confidenceThreshold,
            'raw_output'           => $rawOutput,
        ];
    }

    /**
     * Rule-based fallback when Langflow is unavailable.
     * Demonstrates the system still works gracefully.
     */
    protected function fallbackClassify(string $message, ?string $location): array
    {
        $lower = mb_strtolower($message);

        $rules = [
            ['kebakaran|api|terbakar|asap',  'kebakaran',    'DAMKAR',    'critical', 0.88],
            ['sampah|buang|limbah|bau',       'sampah',       'DLH',       'medium',   0.82],
            ['banjir|genang|air|got',         'banjir',       'PDAM',      'high',     0.80],
            ['macet|lalin|lampu|marka',       'lalu_lintas',  'DISHUB',    'medium',   0.78],
            ['pengamen|gepeng|gelandangan',   'ketertiban',   'SATPOL_PP', 'medium',   0.85],
            ['jalan|rusak|berlubang|retak',   'infrastruktur','DPUPR',     'high',     0.79],
            ['pohon|tumbang|ranting',         'ketertiban',   'DLH',       'medium',   0.76],
        ];

        foreach ($rules as [$pattern, $cat, $agency, $priority, $conf]) {
            if (preg_match("/{$pattern}/ui", $lower)) {
                return [
                    'category'             => $cat,
                    'subcategory'          => null,
                    'location'             => $location,
                    'priority'             => $priority,
                    'responsible_agency'   => $agency,
                    'confidence'           => $conf,
                    'reason'               => 'Klasifikasi berdasarkan kata kunci (fallback).',
                    'recommended_action'   => "Petugas {$agency} melakukan verifikasi lapangan.",
                    'requires_human_review'=> $conf < $this->confidenceThreshold,
                    'raw_output'           => ['source' => 'fallback'],
                ];
            }
        }

        return [
            'category'             => 'umum',
            'subcategory'          => null,
            'location'             => $location,
            'priority'             => 'medium',
            'responsible_agency'   => 'UMUM',
            'confidence'           => 0.40,
            'reason'               => 'Laporan tidak dapat diklasifikasikan secara otomatis.',
            'recommended_action'   => 'Diperlukan review manual oleh operator.',
            'requires_human_review'=> true,
            'raw_output'           => ['source' => 'fallback'],
        ];
    }

    protected function extractJson(string $text): string
    {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            return $m[0];
        }
        return $text;
    }

    protected function buildHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($this->apiKey) {
            $headers['x-api-key'] = $this->apiKey;
        }
        return $headers;
    }
}
