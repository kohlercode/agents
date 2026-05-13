# How to install the `agents` Extension

There are 2 ways to install the extension, which are explained here.


## 1. Composer Installation

Add `kohlercode/agents` to your composer configuration.

## 2. Classic Installation

Download and install the extension via the Extension Manager. Here are the 5 steps:

1. Open `https://yoursite.com/typo3/module/extensions` in your backend.
2. Update the Extension Manager's external list with `Get Extensions` and `Update now`.
3. Search for `agents`
4. Click the `Import and Install` button.
5. Clear all System Caches and Autoload Data


# How to configure the `agents` Extension

The backend module `Agents > Settings` offers a configuration interface with these settings:

## Basic Settings
  
| Setting | Value |
| -------- | -------- |
| **System Prompt** | Will be included into any prompt of any agent. |
| **Number of pinned chats** | Restricts the number of chats, a user can pin to the top of the chat list. |
| **Backend Module Position** | Defines the position of the `agent` module in the backend. |

## Provider Settings

You must at least create one Provider, by clicking on `+ New Provider`.

| Setting | Value |
| -------- | -------- |
| **Title** | Internal title of your provider. |
| **Provider** | List of currently supported providers. |
| **API Key** | Your provider's API key. The key will be encrypted and then saved to the database. **You will NOT be able to see the key once it's saved!** But you can overwrite it by entering a new key and click `save`. |
| **Model** | The model you want to work with. (like ``gemini-3.1-pro``, ``deepkseek-4.0-pro`` etc.) |
| **API base URL (optional)** | You can overwrite the API base URL here if needed, but usually the Extenension has the latest URLs already available. |

## Supported Providers

Currently, the extension supports the following LLM Providers:

- **Google (Ai Studio)**<br>
  https://aistudio.google.com/

- **DeepSeek**<br>
  https://platform.deepseek.com/

- **OpenRouter**<br>
  https://openrouter.ai/

### Do you need a different Provider?

Consider contributing to this Extension and create a new Provider Class:
[/Classes/Llm/Provider/](/Classes/Llm/Provider/)

<hr>

&uarr; [Back to Index](index.md)