import json
from pathlib import Path

base_url = "http://localhost/clickresto-api/public/api/v1"

collection = {
    "info": {
        "name": "ClickResto - Relatorios Analiticos",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
        "description": "Relatorios analiticos R01-R40 com filtros iguais ao script."
    },
    "variable": [
        {"key": "base_url", "value": base_url},
        {"key": "token", "value": ""},
        {"key": "empresa_id", "value": "1"},
        {"key": "data", "value": "2026-03-13"},
        {"key": "data_inicio", "value": "2026-03-01"},
        {"key": "data_fim", "value": "2026-03-13"},
        {"key": "tipo_filtro", "value": "periodo"},
        {"key": "tipo_agrupamento", "value": "dia"}
    ],
    "item": [
        {
            "name": "Relatorios Analiticos",
            "item": []
        }
    ]
}

folder = collection['item'][0]
for i in range(1, 41):
    codigo = f"r{i:02d}"
    folder['item'].append({
        "name": f"R{i:02d}",
        "request": {
            "method": "GET",
            "header": [
                {"key": "Authorization", "value": "Bearer {{token}}"},
                {"key": "X-Empresa-Id", "value": "{{empresa_id}}"}
            ],
            "url": {
                "raw": "{{base_url}}/relatorios/analiticos/" + codigo + "?tipo_filtro={{tipo_filtro}}&tipo_agrupamento={{tipo_agrupamento}}&dia_ref={{data}}&data_inicio={{data_inicio}}&data_fim={{data_fim}}",
                "host": ["{{base_url}}"],
                "path": ["relatorios", "analiticos", codigo],
                "query": [
                    {"key": "tipo_filtro", "value": "{{tipo_filtro}}"},
                    {"key": "tipo_agrupamento", "value": "{{tipo_agrupamento}}"},
                    {"key": "dia_ref", "value": "{{data}}"},
                    {"key": "data_inicio", "value": "{{data_inicio}}"},
                    {"key": "data_fim", "value": "{{data_fim}}"}
                ]
            }
        },
        "description": "Executa o relatorio analitico " + codigo.upper() + " com filtros padrao."
    })

out = Path('postman/ClickResto-Relatorios-Analiticos.postman_collection.json')
out.write_text(json.dumps(collection, ensure_ascii=False, indent=2), encoding='utf-8')
print('created', out)
