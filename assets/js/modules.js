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
  const esc = s => String(s==null?'':s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const fmt = n => (n==null?'—':Number(n).toLocaleString('es-CO'));
  const reduced = () => matchMedia('(prefers-reduced-motion:reduce)').matches;
  function countUp(el,to){ if(reduced()){el.textContent=fmt(to);return;} const t0=performance.now();
    (function step(t){const p=Math.min(1,(t-t0)/1000);el.textContent=fmt(Math.round(to*(1-Math.pow(1-p,3))));
      if(p<1)requestAnimationFrame(step);})(performance.now()); }
  function deltaTag(d){const good=d<=0;const a=d<0?'▼':(d>0?'▲':'—');
    return `<span class="spz-delta ${good?'good':'bad'}">${a} ${Math.abs(d).toLocaleString('es-CO')}%</span>`;}
  function kpi(el,d){el.innerHTML=`<div class="spz-kpi"><span class="spz-kpi__k">${esc(d.titulo)}</span>
    <b class="spz-kpi__v" data-cu="${d.valor}">0</b><span class="spz-kpi__u">${esc(d.unidad||'')}</span>
    <small>${esc(d.leyenda||'')}</small></div>`; countUp(el.querySelector('[data-cu]'),d.valor);}
  function compare(el,d){el.innerHTML=`<div class="spz-compare"><h4>${esc(d.titulo)}</h4>
    <div class="spz-compare__row"><div><small>${esc(d.from.y)}</small><b>${fmt(d.from.v)}</b></div><span>→</span>
    <div><small>${esc(d.to.y)}</small><b>${fmt(d.to.v)}</b></div></div>${deltaTag(d.delta)}
    <div class="spz-compare__u">${esc(d.unidad||'')}</div>${d.fuente?`<p class="spz-src">Fuente: ${esc(d.fuente)}</p>`:''}</div>`;}
  function timeline(el,d){el.innerHTML=`<div class="spz-timeline"><h4>${esc(d.titulo)}</h4>
    ${d.total?`<span class="spz-timeline__k">${d.total} acuerdos</span>`:''}
    <ol>${d.eventos.map(e=>`<li><time>${esc(e.fecha)}</time><p>${esc(e.texto)}</p></li>`).join('')}</ol></div>`;}
  function logro(el,d){el.innerHTML=`<div class="spz-logro"><h4>${esc(d.titulo)}</h4><p>${esc(d.texto)}</p></div>`;}
  function diagrama(el,d){
    var html='<div class="spz-diagrama">';
    html+='<div class="spz-diagrama__centro">'+esc(d.centro||'')+'</div>';
    html+='<ul class="spz-diagrama__ramas">';
    (d.ramas||[]).forEach(function(r){
      html+='<li class="spz-diagrama__rama">';
      html+='<span class="spz-diagrama__nombre">'+esc(r.nombre||'')+'</span>';
      if(r.kpi){html+='<span class="spz-diagrama__kpi">'+esc(r.kpi)+'</span>';}
      if(r.sub&&r.sub.length){
        html+='<ul class="spz-diagrama__sub">';
        r.sub.forEach(function(s){html+='<li>'+esc(s)+'</li>';});
        html+='</ul>';
      }
      html+='</li>';
    });
    html+='</ul></div>';
    el.innerHTML=html;
  }
  function estrategia(el,d){
    var html='<div class="spz-estrategia">';
    if(d.descripcion){html+='<p class="spz-estrategia__desc">'+esc(d.descripcion)+'</p>';}
    if(d.lineas&&d.lineas.length){
      html+='<ol class="spz-estrategia__lineas">';
      d.lineas.forEach(function(l){html+='<li>'+esc(l)+'</li>';});
      html+='</ol>';
    }
    if(d.comunicaciones&&d.comunicaciones.length){
      html+='<div class="spz-estrategia__comms">';
      d.comunicaciones.forEach(function(c){html+='<span class="spz-chip">'+esc(c)+'</span>';});
      html+='</div>';
    }
    html+='</div>';
    el.innerHTML=html;
  }
  const R={kpi,compare,timeline,logro,diagrama,estrategia};

  // Build a plain-object rows array for the "Ver datos" panel per module type.
  function moduleDataForPanel(modulo,d){
    switch(modulo){
      case 'kpi':
        if(d.serie&&Array.isArray(d.serie))return d.serie;
        var kRows=[{campo:'Valor',valor:d.valor,unidad:d.unidad||''}];
        if(d.leyenda)kRows.push({campo:'Leyenda',valor:d.leyenda});
        return kRows;
      case 'compare':
        return[
          {período:String(d.from&&d.from.y||''),valor:d.from&&d.from.v},
          {período:String(d.to&&d.to.y||''),valor:d.to&&d.to.v}
        ];
      case 'timeline':
        return(d.eventos||[]).map(function(e){return{fecha:e.fecha,texto:e.texto};});
      case 'logro':
        return[{titulo:d.titulo,texto:d.texto}];
      case 'diagrama':
        return(d.ramas||[]).map(function(r){
          return{nombre:r.nombre||'',kpi:r.kpi||'',sub:(r.sub||[]).join(', ')};
        });
      case 'estrategia':
        return(d.lineas||[]).map(function(l,i){return{'#':i+1,línea:l};});
      default:return[];
    }
  }

  return { render(el,payload){ const fn=R[payload.modulo]; if(fn){
    fn(el,payload);
    if(window.SPZ&&window.SPZ.util&&typeof window.SPZ.util.attachVerDatos==='function'){
      window.SPZ.util.attachVerDatos(el,moduleDataForPanel(payload.modulo,payload),{
        title:payload.titulo||String(payload.modulo||'Módulo'),
        fuente:payload.fuente||'',
        descripcion:payload.descripcion||''
      });
    }
  } else el.innerHTML='<p class="spz-empty">Módulo no soportado: '+esc(String(payload.modulo||'?'))+'</p>'; } };
})();
