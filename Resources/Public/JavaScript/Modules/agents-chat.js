const root = document.querySelector('#agents-chat-module');

if (!root) {
  throw new Error('Agents chat module root element not found.');
}

const listEl = document.querySelector('#agents-chat-list');
const messagesEl = document.querySelector('#agents-message-list');
const formEl = document.querySelector('#agents-chat-form');
const inputEl = document.querySelector('#agents-chat-input');
const newChatEl = document.querySelector('#agents-new-chat');

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

const renderChats = (chats) => {
  listEl.innerHTML = '';
  chats.forEach((chat) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `list-group-item list-group-item-action ${Number(chat.uid) === Number(activeChatId) ? 'active' : ''}`;
    button.textContent = chat.title || `Chat #${chat.uid}`;
    button.addEventListener('click', () => {
      activeChatId = Number(chat.uid);
      loadMessages();
      renderChats(chats);
    });
    listEl.appendChild(button);
  });
};

const renderMessages = (messages) => {
  messagesEl.innerHTML = '';
  messages.forEach((message) => {
    const row = document.createElement('div');
    row.className = 'mb-2';
    row.innerHTML = `<strong>${message.role}:</strong> ${message.content || ''}`;
    messagesEl.appendChild(row);
  });
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
