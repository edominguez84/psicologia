<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Manual del sistema</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #1f2a37; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; color: #294a68; margin-bottom: 2px; }
        h2 { font-size: 16px; color: #294a68; margin-top: 26px; margin-bottom: 8px; border-bottom: 2px solid #e3edf7; padding-bottom: 5px; page-break-after: avoid; }
        h3 { font-size: 13px; color: #4c7a9c; margin-top: 16px; margin-bottom: 4px; page-break-after: avoid; }
        p.meta { color: #4b5a6b; font-size: 11px; margin-top: 0; margin-bottom: 20px; }
        p { margin: 6px 0; }
        ul, ol { margin: 6px 0; padding-left: 20px; }
        li { margin-bottom: 4px; }
        .cover-intro { background: #f4f8fc; border: 1px solid #e3edf7; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; }
        .role-badge { display: inline-block; font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 2px 8px; border-radius: 10px; margin-right: 4px; }
        .role-super { background: #e3edf7; color: #294a68; }
        .role-admin { background: #eef2f6; color: #4c7a9c; }
        .role-patient { background: #f7e9e0; color: #b9744c; }
        .step-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .step-table td { border: 1px solid #e3edf7; padding: 8px; vertical-align: top; font-size: 11px; }
        .step-table td.step-n { width: 24px; font-weight: bold; color: #294a68; text-align: center; }
        .step-table td.step-role { width: 90px; }
        .note { background: #fdf6ee; border: 1px solid #f0ddc4; border-radius: 6px; padding: 8px 10px; font-size: 11px; margin: 8px 0; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>Manual del sistema</h1>
    <p class="meta">{{ config('site.name') }} — Plataforma de terapia online · Generado el {{ now()->format('d/m/Y') }}</p>

    <div class="cover-intro">
        <p>
            Este manual explica cómo funciona la plataforma para cada rol — <strong>super administrador</strong>,
            <strong>administrador</strong> y <strong>paciente</strong> — y en particular el recorrido completo
            para que una cita quede efectivamente agendada y confirmada.
        </p>
    </div>

    <h2>1. Roles del sistema</h2>

    <h3><span class="role-badge role-super">Super administrador</span></h3>
    <p>
        Control total de la plataforma: configuración del sitio, métodos de pago (Wompi/transferencia),
        gestión de usuarios (banear, cambiar roles), aprobación de testimonios, filtro de contenido,
        informes, dashboard analítico, canales del chatbot y registro de auditoría. Es el único rol que
        puede crear cuentas de staff manualmente y ver los logs del sistema.
    </p>

    <h3><span class="role-badge role-admin">Administrador / Editor</span></h3>
    <p>
        Gestiona el contenido público del sitio (apariencia, secciones, textos, galería, contacto) y la
        operación diaria: mensajes recibidos, horarios de citas y llamadas gratis, y las solicitudes de
        cita (aprobar/rechazar). No tiene acceso a usuarios, pagos, testimonios ni configuración sensible
        — eso queda reservado al super administrador.
    </p>

    <h3><span class="role-badge role-patient">Paciente</span></h3>
    <p>
        Se registra públicamente desde el sitio. Puede editar su perfil, solicitar y ver sus propias
        citas, dejar un testimonio (sujeto a aprobación), y escribirle al chatbot. No tiene acceso al
        panel de administración.
    </p>

    <div class="page-break"></div>

    <h2>2. Cómo un paciente hace efectiva una cita</h2>
    <p>Recorrido completo, paso a paso, desde que una persona llega al sitio hasta que su cita queda confirmada.</p>

    <table class="step-table">
        <tr>
            <td class="step-n">1</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                Entra al sitio y hace clic en <strong>"Agendar una cita"</strong> (distinto del botón
                "Reserva una llamada gratis", que no requiere cuenta ni pago). Si no hay ningún horario
                de cita disponible en ese momento, este botón no aparece.
            </td>
        </tr>
        <tr>
            <td class="step-n">2</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                Si no tiene cuenta, se registra: nombre, email, teléfono, fecha de nacimiento, sexo,
                departamento y municipio (El Salvador), y una contraseña que debe cumplir el criterio de
                seguridad (mínimo 10 caracteres, con mayúscula, minúscula, número y símbolo — el
                formulario muestra un medidor de fuerza en vivo).
            </td>
        </tr>
        <tr>
            <td class="step-n">3</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                Recibe un código de 6 dígitos por email y lo ingresa para confirmar su cuenta (verificación
                en dos pasos). Sin este código no puede continuar. Puede marcar "recordar este
                dispositivo" para no repetir este paso en logins futuros desde el mismo navegador.
            </td>
        </tr>
        <tr>
            <td class="step-n">4</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                Ya en su perfil, va a <strong>"Mis citas"</strong>, elige un horario disponible, escribe
                una nota opcional, y selecciona el método de pago: <strong>transferencia bancaria</strong>
                o <strong>Wompi</strong> (tarjeta). Si eligió una promoción/plan desde la landing, el
                precio de esa promoción se refleja automáticamente en el monto a pagar.
            </td>
        </tr>
        <tr>
            <td class="step-n">5a</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                <strong>Si eligió transferencia:</strong> ve los datos bancarios configurados (banco,
                número de cuenta, titular) y las instrucciones. Hace la transferencia por su cuenta, toma
                captura del comprobante y la envía por WhatsApp. La cita queda como "avisada, en
                revisión" hasta que el super administrador confirme el pago manualmente.
            </td>
        </tr>
        <tr>
            <td class="step-n">5b</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                <strong>Si eligió Wompi:</strong> es redirigido automáticamente a la pantalla de pago de
                Wompi, ingresa los datos de su tarjeta, y al completar el pago, Wompi confirma la
                transacción a la plataforma de forma automática — no requiere ninguna acción manual del
                super administrador.
            </td>
        </tr>
        <tr>
            <td class="step-n">6</td>
            <td class="step-role"><span class="role-badge role-super">Super admin</span></td>
            <td>
                En <strong>Citas</strong>, revisa la columna "Pago": si dice <strong>"Confirmado"</strong>
                (automático con Wompi, o tras darle clic a "Confirmar pago" con transferencia), procede a
                decidir sobre la cita en sí.
            </td>
        </tr>
        <tr>
            <td class="step-n">7</td>
            <td class="step-role"><span class="role-badge role-super">Super admin / Admin</span></td>
            <td>
                Aprueba o rechaza la solicitud de cita (columna "Estado", independiente del estado de
                pago). El paciente recibe un email avisando la decisión.
            </td>
        </tr>
        <tr>
            <td class="step-n">8</td>
            <td class="step-role"><span class="role-badge role-patient">Paciente</span></td>
            <td>
                Ve en "Mis citas" el estado final: <strong>Aprobada</strong> (la cita queda agendada para
                el horario elegido) o <strong>Rechazada</strong>. Mientras la cita esté "Pendiente", puede
                cancelarla desde ahí mismo.
            </td>
        </tr>
    </table>

    <div class="note">
        <strong>Importante — aprobar la cita vs. confirmar el pago:</strong> son dos decisiones
        independientes en la misma fila de la tabla de Citas. El estado de pago (columna "Pago") indica
        si el dinero ya llegó; el estado de la cita (columna "Estado") indica si la psicóloga acepta
        atender en ese horario. Lo normal es aprobar la cita solo después de ver el pago confirmado.
    </div>

    <div class="page-break"></div>

    <h2>3. La llamada gratuita de 15 minutos (sin cuenta ni pago)</h2>
    <p>
        Es un flujo totalmente distinto y más simple: cualquier visitante, sin registrarse, puede hacer
        clic en <strong>"Reserva una llamada gratis de 15 min"</strong>, elegir un horario del catálogo
        que el super administrador o administrador configuró para llamadas gratis (separado del catálogo
        de citas pagadas), y dejar sus datos de contacto. El horario elegido queda reservado
        automáticamente. Si no hay horarios de llamada gratis disponibles, este botón no se muestra.
    </p>

    <h2>4. Configuración que hace posible este flujo (solo super admin)</h2>
    <ul>
        <li><strong>Horarios de citas</strong> y <strong>Horarios de llamada gratis</strong>: catálogos separados de franjas horarias disponibles.</li>
        <li><strong>Métodos de pago</strong>: credenciales de Wompi (App ID/API Secret, modo prueba o producción) y/o datos de transferencia bancaria con imagen del número de cuenta.</li>
        <li><strong>Promociones y planes</strong>: tarjetas con nombre, precio, descripción y vigencia que aparecen en la landing; su precio se refleja en el pago cuando el paciente elige una.</li>
        <li><strong>Usuarios</strong>: ver, banear (no se puede banear a alguien con sesión activa) y cambiar el rol de cualquier cuenta.</li>
        <li><strong>Testimonios</strong>: cada testimonio de paciente (con calificación de 1 a 5 estrellas) requiere aprobación antes de mostrarse públicamente; el texto se filtra contra un listado de palabras prohibidas configurable, y una cuenta que lo intente repetidamente queda suspendida automáticamente.</li>
        <li><strong>Canales del chatbot</strong>: credenciales de Telegram, WhatsApp Business y Facebook Messenger, más la llave de IA que genera las respuestas del bot de Telegram.</li>
        <li><strong>Dashboard analítico</strong>: visitas al sitio, clics por red social, conversión de registro a cita, género y ubicación de los pacientes, y citas aprobadas/canceladas por mes.</li>
    </ul>

    <h2>5. Idioma del sitio</h2>
    <p>
        La landing pública puede verse en español o inglés mediante el switch en el encabezado — la
        preferencia se recuerda en una cookie del navegador de cada visitante.
    </p>
</body>
</html>
