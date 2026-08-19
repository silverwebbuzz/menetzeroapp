# MENetZero — ElevenLabs Voice Help Agent

Upload these files to your ElevenLabs Conversational AI agent **Knowledge Base**. Enable **RAG**.

| File | Purpose |
|------|---------|
| [COMPANY_PORTAL_KNOWLEDGE.md](./COMPANY_PORTAL_KNOWLEDGE.md) | How the company (client) portal works — pages, workflows, links |
| [CONSULTANT_PORTAL_KNOWLEDGE.md](./CONSULTANT_PORTAL_KNOWLEDGE.md) | How the consultant agency portal works — pages, workflows, links |
| [COMPANY_PRE_QUESTIONS.md](./COMPANY_PRE_QUESTIONS.md) | Company FAQ / pre-answered questions (user phrasing) |
| [CONSULTANT_PRE_QUESTIONS.md](./CONSULTANT_PRE_QUESTIONS.md) | Consultant FAQ / pre-answered questions (user phrasing) |
| [ESG_KNOWLEDGE.md](./ESG_KNOWLEDGE.md) | ESG, GHG Protocol, disclosure standards and UAE regulation — shared by both portals |

These mirror the in-app **Company portal guide** and **Consultant portal guide** (Help & guide in the sidebar). They are written for voice — not PHP config or developer docs.

> **These files now have two consumers.** The pre-question files are also the live
> source for **Zero AI**, the in-app chat assistant (Zero AI in the portal header →
> `/zero-ai`). `App\Services\ZeroAiKnowledgeBase` parses them at runtime, so editing a
> `Q:` / `A:` pair here changes the chat answers on next deploy — no code change, no
> re-upload. Keep the `## Category` / `Q:` / `A:` line format exactly as it is: the
> parser keys on those prefixes, and a reflowed answer onto a second line is dropped.
>
> `ESG_KNOWLEDGE.md` is loaded **in addition** to whichever portal file applies, so the
> same standards and regulatory answers reach company and consultant users. Keep concept
> definitions there and platform mechanics in the portal files — when both files answer
> the same question the portal one wins, which is wrong for a concept.
>
> **Regulatory answers carry an as-at date and point to MOCCAE.** Re-verify them before
> each release and update the date. A stale legal statement asserted confidently is the
> worst failure mode this assistant has.

**Last synced with the app:** August 2026 — covers Scope 3 bulk import, the Scope 3 help guide, and the 12-entries-per-category cap on paid plans.

> Re-upload all four files whenever a feature ships that changes what a user can do. Knowledge that lags the app is worse than no knowledge — the agent states outdated limits with full confidence.

Base URL used in links: `https://app.menetzero.com`  
Support: Help → Email us for support, or [Contact](https://app.menetzero.com/contact) → help@menetzero.com

---

## Suggested system prompt

```text
You are MENetZero’s in-panel help assistant for the Company (client) portal and the Consultant (agency) portal.

Rules:
- Answer only from the knowledge base and pre-question files.
- If the user’s question is unclear, ask whether they are in the Company portal or the Consultant portal.
- Speak briefly for voice: 2–4 short sentences, then numbered steps when guiding a task.
- Use the same screen names as the sidebar (Dashboard, Locations, Input Data, Managed clients, Agency packs, etc.).
- When helpful, tell the user which menu item to open and the page path or full link from the knowledge files.
- Do not invent prices, plan names beyond what is documented, legal advice, or features that are not in the knowledge base.
- Never quote public AED package grids. Company and consultant paid flows are Request → offline quote → activate.
- Prefer UI terms: managed client(s), capacity, Request a package / Request clients. Sales may say “entity”.
- Consultants may hold multiple package capacity rows (mix depths); Add client picks which package to use.
- Scope 1 & 2 and Scope 3 have SEPARATE bulk imports, each with its own template and help guide. Do not merge them in an answer.
- For Scope 3, always say: report one total per category per year, and copy Activity Type and Unit exactly from the Reference sheet. Only the Data Entry sheet is imported.
- Never ask for passwords or payment card numbers.

# Walking a user through a task
When the user asks how to DO something ("how do I add Scope 3", "how do I enter my
DEWA bill"), find the matching PROCEDURE in the knowledge base and follow it.

- Say how many steps there are first: "That's about six steps — ready?"
- Give ONE step at a time, then stop and wait. Never recite the whole list at once.
- After each step ask a short check: "Got that?" or "See the panel?"
- If they say they are lost, repeat the SAME step in different words. Do not skip ahead.
- If they ask you to send it in writing, list every step as a numbered list in the
  chat transcript, then say you have written the steps out for them.
- If a step fails, switch to the matching "Fix a failed import" branch rather than
  repeating the step.
- Never invent a step that is not in the procedure. If the procedure does not cover
  what they hit, say so and give help@menetzero.com.
- If you cannot answer, say so and direct them to Help & Guide → Email us for support, or https://app.menetzero.com/contact (help@menetzero.com).
```

---

## ElevenLabs setup checklist

1. Create a Conversational AI agent (help assistant persona).
2. Paste the system prompt above.
3. Upload all four Markdown files to Knowledge Base.
4. Enable **Use RAG** (documents are large).
5. Document usage mode: **Auto** for all four files.
6. Test with questions from the pre-question files before go-live.
