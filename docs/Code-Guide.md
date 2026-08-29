# Code Guide

Guidance for AI working with code in this repo.

## General Principles
- Concise, short solutions for new modules or code
- Watch for over-engineering, oversized files needing refactor
- Watch for syntax/style mismatching rest of codebase
- Watch for obvious bugs
- Review existing files before refactor or change
- Right data structures and algorithms for problems
- Single responsibility: functions/components have one clear purpose
- Follow existing codebase patterns; new patterns must align with architecture
- No external libraries unless absolutely necessary; use project dependency file for versions
- Least privilege: don't expose data needlessly
- Avoid redundancy unless improves usability

## Comments and Naming
- Self-documenting code preferred: clear naming over explanatory comments
- Comments one-liner, one sentence, only for non-obvious logic or workarounds
- No emojis or special characters in comments
- No TODO/FIXME/temp comments in committed code unless directed

## Documentation
- Concise, useful, well-structured over verbose
- Update docs when code changes; document non-obvious design decisions
- Markdown files use kebab naming (ex. some-description-changes.md)
- Write activity-log.md in /docs to refer back if confused
- Don't auto-commit activity logs and docs

## Workflow
- Make to-do list, run major changes by user first
- Stop and ask when: uncertain how to proceed, requirements unclear,
  adding type ignores/suppressions/any types, or better approach exists
- Atomic changes: group related changes (code + tests); each passes
  type check, tests, lint before staging
- Detect project workflow (spec-first, TDD) and follow it when present
- Tests alongside implementation; cover edge cases and critical paths
- Refactor incrementally, preserve functionality, maintain test coverage
- Fact-based: don't assume framework behavior; verify or ask

## Version Control
- Don't commit unless I say so explicitly
- If commit: clear semantic messages, focused, atomic
- No auto-push any branch
- Status/diff commands fine for analysis

## Backward Compatibility
- Only for public-facing interfaces (APIs, libraries)
- Internal refactoring: unit/integration/E2E tests are confirmation gate
