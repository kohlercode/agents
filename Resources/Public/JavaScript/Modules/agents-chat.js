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

const appendInlineMarkdown = (target, text) => {
  const source = String(text || '');
  const inlinePattern = /(`([^`]+)`|\*\*([^*]+)\*\*|\*([^*]+)\*|\[([^\]]+)\]\(([^)\s]+)\))/g;
  let lastIndex = 0;
  let match;

  while ((match = inlinePattern.exec(source)) !== null) {
    if (match.index > lastIndex) {
      target.appendChild(document.createTextNode(source.slice(lastIndex, match.index)));
    }

    if (match[2]) {
      const code = document.createElement('code');
      code.textContent = match[2];
      target.appendChild(code);
    } else if (match[3]) {
      const strong = document.createElement('strong');
      strong.textContent = match[3];
      target.appendChild(strong);
    } else if (match[4]) {
      const emphasis = document.createElement('em');
      emphasis.textContent = match[4];
      target.appendChild(emphasis);
    } else if (match[5] && match[6] && isSafeHttpUrl(match[6])) {
      const link = document.createElement('a');
      link.href = new URL(match[6], window.location.origin).toString();
      link.textContent = match[5];
      link.target = '_blank';
      link.rel = 'noopener noreferrer nofollow';
      target.appendChild(link);
    } else {
      target.appendChild(document.createTextNode(match[0]));
    }

    lastIndex = inlinePattern.lastIndex;
  }

  if (lastIndex < source.length) {
    target.appendChild(document.createTextNode(source.slice(lastIndex)));
  }
};

const isMarkdownBlockStart = (line) => (
  /^```/.test(line)
  || /^#{1,3}\s+/.test(line)
  || /^>\s?/.test(line)
  || /^(\s*)([-*]|\d+\.)\s+/.test(line)
);

const renderMarkdown = (markdown) => {
  const fragment = document.createDocumentFragment();
  const lines = String(markdown || '').replace(/\r\n?/g, '\n').split('\n');

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    if (line.trim() === '') {
      continue;
    }

    const fenceMatch = line.match(/^```\s*([a-z0-9_-]+)?\s*$/i);
    if (fenceMatch) {
      const codeLines = [];
      i++;
      while (i < lines.length && !/^```/.test(lines[i])) {
        codeLines.push(lines[i]);
        i++;
      }

      const pre = document.createElement('pre');
      const code = document.createElement('code');
      if (fenceMatch[1]) {
        code.dataset.language = fenceMatch[1].toLowerCase();
      }
      code.textContent = codeLines.join('\n');
      pre.appendChild(code);
      fragment.appendChild(pre);
      continue;
    }

    const headingMatch = line.match(/^(#{1,3})\s+(.+)$/);
    if (headingMatch) {
      const heading = document.createElement(`h${Math.min(headingMatch[1].length + 3, 6)}`);
      appendInlineMarkdown(heading, headingMatch[2]);
      fragment.appendChild(heading);
      continue;
    }

    const quoteMatch = line.match(/^>\s?(.*)$/);
    if (quoteMatch) {
      const blockquote = document.createElement('blockquote');
      const quoteLines = [quoteMatch[1]];
      while (i + 1 < lines.length && /^>\s?/.test(lines[i + 1])) {
        i++;
        quoteLines.push(lines[i].replace(/^>\s?/, ''));
      }
      appendInlineMarkdown(blockquote, quoteLines.join('\n'));
      fragment.appendChild(blockquote);
      continue;
    }

    const listMatch = line.match(/^(\s*)([-*]|\d+\.)\s+(.+)$/);
    if (listMatch) {
      const ordered = /\d+\./.test(listMatch[2]);
      const list = document.createElement(ordered ? 'ol' : 'ul');
      let currentLine = line;

      while (currentLine) {
        const itemMatch = currentLine.match(/^(\s*)([-*]|\d+\.)\s+(.+)$/);
        if (!itemMatch || /\d+\./.test(itemMatch[2]) !== ordered) {
          break;
        }

        const item = document.createElement('li');
        appendInlineMarkdown(item, itemMatch[3]);
        list.appendChild(item);

        if (i + 1 >= lines.length) {
          break;
        }
        const nextLine = lines[i + 1];
        if (nextLine.trim() === '') {
          break;
        }
        const nextItem = nextLine.match(/^(\s*)([-*]|\d+\.)\s+(.+)$/);
        if (!nextItem || /\d+\./.test(nextItem[2]) !== ordered) {
          break;
        }
        i++;
        currentLine = nextLine;
      }

      fragment.appendChild(list);
      continue;
    }

    const paragraphLines = [line.trim()];
    while (i + 1 < lines.length && lines[i + 1].trim() !== '' && !isMarkdownBlockStart(lines[i + 1])) {
      i++;
      paragraphLines.push(lines[i].trim());
    }

    const paragraph = document.createElement('p');
    appendInlineMarkdown(paragraph, paragraphLines.join(' '));
    fragment.appendChild(paragraph);
  }

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
