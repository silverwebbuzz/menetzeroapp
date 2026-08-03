# MENetZero — ElevenLabs Voice Help Agent

Upload these files to your ElevenLabs Conversational AI agent **Knowledge Base**. Enable **RAG**.

| File | Purpose |
|------|---------|
| [COMPANY_PORTAL_KNOWLEDGE.md](./COMPANY_PORTAL_KNOWLEDGE.md) | How the company (client) portal works — pages, workflows, links |
| [CONSULTANT_PORTAL_KNOWLEDGE.md](./CONSULTANT_PORTAL_KNOWLEDGE.md) | How the consultant agency portal works — pages, workflows, links |
| [COMPANY_PRE_QUESTIONS.md](./COMPANY_PRE_QUESTIONS.md) | Company FAQ / pre-answered questions (user phrasing) |
| [CONSULTANT_PRE_QUESTIONS.md](./CONSULTANT_PRE_QUESTIONS.md) | Consultant FAQ / pre-answered questions (user phrasing) |

These mirror the in-app **Company portal guide** and **Consultant portal guide** (Help & guide in the sidebar). They are written for voice — not PHP config or developer docs.

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
