const root = document.querySelector('#agents-settings-module');

if (!root) {
  throw new Error('Agents settings module root element not found.');
}

const promptEl = document.querySelector('#agents-system-prompt');
const saveSettingsEl = document.querySelector('#agents-save-settings');
const newProviderEl = document.querySelector('#agents-new-provider');
const providerListEl = document.querySelector('#agents-provider-list');

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

const renderProviders = (providers) => {
  providerListEl.innerHTML = '';
  providers.forEach((provider) => {
    const card = document.createElement('div');
    card.className = 'border rounded p-2 mb-2';
    const isActive = Number(provider.uid) === Number(activeProviderUid);
    card.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <strong>${provider.title}</strong>
          <div class="text-muted">${provider.provider_key} / ${provider.model_identifier || '-'}</div>
        </div>
        <button class="btn btn-sm ${isActive ? 'btn-success' : 'btn-outline-primary'}">
          ${isActive ? 'Active' : 'Activate'}
        </button>
      </div>
    `;
    card.querySelector('button').addEventListener('click', async () => {
      await apiPost(root.dataset.routeActivateProvider, { providerUid: Number(provider.uid) });
      await loadSettings();
      await loadProviders();
    });
    providerListEl.appendChild(card);
  });
};

const loadSettings = async () => {
  const result = await apiGet(root.dataset.routeGetSettings);
  if (!result.success) {
    return;
  }
  const settings = result.data.settings || {};
  promptEl.value = settings.system_prompt || '';
  activeProviderUid = Number(settings.active_provider_uid || 0);
};

const loadProviders = async () => {
  const result = await apiGet(root.dataset.routeListProviders);
  if (!result.success) {
    return;
  }
  renderProviders(result.data.providers || []);
};

saveSettingsEl.addEventListener('click', async () => {
  await apiPost(root.dataset.routeSaveSettings, {
    systemPrompt: promptEl.value,
    activeProviderUid,
  });
});

newProviderEl.addEventListener('click', async () => {
  const providerKey = window.prompt('Provider key (google|deepseek|openrouter)', 'openrouter');
  if (!providerKey) {
    return;
  }
  const title = window.prompt('Provider title', `${providerKey} provider`);
  if (!title) {
    return;
  }
  const modelIdentifier = window.prompt('Default model identifier', '');
  const apiBaseUrl = window.prompt('API base URL (optional)', '');
  const apiKey = window.prompt('API key (stored encrypted)', '');
  await apiPost(root.dataset.routeSaveProvider, {
    title,
    providerKey,
    modelIdentifier,
    apiBaseUrl,
    apiKey,
  });
  await loadProviders();
});

Promise.all([loadSettings(), loadProviders()]);
