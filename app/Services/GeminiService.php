<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GeminiService
{
    private Client $http;
    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) env('GEMINI_API_KEY', '');
        $this->model = $model ?? (string) env('GEMINI_MODEL', 'gemini-1.5-flash');
        $this->http = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/',
            'timeout' => (float) env('GEMINI_TIMEOUT', 20),
        ]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Generate plain text from Gemini given a prompt.
     * @throws \RuntimeException on config/network errors
     */
    public function generateText(string $prompt, array $generationConfig = []): string
    {
        $response = $this->callGenerateContent([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => $generationConfig,
        ]);

        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Ask Gemini to return structured JSON by setting response_mime_type.
     * Attempts to json_decode the first candidate text.
     * @return array Decoded JSON (empty array on failure)
     */
    public function generateJson(string $prompt, ?array $schema = null): array
    {
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ],
        ];

        if ($schema) {
            // Best-effort: newer APIs support response_schema, ignore if not available
            $payload['generationConfig']['response_schema'] = $schema;
        }

        $response = $this->callGenerateContent($payload);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Low-level call to Gemini generateContent REST endpoint.
     * @throws \RuntimeException
     */
    private function callGenerateContent(array $json): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured');
        }

        $endpoint = sprintf('v1beta/models/%s:generateContent', $this->model);
        try {
            $res = $this->http->post($endpoint, [
                'query' => ['key' => $this->apiKey],
                'json' => $json,
            ]);
            $body = json_decode((string) $res->getBody(), true);
            if (!is_array($body)) {
                throw new \RuntimeException('Invalid response from Gemini API');
            }
            // Surface API errors if present
            if (isset($body['error'])) {
                $msg = $body['error']['message'] ?? 'Unknown Gemini API error';
                throw new \RuntimeException('Gemini API error: ' . $msg);
            }
            return $body;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('HTTP error calling Gemini API: ' . $e->getMessage(), 0, $e);
        }
    }
}
