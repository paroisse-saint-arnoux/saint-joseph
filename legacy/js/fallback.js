/* Filet de sécurité : si le script principal n'a pas démarré (ex. vieux navigateur),
   on affiche quand même tout le contenu pour qu'aucune section ne reste invisible. */
window.addEventListener('load', function(){
  setTimeout(function(){
    if (!window.__revealReady) {
      var els = document.querySelectorAll('.reveal');
      for (var i = 0; i < els.length; i++) { els[i].className += ' in'; }
    }
  }, 1600);
});
