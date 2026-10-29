{{-- Outil de retouche (fiche d'une image) : Cropper.js choisit la zone, la rotation et le miroir ;
     la retouche est appliquée sur l'original par le serveur (App\Support\Library::edit). --}}
<div
    x-data="{
        state: $wire.$entangle(@js($getStatePath())),
        cropper: null,
        result: '',
        init() {
            const img = this.$refs.image;
            const start = () => setTimeout(() => {
                this.cropper = new window.Cropper(img, {
                    // Zoom réservé à l'ajustement automatique (pas à la molette ni au pincement).
                    viewMode: 1, autoCropArea: 1, zoomOnWheel: false, zoomOnTouch: false, background: true, responsive: true,
                    crop: () => {
                        const d = this.cropper.getData(true);
                        this.state = { x: d.x, y: d.y, width: d.width, height: d.height, rotate: d.rotate, scaleX: d.scaleX, scaleY: d.scaleY };
                        this.result = d.width + ' × ' + d.height + ' px';
                    },
                });
            }, 250);
            img.complete ? start() : img.addEventListener('load', start, { once: true });
        },
        // Après une rotation : l'image (éventuellement passée en portrait) est ajustée et centrée dans la zone.
        fit() {
            const box = this.cropper.getContainerData();
            const image = this.cropper.getImageData();
            const turned = Math.abs(image.rotate % 180) === 90;
            const width = turned ? image.naturalHeight : image.naturalWidth;
            const height = turned ? image.naturalWidth : image.naturalHeight;
            // Le cadre empêche l'image de devenir plus petite que lui (viewMode 1) : on le réduit d'abord.
            this.cropper.setCropBoxData({ left: box.width / 2 - 5, top: box.height / 2 - 5, width: 10, height: 10 });
            this.cropper.zoomTo(Math.min(box.width / width, box.height / height));
            const canvas = this.cropper.getCanvasData();
            this.cropper.setCanvasData({ left: (box.width - canvas.width) / 2, top: (box.height - canvas.height) / 2 });
            this.maximize();
        },
        // Cadre le plus grand possible dans l'image, aux proportions choisies, centré.
        maximize() {
            const canvas = this.cropper.getCanvasData();
            const ratio = this.cropper.options.aspectRatio;
            let width = canvas.width, height = canvas.height;
            if (ratio && ! isNaN(ratio)) { width / height > ratio ? width = height * ratio : height = width / ratio }
            this.cropper.setCropBoxData({ left: canvas.left + (canvas.width - width) / 2, top: canvas.top + (canvas.height - height) / 2, width, height });
        },
        rotate(deg) { this.cropper.rotate(deg); this.fit() },
        flip(axis) { const d = this.cropper.getData(); axis === 'x' ? this.cropper.scaleX(-d.scaleX) : this.cropper.scaleY(-d.scaleY) },
        ratio(value) { this.cropper.setAspectRatio(value); this.maximize() },
        reset() { this.cropper.reset(); this.cropper.setAspectRatio(NaN); this.fit() },
        destroy() { this.cropper?.destroy() },
    }"
    wire:ignore
>
    <style>
        .sj-crop-bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-bottom: 10px; }
        .sj-crop-bar .sep { width: 1px; height: 24px; background: #e5e7eb; margin: 0 4px; }
        .sj-crop-btn { padding: 5px 10px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; font-size: .8rem; line-height: 1.2; }
        .sj-crop-btn:hover { border-color: #C5A24B; }
        .sj-crop-area { height: 60vh; background: #f3f4f6; border-radius: 10px; overflow: hidden; }
        .sj-crop-area img { display: block; max-width: 100%; }
    </style>

    <div class="sj-crop-bar">
        <button type="button" class="sj-crop-btn" x-on:click="rotate(-90)" title="Pivoter à gauche">↺ Pivoter à gauche</button>
        <button type="button" class="sj-crop-btn" x-on:click="rotate(90)" title="Pivoter à droite">↻ Pivoter à droite</button>
        <button type="button" class="sj-crop-btn" x-on:click="flip('x')">⇋ Miroir</button>
        <button type="button" class="sj-crop-btn" x-on:click="flip('y')">⇅ Retourner</button>
        <span class="sep"></span>
        <span style="font-size:.8rem; color:#6b7280;">Proportions :</span>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(NaN)">Libre</button>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(1)">Carré</button>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(4/3)">4:3</button>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(3/2)">3:2</button>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(16/9)">16:9</button>
        <button type="button" class="sj-crop-btn" x-on:click="ratio(1200/630)" title="Format conseillé pour l'image de partage d'un article">Partage (1200 × 630)</button>
        <span class="sep"></span>
        <button type="button" class="sj-crop-btn" x-on:click="reset()">Réinitialiser</button>
        <span style="margin-left:auto; font-size:.8rem; color:#6b7280;" x-text="result ? 'Résultat : ' + result : ''"></span>
    </div>

    <div class="sj-crop-area">
        <img x-ref="image" src="{{ $url }}" alt="">
    </div>
    <p style="font-size:.8rem; color:#6b7280; margin-top:8px;">
        Faites glisser le cadre et ses poignées pour choisir la zone à garder. La retouche est appliquée sur l'original en pleine résolution.
    </p>
</div>
