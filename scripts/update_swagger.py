import json
from pathlib import Path

path = Path('public/swagger/openapi.json')
with path.open('r', encoding='utf-8') as f:
    data = json.load(f)

paths = data.setdefault('paths', {})

bearer = [{"bearerAuth": []}]

def ensure_path(path_key):
    if path_key not in paths:
        paths[path_key] = {}
    return paths[path_key]

def ensure_method(path_key, method, obj):
    path_item = ensure_path(path_key)
    path_item[method] = obj

# /cozinha/indicadores
ensure_method('/cozinha/indicadores', 'get', {
    'tags': ['Cozinha'],
    'summary': 'Indicadores da cozinha',
    'security': bearer,
    'parameters': [
        {
            'name': 'data',
            'in': 'query',
            'required': False,
            'schema': {'type': 'string', 'format': 'date'}
        }
    ],
    'responses': {
        '200': {
            'description': 'OK',
            'content': {
                'application/json': {
                    'schema': {
                        'type': 'object',
                        'properties': {
                            'data': {'type': 'string', 'format': 'date'},
                            'totais': {
                                'type': 'object',
                                'properties': {
                                    'pedidos_dia': {'type': 'integer'},
                                    'pedidos_preparo_dia': {'type': 'integer'},
                                    'pedidos_prontos_dia': {'type': 'integer'}
                                }
                            },
                            'pedidos_em_preparo': {'type': 'array', 'items': {'type': 'object'}},
                            'pedidos_prontos': {'type': 'array', 'items': {'type': 'object'}}
                        }
                    }
                }
            }
        }
    }
})

# /relatorios/pagamentos-dashboard
ensure_method('/relatorios/pagamentos-dashboard', 'get', {
    'tags': ['Relatorios'],
    'summary': 'Dashboard de pagamentos',
    'security': bearer,
    'parameters': [
        {'name': 'periodo', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'data', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'mes', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'ano', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'data_inicio', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'data_fim', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'status', 'in': 'query', 'required': False, 'schema': {'type': 'string'}}
    ],
    'responses': {
        '200': {
            'description': 'OK',
            'content': {
                'application/json': {
                    'schema': {
                        'type': 'object',
                        'properties': {
                            'periodo': {'type': 'string'},
                            'status': {'type': 'string', 'nullable': True},
                            'intervalo': {
                                'type': 'object',
                                'properties': {
                                    'inicio': {'type': 'string', 'format': 'date'},
                                    'fim': {'type': 'string', 'format': 'date'}
                                }
                            },
                            'total_apurado': {'type': 'number'},
                            'total_pedidos': {'type': 'integer'},
                            'ticket_medio': {'type': 'number'},
                            'total_troco': {'type': 'number'},
                            'total_taxa_entrega': {'type': 'number'},
                            'serie_diaria': {'type': 'array', 'items': {'type': 'object'}},
                            'serie_diaria_por_forma': {'type': 'array', 'items': {'type': 'object'}},
                            'totais_por_forma_pagamento': {'type': 'array', 'items': {'type': 'object'}},
                            'pedidos': {'type': 'array', 'items': {'type': 'object'}}
                        }
                    }
                }
            }
        }
    }
})

# /relatorios/pagamentos-dashboard/export
ensure_method('/relatorios/pagamentos-dashboard/export', 'get', {
    'tags': ['Relatorios'],
    'summary': 'Exportacao do dashboard de pagamentos',
    'security': bearer,
    'parameters': [
        {'name': 'periodo', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'data', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'mes', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'ano', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'data_inicio', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'data_fim', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'status', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'formato', 'in': 'query', 'required': False, 'schema': {'type': 'string'}}
    ],
    'responses': {
        '200': {
            'description': 'Arquivo CSV/Excel',
            'content': {
                'text/csv': {
                    'schema': {'type': 'string', 'format': 'binary'}
                }
            }
        }
    }
})

# /relatorios/analiticos/{codigo}
ensure_method('/relatorios/analiticos/{codigo}', 'get', {
    'tags': ['Relatorios'],
    'summary': 'Relatorios analiticos R01-R40',
    'security': bearer,
    'parameters': [
        {'name': 'codigo', 'in': 'path', 'required': True, 'schema': {'type': 'string'}},
        {'name': 'tipo_filtro', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'tipo_agrupamento', 'in': 'query', 'required': False, 'schema': {'type': 'string'}},
        {'name': 'dia_ref', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'ano_ref', 'in': 'query', 'required': False, 'schema': {'type': 'integer'}},
        {'name': 'mes_ref', 'in': 'query', 'required': False, 'schema': {'type': 'integer'}},
        {'name': 'semana_ref', 'in': 'query', 'required': False, 'schema': {'type': 'integer'}},
        {'name': 'data_inicio', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'data_fim', 'in': 'query', 'required': False, 'schema': {'type': 'string', 'format': 'date'}},
        {'name': 'X-Empresa-Id', 'in': 'header', 'required': False, 'schema': {'type': 'integer'}}
    ],
    'responses': {
        '200': {
            'description': 'OK',
            'content': {
                'application/json': {
                    'schema': {
                        'type': 'object',
                        'properties': {
                            'codigo': {'type': 'string'},
                            'parametros': {'type': 'object'},
                            'dados': {'type': 'array', 'items': {'type': 'object'}}
                        }
                    }
                }
            }
        }
    }
})

# update auth login schema to senha
login = paths.get('/auth/login', {}).get('post')
if login:
    body = login.get('requestBody', {}).get('content', {}).get('application/json', {}).get('schema', {})
    if isinstance(body, dict) and 'properties' in body:
        props = body['properties']
        if 'password' in props and 'senha' not in props:
            props['senha'] = props.pop('password')
        if 'required' in body and 'password' in body['required']:
            body['required'] = ['senha' if r == 'password' else r for r in body['required']]

path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')
print('updated', path)
