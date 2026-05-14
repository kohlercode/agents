const root = document.querySelector('#agents-chat-module');

if (!root) {
  throw new Error('Agents chat module root element not found.');
}

document.getElementById('save-bookmark').addEventListener('click', function() {
  console.log(TYPO3.settings.ajaxUrls);
  const bookmarkAjaxUrl = TYPO3.settings.ajaxUrls.bookmark_create;
  fetch(bookmarkAjaxUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      title: 'Agents Chat',
      url: window.location.href,
    }),
  })
});

const listEl = document.querySelector('#agents-chat-list');
const messagesEl = document.querySelector('#agents-message-list');
const formEl = document.querySelector('#agents-chat-form');
const inputEl = document.querySelector('#agents-chat-input');
const newChatEl = document.querySelector('#agents-new-chat');
const threadTitleEl = document.querySelector('#agents-chat-thread-title');
const providerSelectEl = document.querySelector('#agents-chat-provider');

let activeChatId = 0;
let chats = [];

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

const getActiveChat = () => chats.find((chat) => Number(chat.uid) === Number(activeChatId));

const hasProviderOptions = () => {
  if (!providerSelectEl) {
    return false;
  }
  return Array.from(providerSelectEl.options).some((option) => option.value !== '');
};

const updateProviderSelect = () => {
  if (!providerSelectEl) {
    return;
  }

  const active = getActiveChat();
  providerSelectEl.disabled = !active || !hasProviderOptions();

  if (!active) {
    providerSelectEl.value = '';
    return;
  }

  const providerUid = String(Number(active.provider_uid || 0));
  const hasMatchingOption = providerUid !== '0'
    && Array.from(providerSelectEl.options).some((option) => option.value === providerUid);
  providerSelectEl.value = hasMatchingOption ? providerUid : '';
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

const renderChats = (nextChats) => {
  chats = Array.isArray(nextChats) ? nextChats : [];
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
  updateProviderSelect();
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

const setChatProvider = async (providerUid) => {
  const chatUid = Number(activeChatId);
  if (!chatUid || !providerUid) {
    updateProviderSelect();
    return;
  }

  if (providerSelectEl) {
    providerSelectEl.disabled = true;
  }

  const result = await apiPost(root.dataset.routeSetProvider, { chatUid, providerUid });
  if (!result.success) {
    const message = result.error?.message || 'Could not update chat provider.';
    window.alert(message);
    updateProviderSelect();
    return;
  }

  chats = chats.map((chat) => (
    Number(chat.uid) === chatUid
      ? {
          ...chat,
          provider_uid: result.data.providerUid,
          model_identifier: result.data.modelIdentifier || chat.model_identifier || '',
        }
      : chat
  ));
  renderChats(chats);
};

const isSafeHttpUrl = (url) => {
  try {
    const parsed = new URL(url, window.location.origin);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
};

const getAllowedIframeOrigins = () => {
  const configuredOrigins = (root.dataset.allowedIframeOrigins || '')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean);

  return new Set([window.location.origin, ...configuredOrigins]);
};

const markdownParser = window.markdownit?.({
  html: false,
  linkify: true,
  typographer: true,
  breaks: true,
});

if (markdownParser) {
  const defaultLinkOpen = markdownParser.renderer.rules.link_open
    || ((tokens, index, options, env, self) => self.renderToken(tokens, index, options));

  markdownParser.renderer.rules.link_open = (tokens, index, options, env, self) => {
    const token = tokens[index];
    const hrefIndex = token.attrIndex('href');

    if (hrefIndex >= 0 && isSafeHttpUrl(token.attrs[hrefIndex][1])) {
      token.attrs[hrefIndex][1] = new URL(token.attrs[hrefIndex][1], window.location.origin).toString();
      token.attrSet('target', '_blank');
      token.attrSet('rel', 'noopener noreferrer nofollow');
    } else if (hrefIndex >= 0) {
      token.attrs.splice(hrefIndex, 1);
    }

    return defaultLinkOpen(tokens, index, options, env, self);
  };
}

const decorateRenderedMarkdown = (container) => {
  container.querySelectorAll('a[href]').forEach((link) => {
    if (!isSafeHttpUrl(link.href)) {
      link.removeAttribute('href');
      return;
    }

    link.href = new URL(link.href, window.location.origin).toString();
    link.target = '_blank';
    link.rel = 'noopener noreferrer nofollow';
  });

  container.querySelectorAll('table').forEach((table) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'agents-chat-message__table-scroll';
    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  });
};

const renderMarkdown = (markdown) => {
  const fragment = document.createDocumentFragment();
  const source = String(markdown || '');

  if (!markdownParser || !window.DOMPurify) {
    const paragraph = document.createElement('p');
    paragraph.textContent = source;
    fragment.appendChild(paragraph);
    return fragment;
  }

  const template = document.createElement('template');
  template.innerHTML = window.DOMPurify.sanitize(markdownParser.render(source), {
    ALLOWED_TAGS: [
      'a', 'blockquote', 'br', 'code', 'del', 'em', 'h1', 'h2', 'h3',
      'h4', 'h5', 'h6', 'hr', 'li', 'ol', 'p', 'pre', 's', 'strong',
      'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul',
    ],
    ALLOWED_ATTR: ['class', 'colspan', 'href', 'rel', 'rowspan', 'scope', 'target', 'title'],
  });
  decorateRenderedMarkdown(template.content);
  fragment.appendChild(template.content);

  return fragment;
};

const createArtifactElement = (artifact) => {
  if (!artifact || typeof artifact !== 'object') {
    return null;
  }

  const type = String(artifact.type || '').toLowerCase();
  const url = String(artifact.url || '').trim();
  if (!['image', 'video', 'iframe'].includes(type) || !isSafeHttpUrl(url)) {
    return null;
  }

  const resolvedUrl = new URL(url, window.location.origin);
  const wrapper = document.createElement('figure');
  wrapper.className = `agents-chat-artifact agents-chat-artifact--${type}`;

  if (type === 'image') {
    const image = document.createElement('img');
    image.src = resolvedUrl.toString();
    image.alt = String(artifact.alt || artifact.title || '');
    image.loading = 'lazy';
    wrapper.appendChild(image);
  } else if (type === 'video') {
    const video = document.createElement('video');
    video.controls = true;
    video.preload = 'metadata';
    const source = document.createElement('source');
    source.src = resolvedUrl.toString();
    if (artifact.mimeType) {
      source.type = String(artifact.mimeType);
    }
    video.appendChild(source);
    wrapper.appendChild(video);
  } else if (type === 'iframe') {
    if (!getAllowedIframeOrigins().has(resolvedUrl.origin)) {
      const blocked = document.createElement('figcaption');
      blocked.textContent = `Blocked iframe from untrusted origin: ${resolvedUrl.origin}`;
      wrapper.appendChild(blocked);
      return wrapper;
    }

    const iframe = document.createElement('iframe');
    iframe.src = resolvedUrl.toString();
    iframe.title = String(artifact.title || 'Embedded content');
    iframe.loading = 'lazy';
    iframe.referrerPolicy = 'no-referrer';
    iframe.sandbox = 'allow-forms allow-popups allow-scripts allow-same-origin';
    wrapper.appendChild(iframe);
  }

  if (artifact.title) {
    const caption = document.createElement('figcaption');
    caption.textContent = String(artifact.title);
    wrapper.appendChild(caption);
  }

  return wrapper;
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
    body.appendChild(renderMarkdown(message.content || ''));

    bubble.append(meta, body);
    if (Array.isArray(message.artifacts) && message.artifacts.length > 0) {
      const artifacts = document.createElement('div');
      artifacts.className = 'agents-chat-message__artifacts';
      message.artifacts.forEach((artifact) => {
        const element = createArtifactElement(artifact);
        if (element) {
          artifacts.appendChild(element);
        }
      });
      if (artifacts.childElementCount > 0) {
        bubble.appendChild(artifacts);
      }
    }
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

if (providerSelectEl) {
  providerSelectEl.addEventListener('change', async () => {
    await setChatProvider(Number(providerSelectEl.value));
  });
}

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
