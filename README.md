# Psicología — Landing (Laravel 12 + Vue 3 + MySQL)

Sitio de una sola página para una consulta de psicología online (especialidad en trauma y
EMDR), inspirado en la estructura de `aracelypenatepsicologa.com`. Incluye formulario de
contacto y un "chequeo de bienestar emocional", ambos con persistencia en MySQL y aviso por
email, además de un panel de administración (`/admin`) para editar colores, textos, contacto,
logo y ver los mensajes recibidos sin tocar código.

## Stack

| Capa       | Tecnología                                            |
|------------|-------------------------------------------------------|
| Backend    | Laravel 12 (PHP 8.3)                                  |
| Autenticación | Laravel Breeze (stack Blade)                       |
| Frontend   | Blade + Vue 3 (componentes montados sobre Vite) + Alpine.js (panel admin) |
| Estilos    | Tailwind CSS v4 — paleta celeste pastel + blanco       |
| Base datos | MySQL 8 (`psicologia`)                                 |
| Entorno    | Laragon (Apache + MySQL)                               |

Vue no gestiona el enrutado: Laravel sirve el HTML con Blade y cada zona interactiva
(menú móvil, tarjetas de mitos, acordeón de FAQ, chequeo emocional, formulario de contacto)
se monta como un mini-app Vue vía el atributo `data-vue="Componente"` + `data-props='{...}'`
(ver `resources/js/app.js`).

## Estructura relevante

```
config/site.php                        ← contenido por defecto de la web (textos, servicios, FAQ…)
app/Http/Controllers/SiteController    ← home + página de privacidad
app/Http/Controllers/Api/              ← ContactController, CheckupController
app/Http/Controllers/Admin/            ← panel /admin (tema, contenido, contacto, mensajes, logo)
app/Http/Requests/                     ← validación (StoreContactRequest, StoreCheckupRequest)
app/Http/Middleware/EnsureUserIsAdmin  ← restringe /admin a usuarios con role = admin
app/Services/SiteSettingsService.php   ← overrides de tema/contenido guardados en BD
app/Support/SiteContentSections.php    ← esquema de campos editables por sección
app/Console/Commands/MakeAdminUser.php ← `php artisan make:admin`
app/Mail/ContactReceived.php           ← email de aviso a la psicóloga
app/Models/                            ← ContactMessage, EmotionalCheckup, SiteSetting, User (+role)
database/migrations/                   ← contact_messages, emotional_checkups, role, site_settings
resources/views/home.blade.php         ← la landing
resources/views/partials/              ← header, footer, icon, logo
resources/views/admin/                 ← vistas del panel de administración
resources/views/auth/                  ← login / recuperar contraseña (Breeze, en español)
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

Casi todo (títulos, textos, servicios, testimonios, FAQ, mitos, datos de contacto) vive por
defecto en `config/site.php`. Tras editar ese archivo directamente:
```bash
php artisan config:clear
```

La foto de cabecera: coloca `public/images/aracely.jpg` y cambia `about.photo` en
`config/site.php`.

La forma recomendada de editar contenido, colores, contacto y logo **sin tocar código** es
el panel de administración (ver siguiente sección) — los cambios ahí se guardan en MySQL y
tienen prioridad sobre `config/site.php`, sin necesidad de limpiar caché ni recompilar nada.

## Panel de administración (`/admin`)

Roles `super_admin`/`admin`/`editor`/`user` (sin autorregistro público — las cuentas las crea
un `super_admin`/`admin` desde `/admin/users` o vía `php artisan make:admin`). Incluye:

- **Colores** (`/admin/theme`) — 4 colores (principal, fondo, acento, texto) que sobrescriben
  la paleta pastel en vivo, mediante variables CSS inyectadas en `<head>`. No requiere
  recompilar assets.
- **Logo** (`/admin/logo`) — sube una imagen (PNG/JPG, máx. 1 MB, hasta 800×800 px) para
  reemplazar el icono SVG por defecto en el header y el footer.
- **Foto de portada** (`/admin/about-photo`) — reemplaza la foto de la sección "Sobre mí"
  (PNG/JPG, máx. 2 MB, hasta 2000×2000 px).
- **Galería** (`/admin/gallery`) — administra las imágenes del carrusel de inicio: subir,
  eliminar y reordenar (botones ↑/↓) con hasta el número de imágenes que quieras. Trae 3
  fotos de ejemplo relacionadas con psicología/bienestar; al quitar una "de fábrica" solo se
  quita del listado, al quitar una que subiste también se borra el archivo.
- **Redes sociales** (`/admin/social`) — un campo de URL por red (Facebook, Instagram,
  TikTok, LinkedIn, YouTube, X). Dejar el campo vacío oculta ese icono en el sitio; los
  iconos aparecen en el footer y en la sección de contacto.
- **Contenido** (`/admin/content/{sección}`) — un formulario por sección de `config/site.php`
  (hero, sobre mí, servicios, beneficios, EMDR, testimonios, mitos, FAQ, contacto, footer),
  con repetidores (Alpine.js) para listas de tarjetas/preguntas.
- **Contacto** (`/admin/contact`) — WhatsApp, email, zona de atención y tiempo de respuesta.
- **Mensajes** (`/admin/messages`) — bandeja de `contact_messages` y `emotional_checkups`
  guardados en MySQL, con acción para marcar un mensaje de contacto como atendido.
- **Usuarios** (`/admin/users`) — lista de cuentas con su rol y estado; banear/reactivar y
  cambiar de rol. Nadie puede banearse a sí misma ni dejar el sitio sin ningún `super_admin`
  activo (esas acciones se bloquean con un aviso).
- **Seguridad** (`/admin/security`) — activa qué canales de 2FA (SMS/WhatsApp) y proveedores de
  login social (Google/Facebook/Microsoft) están disponibles para que cada usuario elija su
  propio método en "Mi seguridad". Un canal marcado sin credenciales de `.env` queda "pendiente
  de configuración" y el sistema sigue usando email de respaldo.

**Crear la cuenta de administradora** (no se commitea ninguna contraseña):
```bash
php artisan make:admin
```
Pide el email y la contraseña de forma interactiva (mínimo 8 caracteres), con rol
`super_admin` por defecto (`--role=admin` para uno con menos privilegios). Para scripts de
despliegue no interactivos, puedes definir `ADMIN_EMAIL`/`ADMIN_PASSWORD` en `.env` antes de
ejecutar el comando (ver `.env.example`).

El acceso está en `/login` (hay un enlace discreto "Acceso administración" al pie del sitio).
Un usuario autenticado que no sea `admin`/`super_admin` recibe **403** al visitar `/admin`.

## Seguridad del login (2FA, dispositivos de confianza, login social)

Tras la contraseña correcta, **el login no se completa hasta pasar un segundo factor** — nunca
se autentica la sesión con solo la contraseña. Cada persona elige su propio método en
`/perfil/seguridad` (accesible a cualquier usuario logueado, no solo admins):

- **Email** (método por defecto) — código de 6 dígitos enviado por correo, válido **2 horas**.
  Si se intenta usar un código ya expirado, el sistema invalida ese código y **envía uno nuevo
  automáticamente**, sin que haga falta pedirlo a mano.
- **SMS / WhatsApp** — estructura completa lista (columnas, UI, selector de canal), pero
  requiere una cuenta de Twilio: hasta que `.env` tenga `TWILIO_SID`/`TWILIO_TOKEN` reales, el
  sistema cae automáticamente a enviar por email en su lugar, para que nadie quede bloqueado
  por elegir un canal aún no conectado a un proveedor real.
- **Autenticador (TOTP)** — Google Authenticator, Authy o similar; se activa generando un QR
  desde `/perfil/seguridad` y confirmando con un código válido.
- **Recordar este dispositivo** — checkbox en la pantalla de verificación; salta el 2FA en ese
  navegador durante el número de días configurado en `/admin/security` (30 por defecto). Se
  invalida automáticamente al cambiar la contraseña.
- **Login social (Google/Facebook/Microsoft)** — vía `laravel/socialite`, listo pero inactivo
  hasta configurar `GOOGLE_CLIENT_ID`/`FACEBOOK_CLIENT_ID`/`MICROSOFT_CLIENT_ID` (y sus
  `_SECRET`) en `.env`, más activarlo desde `/admin/security`. Solo funciona para cuentas ya
  existentes que compartan el mismo email (no crea cuentas nuevas, igual que el resto del
  sitio); un login social exitoso **omite el reto 2FA propio** — el proveedor ya es una
  autenticación fuerte por sí mismo.
- **Baneo** — una cuenta baneada (`/admin/users`) no puede iniciar sesión, y si ya tenía una
  sesión activa, se le cierra en su siguiente petición a cualquier página que requiera login.

## Demo estática en Netlify (sin backend)

Netlify solo sirve archivos estáticos: no ejecuta PHP/Laravel ni ofrece MySQL. Por eso
existe `static-demo/`, una exportación del HTML ya renderizado (mismo diseño, formularios
que funcionan visualmente vía WhatsApp / cálculo en el navegador, pero **sin guardar nada
en base de datos ni enviar email**). Ver [`static-demo/README.md`](static-demo/README.md)
para el detalle y cómo desplegarla. El `netlify.toml` de la raíz ya la configura como
`publish directory`, así que basta con conectar el repo en Netlify sin tocar nada más.

Para el sitio 100% funcional (formularios reales, panel admin) hay que desplegar el proyecto
Laravel completo en un hosting con PHP + MySQL — Netlify no sirve para eso. `static-demo/`
tampoco recibe automáticamente los cambios de logo/colores/contenido hechos desde `/admin`;
para reflejarlos hay que regenerar esa carpeta siguiendo los pasos de su propio README.

## Tests

```bash
php artisan test
```
73 tests: envío válido/inválido del formulario de contacto (incluido honeypot y email), el
cálculo de puntuación/banda del chequeo emocional, el flujo de autenticación de Breeze
(login, recuperación de contraseña, verificación de email), el control de acceso a `/admin`
(invitado → redirect a login, usuario normal → 403, administradora → 200), el CRUD de redes
sociales, foto de portada y galería (subir/reordenar/eliminar, con `Storage::fake`), y todo el
sistema de seguridad del login: reto 2FA por email/TOTP (código correcto/incorrecto/expirado
con reenvío automático), dispositivos de confianza (crear, saltar 2FA, expiración), baneo
(bloquea login y mata sesiones activas), login social (mock de Socialite: cuenta existente,
sin cuenta, conexión recurrente, omite 2FA) y gestión de usuarios/roles (banear, guardas
anti-autobaneo y anti-último-`super_admin`). Usan SQLite en memoria.
