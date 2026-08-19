@extends($layout)

@section('title', 'Zero AI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/zero-ai.css') }}?v=20260819">
@endpush

@section('content')
<div class="zai" x-data="zeroAi({ askUrl: @js($askUrl) })" x-cloak>

    {{-- Left rail: pre-filled questions from the knowledge base --}}
    <aside class="zai-rail" :class="{ 'is-open': railOpen }">
        <div class="zai-rail__head">
            <div>
                <p class="zai-rail__title">Suggested questions</p>
                <p class="zai-rail__sub">{{ $categories->sum(fn ($c) => count($c['questions'])) }} answers available</p>
            </div>
            <button type="button" class="zai-rail__close" @click="railOpen = false" aria-label="Close questions">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        <div class="zai-rail__search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
            <input type="search" x-model="filter" placeholder="Filter questions…" aria-label="Filter suggested questions">
        </div>

        <nav class="zai-rail__list">
            @foreach($categories as $category)
                <section class="zai-group" data-category="{{ $category['category'] }}">
                    <h3 class="zai-group__title">{{ $category['category'] }}</h3>
                    @foreach($category['questions'] as $item)
                        <button type="button"
                                class="zai-q"
                                data-question="{{ $item['question'] }}"
                                @click="send(@js($item['question']))">
                            {{ $item['question'] }}
                        </button>
                    @endforeach
                </section>
            @endforeach
            <p class="zai-rail__empty" data-empty hidden>No questions match that filter.</p>
        </nav>

        <div class="zai-rail__foot">
            <p class="zai-upsell__title">Need answers beyond these?</p>
            <p class="zai-upsell__body">Zero AI covers the platform plus ESG, GHG Protocol, disclosure standards and UAE reporting rules — free. Tailored analysis of your own data and open-ended ESG advice are coming to paid plans.</p>
        </div>
    </aside>

    <div class="zai-rail__scrim" :class="{ 'is-open': railOpen }" @click="railOpen = false" aria-hidden="true"></div>

    {{-- Conversation --}}
    <section class="zai-main">
        <header class="zai-head">
            <button type="button" class="zai-head__menu" @click="railOpen = true" aria-label="Show suggested questions">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
            </button>
            <div class="zai-head__id">
                <span class="zai-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <h1 class="zai-head__title">Zero AI <span class="zai-badge">Free</span></h1>
                    <p class="zai-head__sub">Your ESG &amp; carbon assistant</p>
                </div>
            </div>
            <button type="button" class="zai-head__reset" @click="reset()" x-show="messages.length > 0">Clear chat</button>
        </header>

        <div class="zai-thread" x-ref="thread">
            {{-- Empty state --}}
            <div class="zai-welcome" x-show="messages.length === 0">
                <span class="zai-welcome__mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z" stroke-linejoin="round"/></svg>
                </span>
                <h2>Ask me about MENetZero</h2>
                <p>I answer from our curated knowledge base — using the platform, plus ESG concepts, the GHG Protocol, disclosure standards and UAE reporting rules. Pick a question on the left or type your own.</p>
                <div class="zai-chips">
                    @foreach($categories->take(6) as $category)
                        @php $seed = $category['questions'][0] ?? null; @endphp
                        @if($seed)
                            <button type="button" class="zai-chip"
                                    @click="send(@js($seed['question']))">
                                {{ $seed['question'] }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Messages --}}
            <template x-for="(msg, i) in messages" :key="i">
                <article class="zai-msg" :class="'zai-msg--' + msg.role">
                    <span class="zai-msg__avatar" aria-hidden="true">
                        <template x-if="msg.role === 'assistant'">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z" stroke-linejoin="round"/></svg>
                        </template>
                        <template x-if="msg.role === 'user'">
                            <span x-text="initial"></span>
                        </template>
                    </span>
                    <div class="zai-msg__body">
                        <p class="zai-msg__who" x-text="msg.role === 'user' ? 'You' : 'Zero AI'"></p>
                        <div class="zai-msg__text" x-html="msg.html"></div>

                        <template x-if="msg.procedure">
                            <div class="zai-steps">
                                <p class="zai-steps__title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke-linecap="round"/></svg>
                                    <span x-text="msg.procedure.title"></span>
                                </p>
                                <template x-if="msg.procedure.intro">
                                    <p class="zai-steps__intro" x-text="msg.procedure.intro"></p>
                                </template>
                                <ol class="zai-steps__list">
                                    <template x-for="(step, si) in msg.procedure.steps" :key="si">
                                        <li x-html="formatStep(step)"></li>
                                    </template>
                                </ol>
                            </div>
                        </template>

                        <template x-if="msg.category">
                            <p class="zai-msg__tag" x-text="msg.category"></p>
                        </template>

                        <template x-if="msg.related && msg.related.length">
                            <div class="zai-related">
                                <p class="zai-related__title">Related questions</p>
                                <template x-for="rel in msg.related" :key="rel.question">
                                    <button type="button" class="zai-related__btn" x-text="rel.question" @click="send(rel.question)"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </article>
            </template>

            {{-- Thinking --}}
            <article class="zai-msg zai-msg--assistant" x-show="busy">
                <span class="zai-msg__avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8L12 3z" stroke-linejoin="round"/></svg>
                </span>
                <div class="zai-msg__body">
                    <p class="zai-msg__who">Zero AI</p>
                    <div class="zai-dots"><span></span><span></span><span></span></div>
                </div>
            </article>
        </div>

        {{-- Composer --}}
        <div class="zai-composer">
            <form class="zai-form" @submit.prevent="send(draft)">
                <textarea x-model="draft"
                          x-ref="input"
                          rows="1"
                          maxlength="500"
                          placeholder="Ask about emissions data, reports, disclosures…"
                          aria-label="Ask Zero AI a question"
                          @keydown.enter.prevent="send(draft)"
                          @input="autosize($event.target)"></textarea>
                <button type="submit" :disabled="busy || !draft.trim()" aria-label="Send question">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
            <p class="zai-disclaimer">Zero AI answers from the MENetZero knowledge base. Regulatory information is general and dated, not legal advice — verify with <a href="https://www.moccae.gov.ae" target="_blank" rel="noopener">MOCCAE</a> or your adviser. For account-specific help, <a href="{{ $portal === 'consultant' ? route('consultant.support') : route('client.support') }}" target="_blank" rel="noopener">contact support</a>.</p>
        </div>
    </section>
</div>
@endsection

@push('head')
<script>
// Registered on alpine:init so the component exists no matter how the CDN
// bundle and this stack end up ordered on the page.
document.addEventListener('alpine:init', () => {
    Alpine.data('zeroAi', (config) => ({
        askUrl: config.askUrl,
        messages: [],
        draft: '',
        filter: '',
        busy: false,
        railOpen: false,
        initial: @js(strtoupper(substr(auth()->user()?->name ?: 'You', 0, 1))),

        init() {
            // Filter the server-rendered question list without re-rendering it.
            this.$watch('filter', (term) => this.applyFilter(term));
        },

        applyFilter(term) {
            const needle = (term || '').trim().toLowerCase();
            const rail = this.$root.querySelector('.zai-rail__list');
            if (!rail) return;
            let visible = 0;

            rail.querySelectorAll('.zai-group').forEach((group) => {
                let shown = 0;
                group.querySelectorAll('.zai-q').forEach((btn) => {
                    const hit = !needle || btn.dataset.question.toLowerCase().includes(needle);
                    btn.hidden = !hit;
                    if (hit) shown++;
                });
                group.hidden = shown === 0;
                visible += shown;
            });

            const empty = rail.querySelector('[data-empty]');
            if (empty) empty.hidden = visible !== 0;
        },

        async send(text) {
            const question = (text || '').trim();
            if (!question || this.busy) return;

            this.draft = '';
            if (this.$refs.input) this.$refs.input.style.height = 'auto';
            this.railOpen = false;
            this.push('user', question);
            this.busy = true;
            this.scroll();

            try {
                const res = await fetch(this.askUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ question }),
                });

                if (!res.ok) throw new Error('Request failed');

                const data = await res.json();
                this.busy = false;
                this.push('assistant', data.answer, {
                    category: data.category,
                    related: data.related || [],
                    procedure: data.procedure || null,
                });
            } catch (e) {
                this.busy = false;
                this.push('assistant', 'Something went wrong reaching the knowledge base. Please try again.');
            }

            this.scroll();
        },

        push(role, text, extra = {}) {
            this.messages.push({
                role,
                html: this.format(text),
                category: extra.category || null,
                related: extra.related || [],
                procedure: extra.procedure || null,
            });
        },

        /** Escape, then linkify — answers are trusted markdown-lite, but never raw HTML. */
        format(text) {
            const escaped = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            return escaped.replace(/(https?:\/\/[^\s<]+[^\s<.,;:)\]])/g,
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
        },

        /**
         * Steps carry their destination as "label (url)". A bare URL mid-sentence
         * is noise, so collapse the parenthesised one into a compact link and leave
         * the instruction text itself untouched — guessing where the label starts
         * mangles sentences more often than it helps.
         */
        formatStep(step) {
            return this.format(step).replace(
                /\s?\((<a ([^>]+)>)(https?:\/\/[^<]+)<\/a>\)/g,
                (m, openTag, attrs) => ` <a ${attrs} class="zai-steps__link">open \u2197</a>`
            );
        },

        autosize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 160) + 'px';
        },

        reset() {
            this.messages = [];
            this.draft = '';
        },

        scroll() {
            this.$nextTick(() => {
                const t = this.$refs.thread;
                if (t) t.scrollTop = t.scrollHeight;
            });
        },
    }));
});
</script>
@endpush
