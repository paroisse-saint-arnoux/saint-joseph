// Safety net: never surface a raw "Script error" banner to visitors.
  window.addEventListener('error', function(e){ if(e){ e.preventDefault&&e.preventDefault(); } return true; });
  window.addEventListener('unhandledrejection', function(e){ if(e){ e.preventDefault&&e.preventDefault(); } });
  const header=document.getElementById('header');
  addEventListener('scroll',()=>header.classList.toggle('scrolled',scrollY>40));
  const burger=document.getElementById('burger'),menu=document.getElementById('menu');
  burger.addEventListener('click',()=>menu.classList.toggle('open'));

  /* ============================================================
     FICHES ÉQUIPE — contenu par personne (À COMPLÉTER LIBREMENT)
     Pour chaque personne : "role" (sous le nom) et "bio" (texte).
     Laissez "" si vous ne voulez rien afficher pour ce champ.
     Utilisez \n pour faire un saut de ligne dans la bio.
     La clé (à gauche) doit rester identique à celle de la photo.
     ============================================================ */
  const MEMBRES = (window.SJO2 && window.SJO2.membres) || {};

  (function(){
    const modal=document.getElementById('memberModal');
    if(!modal) return;
    const mImg=document.getElementById('mImg'), mName=document.getElementById('mName'),
          mRole=document.getElementById('mRole'), mBio=document.getElementById('mBio'),
          mClose=document.getElementById('mClose');
    let lastFocus=null;
    function open(member){
      const key=member.getAttribute('data-key');
      const name=member.querySelector('.nm').textContent;
      const img=member.querySelector('img').src;
      const data=MEMBRES[key]||{role:"",bio:""};
      mImg.src=img; mImg.alt=name; mName.textContent=name;
      mRole.textContent=data.role||""; mRole.style.display=data.role?'block':'none';
      mBio.textContent=data.bio||"Présentation à venir.";
      mBio.style.opacity=data.bio?'1':'.7';
      lastFocus=member; modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
      document.body.style.overflow='hidden'; mClose.focus();
    }
    function close(){
      modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');
      document.body.style.overflow=''; if(lastFocus) lastFocus.focus();
    }
    document.querySelectorAll('.member').forEach(m=>{
      m.addEventListener('click',()=>open(m));
      m.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();open(m);}});
    });
    mClose.addEventListener('click',close);
    modal.addEventListener('click',e=>{if(e.target===modal)close();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('open'))close();});
  })();

  // Smooth in-page navigation that works reliably on iOS Safari
  function goTo(id){
    try{
      const t=document.getElementById(id); if(!t) return;
      const y=t.getBoundingClientRect().top+(window.pageYOffset||document.documentElement.scrollTop||0)-78;
      if('scrollBehavior' in document.documentElement.style){
        window.scrollTo({top:y,behavior:'smooth'});
      }else{
        window.scrollTo(0,y); // older Safari fallback
      }
    }catch(err){
      const t=document.getElementById(id);
      if(t) t.scrollIntoView();
    }
  }
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click',e=>{
      try{
        const href=a.getAttribute('href')||'';
        const id=href.slice(1);
        if(!id) return;                 // plain "#" links (social placeholders) do nothing
        const t=document.getElementById(id);
        if(!t) return;
        e.preventDefault();
        if(menu) menu.classList.remove('open');  // close mobile menu if open
        goTo(id);
        if(history&&history.replaceState) history.replaceState(null,'','#'+id);
      }catch(err){/* never surface a script error to the user */}
    });
  });

  const io=new IntersectionObserver((es)=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}}),{threshold:.12});
  document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
  window.__revealReady=true;

  function fillCount(el, instant){
    if(el.dataset.done) return; el.dataset.done='1';
    const target=parseFloat(el.dataset.count)||0, dec=parseInt(el.dataset.dec||0);
    const unit=el.dataset.unit?' <small>'+el.dataset.unit+'</small>':'';
    const fmt=c=>(dec?c.toFixed(dec).replace('.',','):Math.round(c).toLocaleString('fr-FR'))+unit;
    if(instant){ el.innerHTML=fmt(target); return; }
    let c=0;const step=target/55;
    const run=()=>{c+=step;if(c>=target)c=target;el.innerHTML=fmt(c);if(c<target)requestAnimationFrame(run);};
    run();
  }
  const cio=new IntersectionObserver((es)=>es.forEach(e=>{
    if(!e.isIntersecting)return;
    fillCount(e.target,false); cio.unobserve(e.target);
  }),{threshold:.35});
  document.querySelectorAll('[data-count]').forEach(el=>cio.observe(el));
  // Filet de sécurité : si l'observateur ne s'est pas déclenché (certains
  // navigateurs mobiles / ouverture locale), on force l'affichage des valeurs.
  setTimeout(()=>{document.querySelectorAll('[data-count]').forEach(el=>{if(!el.dataset.done) fillCount(el,true);});},1800);

  const tiers=document.querySelectorAll('.tier'),freeAmt=document.getElementById('freeAmt'),ha=document.getElementById('helloasso'),freqBtns=document.querySelectorAll('.freq button');
  let amount=150,freq='ponctuel',dwho='part';
  function frEur(n){return Math.round(n).toLocaleString('fr-FR')+' €';}
  function upd(){
    var amt=parseInt(amount,10)||0;
    var dfA=document.getElementById('dfAmt'),dfR=document.getElementById('dfReal'),dfN=document.getElementById('dfNote');
    if(dfA){var rate=(dwho==='part')?0.66:0.60;
      dfA.textContent=frEur(amt);
      dfR.textContent=frEur(amt*(1-rate));
      dfN.textContent=(dwho==='part')?"après 66 % de réduction d'impôt — un reçu fiscal vous sera adressé":"après 60 % de réduction d'impôt — un reçu fiscal vous sera adressé";
    }
  }
  tiers.forEach(t=>t.addEventListener('click',()=>{tiers.forEach(x=>x.classList.remove('active'));t.classList.add('active');amount=t.dataset.amt;freeAmt.value='';upd();}));
  freeAmt.addEventListener('input',()=>{if(freeAmt.value){tiers.forEach(x=>x.classList.remove('active'));amount=freeAmt.value;upd();}});
  freqBtns.forEach(b=>b.addEventListener('click',()=>{freqBtns.forEach(x=>x.classList.remove('active'));b.classList.add('active');freq=b.dataset.freq;upd();}));
  document.querySelectorAll('.don-fiscal .sim-toggle button').forEach(function(btn){btn.addEventListener('click',function(){
    document.querySelectorAll('.don-fiscal .sim-toggle button').forEach(x=>x.classList.remove('on'));btn.classList.add('on');dwho=btn.getAttribute('data-who');upd();});});
  upd();

  // Bouton "Restons en contact" : remplacez l'URL ci-dessous par le lien de votre Google Form.
  // Tant qu'aucun lien n'est renseigné, le bouton mène à la rubrique "Faire un don".
  var CONTACT_FORM_URL = (window.SJO2 && window.SJO2.contactFormUrl) || "";
  (function(){
    var cb=document.getElementById('contactBtn'); if(!cb) return;
    if(CONTACT_FORM_URL){ cb.href=CONTACT_FORM_URL; }
    else{
      cb.removeAttribute('target');
      cb.setAttribute('href','#don');
    }
  })();
