#!/usr/bin/env python3
"""build-views.py — Genera data/views/<seccion>/*.json desde paz_catalog.py.

Ejecutar desde la raiz del plugin:
    python scripts/build-views.py
"""

import json
import pathlib
import sys
import unicodedata

ROOT = pathlib.Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT / "scripts"))

from paz_catalog import CATALOGO  # noqa: E402

# ── helpers ──────────────────────────────────────────────────────────────────
_lookup_path = ROOT / "data" / "topo" / "narino_municipios.lookup.json"
_lookup = json.loads(_lookup_path.read_text(encoding="utf-8"))
NOMBRES = [m["nombre"] for m in _lookup]   # 64 nombres oficiales (con tildes)


def norm(s: str) -> str:
    """Convierte a mayúsculas y elimina diacríticos para join de nombres."""
    return "".join(
        c for c in unicodedata.normalize("NFD", s.upper())
        if unicodedata.category(c) != "Mn"
    ).strip()


def write(seccion: str, slug: str, obj: dict) -> None:
    d = ROOT / "data" / "views" / seccion
    d.mkdir(parents=True, exist_ok=True)
    # Silence index.php (WordPress direct-access protection)
    php = d / "index.php"
    if not php.exists():
        php.write_text("<?php // Silence is golden.\n", encoding="utf-8")
    path = d / f"{slug}.json"
    path.write_text(json.dumps(obj, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"  wrote  {seccion}/{slug}.json")


def geomap_from_dict(src: dict, value_key: str, default=0) -> list:
    """Expande un dict {nombre_norm: valor} a los 64 municipios."""
    srcn = {norm(k): v for k, v in src.items()}
    return [
        {"municipio": nom, value_key: srcn.get(norm(nom), default)}
        for nom in NOMBRES
    ]


# ═══════════════════════════════════════════════════════════════════════════════
# DNI  (slides 4–18)
# ═══════════════════════════════════════════════════════════════════════════════

def dni_desplazamiento_anual():
    """s4 — compare 2023→2024"""
    d = CATALOGO["desplazamiento_narino"]
    write("dni", "desplazamiento-anual", {
        "modulo": "compare",
        "id": "desplazamiento-anual",
        "titulo": "Desplazamiento forzado (Nariño) 2023→2024",
        "unidad": "personas",
        "from": {"y": "2023", "v": d["2023"]},
        "to":   {"y": "2024", "v": d["2024"]},
        "delta": d["delta_2324"],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_desplazamiento_interanual():
    """s4 — compare 2024→2025"""
    d = CATALOGO["desplazamiento_narino"]
    write("dni", "desplazamiento-interanual", {
        "modulo": "compare",
        "id": "desplazamiento-interanual",
        "titulo": "Desplazamiento forzado (Nariño) 2024→2025",
        "unidad": "personas",
        "from": {"y": "2024", "v": d["2024"]},
        "to":   {"y": "2025", "v": d["2025"]},
        "delta": d["delta_2425"],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_confinamiento_anual():
    """s6 — compare 2023→2024"""
    d = CATALOGO["confinamiento_narino"]
    write("dni", "confinamiento-anual", {
        "modulo": "compare",
        "id": "confinamiento-anual",
        "titulo": "Confinamiento forzado (Nariño) 2023→2024",
        "unidad": "personas",
        "from": {"y": "2023", "v": d["2023"]},
        "to":   {"y": "2024", "v": d["2024"]},
        "delta": d["delta_2324"],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_minas_narino():
    """s13 — compare 2023→2024 personas afectadas por minas (anual)"""
    d = CATALOGO["minas_narino"]
    write("dni", "minas-narino", {
        "modulo": "compare",
        "id": "minas-narino",
        "titulo": "Personas afectadas por minas antipersonal — anual (Nariño) 2023→2024",
        "unidad": "civiles",
        "from": {"y": "2023", "v": d["2023"]},
        "to":   {"y": "2024", "v": d["2024"]},
        "delta": d["delta_2324"],
        "fuente": "Comités de Justicia Transicional Ley 1448 de 2011"
    })


def dni_minas_interanual():
    """s13 — compare 2024→2025 personas afectadas por minas (interanual)"""
    d = CATALOGO["minas_narino"]
    write("dni", "minas-interanual", {
        "modulo": "compare",
        "id": "minas-interanual",
        "titulo": "Personas afectadas por minas antipersonal — interanual (Nariño) 2024→2025",
        "unidad": "civiles",
        "from": {"y": "2024", "v": d["2024"]},
        "to":   {"y": "2025", "v": d["2025"]},
        "delta": d["delta_2425"],
        "fuente": "Comités de Justicia Transicional Ley 1448 de 2011"
    })


def dni_minas_narino_parcial():
    """s13 — compare 2025→2026 personas afectadas por minas (parcial)"""
    d = CATALOGO["minas_narino"]
    write("dni", "minas-narino-parcial", {
        "modulo": "compare",
        "id": "minas-narino-parcial",
        "titulo": "Personas afectadas por minas antipersonal — parcial (Nariño) 2025→2026",
        "unidad": "civiles",
        "from": {"y": "2025", "v": d["2025"]},
        "to":   {"y": "2026", "v": d["2026"]},
        "delta": d["delta_2526"],
        "fuente": "Comités de Justicia Transicional Ley 1448 de 2011"
    })


def dni_firmantes_100():
    """s15 — kpi 100% reducción homicidios firmantes"""
    d = CATALOGO["firmantes"]
    write("dni", "firmantes-100", {
        "modulo": "kpi",
        "id": "firmantes-100",
        "titulo": "Homicidios de firmantes de paz",
        "valor": d["reduccion"],
        "unidad": "%",
        "leyenda": "Reducción",
        "serie": d["serie"],
        "fuente": "Consejo Departamental de Reincorporación"
    })


def dni_confinamiento_fcs_100():
    """s7 — kpi 100% reducción confinamiento FCS"""
    d = CATALOGO["confinamiento_fcs"]
    write("dni", "confinamiento-fcs-100", {
        "modulo": "kpi",
        "id": "confinamiento-fcs-100",
        "titulo": "Confinamiento — Injerencia Comuneros del Sur",
        "valor": d["reduccion"],
        "unidad": "%",
        "leyenda": "Reducción total",
        "serie": [
            {"y": "2023", "v": d["2023"]},
            {"y": "2024", "v": d["2024"]},
            {"y": "2025", "v": d["2025"]},
            {"y": "2026", "v": d["2026"]}
        ],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_cneb_desplazamiento():
    """s8 — barras desplazamiento CNEB 2023–2026"""
    d = CATALOGO["cneb_desplazamiento"]
    datos = [{"año": y, "personas": d[y]} for y in ["2023", "2024", "2025", "2026"]]
    write("dni", "cneb-desplazamiento", {
        "vista": "cneb-desplazamiento",
        "titulo": "Desplazamiento en zona de injerencia CNEB (2023–2026)",
        "descripcion": "Personas desplazadas en municipios de influencia de la Coordinadora Nacional Ejército Bolivariano. Fuente: Comité de Justicia Transicional Ley 1448 de 2011.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "humanitarian",
        "datos": datos
    })


def dni_cneb_confinamiento():
    """s8 — barras confinamiento CNEB 2023–2026"""
    d = CATALOGO["cneb_confinamiento"]
    datos = [{"año": y, "personas": d[y]} for y in ["2023", "2024", "2025", "2026"]]
    write("dni", "cneb-confinamiento", {
        "vista": "cneb-confinamiento",
        "titulo": "Confinamiento en zona de injerencia CNEB (2023–2026)",
        "descripcion": "Personas confinadas en municipios de influencia de la Coordinadora Nacional Ejército Bolivariano. Fuente: Comité de Justicia Transicional Ley 1448 de 2011.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "humanitarian",
        "datos": datos
    })


def dni_coordinadora_desplazamiento():
    """s9 — compare desplazamiento Coord. Nacional Ejército Bolivariano Ene-Jun 25→26"""
    d = CATALOGO["coordinadoras"]["desplaz_ejto_bolivariano"]
    write("dni", "coordinadora-desplazamiento", {
        "modulo": "compare",
        "id": "coordinadora-desplazamiento",
        "titulo": "Desplazamiento CNEB — Ene-Jun 2025→2026",
        "unidad": d["unidad"],
        "from": d["from"],
        "to":   d["to"],
        "delta": d["delta"],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_coordinadora_confinamiento():
    """s9 — compare confinamiento CGSB Ene-Jun 25→26"""
    d = CATALOGO["coordinadoras"]["confin_cgsb"]
    write("dni", "coordinadora-confinamiento", {
        "modulo": "compare",
        "id": "coordinadora-confinamiento",
        "titulo": "Confinamiento CGSB — Ene-Jun 2025→2026",
        "unidad": d["unidad"],
        "from": d["from"],
        "to":   d["to"],
        "delta": d["delta"],
        "fuente": "Comité de Justicia Transicional Ley 1448 de 2011"
    })


def dni_rutas_nna():
    """s12 — kpi rutas de prevención NNA 2023–2026"""
    d = CATALOGO["nna_rutas"]
    write("dni", "rutas-nna", {
        "modulo": "kpi",
        "id": "rutas-nna",
        "titulo": "Rutas de prevención NNA — Alto Comisionado de Reincorporación",
        "valor": d["atencion_2026"],
        "unidad": "casos",
        "leyenda": "Ene–Jun 2026",
        "serie": [
            {"y": y, "v": v}
            for y, v in zip(d["years"], d["serie"])
        ],
        "fuente": "Alto Comisionado de Reincorporación"
    })


def dni_desminado_municipios():
    """s14 — geomap 64 municipios, en_desminado 0/1"""
    desminados = {norm(m) for m in CATALOGO["desminado_municipios"]}
    municipios = [
        {"municipio": nom, "en_desminado": 1 if norm(nom) in desminados else 0}
        for nom in NOMBRES
    ]
    write("dni", "desminado-municipios", {
        "vista": "desminado-municipios",
        "titulo": "Municipios en proceso de desminado humanitario",
        "descripcion": "Municipios de Nariño con procesos activos de desminado humanitario (injerencia FCS). 1 = en desminado; 0 = sin dato. San Pablo declarado libre de minas.",
        "tipo_grafico_sugerido": "geomap",
        "categoria": "geographic",
        "total_municipios": 64,
        "municipios": municipios
    })


def dni_desaparecidos_cuerpos():
    """s16 — geomap 64 municipios, cuerpos_recuperados"""
    src = CATALOGO["desaparecidos"]["por_municipio"]
    srcn = {norm(k): v for k, v in src.items()}
    municipios = [
        {"municipio": nom, "cuerpos_recuperados": srcn.get(norm(nom), 0)}
        for nom in NOMBRES
    ]
    write("dni", "desaparecidos-cuerpos", {
        "vista": "desaparecidos-cuerpos",
        "titulo": "Recuperación de cuerpos — UBPD (2025–2026)",
        "descripcion": "Cuerpos recuperados por municipio en búsqueda de personas desaparecidas. Total: 39; 14 con identidad confirmada; 6 entregas dignas; 26 lugares de interés. Fuente: UBPD.",
        "tipo_grafico_sugerido": "geomap",
        "categoria": "geographic",
        "total_municipios": 64,
        "municipios": municipios
    })


def dni_nna_desvinculacion():
    """s11 — línea NNA desvinculados 2023–2025"""
    d = CATALOGO["nna_desvinculacion"]
    datos = [{"año": y, "casos": v} for y, v in zip(d["years"], d["serie"])]
    write("dni", "nna-desvinculacion", {
        "vista": "nna-desvinculacion",
        "titulo": "Niñas, niños y adolescentes desvinculados — Nariño (2023–2025)",
        "descripcion": (
            f"Casos anuales de NNA desvinculados de grupos armados. "
            f"Total acumulado en el programa: {d['total']}. Parcial 2026: {d['parcial_2026']}. "
            "Fuente: ICBF."
        ),
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "humanitarian",
        "datos": datos
    })


def dni_acuerdos_fcs():
    """s17 — timeline acuerdos FCS"""
    d = CATALOGO["acuerdos_fcs"]
    write("dni", "acuerdos-fcs", {
        "modulo": "timeline",
        "id": "acuerdos-fcs",
        "titulo": "Línea de tiempo — Acuerdos con el Frente Comuneros del Sur",
        "total": d["total"],
        "eventos": d["eventos"]
    })


def dni_acuerdos_cneb():
    """s18 — timeline acuerdos CNEB"""
    d = CATALOGO["acuerdos_cneb"]
    write("dni", "acuerdos-cneb", {
        "modulo": "timeline",
        "id": "acuerdos-cneb",
        "titulo": "Línea de tiempo — Acuerdos con la CNEB",
        "total": None,
        "eventos": d["eventos"]
    })


def dni_san_pablo_libre_minas():
    """s14 — logro San Pablo libre de minas"""
    write("dni", "san-pablo-libre-minas", {
        "modulo": "logro",
        "id": "san-pablo-libre-minas",
        "titulo": "San Pablo libre de minas",
        "texto": "San Pablo entregado como municipio libre de minas antipersonal en el marco del proceso de paz con el Frente Comuneros del Sur."
    })


# ═══════════════════════════════════════════════════════════════════════════════
# SEGURIDAD  (slides 20–26)
# ═══════════════════════════════════════════════════════════════════════════════

def seg_homicidios_municipio():
    """s24 — geomap 64 municipios con tasas reales 2025 (y 2023/2024)"""
    src = CATALOGO["homicidios_municipio"]
    srcn = {norm(k): v for k, v in src.items()}
    municipios = [
        {
            "municipio":          nom,
            "tasa_homicidio_2025": srcn.get(norm(nom), {}).get("2025", 0),
            "tasa_homicidio_2024": srcn.get(norm(nom), {}).get("2024", 0),
            "tasa_homicidio_2023": srcn.get(norm(nom), {}).get("2023", 0),
            "casos_2025":          srcn.get(norm(nom), {}).get("casos_2025", 0),
            "priorizado":          norm(nom) in srcn
        }
        for nom in NOMBRES
    ]
    write("seguridad", "homicidios-municipio", {
        "vista": "homicidios-municipio",
        "titulo": "Tasa de homicidio por municipio (2025)",
        "descripcion": "Tasa por 100.000 hab. en municipios priorizados; 0 = sin dato reportado. Fuente: Policía Nacional.",
        "tipo_grafico_sugerido": "geomap",
        "categoria": "geographic",
        "total_municipios": 64,
        "municipios": municipios
    })


def seg_hist_homicidios_gob():
    """s21 — barras histórico por gobierno 1990–2025"""
    d = CATALOGO["hist_homicidios_gob"]
    datos = [
        {"gobierno": g, "tasa": r}
        for g, r in zip(d["govs"], d["rate"])
    ]
    write("seguridad", "hist-homicidios-gob", {
        "vista": "hist-homicidios-gob",
        "titulo": "Histórico de homicidios por gobierno — Colombia (1990–2025)",
        "descripcion": "Tasa de homicidio por 100.000 hab. en Colombia por período presidencial. Gobierno actual (Petro): 25,6 (2023) · 25,7 (2024) · 26,1 (2025). Fuente: Policía Nacional.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "security",
        "datos": datos
    })


def seg_terrorismo():
    """s25 — barras acciones terroristas 2022–2024 por ámbito"""
    d = CATALOGO["terrorismo"]
    datos = []
    for row in d["rows"]:
        for i, y in enumerate(d["years"]):
            datos.append({
                "ambito":           row["scope"],
                "año":              y,
                "acciones":         row["v"][i],
                "tendencia_buena":  row["bueno"]
            })
    write("seguridad", "terrorismo-nacional-narino", {
        "vista": "terrorismo-nacional-narino",
        "titulo": "Acciones terroristas — Colombia vs Nariño (2022–2024)",
        "descripcion": "Número de acciones terroristas por departamento/nación. Nariño: −53% (2022→2023) y −20,5% (2023→2024), tendencia opuesta al resto del país. Fuente: Fuerzas Militares.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "security",
        "datos": datos
    })


def seg_fuerza_publica():
    """s26 — barras bajas fuerza pública 2023–2025"""
    d = CATALOGO["fuerza_publica"]
    datos = []
    for ambito_label, key in [("Nacional", "nacional"), ("Nariño", "narino")]:
        for rol_label, rol_key in [("Policía", "policia"), ("Militares", "militar")]:
            for i, y in enumerate(d["years"]):
                datos.append({
                    "ambito": ambito_label,
                    "rol":    rol_label,
                    "año":    y,
                    "bajas":  d[key][rol_key][i]
                })
    write("seguridad", "fuerza-publica", {
        "vista": "fuerza-publica",
        "titulo": "Fuerza pública asesinada en el marco del conflicto (2023–2025)",
        "descripcion": "Bajas de policías y militares. Nacional: +156,7% policías y +77,8% militares. Nariño: policías estable, militares −60%. Fuente: Policía Nacional – SIEDCO Plus.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "security",
        "datos": datos
    })


def seg_homicidios_departamental():
    """s23 — tabla ranking departamental 2023/2024/2025"""
    d = CATALOGO["ranking_dep"]
    datos = []
    for year, rows in d.items():
        for r in rows:
            datos.append({"año": year, "departamento": r["d"], "tasa": r["v"]})
    write("seguridad", "homicidios-departamental", {
        "vista": "homicidios-departamental",
        "titulo": "Ranking departamental de homicidios — Colombia (2023, 2024, 2025)",
        "descripcion": "Tasa de homicidio por 100.000 hab. por departamento y año (selección con Nariño). Fuente: Policía Nacional – DANE.",
        "tipo_grafico_sugerido": "tabla",
        "categoria": "categorical",
        "tema": "security",
        "datos": datos
    })


def seg_estructuras_armadas():
    """s20 — lista estructuras armadas ilegales"""
    d = CATALOGO["estructuras_armadas"]
    datos = [
        {"grupo": g["nombre"], "estructuras": g["items"]}
        for g in d["grupos"]
    ]
    write("seguridad", "estructuras-armadas", {
        "vista": "estructuras-armadas",
        "titulo": "Estructuras armadas ilegales presentes en Nariño",
        "descripcion": (
            f"{d['grupos_ilegales']} grupos armados al margen de la ley + "
            f"{d['crimen_alto_impacto']} estructura de crimen de alto impacto. "
            f"CNEB: {d['cneb']['total']} combatientes; "
            f"{d['cneb']['en_proceso_paz']} en proceso de paz ({d['cneb']['pct_paz']}%)."
        ),
        "tipo_grafico_sugerido": "tabla",
        "categoria": "categorical",
        "tema": "security",
        "datos": datos
    })


# ═══════════════════════════════════════════════════════════════════════════════
# CONVIVENCIA  (slides 27–29)
# ═══════════════════════════════════════════════════════════════════════════════

def conv_convivencia():
    """s27 — tabla convivencia Nariño vs Colombia 2023–2025"""
    d = CATALOGO["convivencia"]
    datos = []
    for ind in d["indicadores"]:
        for i, y in enumerate(d["years"]):
            datos.append({
                "indicador":      ind["nombre"],
                "año":            y,
                "casos_nacional": ind["nacional"]["casos"][i],
                "tasa_nacional":  ind["nacional"]["tasa"][i],
                "casos_narino":   ind["narino"]["casos"][i],
                "tasa_narino":    ind["narino"]["tasa"][i]
            })
    write("convivencia", "convivencia", {
        "vista": "convivencia",
        "titulo": "Convivencia y seguridad ciudadana — Nariño vs Colombia (2023–2025)",
        "descripcion": "Casos y tasa por 100.000 hab. de violencia intrafamiliar, lesiones personales y feminicidio. Fuente: SIEDCO Plus · Observatorio Gobernación de Nariño.",
        "tipo_grafico_sugerido": "tabla",
        "categoria": "categorical",
        "tema": "coexistence",
        "datos": datos
    })


def conv_hurtos():
    """s28 — tabla hurtos Nariño vs Colombia 2023–2025"""
    d = CATALOGO["hurtos"]
    datos = []
    for ind in d["indicadores"]:
        for i, y in enumerate(d["years"]):
            datos.append({
                "indicador":      ind["nombre"],
                "año":            y,
                "casos_nacional": ind["nacional"]["casos"][i],
                "casos_narino":   ind["narino"]["casos"][i]
            })
    write("convivencia", "hurtos", {
        "vista": "hurtos",
        "titulo": "Hurtos — Nariño vs Colombia (2023–2025)",
        "descripcion": "Casos de hurto a residencias, comercio, automotores y motocicletas. null = dato no disponible en la fuente. Fuente: SIEDCO Plus.",
        "tipo_grafico_sugerido": "tabla",
        "categoria": "categorical",
        "tema": "coexistence",
        "datos": datos
    })


def conv_hallazgos_clave():
    """s29 — logro hallazgos clave seguridad pública"""
    items = CATALOGO["hallazgos_clave"]
    write("convivencia", "hallazgos-clave", {
        "modulo": "logro",
        "id": "hallazgos-clave",
        "titulo": "Hallazgos clave — Gestión de seguridad pública",
        "texto": items[0],
        "items": items
    })


# ═══════════════════════════════════════════════════════════════════════════════
# ESTRATEGIA  (slides 31–32)
# ═══════════════════════════════════════════════════════════════════════════════

def est_subsecretaria():
    """s31 — módulo diagrama subsecretaría (ramas radiales)"""
    d = CATALOGO["subsecretaria"]
    ramas = [
        {k: v for k, v in r.items() if k in ("nombre", "kpi", "sub")}
        for r in d["ramas"]
    ]
    write("estrategia", "subsecretaria", {
        "modulo": "diagrama",
        "id": "subsecretaria",
        "titulo": "Subsecretaría de Seguridad Ciudadana — Esquema de estrategia 2026",
        "centro": d["centro"],
        "ramas": ramas
    })


def est_narino_360():
    """s32 — módulo estrategia Nariño 360°"""
    d = CATALOGO["narino_360"]
    write("estrategia", "narino-360", {
        "modulo": "estrategia",
        "id": "narino-360",
        "titulo": "Nariño 360° — Seguridad, Convivencia y Paz Territorial",
        "descripcion": d["descripcion"],
        "lineas": d["lineas"],
        "comunicaciones": d["componentes"]
    })


# ═══════════════════════════════════════════════════════════════════════════════
# TRANSFORMACIONES  (slides 34–36)
# ═══════════════════════════════════════════════════════════════════════════════

def trans_ipm():
    """s34 — barras IPM Nariño vs Nacional 2024–2025"""
    d = CATALOGO["ipm"]
    datos = []
    for fila in d["filas"]:
        for i, y in enumerate(d["anos"]):
            datos.append({
                "indicador": fila["nombre"],
                "año":       y,
                "narino":    fila["narino"][i],
                "nacional":  fila["nacional"][i]
            })
    write("transformaciones", "ipm", {
        "vista": "ipm",
        "titulo": "Índice de Pobreza Multidimensional (IPM) — Nariño vs Nacional (2024–2025)",
        "descripcion": "IPM total, urbano y rural en Nariño y Colombia. Rural de Nariño bajó a 17,2, por debajo del promedio nacional (22,4). Fuente: DANE.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "social",
        "datos": datos
    })


def trans_indicadores_sociales():
    """s35 — tabla indicadores sociales 2024–2025"""
    datos = [
        {
            "indicador":   r["nombre"],
            "valor_2024":  r["de"],
            "valor_2025":  r["a"],
            "impacto":     r["impacto"]
        }
        for r in CATALOGO["indicadores_sociales"]
    ]
    write("transformaciones", "indicadores-sociales", {
        "vista": "indicadores-sociales",
        "titulo": "Indicadores sociales — Nariño (2024–2025)",
        "descripcion": "Variación en educación, acceso a agua y calidad de vivienda según el IPM. Fuente: DANE.",
        "tipo_grafico_sugerido": "tabla",
        "categoria": "social",
        "datos": datos
    })


def trans_desocupacion():
    """s36 — barras tasa desocupación 2024–2025"""
    d = CATALOGO["desocupacion"]
    datos = [
        {"año": y, "narino": d["narino"][i], "nacional": d["nacional"][i]}
        for i, y in enumerate(d["anos"])
    ]
    write("transformaciones", "desocupacion", {
        "vista": "desocupacion",
        "titulo": "Tasa de desocupación — Nariño vs Colombia (2024–2025)",
        "descripcion": "Tasa de desocupación (%). Nariño 6,0% en 2025, la más baja del país (nacional: 8%). Fuente: DANE.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "economic",
        "datos": datos
    })


def trans_pib():
    """s36 — barras PIB sectorial 2024–2025"""
    d = CATALOGO["pib"]
    datos = [
        {"sector": s["nombre"], "valor_2024": s["de"], "valor_2025": s["a"]}
        for s in d["sectores"]
    ]
    write("transformaciones", "pib", {
        "vista": "pib",
        "titulo": "PIB de Nariño por sectores económicos (2024–2025)",
        "descripcion": "PIB a precios corrientes, miles de millones de pesos. Fuente: DANE.",
        "tipo_grafico_sugerido": "bar",
        "categoria": "categorical",
        "tema": "economic",
        "datos": datos
    })


# ═══════════════════════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════════════════════

if __name__ == "__main__":
    print("=== build-views.py — Suite PAZ ===\n")

    print("-- DNI --")
    dni_desplazamiento_anual()
    dni_desplazamiento_interanual()
    dni_confinamiento_anual()
    dni_minas_narino()
    dni_minas_interanual()
    dni_minas_narino_parcial()
    dni_firmantes_100()
    dni_confinamiento_fcs_100()
    dni_cneb_desplazamiento()
    dni_cneb_confinamiento()
    dni_coordinadora_desplazamiento()
    dni_coordinadora_confinamiento()
    dni_rutas_nna()
    dni_desminado_municipios()
    dni_desaparecidos_cuerpos()
    dni_nna_desvinculacion()
    dni_acuerdos_fcs()
    dni_acuerdos_cneb()
    dni_san_pablo_libre_minas()

    print("\n-- SEGURIDAD --")
    seg_homicidios_municipio()
    seg_hist_homicidios_gob()
    seg_terrorismo()
    seg_fuerza_publica()
    seg_homicidios_departamental()
    seg_estructuras_armadas()

    print("\n-- CONVIVENCIA --")
    conv_convivencia()
    conv_hurtos()
    conv_hallazgos_clave()

    print("\n-- ESTRATEGIA --")
    est_subsecretaria()
    est_narino_360()

    print("\n-- TRANSFORMACIONES --")
    trans_ipm()
    trans_indicadores_sociales()
    trans_desocupacion()
    trans_pib()

    print("\nDone. Ejecuta: python scripts/validate-views.py")
