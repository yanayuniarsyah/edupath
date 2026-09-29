---
trigger: always_on
---

# SOFTWARE FACTORY — CORE OPERATING RULE

You are operating inside a controlled Software Factory workflow.

## 1. WORKFLOW
For every development task, follow this sequence:

PLAN → SPECIFY → ARCHITECT → IMPLEMENT → TEST → AUDIT → FIX → VERIFY → DOCUMENT

Do not skip a stage when that stage is relevant to the task.

## 2. REQUIREMENT CONTROL
- Treat the approved specification and acceptance criteria as the source of truth.
- Do not silently change requirements, business rules, data models, API contracts, or user flows.
- If a requirement is ambiguous or contradictory, stop and identify the ambiguity before implementation.
- Do not invent missing requirements.

## 3. BEFORE CODING
Before making significant code changes:
- Understand the existing project structure and relevant dependencies.
- Identify affected files, modules, APIs, database tables, and integrations.
- State the intended implementation and acceptance criteria.
- Prefer the smallest safe change that satisfies the requirement.

## 4. IMPLEMENTATION
- Follow the existing architecture and conventions unless there is a documented reason to change them.
- Avoid unnecessary rewrites.
- Keep responsibilities separated and code maintainable.
- Do not create duplicate functionality when an existing component/service can be reused.
- Never hardcode secrets, credentials, API keys, payment credentials, or private tokens.

## 5. DATABASE AND API SAFETY
- Do not modify production data or destructive database structures without explicit authorization.
- Preserve backward compatibility when practical.
- Validate inputs and handle errors explicitly.
- Maintain tenant isolation, authorization, and access control wherever applicable.
- Never expose secrets or sensitive credentials in source code, logs, responses, or documentation.

## 6. TESTING
Every implemented change must be verified.

Verification should include, when relevant:
- build/type/lint checks
- unit or integration tests
- API testing
- database/migration validation
- browser/UI testing
- regression testing

Do not claim a feature is complete merely because the code was written.

## 7. BROWSER VERIFICATION
For web applications, use available browser/devtools capabilities when appropriate.

Check:
- console errors
- network/API failures
- HTTP status codes
- authentication/authorization behavior
- responsive/mobile behavior
- critical user flows
- visible UI errors

## 8. ROOT-CAUSE DEBUGGING
When an error occurs:
1. Reproduce it.
2. Capture the actual error.
3. Identify the root cause.
4. Fix the root cause.
5. Re-run the relevant verification.
6. Check for regression.

Do not repeatedly patch symptoms without determining the underlying cause.

## 9. SOURCE OF TRUTH
Do not trust assumptions when evidence can be inspected.

Inspect the actual:
- source code
- configuration
- database schema
- API responses
- logs
- browser behavior
- Git history

Use evidence from the project before making technical conclusions.

## 10. GIT SAFETY
- Inspect the current Git state before major changes.
- Keep changes focused and traceable.
- Do not overwrite unrelated work.
- Do not delete or reset user work without explicit authorization.
- Never expose authentication tokens or credentials.

## 11. COMPLETION STANDARD
A task is DONE only when:
- the requested behavior is implemented,
- relevant tests/checks pass,
- critical errors are resolved,
- regression risk has been checked,
- and the result is documented when documentation is relevant.

If verification cannot be performed, explicitly state what could not be verified.

## 12. REPORTING
At the end of each task, report concisely:
- What changed
- Files/modules affected
- Tests/checks performed
- Result
- Remaining risks or blockers

Never report SUCCESS, READY, COMPLETE, or PRODUCTION-READY without evidence.