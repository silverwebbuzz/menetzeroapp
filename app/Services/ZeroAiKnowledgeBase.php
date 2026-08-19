<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Zero AI knowledge base.
 *
 * Parses the pre-question markdown in documentation/elevenlabs-voice-agent/ into
 * Q&A pairs and answers free-text questions with local keyword scoring — no LLM,
 * no API cost. The same files feed the ElevenLabs voice agent, so the chat panel
 * and the voice assistant always say the same thing.
 *
 * Paid phase: this class is the seam. Swap answer() for a model-backed lookup and
 * the controller, routes and UI stay as they are.
 */
class ZeroAiKnowledgeBase
{
    /** Words too common in this domain to carry signal. */
    private const STOP_WORDS = [
        'a', 'about', 'an', 'and', 'answer', 'any', 'are', 'as', 'at', 'be', 'been', 'by',
        'can', 'did', 'do', 'does', 'for', 'from', 'get', 'give', 'has', 'have', 'how',
        'i', 'if', 'in', 'is', 'it', 'me', 'my', 'need', 'of', 'on', 'or', 'our', 'please',
        'question', 'see', 'should', 'show', 'so', 'tell', 'that', 'the', 'their',
        'them', 'there', 'they', 'this', 'to', 'up', 'use', 'want', 'was', 'we', 'what',
        'when', 'where', 'which', 'who', 'why', 'will', 'with', 'you', 'your',
    ];

    /** Minimum score before we claim to have an answer. */
    private const MATCH_THRESHOLD = 0.18;

    public function __construct(private readonly string $basePath = '')
    {
    }

    /**
     * Every Q&A pair for a portal, grouped by category, ready for the sidebar.
     *
     * @return Collection<int, array{category: string, slug: string, questions: array}>
     */
    public function categories(string $portal): Collection
    {
        return collect($this->load($portal))
            ->groupBy('category')
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'slug' => Str::slug($category),
                'questions' => $items->values()->all(),
            ])
            ->values();
    }

    /** A single Q&A pair by its stable id, or null. */
    public function find(string $portal, string $id): ?array
    {
        return collect($this->load($portal))->firstWhere('id', $id);
    }

    /**
     * Answer a free-text question from the knowledge base.
     *
     * @return array{matched: bool, answer: ?array, related: array}
     */
    public function answer(string $portal, string $question): array
    {
        $entries = $this->load($portal);
        $tokens = $this->tokenize($question);

        if ($tokens === [] || $entries === []) {
            return ['matched' => false, 'answer' => null, 'related' => $this->popular($portal)];
        }

        $scored = collect($entries)
            ->map(fn (array $entry) => $entry + ['score' => $this->score($tokens, $question, $entry)])
            ->filter(fn (array $entry) => $entry['score'] > 0)
            ->sortByDesc('score')
            ->values();

        $best = $scored->first();

        if (! $best || $best['score'] < self::MATCH_THRESHOLD) {
            return [
                'matched' => false,
                'answer' => null,
                // Even a weak match is a useful "did you mean" nudge.
                'related' => $scored->take(4)->map(fn ($e) => $this->strip($e))->all()
                    ?: $this->popular($portal),
            ];
        }

        return [
            'matched' => true,
            'answer' => $this->strip($best),
            'related' => $scored->slice(1, 3)->map(fn ($e) => $this->strip($e))->values()->all(),
        ];
    }

    /** Suggested starting questions when we have nothing better to offer. */
    public function popular(string $portal): array
    {
        return collect($this->load($portal))->take(4)->map(fn ($e) => $this->strip($e))->all();
    }

    /**
     * Score one entry against the asked question.
     *
     * Question-text hits weigh more than answer-text hits — users phrase questions
     * like questions, and matching on answer prose alone produces confident wrong
     * answers. A whole-phrase hit short-circuits to a near-certain match.
     */
    private function score(array $tokens, string $raw, array $entry): float
    {
        $needle = Str::lower(trim($raw));
        $haystackQ = Str::lower($entry['question']);

        // Exact question text is an unambiguous hit — this is what clicking a
        // suggested question or a "related" link sends back.
        if ($needle !== '' && rtrim($needle, ' ?') === rtrim($haystackQ, ' ?')) {
            return 2.0;
        }

        if (Str::length($needle) > 8 && str_contains($haystackQ, $needle)) {
            return 1.0;
        }

        $questionTokens = $this->tokenize($entry['question']);
        $answerTokens = $this->tokenize($entry['answer']);

        $hitsQ = 0;
        $hitsA = 0;

        foreach ($tokens as $token) {
            if ($this->tokenMatches($token, $questionTokens)) {
                $hitsQ++;
            } elseif ($this->tokenMatches($token, $answerTokens)) {
                $hitsA++;
            }
        }

        if ($hitsQ === 0 && $hitsA === 0) {
            return 0.0;
        }

        // A bare digit ("2") disambiguates "Scope 2" but must never carry a match
        // on its own, or arithmetic and stray numbers score as real questions.
        $hasWordHit = false;
        foreach ($tokens as $token) {
            if (! ctype_digit($token)
                && ($this->tokenMatches($token, $questionTokens) || $this->tokenMatches($token, $answerTokens))) {
                $hasWordHit = true;
                break;
            }
        }

        if (! $hasWordHit) {
            return 0.0;
        }

        $total = count($tokens);
        $base = (($hitsQ * 1.0) + ($hitsA * 0.35)) / $total;

        // Reward entries whose own question is largely covered by the asked terms.
        // Without this, a long definition question ("What is the difference between
        // Quick Input and GHG Inventory?") outranks the short direct one ("Where is
        // the GHG Inventory?") simply by containing more words to hit.
        $coverage = count($questionTokens) > 0
            ? $hitsQ / count($questionTokens)
            : 0.0;

        return round(($base * 0.75) + ($coverage * 0.25), 4);
    }

    /** Exact or stem-ish match, so "report" hits "reports" and "reporting". */
    private function tokenMatches(string $token, array $haystack): bool
    {
        foreach ($haystack as $candidate) {
            if ($candidate === $token) {
                return true;
            }

            if (Str::length($token) >= 5 && Str::length($candidate) >= 5
                && (str_starts_with($candidate, $token) || str_starts_with($token, $candidate))) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function tokenize(string $text): array
    {
        $text = Str::lower(strip_tags($text));
        // Subscripts appear in units the KB uses (tCO₂e); fold them to plain digits
        // so a user typing "tco2e" matches the stored "tCO₂e".
        $text = strtr($text, ['₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4']);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';

        return collect(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->reject(function (string $word) {
                // Single digits are kept: "scope 1" vs "scope 2" turns entirely on them.
                if (ctype_digit($word)) {
                    return false;
                }

                return Str::length($word) < 2 || in_array($word, self::STOP_WORDS, true);
            })
            ->unique()
            ->values()
            ->all();
    }

    /** Drop the internal score before the entry leaves the service. */
    private function strip(array $entry): array
    {
        unset($entry['score']);

        return $entry;
    }

    /**
     * Parse the markdown into Q&A pairs.
     *
     * Format is "## Category" followed by alternating "Q: ..." / "A: ..." lines.
     * Cached because the source files only change on deploy.
     *
     * @return array<int, array{id: string, category: string, question: string, answer: string}>
     */
    private function load(string $portal): array
    {
        $file = $this->file($portal);

        if (! $file || ! is_readable($file)) {
            return [];
        }

        return Cache::remember(
            "zero-ai.kb.{$portal}." . filemtime($file),
            now()->addDay(),
            fn () => $this->parse(file_get_contents($file) ?: '')
        );
    }

    /** @return array<int, array> */
    private function parse(string $markdown): array
    {
        $entries = [];
        $category = 'General';
        $pendingQuestion = null;
        $index = 0;

        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            $line = trim($line);

            if (str_starts_with($line, '## ')) {
                $category = trim(substr($line, 3));
                $pendingQuestion = null;
                continue;
            }

            if (str_starts_with($line, 'Q: ')) {
                $pendingQuestion = trim(substr($line, 3));
                continue;
            }

            if (str_starts_with($line, 'A: ') && $pendingQuestion !== null) {
                $entries[] = [
                    'id' => 'q' . (++$index),
                    'category' => $category,
                    'question' => $pendingQuestion,
                    'answer' => trim(substr($line, 3)),
                ];
                $pendingQuestion = null;
            }
        }

        return $entries;
    }

    private function file(string $portal): ?string
    {
        $base = $this->basePath ?: base_path('documentation/elevenlabs-voice-agent');

        return match ($portal) {
            'consultant' => $base . '/CONSULTANT_PRE_QUESTIONS.md',
            'company' => $base . '/COMPANY_PRE_QUESTIONS.md',
            default => null,
        };
    }
}
