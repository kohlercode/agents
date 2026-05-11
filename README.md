# TYPO3 Agents

Short backend-focused extension that brings AI chat and provider configuration into TYPO3. Assistants can answer questions, draft content, and interact with the site through controlled tool calls.

## Features

- **Multiple LLM providers** — connect and switch between different providers from the backend.
- **System management via tool calls** — the AI agent can perform allowlisted operations on the system (for example inspecting context or creating pages) through defined tools.
- **Multiple chats** — work in parallel conversations from the backend module.
- **Content creation** — support for assistant-driven content workflows in the CMS context.
- **SEO tools** — built-in assistance oriented toward SEO-related tasks.
- **Custom tools (coming soon)** — extend capabilities through child extensions that register additional tools for `agents`.

## Todo: developer ecosystem

- Build an ecosystem so other developers can ship **child extensions** that plug into `agents` and contribute **more tools** (shared conventions, registration API, and documentation).

---

## API key storage

Provider API keys are stored encrypted in the provider record (`api_key_ref` column). Encryption uses TYPO3's system encryption key (`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`) as the basis for key derivation.

## Security defaults

- Tool execution is allowlist-based (`system_info`, `create_page`).
- Unknown tools are denied.
- `create_page` defaults to `dryRun=true`.
- Missing provider configuration returns safe assistant messages.
