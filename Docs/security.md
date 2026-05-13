## API key storage

Provider API keys are stored encrypted in the provider record (`api_key_ref` column). Encryption uses TYPO3's system encryption key (`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`) as the basis for key derivation.

## Security defaults

- Tool execution is limited to tools **registered** in the container with tag `agents.tool` (core Agents tools plus any your extensions add). Names not backed by a registered service are denied.
- Missing provider configuration returns safe assistant messages.

<hr>

&uarr; [Back to Index](index.md)