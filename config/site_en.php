<?php

/*
|--------------------------------------------------------------------------
| Site configuration (English content)
|--------------------------------------------------------------------------
| Same shape as config/site.php, translated to English. Loaded by
| App\Http\Middleware\ResolveSiteLocale when the visitor's chosen language
| is English (see resources/views/partials/header.blade.php for the
| switch). Content edited from the admin panel (site_settings overrides)
| stays in Spanish only — this file only translates the factory-default
| content of home.blade.php, not admin-authored overrides.
*/

return [

    'demo_mode'   => env('SITE_DEMO_MODE', false),

    'name'        => env('SITE_NAME', 'Lic. Erika Magaña'),
    'role'        => 'Psychologist · Trauma & EMDR specialist',
    'tagline'     => 'Online therapy in Spanish',
    'registration'=> env('SITE_REGISTRATION', 'Colegiada J.V.P.P. 4615'),

    'contact' => [
        'whatsapp'      => env('SITE_WHATSAPP', '50360606225'),
        'whatsapp_show' => env('SITE_WHATSAPP_SHOW', '+503 6060 6225'),
        'email'         => env('SITE_EMAIL', 'contacto@ejemplo.com'),
        'area'          => 'United States and Europe',
        'response'      => 'I usually reply the same day',
    ],

    'whatsapp_prefill' => "Hi, I'd like to book a free 15-minute call to learn how therapy works.",

    'hero' => [
        'kicker'   => 'Specialized trauma therapy · EMDR · Online',
        'title'    => 'Find the calm you thought you had lost',
        'subtitle' => 'Online psychological therapy in Spanish to work through trauma, anxiety, and emotional challenges, wherever you are.',
        'points'   => [
            ['title' => 'Trust in your process', 'text' => 'A safe, judgment-free space to understand what you are going through and move at your own pace.'],
            ['title' => 'Calm in everyday life', 'text' => 'Practical tools to manage anxiety and stress that you can use from the very first session.'],
            ['title' => 'Wellbeing wherever you are', 'text' => 'Video call sessions adapted to your time zone, no matter where you live.'],
        ],
        'cta_primary'   => 'Message me on WhatsApp',
        'cta_secondary' => 'Book a free 15-min call',
    ],

    'about' => [
        'title' => 'About me',
        'lead'  => 'I support Spanish-speaking people living outside their home country. I know what it means to build a life in another language, miss your family, and feel like there is no one to really talk to. My work is to give you that space.',
        'paragraphs' => [
            'I am a licensed psychologist specialized in EMDR therapy, an approach recognized by the World Health Organization for treating trauma.',
            "In every session you'll find real listening, no rush and no judgment, plus something practical to take with you: an idea, an exercise, or a different way of looking at what you're going through.",
            'I work exclusively online, in Spanish, with people in the United States and Europe.',
        ],
        'credentials' => [
            'Licensed psychologist',
            'EMDR therapy specialist',
            'Colegiada J.V.P.P. 4615',
            '100% online care in Spanish',
        ],
        'photo' => 'images/aracely.jpg',
    ],

    'gallery' => [
        'images' => [
            ['path' => 'images/gallery/therapy-1.jpg', 'alt' => 'Comfortable, welcoming space for therapy'],
            ['path' => 'images/gallery/therapy-2.jpg', 'alt' => 'A moment of calm and wellbeing'],
            ['path' => 'images/gallery/therapy-3.jpg', 'alt' => 'Online therapy session from home'],
        ],
    ],

    'services' => [
        'title'    => 'How I can help',
        'subtitle' => "Here are some of the difficulties we work on in therapy. If yours isn't listed, write to me and we'll figure it out together.",
        'items' => [
            ['icon' => 'shield', 'title' => 'Psychological trauma', 'text' => 'Difficult or painful past experiences that still affect your present: intrusive memories, intense reactions, or a constant sense of alertness.'],
            ['icon' => 'wind', 'title' => 'Anxiety and stress', 'text' => 'Worry that never stops, racing thoughts, body tension, or anxiety crises that show up unannounced.'],
            ['icon' => 'cloud-rain', 'title' => 'Depression and grief', 'text' => 'Persistent sadness, lack of energy or purpose, and loss processes — of a person, a relationship, or a stage of life — that are hard to move through.'],
            ['icon' => 'heart', 'title' => 'Self-esteem', 'text' => 'Harsh self-criticism, feeling not good enough, and patterns that repeat in your relationships and in how you treat yourself.'],
            ['icon' => 'globe', 'title' => 'Migration and uprooting', 'text' => 'The toll of rebuilding your life in another country: loneliness, culture shock, guilt over distance, and an identity split between two places.'],
            ['icon' => 'users', 'title' => 'Family issues', 'text' => 'Relationships that hurt, boundaries that are hard to set, and conflicts that have dragged on for years.'],
        ],
    ],

    'sessions' => [
        'title' => 'What sessions are like',
        'items' => [
            ['label' => 'Duration', 'value' => '50 minutes'],
            ['label' => 'Frequency', 'value' => 'Weekly or biweekly'],
            ['label' => 'Format', 'value' => 'Video call'],
            ['label' => 'Language', 'value' => 'Spanish'],
            ['label' => 'Confidentiality', 'value' => 'Full'],
            ['label' => 'Schedule', 'value' => 'Adapted to your time zone'],
        ],
    ],

    'benefits' => [
        'title'    => 'What you can expect',
        'subtitle' => "I don't promise magic solutions. I do promise a serious, methodical process where you'll notice real change.",
        'items' => [
            'Understand what is happening to you and why, in clear language.',
            'Reduce the intensity of memories and emotions that overwhelm you today.',
            'Recover sleep, focus, and motivation.',
            'Set boundaries without feeling guilty.',
            'Relate from a calmer place, with yourself and with others.',
            'Leave every session with something concrete to apply.',
        ],
    ],

    'emdr' => [
        'title' => 'EMDR Therapy',
        'lead'  => 'EMDR (Eye Movement Desensitization and Reprocessing) is an approach based on how the brain works. Through guided stimulation — usually eye movements — we help your nervous system reprocess painful memories so it stops reacting as if the danger were still present.',
        'advantages' => [
            ['title' => 'No need to relive it in detail', 'text' => "You don't have to retell what happened over and over. We work with what affects you today."],
            ['title' => 'Scientific backing', 'text' => 'Recommended by the WHO and international clinical guidelines for trauma treatment.'],
            ['title' => 'Faster results', 'text' => 'In many cases, progress happens sooner than with purely conversational approaches.'],
            ['title' => 'Works online', 'text' => 'Bilateral stimulation adapts perfectly to video calls.'],
        ],
        'steps' => [
            ['n' => '1', 'title' => 'Write to me', 'text' => "Tell me by WhatsApp or email what brings you here, and we'll answer any questions."],
            ['n' => '2', 'title' => 'First session', 'text' => 'We get to know each other, review your history, and set goals.'],
            ['n' => '3', 'title' => 'Preparation', 'text' => 'You learn regulation skills before addressing the hardest parts.'],
            ['n' => '4', 'title' => 'Reprocessing', 'text' => 'We work on target memories with EMDR and consolidate the changes.'],
        ],
    ],

    'testimonials' => [
        'title' => 'What people who completed the process say',
        'note'  => 'Real testimonials shared with permission. Shortened, with identifying details omitted.',
        'items' => [
            ['name' => 'Luz C.', 'place' => 'Barcelona', 'text' => "I came in carrying childhood things I thought I'd already overcome. After the process I sleep well and my relationships changed completely. I wish I had done this sooner."],
            ['name' => 'Camila B.', 'place' => 'United States', 'text' => 'I was going through grief and a very hard situation at work. I regained my calm and the feeling of being in control of my life again.'],
            ['name' => 'Arely D.', 'place' => 'Italy', 'text' => "I understood where patterns I kept repeating in my relationship came from, without even realizing it. It was uncomfortable and freeing at the same time. I treat myself much better now."],
        ],
    ],

    'myths' => [
        'title'    => 'Myths about going to therapy',
        'subtitle' => 'Tap each card to flip it.',
        'items' => [
            ['myth' => '"Therapy is only for people with serious problems."', 'truth' => "Therapy is mental health care. You don't need to be in crisis to ask for help."],
            ['myth' => '"Asking for help is a sign of weakness."', 'truth' => 'Recognizing you need support and seeking it is one of the bravest things you can do.'],
            ['myth' => '"Willpower alone is enough."', 'truth' => 'Willpower helps, but trauma and anxiety live in the nervous system, not in motivation.'],
            ['myth' => '"What happens at home stays at home."', 'truth' => "Family loyalty doesn't conflict with talking about what hurts you in a confidential space."],
            ['myth' => "\"Online therapy doesn't work as well.\"", 'truth' => 'Evidence shows results equivalent to in-person therapy, including EMDR.'],
            ['myth' => "\"It's an expense I can't afford.\"", 'truth' => 'It is an investment in your wellbeing that shows in your health, your work, and your relationships.'],
        ],
    ],

    'checkup' => [
        'title'    => 'Emotional wellbeing check-in',
        'subtitle' => 'Five anonymous questions to get a sense of how you have been lately. This is not a diagnosis and does not replace professional consultation.',
        'period'   => 'Think about the last two weeks.',
        'options'  => ['Never', 'Sometimes', 'Often', 'Almost always'],
        'questions' => [
            'I find it hard to switch off from worries.',
            'I feel low on energy or unmotivated to do things.',
            'Difficult memories or thoughts show up without my wanting them to.',
            "I sleep poorly or wake up tired.",
            'I feel like I have no one to really talk to about what I am going through.',
        ],
        'results' => [
            'bajo'  => ['title' => "You seem to be in a reasonably stable place", 'text' => 'Even so, if something is weighing on you, talking to a professional always helps. Keep this space in mind if you ever need it.'],
            'medio' => ['title' => 'There are signs of strain worth paying attention to', 'text' => 'Working on this in therapy can help keep it from growing. Write to me and we can look at it, no strings attached.'],
            'alto'  => ['title' => "You're carrying quite a lot right now", 'text' => "You don't have to manage this alone. I'd recommend taking the step of seeking professional support. You can write to me whenever you're ready."],
        ],
        'disclaimer' => "If you are in an emergency situation or having thoughts of harming yourself, contact your country's emergency services immediately.",
    ],

    'faq' => [
        'title' => 'Frequently asked questions',
        'items' => [
            ['q' => 'What exactly is EMDR therapy?', 'a' => 'It is a psychological approach that uses bilateral stimulation (usually eye movements) to help the brain reprocess painful memories. It is recognized by the WHO for trauma treatment.'],
            ['q' => 'Does online therapy work as well as in-person?', 'a' => 'Yes. Scientific evidence shows equivalent results. You just need a quiet space, a good connection, and headphones.'],
            ['q' => 'How do we coordinate schedules if we are in different time zones?', 'a' => 'We agree on a time that works for both of us, taking your time zone into account. Most sessions are scheduled a week ahead.'],
            ['q' => 'What do I need technically?', 'a' => 'A computer or phone with a camera, a stable connection, headphones, and a space where you will not be interrupted for the 50 minutes.'],
            ['q' => 'Is everything confidential?', 'a' => 'Yes. Everything discussed in session is protected by professional confidentiality, with the only legal exceptions being serious risk to your life or that of others.'],
            ['q' => 'How many sessions will I need?', 'a' => 'It depends on each person and their goals. In the first session I give you a realistic estimate. Some processes are short and focused; others take more time.'],
            ['q' => "How do I know I'm ready to start?", 'a' => 'If something is weighing on you and you want to change it, that is enough to begin. The free 15-minute call is exactly for resolving that doubt.'],
            ['q' => 'How does payment work?', 'a' => "We go over this in our first contact, depending on your country. Payment is made in advance per session or per package, by bank transfer or payment platform."],
        ],
    ],

    'contact_section' => [
        'title'    => 'Take the first step',
        'subtitle' => 'Write to me and I will tell you how I can help. No strings attached.',
        'subjects' => ['Book a 15-min call', 'Question about EMDR', 'Pricing and schedule information', 'Other question'],
    ],

    'footer' => [
        'disclaimer' => 'This website is for informational purposes only and does not replace psychological or medical care. In an emergency, contact your country\'s emergency services.',
        'privacy_note' => 'The data you submit through the forms is used only to respond to you and manage a possible appointment. It is not shared with third parties.',
    ],

    'security' => [
        'default_channels' => ['email' => true, 'sms' => false, 'whatsapp' => false],
        'default_oauth' => ['google' => false, 'facebook' => false, 'microsoft' => false],
        'totp_enabled' => true,
        'trusted_device_days' => 30,
        'totp_issuer' => env('SITE_NAME', 'Lic. Erika Magaña'),
    ],
];
