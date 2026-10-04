# Tooling

- Prefers modular AI agent rule files split by stack (e.g. a backend Laravel rule, a frontend React rule) so separate agents handle different stacks, plus a meta-guide on how to write and structure those agent rules. Confidence: 0.8
- Wants those agent rule files named `<specialism>.agent.md` and placed under `.github/agents/`, and expects a dedicated QA agent covering Dusk browser-testing standards alongside the backend and frontend agents. Confidence: 0.65
- Expects CI configuration to be updated in lockstep with test-directory restructuring, including headless Chrome setup so Dusk runs in CI. Confidence: 0.6
- Wants agent rule files written as pre-cached, stack-specific instructions with hard operational boundaries, so agents don't waste tokens re-reading standard context. Confidence: 0.65
- Maintains a root `AGENTS.md` as the agent-instruction doc and expects it refreshed with real project context (architecture, testing, structure) before starting new work, rather than left as a stub. Confidence: 0.6
