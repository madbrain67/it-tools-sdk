/** fetch factice : enregistre les appels et rejoue des reponses en file (JSON 200 par defaut). */
export function fakeFetch(...queue) {
  const calls = [];
  const impl = async (url, init) => {
    calls.push({ url: String(url), method: init.method, headers: init.headers, body: init.body, redirect: init.redirect });
    const next = queue.shift();
    if (next instanceof Error) throw next;
    return next ?? json({ type: 'success' });
  };
  impl.calls = calls;
  return impl;
}

export function json(data, status = 200) {
  return new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json' } });
}

export function bytes(content, status = 200, type = 'application/octet-stream') {
  return new Response(content, { status, headers: { 'Content-Type': type } });
}

export function formEntries(call) {
  return [...call.body.entries()].map(([name, value]) => (typeof value === 'string' ? [name, value] : [name, { filename: value.name, type: value.type, size: value.size }]));
}

export function formObject(call) {
  return Object.fromEntries(formEntries(call));
}
