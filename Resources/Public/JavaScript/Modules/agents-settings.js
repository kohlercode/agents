const root = document.querySelector('#agents-settings-module');

if (!root) {
  throw new Error('Agents settings module root element not found.');
}

const promptEl = document.querySelector('#agents-system-prompt');
const backendModulePositionEl = document.querySelector('#agents-backend-module-position');
const pinnedChatsLimitEl = document.querySelector('#agents-pinned-chats-limit');
const saveSettingsEl = document.querySelector('#agents-save-settings');
const newProviderEl = document.querySelector('#agents-new-provider');
const providerListEl = document.querySelector('#agents-provider-list');

const DEFAULT_PINNED_CHATS_LIMIT = 20;

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

Promise.all([loadSettings()]);
