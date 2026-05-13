# Adding Custom Tools to `agents`

The main extension `agents` can handle basic tasks, based on the default TYPO3 database and file structure. Since there are thousands of TYPO3 extensions that create all kind of database fields and tables, it is impossible to cover it all, or detect automatically.

> This means: You can't tell the agent to create a new entry in your FAQ database, when the agent doesn't know your extension!

But you can create an unlimited amount of custom functions that do something in your TYPO3 installation, by adding some files to another extension. This could be either a custom extension only for your tools, or even an existing extension.

### Use Cases for Custom Tools

1. **Your Extension contains custom database records**
   - Your Tool: Fetches and updates your custom records.
2. **You want to add an MCP server**
   - Your Tool: Connects to an external server to fetch knowledge and other functions

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

### Optional source metadata

Tools may also implement `Kohlercode\Agents\Tool\ToolMetadataInterface` so the settings module can show which extension provides the tool:

```php
use Kohlercode\Agents\Tool\ToolInterface;
use Kohlercode\Agents\Tool\ToolMetadataInterface;

final class FaqManagerTool implements ToolInterface, ToolMetadataInterface
{
    public function getSourceExtensionKey(): string
    {
        return 'agents_faq_tools';
    }

    // ToolInterface methods...
}
```

This metadata is optional. Tools that only implement `ToolInterface` continue to work and are shown with source `unknown`.

### Readable results and media artifacts

Tool results are not displayed as raw JSON in the chat. Return a short human-readable field so the assistant can summarize the outcome:

```php
return [
    'message' => 'FAQ creation completed.',
    'data' => $response['data'],
];
```

Supported readable fields are `message`, `summary`, and `displayText`.

If your tool creates or discovers media that should be rendered in the chat, add an `artifact` or `artifacts` field:

```php
return [
    'message' => 'Generated a preview image.',
    'artifacts' => [
        [
            'type' => 'image',
            'url' => '/fileadmin/agents/previews/example.png',
            'alt' => 'Generated landing page preview',
            'title' => 'Landing page preview',
        ],
    ],
];
```

Artifact `type` can be `image`, `video`, or `iframe`. URLs must be HTTP(S) URLs or same-site absolute paths. Iframes are additionally limited by the backend chat renderer's allowed origins.

### 3. Tag the service

In your extension’s `Configuration/Services.yaml`, register the class and tag it `agents.tool`:

```yaml
services:
  Max\AgentsFaqTools\Tools\FaqManagerTool:
    autowire: true
    tags: ['agents.tool']
```

If your extension uses `autoconfigure: true`, you can alternatively tag the class with Symfony’s `Autoconfigure` attribute (same tag name: `agents.tool`).

### 4. Activate and rebuild the container

Activate your extension (or deploy the change), then flush TYPO3 caches so dependency injection is rebuilt. After that, the new tool appears in the tool list sent to the model and may be executed when the model issues a matching tool call.

### Security note

Any tool **registered as a service** with tag `agents.tool` is eligible to run when the model requests it. That matches TYPO3’s general trust model: administrators who can install extensions control what code runs in the backend. Review third-party tools the same way you review any extension code.

# Basic Tool-Extension Setup

It only needs **4 files** for a very basic setup. The limit is your imagination.

```markdown
Classes
└─ Tools
   └─ FaqManagerTool.php
Configuration
└─ Services.yaml
ext_emconf.php
composer.json
```

## The ``FaqManagerTool.php`` Class

```php
<?php
declare(strict_types=1);

/*
* Your class implements the ToolInterface of the agents extension.
*/

namespace Max\AgentsFaqTools\Tools;
use Kohlercode\Agents\Tool\ToolInterface;

final class FaqManagerTool implements ToolInterface
{
    public function getName(): string
    {
        return 'create_faq';
    }

    public function getDescription(): string
    {
        return 'Allows you to create a new record in the FAQ database.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'storage' => [
                    'type' => 'integer',
                ],
                'question' => [
                    'type' => 'string', 
                    'minLength' => 1, 
                    'maxLength' => 255
                ],
                'answer' => [
                    'type' => 'string', 
                    'minLength' => 1, 
                    'maxLength' => 255
                ],
            ],
            'required' => ['storage','question','answer'],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, int $backendUserId): array
    { 
        /* 
        * Call your custom Repository or any other class for execution, if needed. (Class not provided in this example!)
        */
        $response = $this->faqRepository->addRecord($arguments);

        if($response['status'] === 'success'){
            return [
                'message' => 'FAQ creation completed.',
                'data' => $response['data']
            ];
        }
        else{
            return [
                'message' => 'FAQ creation failed',
            ];
        }
    }
}
```

## The ``composer.json``

```json
{
  "name": "kohlercode/agents-faq-tools",
  "type": "typo3-cms-extension",
  "version": "1.0.0",
  "description": "FAQ management tools for AI agents",
  "license": "GPL-2.0-or-later",
  "authors": [
    {
      "name": "Max Example",
      "email": "admin@example.com"
    }
  ],
  "require": {
    "php": "^8.2",
    "typo3/cms-core": "^14.3",
    "kohlercode/agents": "^1.0",
    "max/faq": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "Max\\AgentsFaqTools\\": "Classes/"
    }
  },
  "extra": {
    "typo3/cms": {
      "extension-key": "agents_faq_tools"
    }
  }
}
```

## The ``Services.yaml``

Here's the important part: By tagging your service with ``['agents.tool']``, your class will be detected by the agents extension and your tool will be exposed to the LLM.

```yaml
services:
  Max\AgentsFaqTools\Tools\FaqManagerTool:
    autowire: true
    tags: ['agents.tool']
```

## The ``ext_emconf.php``

```php
<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Agents FAQ Tools',
    'description' => 'FAQ editing tools for AI agents',
    'category' => 'module',
    'author' => 'Max Example',
    'author_email' => 'max@example.com',
    'state' => 'alpha',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.3.0-14.9.99',
            'agents' => '1.0.0-1.9.99',
            'faq' => '10.0.0-10.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
```

<hr>

&uarr; [Back to Index](index.md)