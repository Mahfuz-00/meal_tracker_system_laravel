# Workflow

- Organizes changes into clean, atomic Git commits grouped by feature, working on a dedicated feature branch rather than committing directly onto `main`, then pushes the branch to the remote. Confidence: 0.8
- Verifies Git identity (expected author) and scans for leaked secrets before pushing to the remote, and reports the push state back explicitly. Confidence: 0.6
- Prefers commit messages written as conventional/typed summaries with a body explaining the "why", one focused commit per coherent unit of work. Confidence: 0.6
- Investigates the environment/state before acting (git status, remotes, identity, tool availability) instead of assuming, and stops to ask before touching shared or destructive-adjacent state. Confidence: 0.7
- Insists changes be surgical and strictly scoped: do not touch, alter, or refactor anything outside the explicitly requested changes, leaving other existing modules, seeders, middleware, layouts, and pre-existing tests completely untouched. Confidence: 0.85
- Judges a feature complete only when it works end-to-end in the running UI — infrastructure that exists but is not wired up to the visible surfaces is reported back as "it does not work". Confidence: 0.7
- For bug fixes, wants the root-cause permanent fix implemented, not a temporary workaround or band-aid. Confidence: 0.6
- When restarting from a reset/fresh-baseline branch, wants prior debugging scaffolding and workaround artifacts abandoned rather than carried forward or re-introduced, and expects earlier (possibly faulty) error context to be disregarded. Confidence: 0.65
- Before building, expects the agent to check what already exists and treat any requirement, file, or feature that is already fully or partially implemented as "done" — skip it and move on to the remaining unfinished work rather than redoing or overwriting completed work. Confidence: 0.9
