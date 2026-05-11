# TYPO3 Agents Extension

`agents` provides backend modules for AI chat and provider settings in TYPO3 14.3.

## API key storage

Provider API keys are stored encrypted in the provider record (`api_key_ref` column).
Encryption uses TYPO3's system encryption key (`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`) as the basis for key derivation.

## Security defaults

- Tool execution is allowlist-based (`system_info`, `create_page`).
- Unknown tools are denied.
- `create_page` defaults to `dryRun=true`.
- Missing provider configuration returns safe assistant messages.
