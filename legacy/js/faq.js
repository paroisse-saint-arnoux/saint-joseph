(function(){
  var qs=document.querySelectorAll('.faq-q'); if(!qs.length) return;
  qs.forEach(function(q){
    q.addEventListener('click',function(){
      var a=q.nextElementSibling, open=q.classList.contains('open');
      qs.forEach(function(x){x.classList.remove('open'); if(x.nextElementSibling) x.nextElementSibling.style.maxHeight=null;});
      if(!open){ q.classList.add('open'); a.style.maxHeight=a.scrollHeight+'px'; }
    });
  });
})();
