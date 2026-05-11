# TYPO3 Agents Extension

`agents` provides backend modules for AI chat and provider settings in TYPO3 14.3.

## Environment variables

Provider API keys are resolved from env var names stored in provider records:

- Google example: `AGENTS_GOOGLE_API_KEY`
- DeepSeek example: `AGENTS_DEEPSEEK_API_KEY`
- OpenRouter example: `AGENTS_OPENROUTER_API_KEY`

The value stored in `api_key_ref` must be the env var name, not the secret value.

## Security defaults

- Tool execution is allowlist-based (`system_info`, `create_page`).
- Unknown tools are denied.
- `create_page` defaults to `dryRun=true`.
- Missing provider configuration returns safe assistant messages.
