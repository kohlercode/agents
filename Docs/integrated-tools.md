# Integrated Tools

The `agents` extension ships with a set of integrated tools. These tools are registered through the TYPO3 service container and are available to the assistant when the model decides that a tool call is useful for the current task.

Tools can read TYPO3 data, create or translate records, fetch external information, or return system context. The assistant receives the tool name, description, and input schema and can then call the matching tool with structured arguments.

## Available Integrated Tools

| Tool | Description | Main Use |
| -------- | -------- | -------- |
| `system_info` | Returns TYPO3 system and site information. | Helps the assistant understand the TYPO3 version, configured sites, site roots, and available site languages. |
| `get_page_by_uid` | Returns a single TYPO3 page by its uid. | Reads page data for inspection, validation, or follow-up tasks. |
| `page_tree_search` | Searches the page tree below a given root page uid. | Finds pages by search term and returns the filtered page tree structure. |
| `create_page` | Creates a basic TYPO3 page below a parent page id. | Adds a new page record with title and optional SEO/Open Graph fields. |
| `get_page_translations` | Returns all existing translations of a TYPO3 page. | Checks which language versions already exist for a page before creating new translations. |
| `translate_page` | Translates an existing TYPO3 page into a target language. | Validates site language availability, checks existing translations, and can localize content elements as well. |
| `rss_feed_reader` | Reads an RSS feed from a given URL and returns the latest items. | Lets the assistant summarize or use information from an external RSS feed. |
| `search_system_log` | Searches the system log for a specific event. | Provides a simple hook for system-log related assistant workflows. |

## TYPO3 System Tools

### `system_info`

Returns general TYPO3 and site information.

| Argument | Required | Description |
| -------- | -------- | -------- |
| none | no | This tool does not require input arguments. |

The result includes the backend user id, TYPO3 version, number of configured sites, site identifiers, base URLs, root page ids, language titles, and site settings.

### `search_system_log`

Searches the TYPO3 system log for a specific term.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `term` | yes | Search term, between 1 and 255 characters. |

The current implementation returns a confirmation message for the requested term and can be extended to return actual log records.

## Page Tools

### `get_page_by_uid`

Returns a single page record by uid.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `uid` | yes | UID of the page record. |
| `include_deleted` | no | When `true`, deleted pages may be included in the lookup. |

Use this tool when the assistant needs to inspect a known page record before deciding what to do next.

### `page_tree_search`

Searches the page tree below a root page uid for pages matching a search term.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `site_uid` | yes | Root page id / mount point page uid used as the search root. |
| `search_term` | yes | Search term, between 1 and 255 characters. |

Use this tool when the assistant knows the root page of a site but does not yet know the target page uid.

### `create_page`

Creates a new page below a parent page.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `parentPid` | yes | UID of the parent page. |
| `title` | yes | Page title. |
| `seo_title` | no | SEO title. |
| `description` | no | Meta description. |
| `og_title` | no | Open Graph title. |
| `og_description` | no | Open Graph description. |
| `doktype` | no | TYPO3 page type. Defaults to `1`. |

The tool creates the page through TYPO3's `DataHandler` and returns the newly created page uid.

## Translation Tools

### `get_page_translations`

Returns all existing translations of a page.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `page_uid` | yes | UID of a page record. This can be either the default-language page or an existing translated page. |

If the input page is already a translation, the tool resolves the default-language parent page automatically and returns translations for that canonical page.

### `translate_page`

Translates an existing default-language page into a target language.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `page_uid` | yes | UID of the default-language page to translate. |
| `target_language_uid` | yes | Target `sys_language_uid`. The language must be configured and enabled for the page's site. |
| `cascade_content_elements` | no | When `true`, the tool also localizes default-language `tt_content` records on the page. Defaults to `true`. |
| `dry_run` | no | When `true`, only validation is performed and no records are written. Defaults to `false`. |

Before writing anything, the tool checks that the page exists, belongs to a site, uses the default language, the target language is configured and enabled, and that no page translation already exists for the target language.

If `dry_run` is enabled, the tool returns a preflight report without creating any records. If translation is executed, it returns the translated page uid and the result of content element localization.

## External Content Tools

### `rss_feed_reader`

Reads an RSS feed from a URL.

| Argument | Required | Description |
| -------- | -------- | -------- |
| `url` | yes | Valid RSS feed URL. |

The tool fetches the feed and returns the latest feed items for the assistant to summarize or use in a response.

## Extending the Tool List

Integrated tools are only the default set that ships with this extension. Additional tools can be added from your own extension by implementing `ToolInterface` and registering the service with the `agents.tool` tag.

See [Adding Custom Tools](custom-tools.md) for details.

<hr>

&uarr; [Back to Index](index.md)
