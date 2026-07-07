# paz_catalog.py
# Catálogo de cifras verbatim del PDF (transcrito desde data.js — proyecto previo).
# NO modificar valores; usar None donde el PDF es ilegible.
# Fuente definitiva: C:/Users/Usuario/.claude/plugins/paz/src/data.js slides s4–s36.

CATALOGO = {

    # ─── DNI ──────────────────────────────────────────────────────────────────

    # s4: Desplazamiento forzado · Nariño
    "desplazamiento_narino": {
        "2023": 25344, "2024": 17441, "2025": 4227,
        "delta_2324": -31.1, "delta_2425": -75.8,
        "nota": "Ene-Jun 2025: 2.098 · Ene-Jun 2026: 2.057 (−1,95%)"
    },

    # s5: Desplazamiento forzado · Injerencia Frente Comuneros del Sur
    "desplazamiento_fcs": {
        "2023": 15465, "2024": 911, "2025": 349,
        "delta_2324": -94, "delta_2425": -61.7,
        "nota": "Semestre 2023: 6.902 → 2026: 592 (−91,4%)"
    },

    # s6: Confinamiento · Nariño
    "confinamiento_narino": {
        "2023": 6772, "2024": 3999, "2025": 1821,
        "delta_2324": -40.9, "delta_2425": -54.5,
        "nota": "Semestre: 559 → 0 (−100%)"
    },

    # s7: Confinamiento · Injerencia Frente Comuneros del Sur (reducción 100%)
    "confinamiento_fcs": {
        "2023": 6772, "2024": 0, "2025": 0, "2026": 0,
        "reduccion": 100,
        "nota": "2023: 6.772 → 2024: 0 · 2025: 0 · 2026: 0"
    },

    # s8: Zona de injerencia CNEB — Desplazamiento y confinamiento (personas)
    "cneb_desplazamiento": {
        "2023": 15697, "2024": 12611, "2025": 2973, "2026": 441
    },
    "cneb_confinamiento": {
        "2023": 2787, "2024": 286, "2025": 142, "2026": 0
    },

    # s9: Reducción por coordinadoras (semestral Ene-Jun)
    "coordinadoras": {
        "desplaz_ejto_bolivariano": {
            "from": {"y": "Ene-Jun 25", "v": 1036},
            "to":   {"y": "Ene-Jun 26", "v": 329},
            "delta": -68.3,
            "unidad": "personas"
        },
        "confin_cgsb": {
            "from": {"y": "Ene-Jun 25", "v": 142},
            "to":   {"y": "Ene-Jun 26", "v": 0},
            "delta": -100,
            "unidad": "personas"
        }
    },

    # s10: Reclutamiento NNA — ICBF
    "reclutamiento_narino":   {"2024": 17, "2025": 17, "delta": 0},
    "reclutamiento_colombia": {"2024": 621, "2025": 386, "delta": -37.8},

    # s11: Desvinculación NNA · Nariño
    "nna_desvinculacion": {
        "serie":        [38, 35, 27],
        "years":        ["2023", "2024", "2025"],
        "total":        428,
        "parcial_2026": 9,
        "delta_2324":   -7.9,
        "delta_2425":   -22.9
    },

    # s12: Rutas de prevención NNA — Alto Comisionado de Reincorporación
    "nna_rutas": {
        "serie":        [25, 17, 7],
        "years":        ["2023", "2024", "2025"],
        "atencion_2026": 7
    },

    # s13: Minas antipersonal · Nariño (personas afectadas)
    "minas_narino": {
        "2023": 42, "2024": 26, "2025": 6, "2026": 6,
        "delta_2324": -38.1,
        "delta_2425": -76.9,
        "delta_2526": 0
    },

    # s14: Minas · Injerencia FCS — desminado humanitario
    "desminado_municipios": [
        "SAMANIEGO", "MALLAMA", "LA LLANADA", "CUMBAL", "SAN PABLO", "SANTACRUZ"
    ],
    "minas_fcs": {
        "2024": 6, "2025": 4, "delta": -33,
        "ene_jun_2026": 0,
        "nota": "San Pablo entregado como libre de minas · Municipios en desminado humanitario"
    },

    # s15: Firmantes de paz — Consejo Departamental de Reincorporación
    "firmantes": {
        "serie": [
            {"y": "2023", "v": 2},
            {"y": "2024", "v": 0},
            {"y": "2025", "v": 0},
            {"y": "2026", "v": 0}
        ],
        "reduccion": 100
    },

    # s16: Búsqueda de personas desaparecidas · UBPD (2025–2026)
    "desaparecidos": {
        "total":        39,
        "identificados": 14,
        "entregas":     6,
        "lugares":      26,
        "por_municipio": {
            "CUMBAL":    13,
            "LA LLANADA": 2,
            "SANTACRUZ": 12,
            "SAMANIEGO": 12
        }
    },

    # s17: Línea de tiempo · Frente Comuneros del Sur
    "acuerdos_fcs": {
        "total": 12,
        "eventos": [
            {"fecha": "Sep 2024", "texto": "Instalación / primeros acuerdos"},
            {"fecha": "Oct 2024", "texto": "Acuerdos"},
            {"fecha": "Nov 2024", "texto": "Acuerdos"},
            {"fecha": "Dic 2024", "texto": "Acuerdos"},
            {"fecha": "Ene 2025", "texto": "Acuerdos"},
            {"fecha": "Jun 2025", "texto": "Acuerdos"}
        ]
    },

    # s18: Línea de tiempo · CNEB
    "acuerdos_cneb": {
        "eventos": [
            {"fecha": "Ene 2025", "texto": "Instalación de mesas"},
            {"fecha": "Feb 2025", "texto": "Protocolos"},
            {"fecha": "May 2025", "texto": "Cese"},
            {"fecha": "Jul 2025", "texto": "Avances"},
            {"fecha": "Dic 2025", "texto": "Acuerdos"},
            {"fecha": "Mar 2026", "texto": "Avances"},
            {"fecha": "Jun 2026", "texto": "Continuidad"}
        ]
    },

    # ─── SEGURIDAD ────────────────────────────────────────────────────────────

    # s20: Estructuras armadas ilegales presentes en Nariño
    "estructuras_armadas": {
        "grupos_ilegales":      4,
        "crimen_alto_impacto":  1,
        "cneb": {
            "total":         3200,
            "en_proceso_paz": 2000,
            "pct_paz":        62.5
        },
        "grupos": [
            {
                "nombre": "Coordinadora Nacional Ejército Bolivariano",
                "items": [
                    "Alfonso Cano", "Ariel Aldana", "Iván Ríos",
                    "Oliver Sinisterra", "Manuel Sucre", "Comisión 48"
                ]
            },
            {
                "nombre": "Estado Mayor Central",
                "items": [
                    "Franco Benavides", "Urías Rondón", "Fredy Gutiérrez",
                    "Alán Rodríguez", "Rafael Aguilera"
                ]
            },
            {
                "nombre": "ELN",
                "items": ["Compañía Manuel Vásquez Castaño"]
            },
            {
                "nombre": "Crimen alto impacto",
                "items": ["AUN – Autodefensas Unidas de Nariño"]
            },
            {
                "nombre": "Frente Comuneros del Sur",
                "items": [
                    "Comuneros del Sur", "José Luis Cabrera",
                    "Elder Santos", "Jaime Toño Obando"
                ]
            }
        ]
    },

    # s21: Histórico homicidios por gobiernos (1990–2025) — Colombia
    "hist_homicidios_gob": {
        "govs": [
            "Gaviria", "Samper", "Pastrana",
            "Uribe I", "Uribe II",
            "Santos I", "Santos II",
            "Duque", "Petro"
        ],
        "rate": [73, 80, 84, 82, 72, 71, 45, 36, 26.1],
        "nota": "Gobierno actual: 25,6 (2023) · 25,7 (2024) · 26,1 (2025)"
    },

    # s22: Tasa de homicidio — Colombia vs Nariño (datos parciales 2019–2020)
    "col_vs_narino": {
        "colombia": {"2019": 35.05, "2020": 33.80},
        "narino":   {"2019": 25,    "2020": 24}
    },

    # s23: Ranking departamental de homicidios — Policía Nacional / DANE
    "ranking_dep": {
        "2023": [
            {"d": "San Andrés y Providencia", "v": 65.1},
            {"d": "Putumayo",                 "v": 61.2},
            {"d": "Valle del Cauca",          "v": 58.4},
            {"d": "Nariño",                   "v": 26.2}
        ],
        "2024": [
            {"d": "Guaviare",        "v": 64.1},
            {"d": "Valle del Cauca", "v": 48.6},
            {"d": "Nariño",         "v": 21.0}
        ],
        "2025": [
            {"d": "Guaviare",                 "v": 67.3},
            {"d": "San Andrés y Providencia", "v": 65.1},
            {"d": "Valle del Cauca",          "v": 55.4},
            {"d": "Nariño",                   "v": 14.7}
        ]
    },

    # s24: Homicidios en municipios priorizados — tasa x 100k hab. (2023–2025)
    "homicidios_municipio": {
        "BARBACOAS":            {"2023": 36.7, "2024": 28.3, "2025": 17.1, "casos_2025": 11},
        "EL CHARCO":           {"2023": 40.3, "2024": 26.8, "2025": 13.4, "casos_2025":  3},
        "PASTO":               {"2023": 10.0, "2024":  7.9, "2025":  9.7, "casos_2025": 39},
        "SAN ANDRES DE TUMACO":{"2023": 33.4, "2024": 21.3, "2025": 14.6, "casos_2025": 40},
        "SAMANIEGO":           {"2023": 98.6, "2024": 40.8, "2025": 47.6, "casos_2025": 14}
    },

    # s25: Acciones terroristas (2022, 2023, 2024) + proyección 2026
    "terrorismo": {
        "years": ["2022", "2023", "2024"],
        "rows": [
            {"scope": "Nacional",        "v": [839,  1126, 1398], "pct": ["+34,2%", "+24,2%"], "bueno": False},
            {"scope": "Chocó",           "v": [ 53,    81,   87], "pct": ["+52,8%",  "+7,4%"], "bueno": False},
            {"scope": "Valle del Cauca", "v": [ 57,   100,  193], "pct": ["+75,4%",   "+93%"], "bueno": False},
            {"scope": "Cauca",           "v": [158,   385,  None], "pct": ["+81%",  "+34,6%"], "bueno": False},
            {"scope": "Nariño",          "v": [ 83,    39,    31], "pct": ["−53%",  "−20,5%"], "bueno": True}
        ],
        "proj_2026": {
            "Nacional": 616, "Chocó": 27, "Cauca": 206,
            "Valle del Cauca": 46, "Nariño": 11
        }
    },

    # s26: Fuerza pública asesinada en el marco del conflicto — SIEDCO Plus
    "fuerza_publica": {
        "years":   ["2023", "2024", "2025"],
        "nacional": {"policia": [30, 71, 96], "militar": [54, 77, 96]},
        "narino":   {"policia": [ 1,  1,  0], "militar": [ 5,  1,  2]},
        "nota": "Nacional: +156,7% policías y +77,8% militares (2023–2025). Nariño: policías estable, militares −60%."
    },

    # ─── CONVIVENCIA ─────────────────────────────────────────────────────────

    # s27: Seguridad ciudadana — SIEDCO Plus / Observatorio Gobernación de Nariño
    "convivencia": {
        "years": ["2023", "2024", "2025"],
        "indicadores": [
            {
                "nombre": "Violencia intrafamiliar",
                "nacional": {"casos": [119466, 136032, 141640], "tasa": [229.2, 238.5, 267]},
                "narino":   {"casos": [  3148,   3339,   3284], "tasa": [185.8, 195.9, 191.6]}
            },
            {
                "nombre": "Lesiones personales",
                "nacional": {"casos": [98886, 90945, 89636], "tasa": [189.7, 172.9, 168.9]},
                "narino":   {"casos": [ 2499,  2585,  2411], "tasa": [147.5, 151.7, 140.7]}
            },
            {
                "nombre": "Feminicidio",
                "nacional": {"casos": [190, 196, 168], "tasa": [0.4, 0.4, 0.3]},
                "narino":   {"casos": [  5,   8,   5], "tasa": [0.3, 0.5, 0.3]}
            }
        ]
    },

    # s28: Hurtos (2023–2025) — SIEDCO Plus
    "hurtos": {
        "years": ["2023", "2024", "2025"],
        "indicadores": [
            {
                "nombre": "Hurto a residencias",
                "nacional": {"casos": [39701, 41720, 25807]},
                "narino":   {"casos": [None,  1329,    517]}
            },
            {
                "nombre": "Hurto a comercio",
                "nacional": {"casos": [None,  None,  24406]},
                "narino":   {"casos": [None,  None,    517]}
            },
            {
                "nombre": "Hurto a automotores",
                "nacional": {"casos": [None, None, None]},
                "narino":   {"casos": [None, None, None]}
            },
            {
                "nombre": "Hurto a motocicletas",
                "nacional": {"casos": [None, None, None]},
                "narino":   {"casos": [None, None, None]}
            }
        ]
    },

    # s29: Hallazgos clave — Análisis de gestión de seguridad pública
    "hallazgos_clave": [
        "La coordinación estratégica entre la autoridad departamental, la fuerza pública y las comunidades constituyó un modelo de gestión que redujo la violencia sin escalada militar.",
        "La reducción de violencia intrafamiliar, lesiones y otros indicadores se dio por procesos de prevención social, comunitaria e institucional y atención temprana a víctimas.",
        "La reducción del hurto se sostiene gracias a la coordinación entre entidades y fuerzas públicas.",
        "El eje transversal de Nariño integra metodología y articulación para reducir indicadores de seguridad y movilidad."
    ],

    # ─── ESTRATEGIA ──────────────────────────────────────────────────────────

    # s31: Subsecretaría de Seguridad Ciudadana — esquema radial
    "subsecretaria": {
        "centro": "Subsecretaría de Seguridad Ciudadana",
        "ramas": [
            {"nombre": "Elecciones seguras"},
            {
                "nombre": "Observatorio",
                "sub": ["Fortalecimiento técnico", "Analítica y prospectiva"]
            },
            {
                "nombre": "Incremento pie de fuerza",
                "kpi": "19.013 policías (2023–2026)"
            },
            {
                "nombre": "Acción unificada",
                "kpi": "80 instituciones · Nariño 360°"
            },
            {
                "nombre": "FONSET",
                "sub": ["Tecnología", "Movilidad", "Investigación judicial"]
            },
            {
                "nombre": "Proyecto estratégico",
                "kpi": "Taminango"
            },
            {"nombre": "Mesas operativas locales"}
        ]
    },

    # s32: Nariño 360 — Seguridad, Convivencia y Paz Territorial
    "narino_360": {
        "descripcion": "Estrategia integral que articula territorio, comunidad, instituciones, fuerza pública y actores privados e internacionales.",
        "lineas": [
            "Fortalecimiento Institucional",
            "Guardián Inteligente OBSC",
            "Acción Unificada para la Seguridad",
            "Cultivos para la Paz",
            "Convivencia Armónica 360"
        ],
        "componentes": [
            "Prevención", "Convivencia", "Sustitución", "Operatividad", "Acción Unificada"
        ]
    },

    # ─── TRANSFORMACIONES ────────────────────────────────────────────────────

    # s34: Índice de Pobreza Multidimensional (IPM) 2024–2025 — DANE
    "ipm": {
        "anos": ["2024", "2025"],
        "filas": [
            {
                "nombre": "IPM total",
                "narino": [18.1, 13.2], "nacional": [11.5, 9.9],
                "dpp_narino": -4.9
            },
            {
                "nombre": "IPM urbano (cabeceras)",
                "narino": [10.0, 8.2], "nacional": [7.8, 6.3],
                "dpp_narino": -1.8
            },
            {
                "nombre": "IPM rural",
                "narino": [24.5, 17.2], "nacional": [24.3, 22.4],
                "dpp_narino": -7.3
            }
        ],
        "nota": "Rural de Nariño más bajo que el promedio nacional."
    },

    # s35: Indicadores sociales 2024–2025 — DANE
    "indicadores_sociales": [
        {"nombre": "Analfabetismo",               "de": 14.70, "a": 8.8,  "impacto": "−5,9 p.p. · 76.000 personas"},
        {"nombre": "Bajo logro educativo",        "de": 60.50, "a": 55.50, "impacto": "−5 p.p."},
        {"nombre": "Rezago escolar",              "de": 22.4,  "a": 18.2,  "impacto": "−4,2 p.p."},
        {"nombre": "Sin acceso a agua mejorada",  "de": 23.0,  "a": 20.10, "impacto": "−2,9 p.p."},
        {"nombre": "Material inadecuado de pisos","de":  4.0,  "a": 1.8,  "impacto": "−2,2 p.p."}
    ],

    # s36: Desocupación y PIB (2024–2025) — DANE
    "desocupacion": {
        "narino":   [6.5, 6.0],
        "nacional": [9,   8],
        "anos":     ["2024", "2025"],
        "nota":     "Desocupación más baja del país."
    },
    "pib": {
        "anos": ["2024", "2025"],
        "sectores": [
            {"nombre": "Agricultura, ganadería, caza, silvicultura y pesca", "de": 26130, "a": 28593},
            {"nombre": "Explotación de minas y canteras",                     "de":  5185, "a":  5852},
            {"nombre": "Industrias manufactureras",                           "de":   217, "a":   243},
            {"nombre": "Comercio y reparación de vehículos",                 "de":   525, "a":   569}
        ],
        "nota": "PIB a precios corrientes, miles de millones de pesos."
    }
}

MUNICIPIOS_64 = None  # se leen del lookup.json en data/topo
