# Agents Extension Verification Checklist

## 1) Backend module availability
1. Install extension `agents`.
2. Flush all caches.
3. Open TYPO3 backend and verify two modules exist under Integrations:
   - Agents Chat
   - Agents Settings

## 2) Settings and provider flow
1. Open Agents Settings.
2. Create three providers with keys:
   - `google`
   - `deepseek`
   - `openrouter`
3. Save a system prompt.
4. Activate one provider.
5. Reload the module and verify values are persisted.

## 3) Chat lifecycle
1. Open Agents Chat.
2. Create a new chat.
3. Send a message.
4. Verify:
   - user message is persisted in `tx_agents_domain_model_message`
   - assistant response is persisted in `tx_agents_domain_model_message`
   - chat is listed in `tx_agents_domain_model_chat`

## 4) Failure-path verification
1. Remove API key environment variable referenced by active provider.
2. Send a chat message.
3. Verify the request does not crash and assistant message indicates missing API key.

## 5) Tool policy verification
1. Confirm `system_info` and `create_page` tools are allowed.
2. Trigger a response containing any non-allowlisted tool name.
3. Verify tool execution is rejected with a policy error and request remains stable.

## 6) Create page tool dry-run and execution
1. Trigger `create_page` with `dryRun=true`.
2. Verify no page is created and response contains planned action details.
3. Trigger `create_page` with `dryRun=false`, valid `parentPid`, and title.
4. Verify page is created once and result returns `createdPageUid`.
