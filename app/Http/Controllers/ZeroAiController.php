<?php

namespace App\Http\Controllers;

use App\Services\ZeroAiKnowledgeBase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Zero AI — the in-app ESG assistant.
 *
 * Free phase: answers come from the same curated knowledge base that feeds the
 * ElevenLabs voice agent, matched locally. No model call, so no key and no spend.
 */
class ZeroAiController extends Controller
{
    public function __construct(private readonly ZeroAiKnowledgeBase $kb)
    {
    }

    /** Chat page for the company portal. */
    public function company()
    {
        return view('zero-ai.index', [
            'portal' => 'company',
            'layout' => 'layouts.app',
            'categories' => $this->kb->categories('company'),
            'askUrl' => route('client.zero-ai.ask'),
        ]);
    }

    /** Chat page for the consultant portal. */
    public function consultant()
    {
        return view('zero-ai.index', [
            'portal' => 'consultant',
            'layout' => 'consultant.layouts.app',
            'categories' => $this->kb->categories('consultant'),
            'askUrl' => route('consultant.zero-ai.ask'),
        ]);
    }

    /** Answer one question. Called by the chat panel via fetch(). */
    public function ask(Request $request, string $portal): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $question = Str::squish($validated['question']);
        $result = $this->kb->answer($portal, $question);

        return response()->json([
            'matched' => $result['matched'],
            'question' => $question,
            'answer' => $result['matched']
                ? $result['answer']['answer']
                : "I don't have an answer for that in the free knowledge base yet. Try one of the suggested questions, or contact our team and we'll help directly.",
            'category' => $result['answer']['category'] ?? null,
            'related' => collect($result['related'])
                ->map(fn (array $entry) => [
                    'id' => $entry['id'],
                    'question' => $entry['question'],
                ])
                ->all(),
        ]);
    }

    public function askCompany(Request $request): JsonResponse
    {
        return $this->ask($request, 'company');
    }

    public function askConsultant(Request $request): JsonResponse
    {
        return $this->ask($request, 'consultant');
    }
}
