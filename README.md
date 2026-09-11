# Psicología — Landing (Laravel 12 + Vue 3 + MySQL)

Sitio de una sola página para una consulta de psicología online (especialidad en trauma y
EMDR), inspirado en la estructura de `aracelypenatepsicologa.com`. Incluye formulario de
contacto y un "chequeo de bienestar emocional", ambos con persistencia en MySQL y aviso por
email.

## Stack

| Capa       | Tecnología                                            |
|------------|-------------------------------------------------------|
| Backend    | Laravel 12 (PHP 8.3)                                  |
| Frontend   | Blade + Vue 3 (componentes montados sobre Vite)       |
| Estilos    | Tailwind CSS v4                                        |
| Base datos | MySQL 8 (`psicologia`)                                 |
| Entorno    | Laragon (Apache + MySQL)                               |

Vue no gestiona el enrutado: Laravel sirve el HTML con Blade y cada zona interactiva
(menú móvil, tarjetas de mitos, acordeón de FAQ, chequeo emocional, formulario de contacto)
se monta como un mini-app Vue vía el atributo `data-vue="Componente"` + `data-props='{...}'`
(ver `resources/js/app.js`).

## Estructura relevante

```
config/site.php                        ← TODO el contenido de la web (textos, servicios, FAQ…)
app/Http/Controllers/SiteController    ← home + página de privacidad
app/Http/Controllers/Api/              ← ContactController, CheckupController
app/Http/Requests/                     ← validación (StoreContactRequest, StoreCheckupRequest)
app/Mail/ContactReceived.php           ← email de aviso a la psicóloga
app/Models/                            ← ContactMessage, EmotionalCheckup
database/migrations/                   ← contact_messages, emotional_checkups
resources/views/home.blade.php         ← la landing
resources/views/partials/              ← header, footer, icon
resources/js/components/*.vue          ← FaqAccordion, MythCards, EmotionalCheckup, ContactForm, MobileNav
lang/es/                               ← mensajes de validación en español
```

## Puesta en marcha en local (Laragon)

1. **Requisitos:** Laragon con PHP 8.3+, Composer, Node 18+ y MySQL en marcha.

2. **Dependencias:**
   ```bash
   composer install
   npm install
   ```

3. **Entorno:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Revisa el bloque `DB_*` (por defecto `root` sin contraseña, base `psicologia`) y el
   bloque `SITE_*` (WhatsApp, email, número de colegiada).

4. **Base de datos:**
   ```bash
   # crear la base (o hazlo desde HeidiSQL / Laragon → Database)
   mysql -uroot -e "CREATE DATABASE psicologia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   php artisan migrate
   ```

5. **Assets + servidor:**
   ```bash
   npm run dev        # desarrollo con HMR
   php artisan serve  # http://127.0.0.1:8000
   ```

## "Despliegue" en local (dominio .test de Laragon)

1. Compila los assets para producción:
   ```bash
   npm run build
   ```

2. En Laragon: clic derecho en el icono → **Apache → Reload** (o **Menu → Quick app**).
   Laragon detecta la carpeta `C:\laragon\www\psicologia`, crea el vhost y añade la
   entrada a `hosts` automáticamente. Con Auto Virtual Hosts activado el sitio queda en:

   ```
   http://psicologia.test
   ```

   El `document root` del vhost debe apuntar a `.../psicologia/public`. Si Laragon usa la
   raíz del proyecto, actívalo en **Preferences → Services & Ports → “…/public”** o edita el
   vhost en `C:\laragon\etc\apache2\sites-enabled\`.

3. Cachés de producción (opcional, recomendado tras el build):
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   (Para volver a desarrollar: `php artisan optimize:clear`.)

También funciona sin dominio `.test` en `http://localhost/psicologia/public/`.

## Formularios

- **Contacto** → `POST /contacto` → guarda en `contact_messages` y, si `site.contact.email`
  es válido, envía `ContactReceived`. En local `MAIL_MAILER=log` escribe el correo en
  `storage/logs/laravel.log`. Con Mailpit de Laragon: `MAIL_MAILER=smtp`, `MAIL_PORT=1025`
  y lo ves en `http://localhost:8025`.
- **Chequeo emocional** → `POST /chequeo-emocional` → guarda en `emotional_checkups`
  (5 respuestas 0–3, puntuación 0–15, banda `bajo`/`medio`/`alto`).
- Ambos con *rate limiting*, *honeypot* anti-spam y token CSRF.

Consultar los envíos:
```bash
php artisan tinker
>>> App\Models\ContactMessage::latest()->get(['name','email','subject','created_at']);
>>> App\Models\EmotionalCheckup::latest()->get(['score','band','created_at']);
```

## Editar el contenido

Casi todo (títulos, textos, servicios, testimonios, FAQ, mitos, datos de contacto) vive en
`config/site.php`. Tras editarlo:
```bash
php artisan config:clear
```

La foto de cabecera: coloca `public/images/aracely.jpg` y cambia `about.photo` en
`config/site.php` (ahora usa un SVG de marcador de posición).

## Demo estática en Netlify (sin backend)

Netlify solo sirve archivos estáticos: no ejecuta PHP/Laravel ni ofrece MySQL. Por eso
existe `static-demo/`, una exportación del HTML ya renderizado (mismo diseño, formularios
que funcionan visualmente vía WhatsApp / cálculo en el navegador, pero **sin guardar nada
en base de datos ni enviar email**). Ver [`static-demo/README.md`](static-demo/README.md)
para el detalle y cómo desplegarla. El `netlify.toml` de la raíz ya la configura como
`publish directory`, así que basta con conectar el repo en Netlify sin tocar nada más.

Para el sitio 100% funcional (formularios reales) hay que desplegar el proyecto Laravel
completo en un hosting con PHP + MySQL — Netlify no sirve para eso.

## Tests

```bash
php artisan test
```
Cubre el envío válido/ inválido del formulario de contacto (incluido honeypot y email) y el
cálculo de puntuación/banda del chequeo emocional. Usan SQLite en memoria.
