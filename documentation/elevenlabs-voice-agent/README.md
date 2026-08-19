# MENetZero — ElevenLabs Voice Help Agent

Upload these files to your ElevenLabs Conversational AI agent **Knowledge Base**. Enable **RAG**.

| File | Purpose |
|------|---------|
| [COMPANY_PORTAL_KNOWLEDGE.md](./COMPANY_PORTAL_KNOWLEDGE.md) | How the company (client) portal works — pages, workflows, links |
| [CONSULTANT_PORTAL_KNOWLEDGE.md](./CONSULTANT_PORTAL_KNOWLEDGE.md) | How the consultant agency portal works — pages, workflows, links |
| [COMPANY_PRE_QUESTIONS.md](./COMPANY_PRE_QUESTIONS.md) | Company FAQ / pre-answered questions (user phrasing) |
| [CONSULTANT_PRE_QUESTIONS.md](./CONSULTANT_PRE_QUESTIONS.md) | Consultant FAQ / pre-answered questions (user phrasing) |

These mirror the in-app **Company portal guide** and **Consultant portal guide** (Help & guide in the sidebar). They are written for voice — not PHP config or developer docs.

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
