# Versión estática de demostración (Netlify)

Esta carpeta es una **exportación estática** de la landing, pensada únicamente para
mostrar el diseño en un enlace público gratis (Netlify). **No es el sitio real** —
el proyecto completo con Laravel + Vue + MySQL vive en la raíz del repo.

## Qué funciona aquí

- Todo el diseño, textos y layout (idéntico al sitio real).
- Menú móvil, acordeón de FAQ, tarjetas de mitos que giran (Vue, sin backend).
- **Chequeo emocional**: calcula la puntuación y la banda **en el navegador** con
  JavaScript (no llama a ninguna API). No queda guardado en ningún sitio.
- **Formulario de contacto**: arma un mensaje con lo que escribas y **abre WhatsApp**
  directamente, en vez de enviarlo a un servidor. Tampoco se guarda.

Ambos formularios muestran un aviso de "Sitio de demostración" para dejarlo claro.

## Qué NO funciona aquí (a propósito)

- No hay backend Laravel ni base de datos: nada se guarda en MySQL.
- No se envía ningún email de aviso.
- No hay rutas de servidor (`/contacto`, `/chequeo-emocional`) — Netlify solo sirve
  archivos estáticos.

Para la versión 100% funcional (formularios reales guardando en MySQL + email) hay
que desplegar el proyecto Laravel completo en un hosting con PHP + MySQL (Railway,
Render, Hostinger, un VPS, etc.), no en Netlify.

## Cómo se generó

1. Se activó `SITE_DEMO_MODE=true` en `.env` del proyecto Laravel.
2. Se compilaron los assets: `npm run build`.
3. Se descargó el HTML ya renderizado de `http://localhost/psicologia/public/` y
   `/privacidad`, y se reescribieron las URLs absolutas de los assets
   (`http://localhost/.../build/...`) a rutas relativas (`build/...`).
4. Se copiaron `public/build/assets/*` y `public/images/*` a esta carpeta.
5. `SITE_DEMO_MODE` se volvió a `false` para no afectar el sitio Laravel real.

Si cambias el contenido en `config/site.php` o el diseño, hay que repetir este
proceso para que la demo estática quede al día (no se regenera sola).

## Desplegar en Netlify

**Opción A — Netlify CLI:**
```bash
npm install -g netlify-cli
netlify deploy --dir=static-demo          # preview
netlify deploy --dir=static-demo --prod   # producción
```

**Opción B — Arrastrar y soltar:**
En [app.netlify.com/drop](https://app.netlify.com/drop) arrastra esta carpeta
(`static-demo/`) completa.

**Opción C — Conectar el repo de GitHub:**
En Netlify → *Add new site* → *Import an existing project* → selecciona el repo →
- **Build command:** (déjalo vacío o `echo "no build"`)
- **Publish directory:** `static-demo`

(El `netlify.toml` en la raíz del repo ya trae esta configuración por defecto.)
