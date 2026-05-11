const root = document.querySelector('#agents-chat-module');

if (!root) {
  throw new Error('Agents chat module root element not found.');
}

const listEl = document.querySelector('#agents-chat-list');
const messagesEl = document.querySelector('#agents-message-list');
const formEl = document.querySelector('#agents-chat-form');
const inputEl = document.querySelector('#agents-chat-input');
const newChatEl = document.querySelector('#agents-new-chat');
const threadTitleEl = document.querySelector('#agents-chat-thread-title');

let activeChatId = 0;

const getAjaxUrl = (routeName) => {
  const allUrls = window.TYPO3?.settings?.ajaxUrls || {};
  return allUrls[routeName] || '';
};

const buildUrlWithQuery = (url, query = {}) => {
  const finalUrl = new URL(url, window.location.origin);
  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      finalUrl.searchParams.set(key, String(value));
    }
  });
  return finalUrl.toString();
};

const apiGet = async (routeName, query = {}) => {
  const url = buildUrlWithQuery(getAjaxUrl(routeName), query);
  const response = await fetch(url, {
    credentials: 'same-origin',
  });
  return response.json();
};

const apiPost = async (routeName, payload = {}) => {
  const url = getAjaxUrl(routeName);
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return response.json();
};

const updateThreadTitle = (chats) => {
  if (!threadTitleEl) {
    return;
  }
  const selectLabel = root.dataset.labelSelectChat || '';
  const active = chats.find((c) => Number(c.uid) === Number(activeChatId));
  if (!active) {
    threadTitleEl.textContent = selectLabel;
    return;
  }
  threadTitleEl.textContent = active.title || `Chat #${active.uid}`;
};

const renderChats = (chats) => {
  listEl.innerHTML = '';
  chats.forEach((chat) => {
    const button = document.createElement('button');
    button.type = 'button';
    const isActive = Number(chat.uid) === Number(activeChatId);
    button.className = `list-group-item list-group-item-action agents-chat-thread-item ${isActive ? 'active' : ''}`;
    button.textContent = chat.title || `Chat #${chat.uid}`;
    button.addEventListener('click', () => {
      activeChatId = Number(chat.uid);
      loadMessages();
      renderChats(chats);
    });
    listEl.appendChild(button);
  });
  updateThreadTitle(chats);
};

const renderMessages = (messages) => {
  messagesEl.innerHTML = '';
  const fragment = document.createDocumentFragment();
  messages.forEach((message) => {
    const rawRole = String(message.role || 'assistant').toLowerCase();
    const safeRole = /^[a-z0-9_-]+$/.test(rawRole) ? rawRole : 'assistant';
    const wrap = document.createElement('div');
    wrap.className = `agents-chat-message agents-chat-message--${safeRole}`;

    const bubble = document.createElement('div');
    bubble.className = 'agents-chat-message__bubble';

    const meta = document.createElement('div');
    meta.className = 'agents-chat-message__role';
    meta.textContent = rawRole;

    const body = document.createElement('div');
    body.className = 'agents-chat-message__text';
    body.textContent = message.content || '';

    bubble.append(meta, body);
    wrap.appendChild(bubble);
    fragment.appendChild(wrap);
  });
  messagesEl.appendChild(fragment);
  messagesEl.scrollTop = messagesEl.scrollHeight;
};

const loadChats = async () => {
  const result = await apiGet(root.dataset.routeListChats);
  if (!result.success) {
    return;
  }
  const chats = result.data.chats || [];
  if (!activeChatId && chats.length > 0) {
    activeChatId = Number(chats[0].uid);
  }
  renderChats(chats);
  if (activeChatId) {
    await loadMessages();
  } else {
    renderMessages([]);
  }
};

const loadMessages = async () => {
  if (!activeChatId) {
    return;
  }
  const result = await apiGet(root.dataset.routeListMessages, { chatUid: activeChatId });
  if (result.success) {
    renderMessages(result.data.messages || []);
  }
};

newChatEl.addEventListener('click', async () => {
  const title = window.prompt('Chat title', 'New chat');
  if (!title) {
    return;
  }
  const result = await apiPost(root.dataset.routeCreateChat, { title });
  if (!result.success) {
    return;
  }
  activeChatId = Number(result.data.chatUid);
  await loadChats();
});

formEl.addEventListener('submit', async (event) => {
  event.preventDefault();
  const chatUid = Number(activeChatId);
  if (!chatUid) {
    return;
  }

  const message = inputEl.value.trim();
  if (!message) {
    return;
  }
  inputEl.value = '';
  await apiPost(root.dataset.routeSendMessage, { chatUid, message });
  await loadMessages();
});

void loadChats();
