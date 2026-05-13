# TYPO3 Agents

Short backend-focused extension that brings AI chat and provider configuration into TYPO3. Assistants can answer questions, draft content, and interact with the site through controlled tool calls.



## Features

- **Multiple LLM providers** — connect and switch between different providers from the backend.
- **System management via tool calls** — the AI agent can call registered tools (built-in or from other extensions) that implement a shared contract and are wired through TYPO3’s dependency injection.
- **Multiple chats** — work in parallel conversations from the backend module.
- **Content creation** — support for assistant-driven content workflows in the CMS context.
- **SEO tools** — built-in assistance oriented toward SEO-related tasks.
- **Custom tools** — other TYPO3 extensions can register additional tools; see [Adding tools from another extension](#adding-tools-from-another-extension).

---

## Adding tools from another extension

Tools are plain PHP classes that implement `Kohlercode\Agents\Tool\ToolInterface`. The Agents extension collects every service tagged with `agents.tool` from the **merged** Symfony service container, so your extension only needs to declare its tool services and flush caches.

### 1. Depend on Agents

Ensure your site loads the `agents` extension before yours (declare a dependency in your extension’s `ext_emconf.php` / Composer metadata as you usually would). If you consume the interface from Composer, require the package that ships this extension (for example `kohlercode/agents`) so `ToolInterface` autoloads in development and CI.

### 2. Implement the interface

Create a class under your extension namespace that implements `Kohlercode\Agents\Tool\ToolInterface`:

- `getName()` — stable function name exposed to the LLM (use only characters the provider accepts; prefer `snake_case`).
- `getDescription()` — short text the model uses to decide when to call the tool.
- `getInputSchema()` — JSON Schema object describing arguments (same shape as OpenAI-style function parameters).
- `execute(array $arguments, int $backendUserId)` — perform the work; return a JSON-serializable array for the assistant.

Use constructor injection for TYPO3 and domain services; services are autowired like any other extension service.

**Naming:** prefix tool names with your extension key (for example `my_shop_search_orders`) so they do not collide with core Agents tools or other extensions.

### 3. Tag the service

In your extension’s `Configuration/Services.yaml`, register the class and tag it `agents.tool`:

```yaml
services:
  Vendor\MyExtension\AgentTools\SearchOrdersTool:
    autowire: true
    tags: ['agents.tool']
```

If your extension uses `autoconfigure: true`, you can alternatively tag the class with Symfony’s `Autoconfigure` attribute (same tag name: `agents.tool`).

### 4. Activate and rebuild the container

Activate your extension (or deploy the change), then flush TYPO3 caches so dependency injection is rebuilt. After that, the new tool appears in the tool list sent to the model and may be executed when the model issues a matching tool call.

### Security note

Any tool **registered as a service** with tag `agents.tool` is eligible to run when the model requests it. That matches TYPO3’s general trust model: administrators who can install extensions control what code runs in the backend. Review third-party tools the same way you review any extension code.

---

## API key storage

Provider API keys are stored encrypted in the provider record (`api_key_ref` column). Encryption uses TYPO3's system encryption key (`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`) as the basis for key derivation.

## Security defaults

- Tool execution is limited to tools **registered** in the container with tag `agents.tool` (core Agents tools plus any your extensions add). Names not backed by a registered service are denied.
- Missing provider configuration returns safe assistant messages.

