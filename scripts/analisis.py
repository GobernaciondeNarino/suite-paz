#!/usr/bin/env python3
"""analisis.py — Párrafos ciudadanos (~594 caracteres) para los 34 elementos de Suite PAZ.

Importado por build-views.py para inyectar obj['analisis'] en cada vista/módulo.
Claves: slug exacto del archivo JSON (sin extensión).
"""

ANALISIS = {

    # ── DNI ──────────────────────────────────────────────────────────────────

    "desplazamiento-anual": (
        "El desplazamiento forzado obliga a familias enteras a abandonar su hogar, "
        "sus tierras y su comunidad de un momento a otro. En Nariño, 25.344 personas "
        "fueron desplazadas en 2023; en 2024 esa cifra bajó a 17.441, una reducción "
        "del 31,1%. Eso significa que cerca de 8.000 personas pudieron quedarse en "
        "sus territorios en lugar de huir. Cada familia que no se desplaza conserva "
        "su sustento, sus vínculos comunitarios y su dignidad. Esta mejora refleja "
        "los acuerdos de paz, la presencia institucional y el trabajo conjunto entre "
        "la Gobernación, la fuerza pública y las comunidades de Nariño."
    ),

    "desplazamiento-interanual": (
        "El desplazamiento forzado es una de las heridas más profundas del conflicto. "
        "Entre 2024 y 2025, las personas desplazadas en Nariño bajaron de 17.441 a "
        "4.227, una reducción del 75,8%. Más de 13.000 personas evitaron tener que "
        "abandonar sus hogares en un solo año. Esta caída tan pronunciada refleja los "
        "procesos de paz, los acuerdos con grupos armados y la articulación "
        "institucional liderada desde la Gobernación. Menos desplazamiento significa "
        "comunidades más estables, familias que conservan sus cultivos y niños que "
        "continúan en su escuela sin que la violencia interrumpa su vida cotidiana."
    ),

    "confinamiento-anual": (
        "El confinamiento forzado ocurre cuando un grupo armado impide que las "
        "personas salgan de su territorio, cortando el acceso a alimentos, salud y "
        "trabajo. En Nariño, 6.772 personas vivieron esa situación en 2023; en 2024 "
        "la cifra bajó a 3.999, una reducción del 40,9%. Aunque el número sigue "
        "siendo alto, la mejora es concreta: más de 2.700 personas recuperaron la "
        "posibilidad de moverse, llevar a sus hijos al colegio y acceder a servicios "
        "de salud sin el miedo que impone el confinamiento forzado. Cada persona que "
        "sale del encierro recupera su dignidad y su libertad de vivir."
    ),

    "minas-narino": (
        "Las minas antipersonal amenazan la vida de las comunidades rurales de "
        "Nariño: impiden cultivar, caminar libremente y acceder a servicios básicos. "
        "En 2023, 42 civiles resultaron afectados por minas en el departamento; en "
        "2024 esa cifra bajó a 26, una reducción del 38,1%. Cada caso sigue siendo "
        "una tragedia, pero la tendencia a la baja es resultado del desminado "
        "humanitario, los acuerdos de paz y la educación comunitaria sobre el riesgo. "
        "Cada persona que no resulta herida es una familia que se mantiene unida y "
        "un territorio que avanza hacia la normalidad y la paz en Nariño."
    ),

    "minas-interanual": (
        "Las minas antipersonal siembran terror en las comunidades rurales: un paso "
        "en falso puede costar una pierna, la vida o la infancia de un niño. En "
        "Nariño, el número de civiles afectados bajó de 26 en 2024 a 6 en 2025, una "
        "reducción del 76,9%. Esto demuestra que la acción combinada del desminado "
        "humanitario, los procesos de paz y la educación en gestión del riesgo está "
        "dando resultados concretos en el territorio. Cada civil que no resulta "
        "herido por una mina es una persona que conserva su integridad, su capacidad "
        "de trabajo y su posibilidad de vivir sin miedo en su propio territorio."
    ),

    "minas-narino-parcial": (
        "Este indicador compara los civiles afectados por minas antipersonal en "
        "Nariño entre 2025 y el primer semestre de 2026: en ambos períodos la cifra "
        "es 6, una variación del 0%. Aunque no hay reducción, tampoco hay aumento, "
        "y los niveles son mucho menores que en años anteriores (42 en 2023 y 26 en "
        "2024). El desminado humanitario sigue activo en municipios como Cumbal, "
        "Mallama y Samaniego. La meta es continuar reduciendo esta cifra hasta que "
        "ninguna persona de Nariño resulte herida por un artefacto explosivo dejado "
        "por el conflicto armado en el territorio departamental."
    ),

    "firmantes-100": (
        "Los firmantes de paz son personas que dejaron las armas y se comprometieron "
        "a construir una vida en la legalidad. Protegerlos es esencial para que el "
        "proceso de paz sea real y sostenible. En 2023 se registraron 2 homicidios "
        "de firmantes en Nariño; desde 2024 esa cifra es cero, una reducción del "
        "100%. Que ningún firmante haya sido asesinado en 2024, 2025 ni en lo que "
        "va de 2026 significa que el Estado cumple su compromiso con quienes "
        "apostaron por la paz. Eso fortalece la confianza en el proceso y abre "
        "camino para que más personas elijan la vida civil y construyan la paz."
    ),

    "confinamiento-fcs-100": (
        "En 2023, 6.772 personas en la zona del Frente Comuneros del Sur vivían "
        "confinadas: atrapadas en sus territorios por la presencia armada, sin poder "
        "salir con libertad. A partir de 2024, gracias al proceso de paz con ese "
        "frente, ese número cayó a cero y se ha mantenido en cero en 2025 y 2026. "
        "Una reducción del 100% significa que esas comunidades recuperaron "
        "completamente su libertad de movimiento: pueden ir al mercado, al médico y "
        "a la escuela sin el control armado que antes las limitaba. Ese es el impacto "
        "humano más directo y tangible de los acuerdos de paz en Nariño."
    ),

    "cneb-desplazamiento": (
        "En la zona de influencia de la Coordinadora Nacional Ejército Bolivariano "
        "(CNEB), las personas desplazadas cayeron de 15.697 en 2023 a 12.611 en "
        "2024, luego a 2.973 en 2025 y a 441 en lo que va de 2026. Esa reducción "
        "del 97% en tres años muestra que las negociaciones y la presencia "
        "institucional están funcionando: miles de familias nariñenses pudieron "
        "quedarse en sus territorios o regresar a ellos. Menos desplazamiento "
        "significa estabilidad, arraigo y esperanza de vida digna para comunidades "
        "que antes vivían bajo la presión constante del conflicto armado en Nariño."
    ),

    "cneb-confinamiento": (
        "El confinamiento forzado impide que las personas salgan de sus territorios, "
        "cortando el acceso a alimentos, salud y trabajo. En la zona de influencia "
        "de la CNEB, el número de personas confinadas cayó de 2.787 en 2023 a 286 "
        "en 2024, luego a 142 en 2025 y a 0 en lo que va de 2026. Esta reducción "
        "al 100% en 2026 significa que las comunidades de esa zona recuperaron su "
        "libertad de movimiento: pueden acceder a servicios y retomar su vida "
        "cotidiana sin el control armado. Es un avance concreto y medible que "
        "refleja el impacto directo de los procesos de paz en el territorio nariñense."
    ),

    "coordinadora-desplazamiento": (
        "El desplazamiento forzado priva a las familias de su hogar, sus cultivos y "
        "sus raíces. En la zona de la CNEB, entre enero y junio de 2025 hubo 1.036 "
        "personas desplazadas; en el mismo período de 2026 esa cifra bajó a 329, "
        "una reducción del 68,3%. Eso significa que cerca de 700 personas evitaron "
        "tener que huir de sus hogares. Cada familia que no se desplaza conserva su "
        "sustento, sus vínculos comunitarios y su proyecto de vida en Nariño. Este "
        "resultado refleja el trabajo sostenido de negociación y la presencia "
        "institucional en los territorios más afectados del departamento."
    ),

    "coordinadora-confinamiento": (
        "Entre enero y junio de 2025 todavía 142 personas vivían confinadas en zonas "
        "bajo influencia de la Coordinadora Guerrillera Simón Bolívar (CGSB). En el "
        "mismo período de 2026 esa cifra cayó a cero, una reducción del 100%. El "
        "confinamiento priva a las familias del acceso al mercado, la salud y la "
        "educación. Que en 2026 no se registre ningún caso en esas zonas es una "
        "señal directa de que las negociaciones y la presencia institucional generan "
        "resultados reales: comunidades que antes vivían bajo control armado hoy se "
        "mueven con plena libertad por sus territorios de Nariño."
    ),

    "rutas-nna": (
        "Las rutas de prevención son el mecanismo que activa el Estado cuando detecta "
        "a un menor en riesgo de ser reclutado por un grupo armado. El Alto "
        "Comisionado de Reincorporación reportó 25 activaciones en 2023, 17 en 2024 "
        "y 7 en 2025. En los primeros meses de 2026 ya se han atendido 7 casos. La "
        "tendencia a la baja indica que el entorno de seguridad mejora y que hay "
        "menos menores en riesgo inmediato. Cada ruta activada a tiempo es un niño "
        "que evita caer en el conflicto y tiene la oportunidad de crecer en paz, con "
        "acceso a educación y un proyecto de vida en Nariño."
    ),

    "desminado-municipios": (
        "Las minas antipersonal contaminan el territorio e impiden que las "
        "comunidades usen sus tierras con seguridad. En Nariño, cinco municipios "
        "tienen procesos activos de desminado humanitario: Cumbal, La Llanada, "
        "Mallama, Samaniego y Santacruz. San Pablo ya fue declarado libre de minas "
        "como resultado de estos esfuerzos. Limpiar el suelo de minas significa que "
        "las personas pueden caminar, trabajar y cultivar sin riesgo de resultar "
        "heridas. Cada municipio libre de minas es un territorio devuelto a sus "
        "habitantes para que lo usen con plena libertad y construyan un futuro sin "
        "el peligro de la guerra."
    ),

    "desaparecidos-cuerpos": (
        "La Unidad de Búsqueda de Personas dadas por Desaparecidas (UBPD) trabaja "
        "para encontrar y entregar digna sepultura a quienes el conflicto arrebató "
        "a sus familias. Entre 2025 y 2026 se recuperaron 39 cuerpos en Nariño: "
        "13 en Cumbal, 12 en Samaniego, 12 en Santacruz y 2 en La Llanada. De "
        "ellos, 14 tienen identidad confirmada y 6 recibieron una entrega digna. Se "
        "identificaron 26 lugares de interés para continuar la búsqueda. Cada cuerpo "
        "encontrado permite que una familia cierre una herida abierta de años y "
        "encuentre algo de paz después de tanta incertidumbre y dolor."
    ),

    "nna-desvinculacion": (
        "Este indicador cuenta los niños, niñas y adolescentes separados de grupos "
        "armados que iniciaron un proceso de reintegración a la vida civil. En "
        "Nariño, el ICBF atendió 38 casos en 2023, 35 en 2024 y 27 en 2025. En "
        "total, 428 NNA han pasado por el programa, y en los primeros meses de 2026 "
        "ya hay 9 casos nuevos. Que el número anual baje es una buena señal: menos "
        "menores están siendo reclutados. Pero cada caso sigue siendo una vida "
        "interrumpida; acompañar a estos jóvenes es una obligación del Estado "
        "colombiano y una apuesta por el futuro de las comunidades nariñenses."
    ),

    "acuerdos-fcs": (
        "Este cronograma muestra los 12 acuerdos alcanzados entre la Gobernación de "
        "Nariño y el Frente Comuneros del Sur entre septiembre de 2024 y junio de "
        "2025. Desde la instalación y los primeros compromisos en septiembre de 2024, "
        "se construyeron acuerdos mes a mes hasta junio de 2025. Cada acuerdo "
        "representa un paso concreto hacia la paz: menos violencia, más protección "
        "para las comunidades y la posibilidad real de que personas vinculadas a ese "
        "grupo se reintegren a la vida civil. Este proceso es uno de los más "
        "relevantes para la seguridad en el suroccidente del departamento."
    ),

    "acuerdos-cneb": (
        "Este calendario muestra el avance de los diálogos entre la Gobernación de "
        "Nariño y la Coordinadora Nacional Ejército Bolivariano. Desde la instalación "
        "de mesas en enero de 2025 se avanzó en protocolos (febrero), un cese (mayo), "
        "confirmación de avances (julio) y acuerdos formales (diciembre). En 2026 se "
        "registraron nuevos avances en marzo y la continuidad del proceso en junio. "
        "Cada paso representa una negociación compleja que, cuando prospera, significa "
        "menos violencia y más seguridad para las comunidades que viven en zonas "
        "afectadas por este grupo armado en Nariño."
    ),

    "san-pablo-libre-minas": (
        "San Pablo es el primer municipio de Nariño declarado libre de minas "
        "antipersonal en el marco del proceso de paz con el Frente Comuneros del Sur. "
        "Este logro significa que los habitantes de San Pablo pueden caminar, cultivar "
        "y moverse por todo su territorio sin el riesgo de pisar un artefacto "
        "explosivo. La declaratoria elimina un peligro físico real, devuelve la "
        "confianza, reactiva la economía local y permite el regreso de familias "
        "desplazadas. Es una prueba concreta de que los acuerdos de paz generan "
        "beneficios tangibles y que la paz vale la pena para Nariño."
    ),

    # ── SEGURIDAD ─────────────────────────────────────────────────────────────

    "homicidios-municipio": (
        "Este mapa muestra la tasa de homicidio por cada 100.000 habitantes en los "
        "municipios de Nariño en 2025. Los municipios con mayores tasas son Samaniego "
        "(47,6; 14 casos), San Andrés de Tumaco (14,6; 40 casos), Barbacoas (17,1; "
        "11 casos), El Charco (13,4; 3 casos) y Pasto (9,7; 39 casos). La gran "
        "mayoría de los 64 municipios no reportan casos. Samaniego bajó de 98,6 en "
        "2023 a 47,6 en 2025, un avance notable aunque persiste como la cifra más "
        "alta del departamento. Reducir estos focos es la prioridad para que todos "
        "los nariñenses vivan con el mismo derecho a la seguridad."
    ),

    "hist-homicidios-gob": (
        "Este gráfico muestra cómo la tasa de homicidio en Colombia cambió a lo "
        "largo de distintos gobiernos. En los años noventa era extrema: 73 con "
        "Gaviria, 80 con Samper y 84 con Pastrana. Luego bajó: Uribe II (72), Santos "
        "II (45), Duque (36). Con el gobierno Petro la tasa es 26,1 por cada 100.000 "
        "habitantes en 2025, la más baja de la historia reciente. Para Nariño esto "
        "es relevante porque el departamento comparte esa tendencia: su tasa propia "
        "es 14,7 en 2025, resultado del trabajo conjunto entre el gobierno "
        "departamental, la fuerza pública y las comunidades de Nariño."
    ),

    "terrorismo-nacional-narino": (
        "Las acciones terroristas incluyen atentados, emboscadas y hostigamientos que "
        "generan terror en las comunidades. En Colombia estas acciones aumentaron de "
        "839 en 2022 a 1.398 en 2024. Nariño va en dirección contraria: bajó de 83 "
        "acciones en 2022 a 39 en 2023 y a 31 en 2024, una reducción del 63%. "
        "Mientras otros departamentos como Valle del Cauca pasaron de 57 a 193 "
        "acciones, Nariño redujo este tipo de violencia de forma sostenida. Para la "
        "gente del departamento esto significa menos miedo, menos daño a la "
        "infraestructura y más posibilidades de vida cotidiana en paz."
    ),

    "fuerza-publica": (
        "Este indicador registra policías y militares asesinados en el conflicto. En "
        "Nariño las bajas de policías fueron 1 en 2023, 1 en 2024 y 0 en 2025; los "
        "militares registraron 5 en 2023, 1 en 2024 y 2 en 2025. A nivel nacional "
        "la situación empeoró: los policías subieron de 30 a 96 y los militares de "
        "54 a 96. Que Nariño mantenga cifras bajas mientras el país enfrenta un "
        "aumento del 220% en bajas de fuerza pública demuestra que la estrategia "
        "de seguridad del departamento está conteniendo el conflicto y protegiendo "
        "también a quienes tienen la misión de cuidar a la ciudadanía nariñense."
    ),

    "homicidios-departamental": (
        "Este indicador compara la tasa de homicidio de Nariño con otros "
        "departamentos del país. En 2023 Nariño tenía una tasa de 26,2 por 100.000 "
        "habitantes; en 2024 bajó a 21,0 y en 2025 llegó a 14,7, uno de los valores "
        "más bajos del país. Mientras Guaviare (67,3) y Valle del Cauca (55,4) "
        "mantienen tasas muy altas en 2025, Nariño logró una reducción sostenida "
        "durante tres años consecutivos. Esto significa que la probabilidad de que "
        "un nariñense pierda la vida por homicidio es cada año menor, lo que se "
        "traduce en familias más seguras y comunidades con más esperanza de futuro."
    ),

    "estructuras-armadas": (
        "Este panel muestra los grupos armados ilegales presentes en Nariño: la "
        "Coordinadora Nacional Ejército Bolivariano (CNEB), el Estado Mayor Central, "
        "el ELN, el Frente Comuneros del Sur y las Autodefensas Unidas de Nariño. "
        "La CNEB es el grupo más numeroso, con 3.200 combatientes, de los cuales "
        "2.000 (el 62,5%) están vinculados a procesos de paz. Conocer la estructura "
        "del conflicto es el primer paso para abordarlo: con esta información la "
        "Gobernación diseña estrategias de negociación y seguridad para proteger a "
        "las comunidades y reducir la violencia en el departamento nariñense."
    ),

    # ── CONVIVENCIA ───────────────────────────────────────────────────────────

    "convivencia": (
        "Este indicador mide tres formas de violencia que afectan a familias y "
        "mujeres en Nariño: violencia intrafamiliar, lesiones personales y "
        "feminicidio. En 2025, la violencia intrafamiliar registró 3.284 casos (tasa "
        "191,6 por 100.000 hab.), menos que en 2024 (3.339; tasa 195,9). Las "
        "lesiones personales bajaron de 2.585 a 2.411 casos. Los feminicidios "
        "pasaron de 5 en 2023 a 8 en 2024 y volvieron a 5 en 2025. Que estas cifras "
        "disminuyan indica que la prevención y la atención temprana funcionan; "
        "reducirlas más significa hogares más seguros y menos sufrimiento para las "
        "familias de Nariño."
    ),

    "hurtos": (
        "Este indicador muestra los robos registrados en Nariño frente al total "
        "nacional. El hurto a residencias en 2024 fue de 1.329 casos en Nariño, "
        "bajando a 517 en 2025. El hurto a comercio registró 517 casos en el "
        "departamento en 2025 (nacional: 24.406). Los datos de hurto a automotores "
        "y motocicletas están en actualización en la fuente oficial. Que el hurto a "
        "residencias se reduzca a menos de la mitad entre 2024 y 2025 es una buena "
        "noticia: más familias guardan su patrimonio y se sienten seguras en sus "
        "hogares, resultado de la articulación entre comunidades e instituciones."
    ),

    "hallazgos-clave": (
        "Este panel resume los hallazgos más importantes de la gestión de seguridad "
        "pública en Nariño. La clave ha sido la coordinación entre la Gobernación, "
        "la fuerza pública y las comunidades: juntos lograron reducir la violencia "
        "sin necesidad de una escalada militar. La prevención social y la atención "
        "oportuna a las víctimas redujeron la violencia intrafamiliar y las lesiones. "
        "La articulación entre instituciones sostuvo la caída del hurto. El eje "
        "Nariño 360° integra metodología e instituciones para que los indicadores de "
        "seguridad sigan mejorando y la gente viva con más tranquilidad."
    ),

    # ── ESTRATEGIA ────────────────────────────────────────────────────────────

    "subsecretaria": (
        "La Subsecretaría de Seguridad Ciudadana coordina la estrategia de seguridad "
        "de la Gobernación en Nariño. Su esquema 2026 integra siete frentes: "
        "elecciones seguras, el Observatorio de Seguridad (con fortalecimiento "
        "técnico y analítica prospectiva), el incremento del pie de fuerza hasta "
        "19.013 policías en el período 2023-2026, la acción unificada con 80 "
        "instituciones bajo Nariño 360°, el FONSET (tecnología, movilidad e "
        "investigación judicial), el proyecto estratégico de Taminango y las mesas "
        "operativas locales. Esta estructura garantiza que cada municipio reciba "
        "atención coordinada y eficiente."
    ),

    "narino-360": (
        "Nariño 360° es la estrategia integral de la Gobernación para construir "
        "seguridad, convivencia y paz en el departamento. Articula cinco líneas de "
        "acción: Fortalecimiento Institucional, Guardián Inteligente (Observatorio "
        "de Seguridad Ciudadana), Acción Unificada para la Seguridad, Cultivos para "
        "la Paz y Convivencia Armónica 360. Participan el territorio, las "
        "comunidades, las instituciones, la fuerza pública y actores privados e "
        "internacionales. Esa articulación es lo que hace sostenibles los avances: "
        "ninguna entidad sola puede lograrlo; se requiere trabajar juntos para "
        "que Nariño avance hacia la paz."
    ),

    # ── TRANSFORMACIONES ─────────────────────────────────────────────────────

    "ipm": (
        "El Índice de Pobreza Multidimensional (IPM) mide cuántas personas no tienen "
        "acceso a educación, salud, agua, vivienda o trabajo. En Nariño, el IPM "
        "total bajó de 18,1% en 2024 a 13,2% en 2025, una mejora de casi 5 puntos. "
        "El IPM rural cayó de 24,5% a 17,2%, por debajo del promedio nacional de "
        "22,4%. Eso significa que las comunidades rurales de Nariño mejoraron su "
        "acceso a servicios básicos más que el promedio del país. Una reducción del "
        "IPM es una señal clara de que la gente vive mejor: con más oportunidades, "
        "más dignidad y más herramientas para construir la paz."
    ),

    "indicadores-sociales": (
        "Estos indicadores muestran cómo mejoró la calidad de vida en Nariño entre "
        "2024 y 2025. El analfabetismo bajó de 14,7% a 8,8%, lo que equivale a "
        "76.000 personas que ahora saben leer y escribir. El bajo logro educativo "
        "cayó 5 puntos (de 60,5% a 55,5%) y el rezago escolar pasó de 22,4% a "
        "18,2%. El porcentaje de hogares sin agua mejorada bajó de 23,0% a 20,1% y "
        "los pisos inadecuados de 4,0% a 1,8%. Cada uno de estos avances representa "
        "mejores condiciones para miles de familias: educación, agua limpia y "
        "vivienda digna son las bases más esenciales de la paz duradera."
    ),

    "desocupacion": (
        "La tasa de desocupación mide el porcentaje de personas que quieren trabajar "
        "pero no encuentran empleo. En Nariño, esa tasa bajó de 6,5% en 2024 a 6,0% "
        "en 2025, mientras el promedio nacional es 8%. Nariño tiene así la tasa de "
        "desempleo más baja del país. Que el desempleo sea bajo significa que más "
        "familias tienen ingresos, pueden pagar la educación de sus hijos y acceder "
        "a la salud. Este resultado es fruto de la estabilización de la seguridad, "
        "el impulso a los sectores productivos y las políticas de empleo que se han "
        "fortalecido en el departamento en los últimos años."
    ),

    "pib": (
        "El Producto Interno Bruto (PIB) muestra cuánto produce la economía de "
        "Nariño. Entre 2024 y 2025 el sector agropecuario creció de 26.130 a 28.593 "
        "miles de millones de pesos, consolidándose como el motor principal del "
        "departamento. La minería subió de 5.185 a 5.852, la manufactura de 217 a "
        "243 y el comercio de 525 a 569. Que la agricultura crezca es especialmente "
        "importante: significa más empleo rural, más ingresos para los campesinos y "
        "mejores condiciones en los municipios. Una economía rural fuerte es también "
        "una de las bases más sólidas para la paz territorial."
    ),

}
