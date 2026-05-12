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

const createChatRow = (chat) => {
  const isActive = Number(chat.uid) === Number(activeChatId);
  const isPinned = Number(chat.pinned) === 1;

  const row = document.createElement('div');
  row.className = `list-group-item agents-chat-thread-item d-flex align-items-center gap-2 ${isActive ? 'active' : ''} ${isPinned ? 'is-pinned' : ''}`;
  row.dataset.chatUid = String(chat.uid);

  const select = document.createElement('button');
  select.type = 'button';
  select.className = 'agents-chat-thread-item__select text-start flex-grow-1 p-0';
  select.textContent = chat.title || `Chat #${chat.uid}`;
  select.addEventListener('click', () => {
    activeChatId = Number(chat.uid);
    void loadMessages();
    void loadChats();
  });

  const pin = document.createElement('button');
  pin.type = 'button';
  pin.className = `agents-chat-thread-item__pin btn btn-sm ${isPinned ? 'is-active' : ''}`;
  pin.title = isPinned ? (root.dataset.labelUnpin || 'Unpin chat') : (root.dataset.labelPin || 'Pin chat');
  pin.setAttribute('aria-label', pin.title);
  pin.setAttribute('aria-pressed', String(isPinned));
  pin.innerHTML = '<span class="agents-chat-thread-item__pin-icon" aria-hidden="true">&#128204;</span>';
  pin.addEventListener('click', async (event) => {
    event.stopPropagation();
    event.preventDefault();
    await togglePin(Number(chat.uid), !isPinned);
  });

  row.append(select, pin);
  return row;
};

const renderChats = (chats) => {
  listEl.innerHTML = '';

  const pinned = chats.filter((c) => Number(c.pinned) === 1);
  const others = chats.filter((c) => Number(c.pinned) !== 1);

  if (pinned.length > 0) {
    const heading = document.createElement('div');
    heading.className = 'agents-chat-thread-list__heading';
    heading.textContent = root.dataset.labelPinnedHeading || 'Pinned';
    listEl.appendChild(heading);

    pinned.forEach((chat) => listEl.appendChild(createChatRow(chat)));

    const separator = document.createElement('div');
    separator.className = 'agents-chat-thread-list__separator';
    listEl.appendChild(separator);
  }

  others.forEach((chat) => listEl.appendChild(createChatRow(chat)));

  updateThreadTitle(chats);
};

const togglePin = async (chatUid, pinned) => {
  const result = await apiPost(root.dataset.routeSetPinned, { chatUid, pinned });
  if (!result.success) {
    const message = result.error?.message || 'Could not update pinned state.';
    window.alert(message);
    return;
  }
  await loadChats();
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
