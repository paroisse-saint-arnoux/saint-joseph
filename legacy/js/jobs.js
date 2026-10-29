(function(){
  var modal=document.getElementById('jobModal'); if(!modal) return;
  var JOBS={
    "community-manager":{comm:"Commission Communication",title:"Community manager",
      mission:"Animer les réseaux sociaux du projet (Facebook, Instagram) : publier régulièrement l'avancement, les temps forts et les appels à la générosité, répondre aux messages et faire grandir une communauté engagée autour de Saint-Joseph.",
      profil:"Aisance avec les réseaux sociaux, sens de l'écriture courte et du visuel, créativité et régularité. Une première expérience (personnelle ou professionnelle) est un plus.",
      engagement:"Quelques heures par semaine, à distance, en lien avec la commission Communication."},
    "designer":{comm:"Commission Communication",title:"Designer / graphiste",
      mission:"Concevoir les visuels du projet — affiches, publications réseaux, livret donateurs, signalétique — dans le respect de l'identité graphique (navy & or) et de la sobriété du projet.",
      profil:"Maîtrise d'outils de création (Canva, Suite Adobe ou équivalent), sens de la mise en page et du détail.",
      engagement:"À la mission, selon les besoins de communication."},
    "data":{comm:"Commission Communication",title:"Spécialistes data (3 jeunes)",
      mission:"Structurer et suivre les données du projet : base de contacts et de donateurs, tableaux de suivi de la collecte, indicateurs et reporting simple et fiable.",
      profil:"Jeunes à l'aise avec les tableurs et la donnée, rigoureux et curieux. Idéal pour des étudiants en data, gestion ou informatique.",
      engagement:"En équipe (3 personnes), de façon ponctuelle et régulière, en lien avec les commissions Finance et Communication."},
    "referent-digital":{comm:"Commission Communication",title:"Référent digital & réseaux",
      mission:"Coordonner l'ensemble des actions digitales (site, réseaux, outils), garantir la cohérence des messages et accompagner les bénévoles du numérique.",
      profil:"Vision d'ensemble du digital, sens de l'organisation et goût de l'animation d'équipe.",
      engagement:"Rôle de coordination régulier, en lien direct avec la commission Communication."},
    "amb-b2b":{comm:"Ambassadeurs · En paroisse",title:"Ambassadeurs commerciaux (B2B)",
      mission:"Représenter le projet auprès des entreprises et mécènes du territoire, présenter les opportunités de mécénat (réduction d'impôt de 60 %) et nouer des partenariats.",
      profil:"Aisance relationnelle, goût du contact et sens de la conviction. Une expérience commerciale ou entrepreneuriale est un atout.",
      engagement:"Rendez-vous et relances, à votre rythme, avec le soutien de la commission."},
    "amb-jeunes":{comm:"Ambassadeurs · En paroisse",title:"Ambassadeurs jeunes & ados",
      mission:"Porter le projet auprès des jeunes et des familles, animer des temps de sensibilisation et de mobilisation, et donner envie de participer à l'aventure.",
      profil:"Jeunes dynamiques et enthousiastes, à l'aise pour parler en public et entraîner les autres.",
      engagement:"Selon vos disponibilités, en lien avec l'aumônerie et la pastorale."},
    "referent-amb":{comm:"Ambassadeurs · En paroisse",title:"Référent ambassadeurs",
      mission:"Recruter, animer et coordonner le réseau d'ambassadeurs (B2B et jeunes), suivre les contacts et les engagements, et faire le lien avec les commissions.",
      profil:"Sens de l'organisation et du management bienveillant, goût du collectif.",
      engagement:"Rôle de coordination régulier."},
    "juriste":{comm:"Commission Construction & Art sacré",title:"Juriste en droit de l'urbanisme",
      mission:"Accompagner le projet sur les aspects réglementaires (PLU, autorisations, permis de construire), sécuriser les démarches et anticiper les risques juridiques.",
      profil:"Formation ou expérience en droit de l'urbanisme ou de l'immobilier. Bénévolat ponctuel d'expertise.",
      engagement:"À la mission, lors des étapes réglementaires clés."},
    "dessinateur":{comm:"Commission Construction & Art sacré",title:"Dessinateur (plans & perspectives)",
      mission:"Produire des plans, croquis et perspectives pour illustrer le projet (communication, présentations, dossiers), en lien avec l'équipe Construction & Art sacré.",
      profil:"Maîtrise d'outils de dessin / CAO (AutoCAD, SketchUp, Illustrator…) et sens de la représentation.",
      engagement:"À la mission, selon les besoins."}
  };
  var jComm=document.getElementById('jComm'),jTitle=document.getElementById('jTitle'),jBody=document.getElementById('jBody'),jClose=document.getElementById('jClose');
  var last=null;
  function esc(t){return (t||'').replace(/&/g,'&amp;').replace(/</g,'&lt;');}
  function open(card){
    var d=JOBS[card.getAttribute('data-job')]; if(!d) return;
    jComm.textContent=d.comm; jTitle.textContent=d.title;
    jBody.innerHTML='<h5>La mission</h5><p>'+esc(d.mission)+'</p><h5>Profil recherché</h5><p>'+esc(d.profil)+'</p><h5>Engagement</h5><p>'+esc(d.engagement)+'</p>';
    last=card; modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden'; jClose.focus();
  }
  function close(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); document.body.style.overflow=''; if(last) last.focus(); }
  document.querySelectorAll('.job').forEach(function(j){
    j.addEventListener('click',function(){open(j);});
    j.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();open(j);}});
  });
  jClose.addEventListener('click',close);
  modal.addEventListener('click',function(e){if(e.target===modal)close();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))close();});
  var jcta=document.getElementById('jCta'); if(jcta) jcta.addEventListener('click',close);
})();
