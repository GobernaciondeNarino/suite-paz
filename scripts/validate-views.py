import json, pathlib, sys
ROOT = pathlib.Path(__file__).resolve().parent.parent
VIEW_REQ = {'vista','titulo','descripcion','tipo_grafico_sugerido','categoria'}
# MOD_TYPES: tipos de módulo nativos + nuevos tipos de módulo PAZ (diagrama, estrategia)
MOD_TYPES = {'kpi','compare','timeline','logro','diagrama','estrategia'}
# Tipos de gráfico válidos (d3plus) + tipos nativos aceptados (tabla se añade en Fix-Task 2)
VALID_TIPOS = {
    'bar','stacked_bar','line','area','stacked_area','pie','donut','treemap',
    'geomap','network','tree','sankey','rings','box_whisker','priestley','tabla',
}
errs=[]; n=0
for p in (ROOT/'data/views').rglob('*.json'):
    n+=1; d=json.load(open(p,encoding='utf-8'))
    if 'modulo' in d:
        if d['modulo'] not in MOD_TYPES: errs.append(f'{p}: modulo inválido {d["modulo"]}')
        if 'id' not in d or 'titulo' not in d: errs.append(f'{p}: modulo sin id/titulo')
    else:
        miss = VIEW_REQ - set(d)
        if miss: errs.append(f'{p}: faltan campos {miss}')
        if 'municipios' not in d and 'datos' not in d: errs.append(f'{p}: sin municipios/datos')
        tipo = d.get('tipo_grafico_sugerido','')
        if tipo not in VALID_TIPOS: errs.append(f'{p}: tipo_grafico_sugerido inválido: {tipo!r}')
if errs: print('\n'.join(errs)); sys.exit(1)
print(f'VIEWS OK: {n} archivos válidos')
