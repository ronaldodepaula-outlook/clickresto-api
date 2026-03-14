import json
from pathlib import Path

path = Path('public/swagger/openapi.json')
with path.open('r', encoding='utf-8') as f:
    data = json.load(f)

paths = data.get('paths', {})

def infer_example(schema):
    if not isinstance(schema, dict):
        return {}
    t = schema.get('type')
    if t == 'array':
        return []
    if t == 'object':
        # basic example using properties if present
        props = schema.get('properties', {})
        example = {}
        for k, v in props.items():
            if isinstance(v, dict):
                vt = v.get('type')
                if vt == 'string':
                    example[k] = v.get('example', '')
                elif vt == 'integer':
                    example[k] = v.get('example', 0)
                elif vt == 'number':
                    example[k] = v.get('example', 0)
                elif vt == 'boolean':
                    example[k] = v.get('example', False)
                elif vt == 'array':
                    example[k] = []
                elif vt == 'object':
                    example[k] = {}
                else:
                    example[k] = v.get('example', None)
            else:
                example[k] = None
        return example
    return {}

for path_key, path_item in paths.items():
    for method, op in path_item.items():
        if method.lower() not in ['get','post','put','patch','delete']:
            continue
        responses = op.setdefault('responses', {})
        if not responses:
            responses['200'] = {'description': 'OK'}
        # choose primary response
        code = '200' if '200' in responses else list(responses.keys())[0]
        resp = responses.setdefault(code, {'description': 'OK'})
        content = resp.setdefault('content', {})
        # prefer application/json
        app_json = content.setdefault('application/json', {})
        schema = app_json.get('schema')
        if schema is None:
            schema = {'type': 'object'}
            app_json['schema'] = schema
        examples = app_json.setdefault('examples', {})
        if 'Success' not in examples:
            examples['Success'] = {
                'summary': 'Exemplo de resposta',
                'value': infer_example(schema)
            }

path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')
print('updated', path)
