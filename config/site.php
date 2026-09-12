<?php

/*
|--------------------------------------------------------------------------
| Configuración del sitio (contenido editable)
|--------------------------------------------------------------------------
| Todo el contenido de la landing vive aquí para poder ajustarlo sin tocar
| las plantillas. El nombre y los datos de contacto se leen del .env cuando
| existen, con valores por defecto de ejemplo.
*/

return [

    // Activa el modo demo (usado al exportar una versión estática sin backend,
    // p. ej. para Netlify): los formularios funcionan en el navegador sin llamar a la API.
    'demo_mode'   => env('SITE_DEMO_MODE', false),

    'name'        => env('SITE_NAME', 'Lic. Erika Magaña'),
    'role'        => 'Psicóloga · Especialista en trauma y EMDR',
    'tagline'     => 'Terapia online en español',
    'registration'=> env('SITE_REGISTRATION', 'Colegiada J.V.P.P. 4615'),

    'contact' => [
        'whatsapp'      => env('SITE_WHATSAPP', '50360606225'), // solo dígitos, con código de país
        'whatsapp_show' => env('SITE_WHATSAPP_SHOW', '+503 6060 6225'),
        'email'         => env('SITE_EMAIL', 'contacto@ejemplo.com'),
        'area'          => 'Estados Unidos y Europa',
        'response'      => 'Suelo responder el mismo día',
    ],

    'whatsapp_prefill' => 'Hola, me gustaría reservar una llamada gratuita de 15 minutos para conocer cómo funciona la terapia.',

    'hero' => [
        'kicker'   => 'Terapia especializada en trauma · EMDR · Online',
        'title'    => 'Recupera la calma que creías perdida',
        'subtitle' => 'Terapia psicológica online en español para afrontar el trauma, la ansiedad y los desafíos emocionales, desde donde estés.',
        'points'   => [
            ['title' => 'Confianza en tu proceso', 'text' => 'Un espacio seguro y sin juicios para entender lo que te pasa y avanzar a tu ritmo.'],
            ['title' => 'Calma en el día a día', 'text' => 'Herramientas concretas para regular la ansiedad y el estrés que puedes usar desde la primera sesión.'],
            ['title' => 'Bienestar desde donde estés', 'text' => 'Sesiones por videollamada adaptadas a tu franja horaria, vivas donde vivas.'],
        ],
        'cta_primary'   => 'Escríbeme por WhatsApp',
        'cta_secondary' => 'Reserva una llamada gratis de 15 min',
    ],

    'about' => [
        'title' => 'Sobre mí',
        'lead'  => 'Acompaño a personas hispanohablantes que viven fuera de su país de origen. Sé lo que significa sostener una vida en otro idioma, extrañar a la familia y sentir que no hay con quién hablar de verdad. Mi trabajo es darte ese espacio.',
        'paragraphs' => [
            'Soy psicóloga colegiada y especialista en terapia EMDR, un abordaje reconocido por la Organización Mundial de la Salud para el tratamiento del trauma.',
            'En cada sesión encontrarás escucha real, sin prisas y sin juicios, y también algo práctico que llevarte: una idea, un ejercicio o una forma distinta de mirar lo que te está pasando.',
            'Trabajo únicamente online, en español, con personas en Estados Unidos y Europa.',
        ],
        'credentials' => [
            'Psicóloga colegiada',
            'Especialista en terapia EMDR',
            'Colegiada J.V.P.P. 4615',
            'Atención 100% online en español',
        ],
        'photo' => 'images/aracely.jpg',
    ],

    // Imágenes del carrusel de inicio. Editable desde /admin/gallery (se guarda
    // en site_settings, estas son solo las de ejemplo de fábrica).
    'gallery' => [
        'images' => [
            ['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'Espacio cómodo y acogedor para la terapia'],
            ['path' => 'images/gallery/therapy-2.jpg', 'alt' => 'Un momento de calma y bienestar'],
            ['path' => 'images/gallery/therapy-3.jpg', 'alt' => 'Sesión de terapia online desde casa'],
        ],
    ],

    'services' => [
        'title'    => 'Cómo te ayudo',
        'subtitle' => 'Estas son algunas de las dificultades que trabajamos en terapia. Si lo tuyo no aparece aquí, escríbeme y lo vemos.',
        'items' => [
            ['icon' => 'shield', 'title' => 'Trauma psicológico', 'text' => 'Experiencias difíciles o dolorosas del pasado que siguen afectando tu presente: recuerdos intrusivos, reacciones intensas o la sensación de estar en alerta constante.'],
            ['icon' => 'wind', 'title' => 'Ansiedad y estrés', 'text' => 'Preocupación que no para, pensamientos acelerados, tensión en el cuerpo o crisis de ansiedad que aparecen sin avisar.'],
            ['icon' => 'cloud-rain', 'title' => 'Depresión y duelo', 'text' => 'Tristeza sostenida, falta de energía o de sentido, y procesos de pérdida —de una persona, una relación o una etapa— que cuesta atravesar.'],
            ['icon' => 'heart', 'title' => 'Autoestima', 'text' => 'Autocrítica dura, sensación de no ser suficiente y patrones que se repiten en tus relaciones y en cómo te tratas a ti misma.'],
            ['icon' => 'globe', 'title' => 'Migración y desarraigo', 'text' => 'El desgaste de rehacer tu vida en otro país: soledad, choque cultural, culpa por la distancia y la identidad partida entre dos lugares.'],
            ['icon' => 'users', 'title' => 'Problemas familiares', 'text' => 'Vínculos que duelen, límites difíciles de poner y conflictos que se arrastran desde hace años.'],
        ],
    ],

    'sessions' => [
        'title' => 'Cómo son las sesiones',
        'items' => [
            ['label' => 'Duración', 'value' => '50 minutos'],
            ['label' => 'Frecuencia', 'value' => 'Semanal o quincenal'],
            ['label' => 'Formato', 'value' => 'Videollamada'],
            ['label' => 'Idioma', 'value' => 'Español'],
            ['label' => 'Confidencialidad', 'value' => 'Total'],
            ['label' => 'Horario', 'value' => 'Adaptado a tu zona'],
        ],
    ],

    'benefits' => [
        'title'    => 'Lo que puedes esperar',
        'subtitle' => 'No prometo soluciones mágicas. Sí un proceso serio, con método, en el que vas a notar cambios.',
        'items' => [
            'Entender qué te pasa y por qué, con un lenguaje claro.',
            'Reducir la intensidad de los recuerdos y las emociones que hoy te desbordan.',
            'Recuperar el sueño, la concentración y las ganas.',
            'Poner límites sin sentirte culpable.',
            'Relacionarte desde un lugar más tranquilo, contigo y con los demás.',
            'Salir de cada sesión con algo concreto que aplicar.',
        ],
    ],

    'emdr' => [
        'title' => 'Terapia EMDR',
        'lead'  => 'EMDR (Desensibilización y Reprocesamiento por Movimientos Oculares) es un abordaje basado en cómo funciona el cerebro. Mediante una estimulación guiada —normalmente movimientos oculares— ayudamos a que tu sistema nervioso reprocese los recuerdos dolorosos y deje de reaccionar como si el peligro siguiera presente.',
        'advantages' => [
            ['title' => 'No hace falta revivirlo en detalle', 'text' => 'No necesitas contar una y otra vez lo que pasó. Trabajamos con lo que hoy te afecta.'],
            ['title' => 'Respaldo científico', 'text' => 'Recomendado por la OMS y por guías clínicas internacionales para el tratamiento del trauma.'],
            ['title' => 'Resultados más rápidos', 'text' => 'En muchos casos se avanza antes que con abordajes exclusivamente conversacionales.'],
            ['title' => 'Funciona online', 'text' => 'La estimulación bilateral se adapta perfectamente a la videollamada.'],
        ],
        'steps' => [
            ['n' => '1', 'title' => 'Escríbeme', 'text' => 'Me cuentas por WhatsApp o email qué te trae y resolvemos dudas.'],
            ['n' => '2', 'title' => 'Primera sesión', 'text' => 'Nos conocemos, revisamos tu historia y definimos objetivos.'],
            ['n' => '3', 'title' => 'Preparación', 'text' => 'Aprendes recursos de regulación antes de tocar lo más difícil.'],
            ['n' => '4', 'title' => 'Reprocesamiento', 'text' => 'Trabajamos los recuerdos diana con EMDR y consolidamos los cambios.'],
        ],
    ],

    'testimonials' => [
        'title' => 'Lo que cuentan quienes ya hicieron el proceso',
        'note'  => 'Testimonios reales compartidos con permiso. Se han abreviado y se omiten datos identificativos.',
        'items' => [
            ['name' => 'Luz C.', 'place' => 'Barcelona', 'text' => 'Llegué arrastrando cosas de la infancia que creía superadas. Después del proceso duermo bien y mis relaciones cambiaron por completo. Ojalá lo hubiera hecho antes.'],
            ['name' => 'Camila B.', 'place' => 'Estados Unidos', 'text' => 'Venía de un duelo y de una situación muy dura en el trabajo. Recuperé la calma y la sensación de tener el control de mi vida otra vez.'],
            ['name' => 'Arely D.', 'place' => 'Italia', 'text' => 'Entendí de dónde venían patrones que repetía en pareja sin darme cuenta. Fue incómodo y liberador a la vez. Hoy me trato mucho mejor.'],
        ],
    ],

    'myths' => [
        'title'    => 'Mitos sobre ir a terapia',
        'subtitle' => 'Toca cada tarjeta para verla del derecho.',
        'items' => [
            ['myth' => '«La terapia es solo para gente con problemas graves.»', 'truth' => 'La terapia es cuidado de tu salud mental. No necesitas estar en crisis para pedir ayuda.'],
            ['myth' => '«Pedir ayuda es de débiles.»', 'truth' => 'Reconocer que necesitas apoyo y buscarlo es una de las cosas más valientes que puedes hacer.'],
            ['myth' => '«Con fuerza de voluntad basta.»', 'truth' => 'La voluntad ayuda, pero el trauma y la ansiedad se sostienen en el sistema nervioso, no en las ganas.'],
            ['myth' => '«Lo de casa se queda en casa.»', 'truth' => 'La lealtad familiar no está reñida con hablar de lo que te duele en un espacio confidencial.'],
            ['myth' => '«La terapia online no funciona igual.»', 'truth' => 'La evidencia muestra resultados equivalentes a la terapia presencial, también en EMDR.'],
            ['myth' => '«Es un gasto que no me puedo permitir.»', 'truth' => 'Es una inversión en tu bienestar que se nota en tu salud, tu trabajo y tus vínculos.'],
        ],
    ],

    'checkup' => [
        'title'    => 'Chequeo de bienestar emocional',
        'subtitle' => 'Cinco preguntas anónimas para hacerte una idea de cómo estás últimamente. No es un diagnóstico ni sustituye una consulta profesional.',
        'period'   => 'Piensa en las últimas dos semanas.',
        'options'  => ['Nunca', 'A veces', 'A menudo', 'Casi siempre'],
        'questions' => [
            'Me cuesta desconectar de las preocupaciones.',
            'Me siento con poca energía o sin ganas de hacer cosas.',
            'Recuerdos o pensamientos difíciles aparecen sin que yo quiera.',
            'Duermo mal o me despierto cansada.',
            'Siento que no tengo con quién hablar de verdad de lo que me pasa.',
        ],
        'results' => [
            'bajo'  => ['title' => 'Parece que estás en un momento razonablemente estable', 'text' => 'Aun así, si algo te preocupa, hablarlo con una profesional siempre suma. Guarda este espacio por si lo necesitas.'],
            'medio' => ['title' => 'Hay señales de desgaste que conviene atender', 'text' => 'Trabajar esto en terapia puede ayudarte a que no vaya a más. Escríbeme y lo vemos sin compromiso.'],
            'alto'  => ['title' => 'Estás cargando con bastante ahora mismo', 'text' => 'No tienes que gestionarlo sola. Te recomiendo dar el paso de pedir apoyo profesional. Puedes escribirme cuando quieras.'],
        ],
        'disclaimer' => 'Si estás en una situación de emergencia o tienes pensamientos de hacerte daño, contacta con los servicios de emergencia de tu país de inmediato.',
    ],

    'faq' => [
        'title' => 'Preguntas frecuentes',
        'items' => [
            ['q' => '¿Qué es exactamente la terapia EMDR?', 'a' => 'Es un abordaje psicológico que utiliza estimulación bilateral (habitualmente movimientos oculares) para ayudar al cerebro a reprocesar recuerdos dolorosos. Está reconocido por la OMS para el tratamiento del trauma.'],
            ['q' => '¿La terapia online funciona igual que la presencial?', 'a' => 'Sí. La evidencia científica muestra resultados equivalentes. Solo necesitas un lugar tranquilo, buena conexión y auriculares.'],
            ['q' => '¿Cómo se coordinan los horarios si estamos en zonas distintas?', 'a' => 'Acordamos un horario que funcione para ambas teniendo en cuenta tu franja horaria. La mayoría de sesiones se agendan con antelación semanal.'],
            ['q' => '¿Qué necesito a nivel técnico?', 'a' => 'Un ordenador o móvil con cámara, conexión estable, auriculares y un espacio donde no te interrumpan durante los 50 minutos.'],
            ['q' => '¿Es todo confidencial?', 'a' => 'Sí. Todo lo que se habla en sesión está protegido por el secreto profesional, con las únicas excepciones legales de riesgo grave para tu vida o la de terceros.'],
            ['q' => '¿Cuántas sesiones voy a necesitar?', 'a' => 'Depende de cada persona y de sus objetivos. En la primera sesión te doy una orientación realista. Algunos procesos son breves y focalizados; otros requieren más tiempo.'],
            ['q' => '¿Cómo sé si estoy lista para empezar?', 'a' => 'Si algo te está pesando y quieres cambiarlo, es suficiente para empezar. La llamada gratuita de 15 minutos sirve justo para resolver esa duda.'],
            ['q' => '¿Cómo son los pagos?', 'a' => 'Lo vemos en la primera toma de contacto según tu país. Se abona por adelantado cada sesión o por bono, mediante transferencia o plataforma de pago.'],
        ],
    ],

    'contact_section' => [
        'title'    => 'Da el primer paso',
        'subtitle' => 'Escríbeme y te cuento cómo puedo ayudarte. Sin compromiso.',
        'subjects' => ['Reservar llamada de 15 min', 'Consulta sobre EMDR', 'Información de precios y horarios', 'Otra consulta'],
    ],

    'footer' => [
        'disclaimer' => 'Esta web tiene carácter informativo y no sustituye la atención psicológica ni médica. En caso de emergencia, contacta con los servicios de emergencia de tu país.',
        'privacy_note' => 'Los datos que envíes a través de los formularios se utilizan únicamente para responderte y gestionar una posible cita. No se ceden a terceros.',
    ],

    // Seguridad del login: valores por defecto de instalación. El admin puede
    // ajustar los canales/proveedores activos desde /admin/security (se
    // guarda en site_settings); esto solo son los valores de fábrica.
    'security' => [
        'default_channels' => ['email' => true, 'sms' => false, 'whatsapp' => false],
        'default_oauth' => ['google' => false, 'facebook' => false, 'microsoft' => false],
        'totp_enabled' => true,
        'trusted_device_days' => 30,
        'totp_issuer' => env('SITE_NAME', 'Lic. Erika Magaña'),
    ],
];
