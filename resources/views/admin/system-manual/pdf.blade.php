<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Manual del sistema</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1f2a37; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; color: #294a68; margin-bottom: 2px; }
        h2 { font-size: 16px; color: #294a68; margin-top: 4px; margin-bottom: 8px; border-bottom: 2px solid #e3edf7; padding-bottom: 5px; page-break-after: avoid; }
        p.meta { color: #4b5a6b; font-size: 11px; margin-top: 0; margin-bottom: 20px; }
        p.step-text { margin: 4px 0 10px; }
        .cover-intro { background: #f4f8fc; border: 1px solid #e3edf7; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
        .role-badge { display: inline-block; font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 3px 10px; border-radius: 10px; margin-bottom: 6px; }
        .role-patient { background: #f7e9e0; color: #b9744c; }
        .role-admin { background: #e3edf7; color: #294a68; }
        .step { margin-bottom: 22px; page-break-inside: avoid; }
        .step-shot { width: 100%; border: 1px solid #e3edf7; border-radius: 6px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Manual del sistema</h1>
    <p class="meta">{{ config('site.name') }} — Plataforma de terapia online · Generado el {{ now()->format('d/m/Y') }}</p>

    <div class="cover-intro">
        <p>
            Este manual muestra, con capturas reales de pantalla, el recorrido completo para que una
            cita quede efectivamente agendada y confirmada — desde el lado del <strong>paciente</strong>
            y desde el lado del <strong>administrador</strong>.
        </p>
    </div>

    <h2>Lado del paciente</h2>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">
            Entra al sitio y hace clic en <strong>"Agendar una cita"</strong> — distinto del botón de
            llamada gratis, que no requiere cuenta ni pago. Si no hay ningún horario disponible, este
            botón no aparece.
        </p>
        <img class="step-shot" src="{{ public_path('manual/01-home-agendar.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">Si no tiene cuenta, se registra con sus datos y una contraseña segura.</p>
        <img class="step-shot" src="{{ public_path('manual/02-registro.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">
            Recibe un código de 6 dígitos por email y lo ingresa para confirmar su cuenta. Sin este
            paso no puede continuar.
        </p>
        <img class="step-shot" src="{{ public_path('manual/03-verificacion-2fa.png') }}">
    </div>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">Ya dentro, ve y puede editar su perfil.</p>
        <img class="step-shot" src="{{ public_path('manual/04-perfil-paciente.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">
            En "Mis citas", elige un horario disponible, escribe una nota opcional y selecciona el
            método de pago (transferencia bancaria o Wompi). Si eligió una promoción desde la landing,
            su precio ya se refleja aquí.
        </p>
        <img class="step-shot" src="{{ public_path('manual/05-mis-citas-formulario.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-patient">Paciente</span>
        <p class="step-text">
            Su cita queda en el historial como "Pendiente", con el estado del pago ("Avisado, en
            revisión" para transferencia, o "Confirmado" automáticamente con Wompi).
        </p>
        <img class="step-shot" src="{{ public_path('manual/06-mis-citas-historial.png') }}">
    </div>

    <div class="page-break"></div>

    <h2>Lado del administrador</h2>

    <div class="step">
        <span class="role-badge role-admin">Administrador</span>
        <p class="step-text">Al entrar al panel, ve un resumen general y accesos rápidos al contenido del sitio.</p>
        <img class="step-shot" src="{{ public_path('manual/07-panel-admin.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-admin">Administrador</span>
        <p class="step-text">
            En <strong>Citas</strong>, ve la solicitud del paciente. Las columnas "Estado" (aprobar o
            rechazar la cita) y "Pago" (confirmar que el dinero llegó) son decisiones independientes.
        </p>
        <img class="step-shot" src="{{ public_path('manual/08-admin-citas-pendiente.png') }}">
    </div>

    <div class="page-break"></div>

    <div class="step">
        <span class="role-badge role-admin">Administrador</span>
        <p class="step-text">
            Tras confirmar el pago y aprobar la cita, el paciente recibe un email con la decisión y su
            cita queda agendada para el horario elegido.
        </p>
        <img class="step-shot" src="{{ public_path('manual/09-admin-cita-aprobada.png') }}">
    </div>
</body>
</html>
