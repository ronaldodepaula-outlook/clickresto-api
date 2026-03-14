# -*- coding: utf-8 -*-
import json
from pathlib import Path

path = Path('postman/ClickResto-Completo.postman_collection.json')
with path.open('r', encoding='utf-8') as f:
    data = json.load(f)

info = data.setdefault('info', {})
info.setdefault('description', '')
if 'Documentacao padronizada' not in info['description']:
    info['description'] = (info['description'] + '\n' + 'Documentacao padronizada para testes e integracoes.').strip()


def build_description(item):
    req = item.get('request', {})
    method = req.get('method', 'GET')
    url = req.get('url', {})
    raw = url.get('raw') if isinstance(url, dict) else str(url)
    headers = req.get('header', []) or []
    auth_header = any(h.get('key', '').lower() == 'authorization' for h in headers)
    empresa_header = any(h.get('key', '').lower() == 'x-empresa-id' for h in headers)
    query = []
    if isinstance(url, dict):
        for q in url.get('query', []) or []:
            key = q.get('key')
            if key:
                query.append(key)
    body = req.get('body')
    body_info = None
    if isinstance(body, dict):
        mode = body.get('mode')
        if mode:
            body_info = mode

    lines = []
    lines.append(f"Metodo: {method}")
    if raw:
        lines.append(f"Endpoint: {raw}")
    lines.append("Auth: Bearer {{token}}" if auth_header else "Auth: Publico")
    if empresa_header:
        lines.append("Header: X-Empresa-Id obrigatorio para escopo de empresa.")
    if query:
        lines.append("Query: " + ", ".join(query))
    if body_info:
        lines.append(f"Body: {body_info}")
    lines.append("Retorno: JSON")
    return "\n".join(lines)


def walk(items):
    for item in items:
        if 'request' in item:
            item['description'] = build_description(item)
        if 'item' in item:
            walk(item['item'])

walk(data.get('item', []))

path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')
print('updated', path)
