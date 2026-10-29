(function(){

  const TOTAL=(window.SJO2&&window.SJO2.objectif)||6000000;
  let raised=(window.SJO2&&window.SJO2.raised)||1400000;

  const $=id=>document.getElementById(id);
  const riseRect=$('riseRect'), riseLine=$('riseLine'), curLine=$('curLine'), curCm=$('curCm');
  const GY=590, TY=60, SPAN=GY-TY; // build region (cm scale)

  function fmtM(v){return (v/1000000).toFixed(2).replace('.',',');}
  function fr(n){return Math.round(n).toLocaleString('fr-FR');}

  function render(animate){
    const pct=Math.min(raised/TOTAL,1);
    const remain=TOTAL-raised;
    const pierres=Math.round(raised/10), murs=Math.floor(pierres/100);
    const y=GY-pct*SPAN;

    // clocher fill (rises)
    riseRect.setAttribute('y',y);
    riseRect.setAttribute('height',(GY+8)-y);
    riseLine.setAttribute('y',y-1.5);
    curLine.setAttribute('y1',y); curLine.setAttribute('y2',y);
    curCm.setAttribute('y',y-7); curCm.textContent=fr(pierres)+' pierres';

    // bar
    $('barFill').style.width=(pct*100)+'%';

    // wall of builders (bricks fill bottom-to-top with progress)
    const wall=$('wall');
    if(wall){
      const bricks=wall._bricks||[], n=bricks.length;
      const set=Math.round(pct*n);
      // bricks array is ordered bottom-to-top, left-to-right -> fill first `set`
      for(let i=0;i<n;i++) bricks[i].classList.toggle('set', i<set);
      const wp=$('wallPct'); if(wp) wp.textContent=Math.round(pct*100)+' %';
    }

    // counters
    if(animate){
      countUp($('raisedTxt'),0,pierres,v=>fr(v)+' <small>pierres</small>');
      countUp($('pctTxt'),0,pct*100,v=>Math.round(v)+' %');
      countUp($('pierres'),0,pierres,v=>fr(v));
      countUp($('pierresBar'),0,pierres,v=>fr(v));
      countUp($('wallPierres'),0,pierres,v=>fr(v));
      countUp($('mursDone'),0,murs,v=>fr(v));
    }else{
      $('raisedTxt').innerHTML=fr(pierres)+' <small>pierres</small>';
      $('pctTxt').textContent=Math.round(pct*100)+' %';
      $('pierres').textContent=fr(pierres);
      var pb=$('pierresBar'); if(pb) pb.textContent=fr(pierres);
      var wpz=$('wallPierres'); if(wpz) wpz.textContent=fr(pierres);
      var md=$('mursDone'); if(md) md.textContent=fr(murs);
    }
  }

  function countUp(el,from,to,fmt){
    let c=from;const step=(to-from)/55;
    const run=()=>{c+=step;if((step>0&&c>=to)||(step<0&&c<=to))c=to;el.innerHTML=fmt(c);if(c!==to)requestAnimationFrame(run);};
    run();
  }


  // initial animated build
  window.addEventListener('load',()=>{
    // build the wall — running-bond masonry (rows, odd rows offset by half a brick)
    const wall=$('wall');
    if(wall && !wall._bricks){
      const ROWS=11, COLS=10;
      const rowEls=[]; const order=[];
      for(let r=0;r<ROWS;r++){
        const row=document.createElement('div');
        row.className='row'+(r%2?' odd':'');
        const cells=[];
        if(r%2){ // offset row: half-brick, full bricks, half-brick
          const h1=document.createElement('i'); h1.className='brick half'; row.appendChild(h1); cells.push(h1);
          for(let c=0;c<COLS-1;c++){const b=document.createElement('i');b.className='brick';row.appendChild(b);cells.push(b);}
          const h2=document.createElement('i'); h2.className='brick half'; row.appendChild(h2); cells.push(h2);
        }else{
          for(let c=0;c<COLS;c++){const b=document.createElement('i');b.className='brick';row.appendChild(b);cells.push(b);}
        }
        wall.appendChild(row); rowEls.push(cells);
      }
      // flex-direction:column-reverse renders row 0 at the BOTTOM.
      // Fill order = bottom row first, left-to-right, going up.
      for(let r=0;r<ROWS;r++){ rowEls[r].forEach(b=>order.push(b)); }
      wall._bricks=order;
    }
    render(false); // set targets
    // animate clocher + bar from the ground
    riseRect.setAttribute('y',GY);riseRect.setAttribute('height',8);
    riseLine.setAttribute('y',GY-1.5);curLine.setAttribute('y1',GY);curLine.setAttribute('y2',GY);
    $('barFill').style.width='0%';
    if(wall&&wall._bricks) wall._bricks.forEach(b=>b.classList.remove('set'));
    setTimeout(()=>render(true),300);
    buildRamp();
  });

  // ---- Cumulative funding ramp ----
  function buildRamp(){
    const svg=document.getElementById('ramp');
    const W=1040,H=360,L=58,R=26,T=40,B=92;
    const pw=W-L-R, ph=H-T-B, y0=T+ph, yMax=6.5;
    const years=["Aujourd'hui","2026","2027","2028","2029","2030"];
    const phases=["1,4 M€ acquis","Décollage","Élan","Sommet","Consolidation","Pérennisation"];
    const cumul=[1.4,1.8,3.1,4.7,5.7,6.15];
    const annual=[null,0.4,1.3,1.6,1.0,0.45];
    const n=cumul.length;
    const X=i=>L+pw*(i/(n-1));
    const Y=v=>T+ph*(1-v/yMax);
    const f=v=>(v).toFixed(v<1?2:(v%1?1:0)).replace('.',',');

    let stat='',dyn='';
    // y gridlines + labels
    [0,2,4].forEach(v=>{const y=Y(v);
      stat+=`<line x1="${L}" y1="${y}" x2="${L+pw}" y2="${y}" stroke="rgba(243,238,226,.1)" stroke-width="1"/>`;
      stat+=`<text x="${L-10}" y="${y+4}" fill="#cdd3df" font-size="12" text-anchor="end" font-family="Hanken Grotesk">${v} M€</text>`;});
    // objective line
    const yo=Y(6);
    stat+=`<line x1="${L}" y1="${yo}" x2="${L+pw}" y2="${yo}" stroke="#E2C97F" stroke-width="1.3" stroke-dasharray="6 5" opacity=".75"/>`;
    stat+=`<text x="${L+pw}" y="${yo-9}" fill="#E2C97F" font-size="12.5" text-anchor="end" font-family="Hanken Grotesk" font-weight="600">Objectif 6 M€</text>`;
    // x labels (static)
    for(let i=0;i<n;i++){const x=X(i);
      const anc=i===0?'start':(i===n-1?'end':'middle');
      stat+=`<text x="${x}" y="${y0+26}" fill="#F3EEE2" font-size="14" text-anchor="${anc}" font-family="Fraunces" font-weight="500">${years[i]}</text>`;
      stat+=`<text x="${x}" y="${y0+44}" fill="#cdd3df" font-size="11.5" text-anchor="${anc}" font-family="Hanken Grotesk">${phases[i]}</text>`;
    }

    // area + line (dynamic, revealed left→right)
    let area=`M ${X(0)} ${y0} `, line=`M ${X(0)} ${Y(cumul[0])} `;
    for(let i=0;i<n;i++){area+=`L ${X(i)} ${Y(cumul[i])} `;line+=(i?`L ${X(i)} ${Y(cumul[i])} `:'');}
    area+=`L ${X(n-1)} ${y0} Z`;
    dyn+=`<path d="${area}" fill="url(#rampGrad)" opacity=".9"/>`;
    dyn+=`<path d="${line}" fill="none" stroke="#E2C97F" stroke-width="3" stroke-linejoin="round"/>`;
    // points + value labels
    for(let i=0;i<n;i++){const x=X(i),y=Y(cumul[i]);
      const isNow=i===0;
      dyn+=`<circle cx="${x}" cy="${y}" r="${isNow?7:5.5}" fill="${isNow?'#E2C97F':'#00204E'}" stroke="#E2C97F" stroke-width="2.5"/>`;
      const below = y<76;
      const ly = below ? y+24 : y-26;
      const ly2 = below ? y+40 : y-11;
      const anc = i===0?'start':(i===n-1?'end':'middle');
      const tx = i===0?x+10:(i===n-1?x-2:x);
      dyn+=`<text x="${tx}" y="${ly}" fill="#F3EEE2" font-size="14.5" text-anchor="${anc}" font-family="Fraunces" font-weight="600">${f(cumul[i])} M€</text>`;
      if(annual[i]!=null) dyn+=`<text x="${tx}" y="${ly2}" fill="#E2C97F" font-size="11.5" text-anchor="${anc}" font-family="Hanken Grotesk">+${f(annual[i])} M€</text>`;
    }
    // "nous sommes ici"
    dyn+=`<text x="${X(0)+10}" y="${Y(cumul[0])+40}" fill="#E2C97F" font-size="10.5" text-anchor="start" letter-spacing="1.2" font-family="Hanken Grotesk">NOUS SOMMES ICI</text>`;

    svg.innerHTML=`<defs>
        <linearGradient id="rampGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="rgba(226,201,127,.5)"/><stop offset="1" stop-color="rgba(226,201,127,.04)"/>
        </linearGradient>
        <clipPath id="rampClip"><rect id="rampRect" x="0" y="0" width="0" height="${H}"/></clipPath>
      </defs>
      ${stat}
      <g clip-path="url(#rampClip)">${dyn}</g>`;

    // animate reveal
    const rect=document.getElementById('rampRect');
    let t=0;const dur=90;
    const run=()=>{t++;const p=Math.min(t/dur,1);rect.setAttribute('width',(L+pw+30)*easeOut(p));if(p<1)requestAnimationFrame(run);};
    function easeOut(x){return 1-Math.pow(1-x,3);}
    setTimeout(run,400);
  }


})();
