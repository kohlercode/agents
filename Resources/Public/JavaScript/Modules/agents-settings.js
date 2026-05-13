const root = document.querySelector('#agents-settings-module');

if (!root) {
  throw new Error('Agents settings module root element not found.');
}

const promptEl = document.querySelector('#agents-system-prompt');
const backendModulePositionEl = document.querySelector('#agents-backend-module-position');
const pinnedChatsLimitEl = document.querySelector('#agents-pinned-chats-limit');
const saveSettingsEl = document.querySelector('#agents-save-settings');
const toolsListEl = document.querySelector('#agents-tools-list');

const DEFAULT_PINNED_CHATS_LIMIT = 20;

/** @type {Record<string, string>} */
const toolsLabels = (() => {
  try {
    return JSON.parse(root.dataset.toolsLabels || '{}');
  } catch {
    return {};
  }
})();

let activeProviderUid = 0;

const getAjaxUrl = (routeName) => {
  const allUrls = window.TYPO3?.settings?.ajaxUrls || {};
  return allUrls[routeName] || '';
};

const apiGet = async (routeName) => {
  const response = await fetch(getAjaxUrl(routeName), {
    credentials: 'same-origin',
  });
  return response.json();
};

const apiPost = async (routeName, payload) => {
  const response = await fetch(getAjaxUrl(routeName), {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return response.json();
};

const escapeHtml = (value) => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

/**
 * @param {Array<{ name: string, description: string, parameters?: object }>} tools
 */
const renderToolsList = (tools) => {
  if (!toolsListEl) {
    return;
  }
  if (!Array.isArray(tools) || tools.length === 0) {
    toolsListEl.innerHTML = `<p class="text-muted mb-0">${escapeHtml(toolsLabels.empty || 'No tools are registered.')}</p>`;
    return;
  }

  const items = tools.map((tool) => {
    const name = escapeHtml(tool.name || '');
    const description = escapeHtml(tool.description || '');
    const paramsJson = escapeHtml(JSON.stringify(tool.parameters ?? {}, null, 2));
    const paramsLabel = escapeHtml(toolsLabels.parameters || 'Parameters (JSON schema)');

    return `
      <div class="list-group-item list-group-item-action flex-column align-items-start py-3">
        <div class="d-flex w-100 justify-content-between align-items-start gap-2 mb-1">
          <code class="mb-0 h5 fw-bold">${name}</code>
        </div>
        <p class="mb-2 text-body-secondary">${description}</p>
        <details>
          <summary class="text-muted user-select-none">${paramsLabel}</summary>
          <pre class="mt-2 mb-0 p-2 bg-body-secondary rounded text-break">${paramsJson}</pre>
        </details>
      </div>
    `;
  });

  toolsListEl.innerHTML = `<div class="list-group list-group-flush border rounded">${items.join('')}</div>`;
};

const loadSettings = async () => {
  const result = await apiGet(root.dataset.routeGetSettings);
  if (!result.success) {
    return;
  }
  const settings = result.data.settings || {};
  promptEl.value = settings.system_prompt || '';
  backendModulePositionEl.value = settings.backend_module_position || 'after:media';
  pinnedChatsLimitEl.value = String(Number(settings.pinned_chats_limit) || DEFAULT_PINNED_CHATS_LIMIT);
  activeProviderUid = Number(settings.active_provider_uid || 0);
  renderToolsList(result.data.tools || []);
};

saveSettingsEl.addEventListener('click', async () => {
  const parsedLimit = parseInt(pinnedChatsLimitEl.value, 10);
  const pinnedChatsLimit = Number.isFinite(parsedLimit) && parsedLimit > 0
    ? Math.min(parsedLimit, 999)
    : DEFAULT_PINNED_CHATS_LIMIT;

  await apiPost(root.dataset.routeSaveSettings, {
    systemPrompt: promptEl.value,
    activeProviderUid,
    backendModulePosition: backendModulePositionEl.value,
    pinnedChatsLimit,
  });
  pinnedChatsLimitEl.value = String(pinnedChatsLimit);
});

void loadSettings();
