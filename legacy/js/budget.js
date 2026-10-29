(function(){
  var sec=document.getElementById('budget'); if(!sec) return;
  var arcs=sec.querySelectorAll('#donut .arc'), rows=sec.querySelectorAll('.lg-row');
  var c1=sec.querySelector('.donut-c1'), c2=sec.querySelector('.donut-c2');
  var defC1=c1?c1.textContent:'', defC2=c2?c2.textContent:'';
  function dataFor(i){
    var row=sec.querySelector('.lg-row[data-i="'+i+'"]'); if(!row) return null;
    var nm=row.querySelector('.lg-name'), val=row.querySelector('.lg-val'), pct='';
    if(val){var parts=val.textContent.split('·'); pct=(parts[parts.length-1]||'').trim();}
    return {name:nm?nm.textContent:'', pct:pct};
  }
  function hi(i,on){
    arcs.forEach(function(a){var k=a.dataset.i===String(i);a.classList.toggle('pop',on&&k);a.classList.toggle('dim',on&&!k);});
    rows.forEach(function(r){r.classList.toggle('hl',on&&r.dataset.i===String(i));});
    if(c1&&c2){ if(on){var d=dataFor(i); if(d){c1.textContent=d.pct; c2.textContent=d.name;}}
                else {c1.textContent=defC1; c2.textContent=defC2;} }
  }
  arcs.forEach(function(a){a.addEventListener('mouseenter',function(){hi(a.dataset.i,true);});a.addEventListener('mouseleave',function(){hi(a.dataset.i,false);});});
  rows.forEach(function(r){r.addEventListener('mouseenter',function(){hi(r.dataset.i,true);});r.addEventListener('mouseleave',function(){hi(r.dataset.i,false);});});
  // draw-in animation (robuste + filet de sécurité au téléchargement)
  var played=false;
  function play(){ if(played)return; played=true;
    arcs.forEach(function(a,k){a.style.opacity=0;setTimeout(function(){a.style.transition='opacity .5s';a.style.opacity=1;},120*k);}); }
  try{ var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){play();io.disconnect();}});},{threshold:.25}); io.observe(sec); }catch(e){ play(); }
  setTimeout(function(){ if(!played) play(); arcs.forEach(function(a){a.style.opacity=1;}); },1800);
})();
