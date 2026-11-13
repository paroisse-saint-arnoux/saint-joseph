# Certificat https du développement local

Certificat `*.dev.saint-arnoux.fr` signé par une autorité locale **mkcert** (fichiers `.pem` hors git).

Créer ou renouveler le certificat (valable un peu plus de 2 ans), depuis ce dossier :

```bash
mkcert -cert-file dev.saint-arnoux.fr.pem -key-file dev.saint-arnoux.fr-key.pem "*.dev.saint-arnoux.fr" dev.saint-arnoux.fr
docker compose restart traefik
```

Faire confiance à l'autorité mkcert (une fois par machine) :

- navigateur sous **Windows** (WSL) : `certutil.exe -addstore -user Root "$(wslpath -w "$(mkcert -CAROOT)/rootCA.pem")"`, puis relancer le navigateur ;
- navigateur sous Linux/macOS : `mkcert -install`.
