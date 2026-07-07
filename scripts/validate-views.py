import json, pathlib, sys
ROOT = pathlib.Path(__file__).resolve().parent.parent
VIEW_REQ = {'vista','titulo','descripcion','tipo_grafico_sugerido','categoria'}
MOD_TYPES = {'kpi','compare','timeline','logro'}
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
if errs: print('\n'.join(errs)); sys.exit(1)
print(f'VIEWS OK: {n} archivos válidos')
