/**
 * Suite PAZ · Native PAZ module renderer.
 *
 * Exposes SPZ.modules.render(el, payload) — dispatches on payload.modulo:
 *   kpi      → count-up animation + optional delta badge
 *   compare  → before→after values with percentage delta
 *   timeline → ordered list of hitos (events)
 *   logro    → achievement card
 *
 * "bajar = bueno" rule (bajar = green): a negative delta means improvement.
 * Respects prefers-reduced-motion: skips the count-up animation.
 *
 * Called by frontend.js after fetching the module payload from REST or
 * a local JSON file (harness mode).
 *
 * @package SuitePaz
 */
window.SPZ = window.SPZ || {};
SPZ.modules = (function(){
  const fmt = n => (n==null?'—':Number(n).toLocaleString('es-CO'));
  const reduced = () => matchMedia('(prefers-reduced-motion:reduce)').matches;
  function countUp(el,to){ if(reduced()){el.textContent=fmt(to);return;} const t0=performance.now();
    (function step(t){const p=Math.min(1,(t-t0)/1000);el.textContent=fmt(Math.round(to*(1-Math.pow(1-p,3))));
      if(p<1)requestAnimationFrame(step);})(performance.now()); }
  function deltaTag(d){const good=d<=0;const a=d<0?'▼':(d>0?'▲':'—');
    return `<span class="spz-delta ${good?'good':'bad'}">${a} ${Math.abs(d).toLocaleString('es-CO')}%</span>`;}
  function kpi(el,d){el.innerHTML=`<div class="spz-kpi"><span class="spz-kpi__k">${d.titulo}</span>
    <b class="spz-kpi__v" data-cu="${d.valor}">0</b><span class="spz-kpi__u">${d.unidad||''}</span>
    <small>${d.leyenda||''}</small></div>`; countUp(el.querySelector('[data-cu]'),d.valor);}
  function compare(el,d){el.innerHTML=`<div class="spz-compare"><h4>${d.titulo}</h4>
    <div class="spz-compare__row"><div><small>${d.from.y}</small><b>${fmt(d.from.v)}</b></div><span>→</span>
    <div><small>${d.to.y}</small><b>${fmt(d.to.v)}</b></div></div>${deltaTag(d.delta)}
    <div class="spz-compare__u">${d.unidad||''}</div>${d.fuente?`<p class="spz-src">Fuente: ${d.fuente}</p>`:''}</div>`;}
  function timeline(el,d){el.innerHTML=`<div class="spz-timeline"><h4>${d.titulo}</h4>
    ${d.total?`<span class="spz-timeline__k">${d.total} acuerdos</span>`:''}
    <ol>${d.eventos.map(e=>`<li><time>${e.fecha}</time><p>${e.texto}</p></li>`).join('')}</ol></div>`;}
  function logro(el,d){el.innerHTML=`<div class="spz-logro"><h4>${d.titulo}</h4><p>${d.texto}</p></div>`;}
  const R={kpi,compare,timeline,logro};
  return { render(el,payload){ const fn=R[payload.modulo]; if(fn) fn(el,payload);
    else el.innerHTML='<em>Módulo no soportado</em>'; } };
})();
