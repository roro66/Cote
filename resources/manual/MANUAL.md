# Manual de uso de COTE

COTE (Control de Tesorería) registra el dinero de la caja chica de la empresa: lo que entra a Tesorería, lo que se entrega a cada trabajador, lo que cada trabajador gasta y rinde con sus comprobantes, y el cierre de cada mes.

Este manual describe cada pantalla tal como funciona hoy. Cada sección indica **Quién puede** usarla: el asistente de COTE solo explica a cada usuario las pantallas que puede abrir.

## Perfiles de usuario

**Quién puede:** todos los usuarios

COTE tiene tres perfiles:

| Perfil | Rol en el sistema | Qué hace |
|---|---|---|
| Jefe | `boss` | Todo: personas, cuentas, transacciones, aprobaciones, estadísticas, informes, cierres, usuarios y ajustes manuales de saldo. Es el único que puede reabrir un mes cerrado y administrar usuarios. |
| Tesorero | `treasurer` | Lo mismo que el Jefe, salvo Usuarios, ajustes manuales de saldo y reabrir meses. Solo puede haber un Tesorero. |
| Trabajador | sin rol | Rinde sus propios gastos. Solo ve el Panel, sus Rendiciones y su Perfil. |

El trabajador debe tener su usuario vinculado a su ficha de Persona y esa persona debe tener una cuenta interna. Sin eso no puede crear rendiciones. Lo configura el Jefe en Usuarios y en Cuentas.

Los menús muestran solo lo que cada perfil puede abrir. Si alguien entra por enlace a una pantalla que no le corresponde, ve un error 403 (acceso denegado).

## Conceptos básicos: cuentas, saldos y flujo del dinero

**Quién puede:** todos los usuarios

- **Tesorería:** la cuenta de la caja chica de la empresa. Hay una sola. Su saldo es el dinero disponible para entregar.
- **Cuenta interna de una persona:** el saldo de cada trabajador dentro de COTE. Se crea en Cuentas y nace en $0.
- **Saldo positivo de un trabajador («Por rendir»):** tiene dinero de la empresa. Debe rendirlo con comprobantes o devolverlo.
- **Saldo negativo de un trabajador («Se le debe»):** gastó de su bolsillo más de lo que recibió. La empresa le debe esa diferencia.
- **Saldo cero («Al día»):** no debe ni se le debe nada.

Flujo típico:

1. **Ingreso a caja chica:** entra dinero a Tesorería (cheque, transferencia bancaria, etc.).
2. **Entrega:** transferencia de Tesorería a un trabajador. Su saldo sube.
3. **Rendición:** el trabajador registra lo que gastó, con fotos o PDF de las boletas. Al aprobarse, su saldo baja por el total rendido.
4. **Devolución:** si le sobró dinero, transferencia del trabajador a Tesorería.
5. **Pago de lo adeudado:** si quedó en negativo, se le hace una entrega desde Tesorería para dejarlo al día.
6. **Cierre de mes:** al terminar el mes se cuenta el dinero de Tesorería y se guarda un resumen.

Los saldos **solo cambian al aprobar**. Una transferencia o una rendición pendiente, rechazada o eliminada no mueve ningún saldo.

# Acceso al sistema

## Iniciar sesión

**Quién puede:** todos los usuarios

1. Abre COTE en el navegador con la dirección que te dieron (por ejemplo `http://192.168.200.250:8090` en la oficina).
2. Escribe tu **Correo electrónico** y tu **Contraseña**, tal como te los entregaron.
3. Si estás en tu propio equipo, puedes marcar **Recordarme**: la sesión queda abierta en ese navegador por mucho tiempo, aunque pases horas sin usarla. No la marques en equipos compartidos.
4. Pulsa **Iniciar sesión**. Llegas al Panel, o a la pantalla que habías pedido.

Sin **Recordarme**, la sesión se cierra sola después de 2 horas sin actividad.

Mensajes posibles:

- «Estas credenciales no coinciden con nuestros registros.»: el correo o la contraseña están mal.
- «Tu cuenta está desactivada.»: el Jefe desactivó tu usuario. Habla con él.
- «Demasiados intentos de inicio de sesión. Intenta de nuevo en N segundos.»: después de 5 intentos fallidos hay que esperar alrededor de un minuto.

## Olvidé mi contraseña

**Quién puede:** todos los usuarios

COTE no tiene recuperación de contraseña por correo. Si olvidaste tu contraseña, pídele al Jefe que te asigne una nueva desde **Usuarios** (ver «Cambiar la contraseña de otro usuario»). Después cámbiala tú mismo en tu Perfil.

## Cerrar sesión

**Quién puede:** todos los usuarios

Arriba a la derecha, abre el menú con tu nombre y elige **Cerrar sesión**. Hazlo siempre en equipos compartidos.

## Mi perfil y mi contraseña

**Quién puede:** todos los usuarios

Menú con tu nombre (arriba a la derecha) → **Perfil**.

**Información del perfil:** cambia tu **Nombre** o tu **Correo electrónico** y pulsa **Guardar**. Aparece «Cambios guardados.». El correo debe ir en minúsculas y no puede estar usado por otro usuario. Cambiar el correo de tu usuario no cambia el de tu ficha de Persona.

**Actualizar contraseña:**

1. Escribe tu **Contraseña actual**.
2. Escribe la **Nueva contraseña** (mínimo 8 caracteres) y repítela en **Confirmar contraseña**.
3. Pulsa **Guardar**. Aparece «Contraseña actualizada.».

Si la contraseña actual está mal, bajo el campo aparece «La contraseña es incorrecta.».

## Pantalla, menú, tema claro u oscuro y avisos

**Quién puede:** todos los usuarios

- **Menú lateral** a la izquierda. En el celular se abre con el botón ☰ de la barra superior.
- **Tema claro u oscuro:** botón con luna o sol, arriba a la derecha (también en la pantalla de inicio de sesión). La elección se recuerda en ese navegador; la primera vez sigue la configuración del equipo.
- **Avisos:** los mensajes de éxito o error aparecen arriba a la derecha y se cierran solos en unos segundos.
- **Confirmaciones:** antes de eliminar, aprobar o cerrar algo aparece un cuadro «Confirmar» con el botón de la acción y **Cancelar**.
- **Tablas:** casi todos los listados tienen **Buscar:**, el selector **Mostrar _ entradas** (10, 25, 50, 100 o Todos) y botones **Copiar**, **CSV**, **Excel**, **PDF**, **Imprimir** y **Columnas** (oculta columnas). Las exportaciones toman lo que está cargado en la tabla: para exportar todo, elige antes **Mostrar Todos**.

# Panel

## Panel de inicio

**Quién puede:** todos los usuarios

Menú **Panel**. Es la pantalla que aparece al entrar. Los datos se calculan al abrirla: para verlos actualizados, recarga la página.

**Todos ven «Rendiciones Recientes»:** las últimas 5 rendiciones, con número, descripción, fecha, monto y estado. El trabajador ve solo las suyas.

**Jefe y Tesorero ven además:**

- **Tarjetas:** «Total Cuentas» (cuentas activas), «Saldo Total» (suma de todas las cuentas activas), «Transacciones Pendientes» y «Rendiciones Pendientes».
- **Requiere atención:**
  - «Por rendir»: dinero de la empresa en manos de trabajadores (suma de saldos positivos). Lleva a Cuentas.
  - «Transferencias por aprobar» y «Rendiciones por aprobar»: monto y cantidad pendientes. Llevan a Aprobaciones.
  - «Se les debe (gastaron de su bolsillo)»: las 5 cuentas más negativas. Cada nombre abre sus movimientos.
- **Cuentas por Tipo:** cantidad y saldo de Tesorería y de las cuentas personales.
- **Transacciones Recientes:** las últimas 5. Las pendientes tienen botones **Aprobar** y **Rechazar** que actúan de inmediato, sin pedir confirmación. El rechazo desde aquí no pide motivo: queda registrado como «Rechazada desde dashboard».

# Rendiciones

## Qué es una rendición y sus estados

**Quién puede:** todos los usuarios

Una rendición agrupa uno o más gastos de una persona, cada uno con su comprobante (foto o PDF de la boleta, factura, etc.). Se numera `RND-AAAA-NNNNNN` (año y correlativo que parte de nuevo cada año).

Estados que se ven en pantalla:

- **Enviado:** recién creada o corregida. Espera aprobación. Se puede editar o eliminar.
- **Aprobado:** el Jefe o el Tesorero la aprobó y el total se descontó del saldo de la cuenta. Ya no se puede editar ni eliminar.
- **Rechazado:** se devolvió con un motivo. Se puede corregir y reenviar, o eliminar.

Al crear una rendición queda directamente **Enviado**: no hay borradores ni un paso aparte para enviarla.

Quién ve qué: el Jefe y el Tesorero ven todas las rendiciones. El trabajador ve solo las de su propia persona.

## Lista de rendiciones

**Quién puede:** todos los usuarios

Menú **Rendiciones**. Columnas: #, Número, Título (la descripción general), Solicitante, Cuenta, Total, Estado, Fecha Envío y Acciones. Primero aparecen las enviadas más recientemente.

**Buscar:** encuentra por descripción, número RND, nombre del solicitante o nombre de la cuenta.

Botones de la columna Acciones (cada uno aparece solo si corresponde):

- 👁 **Ver:** abre la ficha.
- ✏ **Editar:** solo si está Enviado o Rechazado.
- ✔ **Aprobar:** solo Jefe o Tesorero, y solo si está Enviado.
- 🗑 **Eliminar:** solo si está Enviado o Rechazado.

Para crear una nueva, pulsa **Nueva Rendición**.

## Crear una rendición

**Quién puede:** todos los usuarios

1. Menú **Rendiciones** → **Nueva Rendición**.
2. En **Cuenta**, elige la cuenta a la que se carga el gasto.
   - El trabajador solo ve su propia cuenta; si tiene una sola, ya viene elegida.
   - El Jefe y el Tesorero ven todas las cuentas personales y pueden rendir a nombre de otra persona. El solicitante será el dueño de la cuenta.
   - Al elegir la cuenta aparecen «Saldo actual» y «Saldo si se aprueba esta rendición». Si queda negativo se avisa: «Quedará en negativo: la empresa le deberá esa diferencia.».
3. Escribe la **Descripción general** (hasta 500 caracteres). Ejemplo: «Gastos semana del 14 al 18 de octubre».
4. Completa el primer **Gasto** (ver «Datos de cada gasto»). Para más gastos, pulsa **Agregar gasto**. Para quitar uno, el ícono de papelera **Quitar gasto** (debe quedar al menos uno).
5. Revisa el **Total**, que se calcula solo.
6. Pulsa **Crear rendición**. Aparece «Rendición creada correctamente.» y se abre su ficha, en estado Enviado.

Si algo falta, el campo queda en rojo con su mensaje. Si la lista de cuentas está vacía, tu usuario no está vinculado a una persona con cuenta: pídeselo al Jefe.

## Datos de cada gasto y comprobantes

**Quién puede:** todos los usuarios

Cada gasto pide:

| Campo | Regla |
|---|---|
| Fecha del comprobante | Obligatoria. No puede ser posterior a hoy. |
| Descripción | Obligatoria, hasta 255 caracteres. Ejemplo: «Almuerzo cuadrilla». |
| Monto (CLP) | Obligatorio, mínimo $1. |
| Tipo de documento | Boleta, Factura, Ticket, Guía de despacho o Vale. |
| Proveedor | Obligatorio, nombre del comercio. |
| N° de documento | Opcional, hasta 100 caracteres. |
| Categoría | Opcional. Si no eliges, queda «Sin categorizar». |
| Comprobantes | Fotos o PDF, con **Agregar archivos**. |

**Comprobantes:**

- Se aceptan **JPG, PNG, WEBP o PDF**, de hasta **10 MB** cada uno. Puedes elegir varios a la vez.
- Las fotos HEIC del iPhone no se aceptan. Configura la cámara en «Más compatible» o envía la foto como JPG.
- Si vuelves a pulsar **Agregar archivos** en el mismo gasto antes de guardar, la nueva selección reemplaza a la anterior.
- Cada gasto necesita **al menos un comprobante**. Si de verdad no hay boleta, marca **Sin comprobante (excepcional)** y escribe el motivo (por ejemplo «colación en local sin boleta»).

Mensajes frecuentes:

- «Adjunta al menos un comprobante, o marca "Sin comprobante" e indica el motivo.»
- «Indica por qué este gasto no tiene comprobante.»
- «Los comprobantes deben ser fotos (JPG, PNG o WEBP) o PDF.»
- «Cada comprobante puede pesar como máximo 10 MB.»

**Documentos duplicados:** si pones N° de documento, COTE revisa que el mismo tipo + proveedor + número no esté ya en la misma rendición ni en otra rendición enviada o aprobada. Si se repite aparece «Documento ya registrado en otra rendición (mismo tipo, proveedor y número).». Una boleta de una rendición rechazada o eliminada sí se puede volver a rendir.

## Ver una rendición (ficha)

**Quién puede:** todos los usuarios

Desde la lista, botón 👁 **Ver**. La ficha muestra:

- **Información General:** número, estado, cuenta, solicitante, fecha de envío, total y descripción. Si fue revisada, «Revisado por» y «Fecha de Revisión». Si fue rechazada, el **Motivo de Rechazo** en rojo.
- **Items de Gasto:** cada gasto con su tipo de documento, proveedor, número, monto y comprobantes. Las miniaturas abren el archivo. Un gasto sin archivos muestra «Sin comprobante» (con el motivo) o «Falta comprobante».

Botones: **Volver al Listado**, **PDF**, **Editar** o **Corregir y reenviar** (si se puede editar) y, para Jefe y Tesorero cuando está Enviado, **Aprobar** y **Rechazar**.

Los comprobantes son privados: solo los ven el Jefe, el Tesorero, quien los subió y el dueño de la rendición.

## PDF de la rendición para imprimir y firmar

**Quién puede:** todos los usuarios

En la ficha, botón **PDF**. Se abre en otra pestaña el archivo `rendicion_RND-….pdf` (tamaño carta) con:

- Encabezado con el número y el estado.
- Información general (solicitante con RUT, fechas, cuenta, revisor) y la descripción.
- Tabla de ítems: fecha, descripción, categoría, documento, comprobante y monto, con el total.
- Espacios de firma «Rendido por» y «Revisado por».
- Cada foto de comprobante en una página aparte, con la rotación corregida. Los comprobantes en PDF no se incluyen: se listan en «Adjuntos que no se muestran en este PDF».

## Editar una rendición enviada

**Quién puede:** todos los usuarios

Se puede editar mientras esté **Enviado** o **Rechazado**. La edita su dueño, el Jefe o el Tesorero.

1. En la lista, ✏ **Editar**; o en la ficha, **Editar**.
2. Cambia lo que necesites: cuenta, descripción, gastos, montos o comprobantes.
3. Los comprobantes ya adjuntos aparecen como miniaturas. Para quitar uno, marca su casilla **Quitar** (se ve tachado). Para agregar, **Agregar archivos**.
4. Pulsa **Guardar cambios**. Aparece «Rendición actualizada correctamente.».

Al guardar se borran definitivamente los gastos que quitaste y los comprobantes marcados «Quitar». Si cambias la cuenta, el solicitante pasa a ser el dueño de la nueva cuenta. Una rendición **Aprobado** no se puede editar: «Esta rendición ya fue aprobada y no se puede modificar.».

## Corregir y reenviar una rendición rechazada

**Quién puede:** todos los usuarios

1. Abre la rendición rechazada. Arriba se ve el motivo del rechazo.
2. Pulsa **Corregir y reenviar**.
3. El formulario muestra el aviso «Rendición rechazada: {motivo}. Al guardar los cambios se enviará nuevamente para aprobación.».
4. Corrige lo que se pidió y pulsa **Guardar y reenviar**.

La rendición vuelve a **Enviado**, con fecha de envío nueva, y se borra el motivo del rechazo.

## Eliminar una rendición

**Quién puede:** todos los usuarios

Solo mientras esté **Enviado** o **Rechazado**, y solo su dueño, el Jefe o el Tesorero.

1. En la lista, 🗑 **Eliminar**.
2. Confirma «¿Está seguro de que desea eliminar esta rendición?» con **Eliminar**.

Aparece «Rendición eliminada correctamente.». La rendición desaparece de los listados y de Aprobaciones. No mueve saldos. Una rendición aprobada no se puede eliminar.

## Efecto de una rendición en el saldo

**Quién puede:** todos los usuarios

- Al **aprobarse**, el total se **resta** del saldo de la cuenta elegida. La cuenta Tesorería no cambia.
- El saldo puede quedar **negativo**: significa que el trabajador gastó de su bolsillo y la empresa le debe esa diferencia.
- Ejemplo: un trabajador con $50.000 rinde $80.000. Al aprobarse queda en −$30.000 («Se le debe $30.000»). Para dejarlo al día, Tesorería le hace una entrega de $30.000.
- Enviar, editar, rechazar o eliminar una rendición no mueve saldos.
- Una rendición aprobada no se puede deshacer desde COTE.
- La **fecha de la rendición** es la del comprobante más reciente; se recalcula al guardar. Es la fecha que se ve en Aprobaciones y en el Panel.

# Aprobaciones

## Aprobar y rechazar rendiciones

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Aprobaciones** → pestaña **Rendiciones Pendientes** (el contador indica cuántas hay). Columnas: Fecha, Persona, Descripción, Monto Total, Items (con «N sin comprobante» si corresponde), Estado y Acciones.

**Revisar:** 👁 **Ver Detalles** abre la rendición completa con sus comprobantes. Revisa sobre todo los gastos marcados «sin comprobante».

**Aprobar:**

1. Pulsa ✔ **Aprobar**.
2. Revisa el resumen (persona, fecha, ítems, gastos sin comprobante, monto). «Al aprobar se actualizan los saldos.»
3. Pulsa **Aprobar**. Aparece «Rendición aprobada correctamente.» y sale de la lista.

También se aprueba desde la ficha de la rendición (botón **Aprobar**) o desde la lista de Rendiciones (✔).

**Rechazar:**

1. Pulsa ✖ **Rechazar** en la fila (o **Rechazar** en la ficha de la rendición).
2. Escribe el **Motivo del rechazo** (obligatorio, hasta 500 caracteres): di qué debe corregir.
3. Pulsa **Rechazar**. Aparece «Rendición rechazada correctamente.».

El trabajador ve el motivo en su rendición y puede corregirla y reenviarla.

## Aprobar y rechazar transferencias

**Quién puede:** rol `boss` o rol `treasurer`

Una transferencia o un ingreso a caja chica se crea **Pendiente** y no mueve saldos hasta que se aprueba.

**Aprobar** (cualquiera de estos caminos):

- **Aprobaciones** → pestaña **Transferencias Pendientes** → ✔ **Aprobar** → revisa N°, Desde, Hacia, Monto y Descripción → **Aprobar**. Aparece «Transacción aprobada correctamente.».
- **Transacciones** → ✔ en la fila → confirma «¿Está seguro de aprobar esta transacción?» con **Aprobar**.
- **Panel** → «Transacciones Recientes» → **Aprobar** (sin confirmación).

Al aprobar se vuelve a revisar que la cuenta de origen tenga fondos. Si no alcanza: «La cuenta origen no tiene fondos suficientes para esta transacción.». Las transferencias pendientes no reservan saldo: si hay dos entregas pendientes, la segunda puede fallar al aprobarse.

**Rechazar:**

1. En **Aprobaciones** → **Transferencias Pendientes**, o en **Transacciones**, pulsa ✗ **Rechazar** en la fila.
2. Escribe el motivo (obligatorio, hasta 500 caracteres) y pulsa **Rechazar**.

El motivo queda en las **Notas** de la transacción como «Motivo de rechazo: …». Desde el Panel también se puede rechazar, pero sin motivo.

# Transacciones

## Tipos de transacción y reglas

**Quién puede:** rol `boss` o rol `treasurer`

- **Transferencia:** mueve dinero entre Tesorería y la cuenta de un trabajador, en cualquiera de los dos sentidos.
  - **Entrega:** de Tesorería a un trabajador.
  - **Devolución:** de un trabajador a Tesorería.
- **Ingreso a caja chica:** dinero que entra desde afuera a Tesorería (cheque, transferencia bancaria, etc.). No tiene cuenta de origen.

Reglas:

- No se puede transferir de un trabajador a otro: siempre interviene Tesorería.
- Al crear una transferencia, la cuenta de origen debe tener saldo suficiente. Un trabajador con saldo 0 o negativo no puede hacer una devolución.
- Toda transacción nace **Pendiente** y mueve los saldos **solo al aprobarse**.
- Estados: **Pendiente**, **Aprobada** y **Rechazada**.
- Numeración: `TXN-AAAA-NNN` (año y correlativo que parte de nuevo cada año).
- Las transacciones no se pueden eliminar. Una equivocada se **rechaza**.

## Lista de transacciones

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Transacciones**. Columnas: #, Número, Tipo, Cuenta Origen, Cuenta Destino, Monto, Saldo (vacía en esta vista), Estado, Creado por, Fecha y Acciones. Primero aparecen las más recientes.

- En un ingreso a caja chica, la Cuenta Origen dice «Ingreso externo».
- **Buscar:** encuentra por número, nombre de la cuenta de origen o destino y nombre de quien la creó.
- **Acciones:** 👁 **Ver**; y si está pendiente, ✔ **Aprobar** y ✗ **Rechazar**.

## Entregar dinero a un trabajador

**Quién puede:** rol `boss` o rol `treasurer`

1. Menú **Transacciones** → **Nueva Transacción**.
2. **Tipo de Transacción:** «Transferencia (entrega o devolución)».
3. **Monto:** el monto a entregar.
4. **Cuenta Origen:** Tesorería (viene elegida).
5. **Cuenta Destino:** la cuenta del trabajador. Cada cuenta muestra su saldo actual.
6. **Descripción** (obligatoria, hasta 500 caracteres) y, si quieres, **Notas** (hasta 1000).
7. Pulsa **Crear Transacción**. Aparece «Transacción creada exitosamente».
8. Apruébala (ver «Aprobar y rechazar transferencias»). Recién ahí baja Tesorería y sube el saldo del trabajador.

Si Tesorería no tiene saldo suficiente: «La cuenta origen no tiene fondos suficientes para esta transacción.». Primero registra un ingreso a caja chica.

Esta misma entrega sirve para **pagarle a un trabajador que quedó en negativo** (gastó de su bolsillo): entrégale el monto que se le debe y su saldo vuelve a $0.

## Registrar una devolución de un trabajador

**Quién puede:** rol `boss` o rol `treasurer`

Cuando un trabajador devuelve dinero que le sobró:

1. **Transacciones** → **Nueva Transacción** → Tipo «Transferencia (entrega o devolución)».
2. **Cuenta Origen:** la cuenta del trabajador. **Cuenta Destino:** Tesorería.
3. Monto y Descripción → **Crear Transacción**.
4. Apruébala. El saldo del trabajador baja y Tesorería sube.

El trabajador debe tener saldo positivo igual o mayor al monto devuelto.

## Registrar un ingreso a caja chica

**Quién puede:** rol `boss` o rol `treasurer`

Cuando entra dinero a la caja chica desde fuera de COTE:

1. **Transacciones** → **Nueva Transacción**.
2. **Tipo de Transacción:** «Ingreso a caja chica (entra dinero a Tesorería)». La cuenta de destino queda fija en Tesorería y la de origen desaparece.
3. **Monto** y **Descripción**: indica de dónde viene el dinero (por ejemplo «Cheque N° 1234 de gerencia»).
4. **Crear Transacción** y luego apruébala. Al aprobarse, sube el saldo de Tesorería.

## Ver el detalle de una transacción

**Quién puede:** rol `boss` o rol `treasurer`

En Transacciones, 👁 **Ver**. Muestra número, tipo, monto, fecha de creación, cuentas de origen y destino (con su persona), descripción, notas, quién la creó y quién la aprobó o rechazó, con fecha.

Si está pendiente aparece el aviso «Esta transacción requiere aprobación para que se actualicen los saldos de las cuentas.». El motivo de un rechazo se ve en **Notas**.

## Editar una transacción pendiente

**Quién puede:** rol `boss` o rol `treasurer`

Solo transacciones **pendientes** (transferencias o ingresos a caja chica):

1. En Transacciones, 👁 **Ver** → **Editar**.
2. Cambia el tipo, las cuentas, el monto, la descripción o las notas. En un ingreso a caja chica el destino es siempre Tesorería.
3. Pulsa **Actualizar Transacción**: «Transacción actualizada correctamente.». En una transferencia se vuelven a revisar los fondos y el sentido.

Una transacción aprobada o rechazada ya no se edita: «Solo se pueden editar transacciones pendientes.».

# Cuentas

## Lista de cuentas y significado del saldo

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Cuentas**. Arriba, cuatro tarjetas:

- **Saldo Tesorería:** «Disponible para entregar».
- **Por rendir:** dinero en manos de trabajadores (saldos positivos).
- **Se les debe:** lo que la empresa debe a trabajadores que gastaron de su bolsillo (saldos negativos).
- **Cuentas:** cuántas están activas del total.

Columnas: #, Nombre, Tipo (Tesorería o Personal), Propietario (nombre y RUT), Saldo, Estado (Activa o Inactiva) y Acciones. El **Saldo** se muestra sin signo y con una etiqueta: «Disponible» (Tesorería), «Por rendir» (positivo), «Se le debe» (negativo) o «Al día» (cero).

**Buscar:** encuentra por nombre de la cuenta.

Acciones: 👁 **Ver** (detalle con el saldo con signo), ✏ **Editar**, **Movimientos** (cartola) y 🗑 **Eliminar**.

## Crear una cuenta

**Quién puede:** rol `boss` o rol `treasurer`

Cada trabajador necesita una cuenta personal para recibir entregas y rendir gastos. Crear la persona no crea la cuenta.

1. Menú **Cuentas** → **Nueva Cuenta**.
2. **Nombre de la Cuenta** (por ejemplo el nombre del trabajador).
3. **Tipo de Cuenta:** «Personal». Aparece **Persona Propietaria**: elige a la persona (solo personas activas).
4. **Notas** opcionales. **Cuenta habilitada** viene marcada.
5. Pulsa **Crear Cuenta**. Aparece «Cuenta creada exitosamente».

Toda cuenta nace con saldo **$0**: el dinero entra con una entrega desde Tesorería. Solo puede existir una cuenta Tesorería.

## Editar una cuenta y ajustar el saldo

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, ✏ **Editar**. Se pueden cambiar el nombre, el tipo, la persona propietaria, las notas y si está habilitada. Pulsa **Actualizar Cuenta**: «Cuenta actualizada exitosamente».

El **Saldo** cambia solo con transacciones y rendiciones aprobadas. Para el Tesorero el campo es de solo lectura.

**Ajuste manual de saldo (solo el Jefe):**

1. En **Saldo**, escribe el saldo correcto.
2. En **Motivo del ajuste de saldo**, explica por qué (obligatorio, hasta 255 caracteres). Ejemplo: «Saldo inicial».
3. Pulsa **Actualizar Cuenta**.

El ajuste queda registrado con el motivo y el nombre de quien lo hizo, y aparece en la cartola como «Ajuste manual». El campo no permite escribir saldos negativos. Si un trabajador parte con deuda o con dinero, conviene registrarlo con una entrega o un ajuste con motivo «Saldo inicial».

Mensajes: «Solo el jefe puede ajustar saldos manualmente.», «Indica el motivo del ajuste de saldo.».

## Movimientos de una cuenta (cartola)

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, botón **Movimientos** (también desde los nombres en «Se les debe» del Panel y desde Cierre de mes).

- **Tarjetas:** «Saldo actual», «Ingresos registrados», «Egresos registrados» y «Saldo inicial sin movimiento que lo respalde». Esta última muestra saldo que no viene de ningún movimiento registrado (saldos antiguos); lo normal es que sea $0.
- **Tabla:** Fecha, Tipo (Transferencia, Ingreso a caja chica, Rendición o Ajuste manual), Referencia (enlace a la transacción o rendición), Detalle, Ingreso, Egreso y Saldo después del movimiento. Primero los más recientes.
- Solo aparecen movimientos **aprobados**, con su fecha de aprobación. Sin movimientos: «Esta cuenta no tiene movimientos aprobados.».

**Exportar cartola a Excel:** descarga `cartola_{nombre}_{fecha}.xlsx` con encabezado (cuenta, RUT, saldo actual, fecha), una fila de saldo inicial, los movimientos del más antiguo al más reciente y una fila de TOTALES.

## Eliminar o desactivar una cuenta

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, 🗑 **Eliminar** y confirma. Solo se puede eliminar una cuenta sin historia. Se bloquea con:

- «Esta cuenta está protegida y no se puede eliminar.»
- «La cuenta de tesorería no se puede eliminar.»
- «No se puede eliminar la cuenta porque tiene transacciones asociadas» (de cualquier estado).
- «No se puede eliminar la cuenta porque tiene gastos asociados»

Si no se puede eliminar, **desactívala**: Editar → desmarca **Cuenta habilitada** → Actualizar Cuenta. Una cuenta inactiva ya no aparece para nuevas transacciones ni rendiciones, pero conserva su historia.

# Personas

## Lista de personas

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Personas**. Una persona es la ficha de cada trabajador (o del tesorero): nombre, RUT, contacto y datos bancarios.

- **Tarjetas:** «Total Personas», «Activas» (con porcentaje) y «Trabajadores».
- **Columnas:** #, Nombre Completo, RUT, Email, Teléfono, Banco, Tipo Cuenta, Estado y Acciones (👁 Ver, ✏ Editar, 🗑 Eliminar).
- **Buscar:** por nombre, apellido, RUT, email, teléfono, banco o tipo de cuenta. El RUT se busca **sin puntos** (por ejemplo `12345678`).
- **Exportar:** Copiar, CSV, Excel, PDF e Imprimir exportan todas las personas que coinciden con la búsqueda (no solo la página visible): nombre, RUT, email, teléfono, banco, tipo y número de cuenta, y estado.

## Crear una persona

**Quién puede:** rol `boss` o rol `treasurer`

1. Menú **Personas** → **Nueva Persona**.
2. Completa:
   - **Nombres** y **Apellidos** (obligatorios).
   - **RUT** (obligatorio, chileno válido, único). Se puede escribir con o sin puntos; se formatea solo. Si el dígito verificador no corresponde: «RUT inválido: el dígito verificador no corresponde.».
   - **Email** (obligatorio, único entre personas).
   - **Teléfono** (opcional, hasta 20 caracteres).
   - **Tipo de Rol:** Trabajador o Tesorero. Es solo informativo; los permisos se dan en Usuarios.
   - **Banco**, **Tipo de Cuenta**, **Número de Cuenta** y **Dirección** (opcionales).
   - **Usuario Activo:** viene marcado; indica que la persona está activa.
3. Pulsa **Crear Persona**. Aparece «Persona creada exitosamente».

Después:

- Para que pueda recibir dinero y rendir, **crea su cuenta** en Cuentas.
- Si va a entrar a COTE, **crea su usuario** en Usuarios y vincúlalo a esta persona.

Si el RUT ya pertenece a otra persona: «Este RUT ya está registrado.». Si ya existe un usuario con rol Tesorero, no se puede crear otra persona con Tipo de Rol Tesorero: «Ya existe un tesorero asignado. Quita ese rol antes de asignarlo a otra persona.».

## Editar o ver una persona

**Quién puede:** rol `boss` o rol `treasurer`

- **Ver:** 👁 abre el «Detalle de Persona», con todos sus datos y sus cuentas personales para transferencias.
- **Editar:** ✏ abre el mismo formulario de creación. Cambia lo necesario y pulsa **Actualizar Persona**: «Persona actualizada exitosamente».

Para desactivar a una persona, desmarca **Usuario Activo**. Una persona inactiva deja de aparecer al crear cuentas, al vincular usuarios y en Estadísticas. No bloquea el acceso de su usuario: para eso, desactiva el usuario en Usuarios.

## Cuentas bancarias personales para transferencias

**Quién puede:** rol `boss` o rol `treasurer`

Son los datos bancarios reales de la persona, para saber dónde depositarle. No reemplazan su cuenta interna de COTE y no mueven saldos.

Están dentro de **Editar** persona (para una persona nueva, primero créala y después edítala), en la sección «Cuentas personales para transferencias».

- **Agregar cuenta:** completa **Alias** (opcional), **Banco**, **Tipo de Cuenta** y **Número de Cuenta** (obligatorios), y las casillas **Predeterminada** y **Activa**. Pulsa **Guardar**: «Cuenta guardada».
- Se guarda al instante, sin necesidad de pulsar «Actualizar Persona».
- Al marcar una como **Predeterminada**, las demás de esa persona dejan de serlo.
- **Editar** o **Eliminar** con los íconos de la fila. Eliminar pide confirmar «¿Eliminar esta cuenta personal?».
- No se puede repetir el mismo banco, tipo y número para una persona.

## Eliminar una persona

**Quién puede:** rol `boss` o rol `treasurer`

En Personas, 🗑 **Eliminar** y confirma. Aparece «Persona eliminada exitosamente».

Se eliminan también sus cuentas bancarias personales y su cuenta interna, si está vacía y sin movimientos.

No se puede eliminar si tiene historia. El mensaje dice qué la bloquea, por ejemplo: «No se puede eliminar esta persona porque tiene gastos y usuario del sistema.». Los motivos posibles son:

- «una cuenta interna con saldo o movimientos»
- «gastos» (rendiciones a su nombre)
- «usuario con rol Tesorero» o «usuario del sistema» (tiene un usuario vinculado)
- «Esta persona está protegida y no se puede eliminar.»

En esos casos, desactívala en vez de eliminarla.

# Configuración

## Categorías de gasto

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Configuración → Categorías**. Las categorías clasifican cada gasto de las rendiciones y se usan en Estadísticas e Informes.

- **Agregar Categoría:** **Código** (opcional, hasta 20 caracteres, único), **Nombre** (obligatorio, único) y **Descripción** (opcional). Pulsa **Guardar**: «Categoría guardada exitosamente».
- **Editar:** botón de editar de la fila, cambia y **Guardar**.
- **Eliminar:** confirma. No se puede si algún gasto la usa: «No se puede eliminar la categoría porque tiene items asociados».

## Bancos

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Configuración → Bancos**. Lista de bancos, cooperativas y tarjetas de prepago que se eligen en las personas. Viene con 30 precargados.

- Columnas: ID, Código, Nombre, Personas Asociadas y Acciones.
- **Agregar Banco:** completa **Código** (hasta 10 caracteres, único), **Nombre** (único) y **Tipo** (Banco, Cooperativa o Tarjeta de prepago). Pulsa **Guardar**: «Banco guardado exitosamente».
- **Editar:** botón de editar de la fila; cambia código, nombre o tipo y pulsa **Guardar**.
- **Eliminar:** no se puede si alguna persona lo usa en su ficha: «No se puede eliminar el banco porque tiene personas asociadas».

En las personas, los bancos de tipo Cooperativa o Tarjeta de prepago aparecen con esa aclaración entre paréntesis.

## Tipos de cuenta bancaria

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Configuración → Tipos de Cuenta**. Son los tipos que se eligen en los datos bancarios de las personas: Cuenta Corriente, Cuenta Vista, Cuenta de Ahorro, Cuenta RUT y Chequera Electrónica, más los que se agreguen.

- **Agregar Tipo de Cuenta:** **Nombre** (obligatorio, único) → **Guardar**: «Tipo de cuenta guardado exitosamente».
- **Editar** y **Eliminar** desde la fila. No se puede eliminar si alguna persona lo usa: «No se puede eliminar el tipo de cuenta porque tiene personas asociadas».

# Estadísticas

## Gráficos de gasto

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Estadísticas**. Todos los gráficos cuentan solo rendiciones **aprobadas** y ubican cada gasto por la **fecha de su comprobante**. Los montos están en pesos chilenos.

- **Gasto mensual por persona (últimos 12 meses):** elige la **Persona**; el gráfico muestra el total mensual, incluido el mes en curso. Botones **Exportar Excel** (archivo con Mes y Total de esa persona) y **Descargar PNG** (imagen del gráfico).
- **Gasto por categoría (últimos N días):** escribe los **Días** (entre 7 y 3650; por defecto 90) y pulsa **Aplicar**. **Mostrar todas** incluye también las categorías sin gasto.
- **Gasto por categoría - últimos N meses:** elige **Meses** (1 a 60) y **Persona** (o Todas). Muestra las 8 categorías con más gasto.
- **Gastos por categoría de una persona:** elige la **Persona**. Muestra sus 12 categorías principales de los últimos 6 meses.
- **Gastos por técnico - últimos N meses:** elige **Meses** (1 a 24) y pulsa **Recargar**. Compara las 8 personas con más gasto rendido.

Pasa el cursor sobre el gráfico para ver los montos. Si cambias el tema claro u oscuro, recarga la página para que los gráficos vuelvan a mostrar tus filtros.

# Informes

## Informe de gastos mensuales

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Informes**. Se abre directamente la ventana **Gastos Mensuales** (si la cierras, vuelve a abrirse con **Generar** en la tarjeta «Gastos mensuales»).

1. **Fecha Inicial** y **Fecha Final:** por defecto, el mes anterior completo.
2. **Tipo de Informe:**
   - «Resumido»: totales por categoría.
   - «Detallado»: cada gasto, agrupado por categoría. Permite marcar **Incluir información de documentos adjuntos** (enlaces a los comprobantes).
3. **Estado de Aprobación:**
   - «Solo rendiciones aprobadas» (por defecto).
   - «Todas las rendiciones»: las aprobadas más las que esperan aprobación, que aparecen como «Pendiente». Las rechazadas y las eliminadas no se incluyen.
4. Pulsa **Generar Informe**.

El resultado muestra el período, el **Total General**, la cantidad de rendiciones e ítems, y la tabla por categoría. Los gastos sin categoría van en «Sin categoría». Una rendición entra en el informe según la **fecha de la rendición** (la de su comprobante más reciente).

**Exportar a Excel:** descarga `gastos_mensuales_{inicio}_a_{fin}.xlsx` con lo mismo que se ve en pantalla. El detallado agrega el N° de recibo de cada gasto y, si se incluyeron documentos, un resumen de los archivos adjuntos.

Si la fecha final es anterior a la inicial: «La fecha final debe ser igual o posterior a la fecha inicial.».

# Cierre de mes

## Qué es el cierre de mes

**Quién puede:** rol `boss` o rol `treasurer`

El cierre de mes guarda una foto fija del resumen de todas las cuentas al terminar un mes, junto con el dinero contado en Tesorería (arqueo) y observaciones.

- Cada movimiento cuenta en el mes en que **se aprobó**, no en la fecha del comprobante.
- Una vez cerrado, la pantalla y el Excel de ese mes muestran lo guardado y ya no cambian.
- **Cerrar un mes no bloquea nada:** se pueden seguir creando y aprobando transacciones y rendiciones. Lo que se apruebe después cuenta en el mes en curso.

## Ver el resumen de un mes

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Cierre de mes**. Por defecto muestra el **mes anterior**. Para otro mes, elígelo en **Mes** y pulsa **Ver**.

- **Estado:** «Abierto» o «Cerrado» (con quién y cuándo).
- **Tarjetas:** «Tesorería al cierre», «Por rendir (trabajadores)», «Se les debe» y «Rendido en el mes».
- **Tabla por cuenta:** Cuenta, Saldo inicial, Entradas (ingresos, entregas y devoluciones recibidas), Salidas (entregas y devoluciones hechas), Rendido (rendiciones aprobadas), Ajustes (manuales) y Saldo final. Tesorería va primero. En un mes abierto, cada cuenta enlaza a sus movimientos.
- **Exportar a Excel:** `cierre_AAAA-MM.xlsx` si está cerrado, o `cierre_AAAA-MM_provisorio.xlsx` si todavía está abierto (puede cambiar).

## Cerrar un mes

**Quién puede:** rol `boss` o rol `treasurer`

1. Abre **Cierre de mes** (viene el mes anterior) y revisa la tabla.
2. Cuenta el dinero real de Tesorería (caja y banco) y escríbelo en **Dinero contado en Tesorería**. El campo muestra como referencia «Según sistema: $X». Si lo dejas vacío, no se registra arqueo.
3. Escribe **Observaciones** si hace falta (hasta 2000 caracteres).
4. Pulsa **Cerrar mes** y confirma «¿Cerrar {mes}?» con **Cerrar mes**. Aparece «Mes {mes} cerrado.».

Después, en «Arqueo de Tesorería» se ve «Según sistema» y «Contado», con «Cuadra.» o «Diferencia: $Z» (negativa si falta dinero, positiva si sobra).

Reglas:

- Solo meses ya terminados: «Solo se pueden cerrar meses ya terminados.»
- En orden, sin saltarse meses: «Primero cierra {mes}.»
- No se puede cerrar un mes anterior al último cerrado ni volver a cerrar uno cerrado.

La lista **Meses cerrados** muestra cada cierre con quién y cuándo, y enlaza a su resumen.

## Reabrir un mes cerrado

**Quién puede:** rol `boss`

Solo el **último** mes cerrado:

1. Abre ese mes en **Cierre de mes** (desde «Meses cerrados» o con el selector).
2. Pulsa **Reabrir mes** y confirma «¿Reabrir {mes}? El resumen guardado se descartará.» con **Reabrir**.

Aparece «Cierre reabierto.». Se pierden el resumen guardado, el arqueo y las observaciones; el mes vuelve a calcularse en vivo y se puede cerrar de nuevo. Para otro mes: «Solo se puede reabrir el último mes cerrado.».

# Usuarios

## Lista de usuarios

**Quién puede:** rol `boss`

Menú **Usuarios** (o menú con tu nombre → **Administrar usuarios**). Columnas: #, Nombre, Email, Roles y Acciones (Editar, Eliminar). Para ver si un usuario está activo, ábrelo con Editar.

Roles disponibles:

- `boss`: Jefe.
- `treasurer`: Tesorero (solo uno).
- `team_leader` y `team_member`: no dan acceso adicional. Un usuario sin `boss` ni `treasurer` es un trabajador.

## Crear un usuario

**Quién puede:** rol `boss`

1. **Usuarios** → **Nuevo Usuario**.
2. **Nombre** y **Email** (único entre usuarios).
3. **Contraseña** y **Confirmar contraseña** (mínimo 8 caracteres). Entrégasela a la persona y pídele que la cambie en su Perfil.
4. **Persona vinculada:** elige su ficha de persona. Un trabajador sin persona vinculada no ve ninguna rendición ni puede crear rendiciones.
5. **Usuario Activo:** marcado.
6. **Roles:** para un trabajador no marques ninguno; para el Tesorero, `treasurer`; para el Jefe, `boss`.
7. Pulsa **Grabar**: «Usuario creado correctamente».

Si eliges `treasurer` y ya existe otro Tesorero, no se guarda y bajo los roles aparece «Ya existe un tesorero asignado. Quita ese rol antes de asignarlo a otro usuario.». Primero quítale el rol al Tesorero actual.

## Editar un usuario y cambiar su contraseña

**Quién puede:** rol `boss`

1. **Usuarios** → **Editar** en la fila.
2. Cambia nombre, email, persona vinculada, estado o roles.
3. Para darle una contraseña nueva (por ejemplo, si la olvidó), escríbela en **Nueva contraseña (opcional)** y en **Confirmar contraseña**. Si las dejas vacías, la contraseña no cambia.
4. Pulsa **Actualizar**: «Usuario actualizado correctamente».

Para dejar a alguien como trabajador, desmarca todos sus roles. Para traspasar el rol de Tesorero, quítaselo primero al Tesorero actual y después márcalo en el nuevo.

La lista «Persona vinculada» muestra las personas activas y, marcadas «— inactiva», las inactivas que ya tienen un usuario.

## Desactivar o eliminar un usuario

**Quién puede:** rol `boss`

- **Desactivar:** Editar → desmarca **Usuario Activo** → Actualizar. La persona queda fuera en su siguiente acción («Tu cuenta está desactivada.») y conserva toda su historia. Es lo recomendado cuando alguien deja la empresa.
- **Eliminar:** botón **Eliminar** y confirma «¿Eliminar este usuario?». No se puede eliminar:
  - al usuario administrador `admin@cote.com`;
  - a ti mismo;
  - a un usuario con historia: «No se puede eliminar el usuario porque tiene registros históricos (transacciones, aprobaciones, gastos o documentos). Desactiva el usuario en su lugar.»

# Preguntas frecuentes

## Un trabajador no puede crear rendiciones o no ve su cuenta

**Quién puede:** todos los usuarios

Si eres trabajador y la lista **Cuenta** aparece vacía al crear una rendición, pídele al Jefe que revise tu configuración.

El Jefe debe revisar tres cosas:

1. En **Usuarios**, que el usuario esté activo y tenga una **Persona vinculada**.
2. En **Personas**, que esa persona esté activa.
3. En **Cuentas**, que exista una cuenta **Personal**, habilitada, con esa persona como propietaria. Si falta, se crea en Cuentas → Nueva Cuenta.

## Cómo empezar a usar COTE desde cero

**Quién puede:** rol `boss` o rol `treasurer`

1. **Personas:** crea a cada trabajador.
2. **Cuentas:** crea una cuenta Personal para cada uno (nacen en $0).
3. **Usuarios** (solo el Jefe): crea el usuario de cada trabajador que rendirá y vincúlalo a su persona.
4. **Transacciones:** registra un **ingreso a caja chica** a Tesorería y apruébalo.
5. **Transacciones:** entrega dinero a cada trabajador y aprueba las entregas.
6. Los trabajadores rinden sus gastos; el Jefe o el Tesorero aprueban o rechazan.
7. Si un trabajador ya tenía dinero o deuda al partir, el Jefe ajusta su saldo en Cuentas → Editar con motivo «Saldo inicial».
8. Al terminar cada mes, **Cierre de mes**.

## Por qué no cambia el saldo de una cuenta

**Quién puede:** todos los usuarios

Los saldos cambian solo cuando una transferencia, un ingreso a caja chica o una rendición se **aprueba**. Si está Pendiente o Enviado, falta aprobarla en **Aprobaciones**. Si fue rechazada o eliminada, no mueve el saldo.

## Qué significa que un trabajador tenga saldo negativo

**Quién puede:** todos los usuarios

Que rindió más de lo que se le entregó: gastó de su bolsillo y la empresa le debe esa diferencia. Aparece como «Se le debe» en Cuentas y en «Se les debe» del Panel. Para pagarle, se le hace una **entrega** desde Tesorería por ese monto y se aprueba.

## Me equivoqué en una transacción

**Quién puede:** rol `boss` o rol `treasurer`

- Si está **Pendiente**: edítala (Ver → Editar), o recházala con motivo y crea una nueva.
- Si ya está **Aprobada**: no se puede editar ni eliminar. Corrígela con la transacción contraria (por ejemplo, una devolución por el mismo monto) o pídele al Jefe un ajuste manual de saldo con su motivo.

## Me equivoqué en una rendición

**Quién puede:** todos los usuarios

- Si está **Enviado** o **Rechazado**: edítala o elimínala.
- Si ya está **Aprobado**: no se puede modificar. Habla con el Jefe o el Tesorero: pueden compensarla con una entrega, una devolución o un ajuste de saldo.
