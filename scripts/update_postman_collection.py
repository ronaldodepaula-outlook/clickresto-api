import json
from pathlib import Path

path = Path('postman/ClickResto-Completo.postman_collection.json')
with path.open('r', encoding='utf-8') as f:
    data = json.load(f)

info = data.setdefault('info', {})
desc = info.get('description', '')
append = """Colecao completa documentada para testes no Postman e referencia para integracoes.
- Use {{base_url}} e {{token}}.
- Para endpoints de empresa, envie X-Empresa-Id quando necessario.
- Consulte a documentacao Swagger em public/swagger/index.html.
"""
if append not in desc:
    info['description'] = (desc + "\n" + append).strip()

# ensure variables
vars_list = data.setdefault('variable', [])
existing = {v.get('key'): v for v in vars_list}
new_vars = {
    'data': '2026-03-13',
    'data_inicio': '2026-03-01',
    'data_fim': '2026-03-13',
    'tipo_filtro': 'periodo',
    'tipo_agrupamento': 'dia',
    'relatorio_codigo': 'r01'
}
for k, v in new_vars.items():
    if k not in existing:
        vars_list.append({'key': k, 'value': v})

# helpers

def find_folder(items, name):
    for item in items:
        if item.get('name') == name and 'item' in item:
            return item
    return None

def find_request(items, name):
    for item in items:
        if item.get('name') == name and 'request' in item:
            return item
    return None

items = data.get('item', [])

# update login body
auth = find_folder(items, 'ClickResto - Auth')
if auth:
    login = find_request(auth.get('item', []), 'Login')
    if login:
        body = login.setdefault('request', {}).setdefault('body', {})
        body['mode'] = 'raw'
        body['raw'] = '{\n  "email": "{{admin_email}}",\n  "senha": "{{admin_password}}"\n}'
        login['description'] = 'Autenticacao. Retorna token, dados do usuario, empresa, licenca e perfis.'

# add cozinha indicadores
cozinha = find_folder(items, 'ClickResto - Cozinha')
if cozinha:
    if not find_request(cozinha.get('item', []), 'Indicadores Cozinha'):
        cozinha.get('item', []).append({
            'name': 'Indicadores Cozinha',
            'request': {
                'method': 'GET',
                'header': [
                    {'key': 'Authorization', 'value': 'Bearer {{token}}'}
                ],
                'url': {
                    'raw': '{{base_url}}/cozinha/indicadores?data={{data}}',
                    'host': ['{{base_url}}'],
                    'path': ['cozinha', 'indicadores'],
                    'query': [
                        {'key': 'data', 'value': '{{data}}'}
                    ]
                }
            },
            'description': 'Indicadores da cozinha: totais do dia e listas de pedidos em preparo e prontos.'
        })

# relatorios
relatorios = find_folder(items, 'ClickResto - Relatorios')
if relatorios:
    rel_items = relatorios.get('item', [])
    if not find_request(rel_items, 'Pagamentos Dashboard'):
        rel_items.append({
            'name': 'Pagamentos Dashboard',
            'request': {
                'method': 'GET',
                'header': [
                    {'key': 'Authorization', 'value': 'Bearer {{token}}'}
                ],
                'url': {
                    'raw': '{{base_url}}/relatorios/pagamentos-dashboard?periodo={{tipo_filtro}}&data={{data}}&data_inicio={{data_inicio}}&data_fim={{data_fim}}',
                    'host': ['{{base_url}}'],
                    'path': ['relatorios', 'pagamentos-dashboard'],
                    'query': [
                        {'key': 'periodo', 'value': '{{tipo_filtro}}'},
                        {'key': 'data', 'value': '{{data}}'},
                        {'key': 'data_inicio', 'value': '{{data_inicio}}'},
                        {'key': 'data_fim', 'value': '{{data_fim}}'}
                    ]
                }
            },
            'description': 'Dashboard de pagamentos com serie diaria, totais por forma, ticket medio e pedidos detalhados.'
        })

    if not find_request(rel_items, 'Pagamentos Dashboard Export'):
        rel_items.append({
            'name': 'Pagamentos Dashboard Export',
            'request': {
                'method': 'GET',
                'header': [
                    {'key': 'Authorization', 'value': 'Bearer {{token}}'}
                ],
                'url': {
                    'raw': '{{base_url}}/relatorios/pagamentos-dashboard/export?periodo={{tipo_filtro}}&data={{data}}&data_inicio={{data_inicio}}&data_fim={{data_fim}}&formato=csv',
                    'host': ['{{base_url}}'],
                    'path': ['relatorios', 'pagamentos-dashboard', 'export'],
                    'query': [
                        {'key': 'periodo', 'value': '{{tipo_filtro}}'},
                        {'key': 'data', 'value': '{{data}}'},
                        {'key': 'data_inicio', 'value': '{{data_inicio}}'},
                        {'key': 'data_fim', 'value': '{{data_fim}}'},
                        {'key': 'formato', 'value': 'csv'}
                    ]
                }
            },
            'description': 'Exportacao CSV/Excel do dashboard de pagamentos. Use formato=csv|excel.'
        })

    # Analiticos
    analiticos = find_folder(rel_items, 'Relatorios Analiticos')
    if not analiticos:
        analiticos = {'name': 'Relatorios Analiticos', 'item': [], 'description': 'Relatorios R01-R40 com filtros iguais ao script analitico.'}
        rel_items.append(analiticos)

    if not find_request(analiticos.get('item', []), 'R01'):
        for i in range(1, 41):
            codigo = f'r{i:02d}'
            analiticos['item'].append({
                'name': f'R{i:02d}',
                'request': {
                    'method': 'GET',
                    'header': [
                        {'key': 'Authorization', 'value': 'Bearer {{token}}'},
                        {'key': 'X-Empresa-Id', 'value': '{{empresa_id}}'}
                    ],
                    'url': {
                        'raw': '{{base_url}}/relatorios/analiticos/' + codigo + '?tipo_filtro={{tipo_filtro}}&tipo_agrupamento={{tipo_agrupamento}}&dia_ref={{data}}&data_inicio={{data_inicio}}&data_fim={{data_fim}}',
                        'host': ['{{base_url}}'],
                        'path': ['relatorios', 'analiticos', codigo],
                        'query': [
                            {'key': 'tipo_filtro', 'value': '{{tipo_filtro}}'},
                            {'key': 'tipo_agrupamento', 'value': '{{tipo_agrupamento}}'},
                            {'key': 'dia_ref', 'value': '{{data}}'},
                            {'key': 'data_inicio', 'value': '{{data_inicio}}'},
                            {'key': 'data_fim', 'value': '{{data_fim}}'}
                        ]
                    }
                },
                'description': 'Executa o relatorio analitico ' + codigo.upper() + ' com os filtros do script.'
            })

path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')
print('updated', path)
