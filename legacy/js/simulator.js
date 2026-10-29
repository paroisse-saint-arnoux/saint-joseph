(function(){
  var range=document.getElementById('simRange'); if(!range) return;
  var euro=document.getElementById('simEuro'), pEl=document.getElementById('simPierres'),
      mEl=document.getElementById('simMurs'), real=document.getElementById('simReal'),
      note=document.getElementById('simRealNote'), bricks=document.getElementById('simBricks'),
      sim=document.getElementById('simulator');
  var who='part';
  for(var i=0;i<50;i++){var b=document.createElement('i');b.className='sb';bricks.appendChild(b);}
  function fr(n){return Math.round(n).toLocaleString('fr-FR');}
  function upd(){
    var v=parseInt(range.value,10)||0;
    euro.textContent=fr(v);
    var p=Math.round(v/10), m=Math.floor(p/100);
    pEl.textContent=fr(p); mEl.textContent=fr(m);
    var rate=(who==='part')?0.66:0.60;
    real.textContent=fr(v*(1-rate))+' €';
    note.textContent=(who==='part')?"après 66 % de réduction d'impôt (particulier)":"après 60 % de réduction d'impôt (entreprise)";
    var lit=Math.min(50,Math.round(p/12)); // visual: ~ up to 600 pierres fills the row
    var nodes=bricks.children;
    for(var i=0;i<nodes.length;i++) nodes[i].classList.toggle('on', i<lit);
  }
  range.addEventListener('input',upd);
  var btns=document.querySelectorAll('.sim-toggle button');
  btns.forEach(function(btn){btn.addEventListener('click',function(){
    btns.forEach(function(x){x.classList.remove('on');}); btn.classList.add('on');
    who=btn.getAttribute('data-who'); upd();
  });});
  // remis à zéro quand on quitte le simulateur
  sim.addEventListener('mouseleave',function(){range.value=0;upd();});
  upd();
})();
