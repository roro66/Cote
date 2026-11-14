# Manual de uso de COTE

COTE (Control de Tesorería) registra el dinero de la caja chica de la empresa: lo que entra a la caja, lo que se entrega a cada trabajador, lo que cada trabajador gasta y rinde con sus boletas, y el cierre de cada mes.

Este manual describe cada pantalla tal como funciona hoy. Cada sección indica **Quién puede** usarla: el asistente de COTE solo explica a cada usuario las pantallas que puede abrir.

## Perfiles de usuario

**Quién puede:** todos los usuarios

COTE tiene tres perfiles:

| Perfil | Rol en el sistema | Qué hace |
|---|---|---|
| Jefe | `boss` | Todo: entregas, devoluciones, ingresos, rendiciones, trabajadores, cuentas, estadísticas, informes, cierres, usuarios y ajustes manuales de saldo. Es el único que puede reabrir un mes cerrado y administrar usuarios. |
| Tesorero | `treasurer` | Lo mismo que el Jefe, salvo Usuarios, ajustes manuales de saldo y reabrir meses. Solo puede haber un Tesorero. |
| Trabajador | sin rol | Rinde sus propios gastos. Solo ve el Panel, sus Rendiciones, el Asistente y su Perfil. |

El trabajador debe tener su usuario vinculado a su ficha de Persona. Lo configura el Jefe en Usuarios.

Los menús muestran solo lo que cada perfil puede abrir. Si alguien entra por enlace a una pantalla que no le corresponde, ve un error 403 (acceso denegado).

## Conceptos básicos: la caja, los trabajadores y el dinero

**Quién puede:** todos los usuarios

- **La caja (Tesorería):** el dinero de la caja chica de la empresa. Es lo disponible para entregar.
- **Cuenta de un trabajador:** cuánto dinero de la empresa tiene cada trabajador. Se crea sola al agregar a la persona y nace en $0.
- **«Tiene en su poder»:** el trabajador tiene dinero de la empresa. Debe rendirlo con boletas o devolverlo.
- **«Le debemos»:** el trabajador gastó de su bolsillo más de lo que recibió. La empresa le debe esa diferencia.
- **«Está al día»:** no tiene dinero de la empresa ni se le debe nada.

Flujo típico:

1. **Entró dinero a la caja:** cheque, transferencia o efectivo de la empresa.
2. **Entrega:** sale dinero de la caja hacia un trabajador.
3. **Rendición:** el trabajador registra lo que gastó, con fotos o PDF de las boletas. Al aprobarse, baja lo que tiene en su poder.
4. **Devolución:** si le sobró dinero, lo devuelve a la caja.
5. **Pagarle lo que le debemos:** si gastó de su bolsillo, se le hace una entrega por ese monto.
6. **Cierre de mes:** al terminar el mes se cuenta el dinero de la caja y se guarda un resumen.

Las entregas, devoluciones e ingresos que registran el Jefe o el Tesorero **quedan listos al guardar**: los saldos cambian de inmediato. Las rendiciones de los trabajadores cambian los saldos recién cuando el Jefe o el Tesorero las aprueban. Lo que ya está hecho se puede **anular**.

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

COTE no tiene recuperación de contraseña por correo. Si olvidaste tu contraseña, pídele al Jefe que te asigne una nueva desde **Usuarios** (ver «Editar un usuario y cambiar su contraseña»). Después cámbiala tú mismo en tu Perfil.

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

## Pantalla, botones rápidos, menú, tema y avisos

**Quién puede:** todos los usuarios

- **Botones rápidos de colores** en la barra superior, para lo de todos los días:
  - Jefe y Tesorero: **Entregar dinero**, **Me devolvieron**, **Entró dinero**, **Revisar rendiciones** (con cuántas hay por revisar), **Libro de caja**, **Cerrar mes** y **Asistente**.
  - Trabajador: **Nueva rendición**, **Mis rendiciones** y **Asistente**.
  - En pantallas chicas se ven solo los íconos.
- **Menú lateral** a la izquierda, con todas las pantallas. En el celular se abre con el botón ☰.
- **Tema claro u oscuro:** botón con luna o sol, arriba a la derecha. La elección se recuerda en ese navegador.
- **«¿Qué es esto?»:** el ícono ⓘ junto a algunas palabras explica qué significan. Haz clic en él.
- **Avisos:** los mensajes de éxito o error aparecen arriba a la derecha y se cierran solos.
- **Confirmaciones:** antes de eliminar, aprobar o cerrar algo aparece un cuadro «Confirmar» con el botón de la acción y **Cancelar**.
- **Tablas:** tienen **Buscar:**, el selector **Mostrar _ entradas** (10, 25, 50, 100 o Todos) y el botón **Descargar Excel**. El Excel trae lo que está cargado en la tabla: para bajar todo, elige antes **Mostrar Todos**.

# Panel

## Panel de inicio y lo que tienes que hacer hoy

**Quién puede:** todos los usuarios

Menú **Panel**. Es la pantalla que aparece al entrar. Los datos se calculan al abrirla: para verlos actualizados, recarga la página.

**Lo que tienes que hacer hoy:** arriba de todo, una lista con un botón en cada cosa.

- Jefe y Tesorero: rendiciones por revisar (**Revisar**), movimientos antiguos esperando aprobación, el mes que falta cerrar (**Cerrar mes**) y los trabajadores que llevan más de 30 días con dinero sin rendir (**Ver cartola**).
- Trabajador: cuánto dinero de la empresa tiene (**Nueva rendición**), si le debemos dinero y las rendiciones que le devolvieron para corregir (**Ver y corregir**).
- Si no hay nada: «Todo al día. No hay nada pendiente.».

**Pregúntale al asistente:** accesos con preguntas frecuentes que abren el Asistente.

**Jefe y Tesorero ven además:** tarjetas con el total de cuentas, el saldo total, los movimientos pendientes y las rendiciones pendientes; «Requiere atención» con lo que está **En poder de trabajadores**, lo que hay por aprobar y a quiénes **Les debemos**; las cuentas por tipo y los **Últimos movimientos**.

**Todos ven «Rendiciones Recientes»:** las últimas 5. El trabajador ve solo las suyas.

# Tareas de todos los días

## Entregar dinero a un trabajador

**Quién puede:** rol `boss` o rol `treasurer`

Botón verde **Entregar dinero** de la barra superior (o **Entregar** en la tarjeta del trabajador, en Trabajadores).

1. **Quién:** haz clic en la tarjeta del trabajador. Cada tarjeta dice si «tiene $X en su poder», si «le debemos $X» o si «está al día». Puedes escribir su nombre para buscarlo.
2. **Cuánto:** escribe el monto. Los puntos de miles se ponen solos (escribe 50000 y aparece 50.000). Si la caja no alcanza, aparece de inmediato «La caja solo tiene $X». Si le debemos dinero, el botón **Pagarle lo que le debemos** escribe ese monto.
3. **Detalle:** un texto corto para acordarte después (viene escrito «Entrega de dinero para gastos»; puedes cambiarlo).
4. **Confirmar:** COTE te muestra en palabras qué va a pasar, por ejemplo «Vas a entregar **$50.000** a **Juan Pérez**. La caja queda con $120.000. Juan queda: tiene $80.000 en su poder.». Si está bien, pulsa **Sí, confirmo**. Si no, **Atrás**.

Al confirmar aparece «¡Listo!» y los saldos ya cambiaron. Desde ahí puedes **Registrar otro**, **Volver al inicio** o **Me equivoqué, anular**.

Esta misma entrega sirve para **pagarle a un trabajador al que le debemos**: el botón de su tarjeta dice **Pagarle**.

## Me devolvieron dinero

**Quién puede:** rol `boss` o rol `treasurer`

Cuando un trabajador devuelve dinero que le sobró: botón **Me devolvieron** de la barra superior (o **Devolvió** en su tarjeta, en Trabajadores).

1. **Quién:** elige al trabajador.
2. **Cuánto:** escribe el monto. No puede ser más de lo que tiene en su poder: «Juan solo tiene $X en su poder.». El botón **Devolvió todo** escribe el total.
3. **Detalle** y **Confirmar** con **Sí, confirmo**.

La caja sube y baja lo que el trabajador tiene en su poder.

## Entró dinero a la caja

**Quién puede:** rol `boss` o rol `treasurer`

Cuando entra dinero nuevo a la caja chica: botón azul **Entró dinero** de la barra superior.

1. **Cuánto:** escribe el monto.
2. **De dónde:** elige «Cheque de la empresa», «Transferencia bancaria de la empresa» o «Efectivo entregado por el jefe», o escribe con tus palabras (por ejemplo «Cheque N° 1234»). Es obligatorio.
3. **Confirmar:** «Entran $X a la caja. La caja queda con $Y.» → **Sí, confirmo**.

## Me equivoqué: anular un movimiento

**Quién puede:** rol `boss` o rol `treasurer`

Una entrega, devolución o ingreso ya hecho se puede **anular**: los saldos vuelven a como estaban y el movimiento queda marcado «Anulada», con quién, cuándo y por qué.

- **Recién hecho:** en la pantalla «¡Listo!», botón **Me equivoqué, anular** y escribe qué estuvo mal.
- **Después:** en **Movimientos**, botón ↶ **Anular** de la fila; o en el detalle del movimiento, botón **Anular**. Escribe el motivo y confirma.

No se puede anular si la caja ya no tiene ese dinero (por ejemplo, un ingreso que ya se entregó): «La caja no tiene dinero suficiente para anular este movimiento.». En ese caso, registra primero el ingreso que corresponda.

Después de anular, si hacía falta, registra el movimiento correcto.

## Libro de caja

**Quién puede:** rol `boss` o rol `treasurer`

Botón morado **Libro de caja** de la barra superior (o menú **Libro de caja**). Es el cuaderno de la caja: todo el dinero que entró y salió en el mes, en orden de fecha.

- Arriba: **Al empezar el mes**, **Entró**, **Salió** y **Hay ahora en la caja** (o **Quedó al terminar el mes**, en meses pasados).
- Tabla: **Fecha**, **Qué pasó** (Entrega, Devolución o Ingreso a caja chica), **Detalle**, **Entrada**, **Salida** y **Saldo** después de cada movimiento. Al final, los totales del mes.
- Las flechas ‹ › cambian de mes.
- **Descargar Excel** baja el mismo libro del mes.

Lo que cada trabajador gastó no está aquí: está en sus rendiciones.

## Trabajadores y su dinero

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Trabajadores**. Una tarjeta por trabajador activo, con su situación en palabras: «Tiene $X en su poder», «Le debemos $X» o «Está al día».

Botones de cada tarjeta:

- **Entregar** (o **Pagarle**, si le debemos): abre la entrega con el trabajador ya elegido.
- **Devolvió:** abre la devolución (solo si tiene dinero en su poder).
- **Rendiciones:** sus rendiciones.
- **Cartola:** todos sus movimientos.
- **Crear su cuenta:** aparece solo si todavía no tiene cuenta para recibir dinero.

Arriba: **En poder de trabajadores** y **Les debemos** en total, la búsqueda por nombre, **Importar desde Excel** y **Agregar trabajador** (abre el formulario de nueva persona).

## Importar trabajadores desde Excel

**Quién puede:** rol `boss` o rol `treasurer`

Para cargar de una vez la nómina que se llevaba en Excel. Menú **Trabajadores** → **Importar desde Excel**.

1. **Descargar planilla modelo** (opcional): trae las columnas Nombres, Apellidos, RUT, Correo, Teléfono, Dirección, Banco, Tipo de cuenta, N° de cuenta y Saldo actual, y una hoja de instrucciones. También sirve la planilla propia: COTE reconoce títulos como «Nombre completo», «Rut», «Banco», «N° Cta» o «Por rendir».
2. Elige el archivo (Excel .xlsx, CSV u OpenOffice .ods) y pulsa **Revisar archivo**. Un Excel antiguo (.xls) hay que abrirlo y guardarlo como .xlsx.
3. COTE muestra cada fila **sin guardar nada**: «Nuevo», «Se actualiza» (el RUT ya existe), «Otra cuenta» (mismo RUT repetido: se agrega otra cuenta bancaria) o «No se importa» en rojo, con el motivo (RUT inválido, falta el nombre). Los avisos en amarillo explican lo que se omitió, por ejemplo un banco que no se reconoce o un correo que ya usa otra persona.
4. Pulsa **Importar N trabajadores** y confirma.

Qué hace al importar:

- Crea a cada trabajador con su cuenta para recibir dinero y sus cuentas bancarias. La primera queda como predeterminada.
- Si el RUT ya existe, actualiza los datos que vienen en la planilla. Las celdas vacías no borran nada.
- **Saldo actual** (opcional): dinero de la empresa que el trabajador tiene hoy; con signo menos si le debemos. Queda en su cartola como «Saldo inicial importado desde Excel». La caja no cambia. Solo se aplica a cuentas que todavía no tienen movimientos.
- «Cuenta RUT» se entiende como BancoEstado y, si falta el número, usa el RUT sin dígito verificador.
- Nombres escritos todo en mayúsculas quedan como «Juan Pérez». Un nombre completo se separa dejando los dos últimos apellidos.

Las filas en rojo se pueden corregir en el Excel y volver a subir: los ya importados solo se actualizan.

## Revisar rendiciones de a una

**Quién puede:** rol `boss` o rol `treasurer`

Botón naranja **Revisar rendiciones** de la barra superior (el número dice cuántas hay). Muestra la rendición más antigua y «Quedan N por revisar».

- A la izquierda, las **fotos de las boletas** en grande (haz clic para verlas completas; los PDF se abren aparte).
- A la derecha, el trabajador, el total, cada gasto (con «Sin boleta: motivo» o «Falta la boleta» si corresponde) y cómo queda si la apruebas.

Botones:

- **Está bien, aprobar:** se aprueba y aparece la siguiente.
- **Devolver para corregir:** elige uno o más motivos listos («Falta la boleta», «La foto no se lee», «El monto no coincide con la boleta», «La fecha no corresponde», «Falta explicar en qué se gastó») o escribe con tus palabras, y pulsa **Devolver**. El trabajador verá el motivo.
- **Ver la siguiente:** deja esta para después.

Cuando no quedan: «No hay rendiciones por revisar».

# Rendiciones

## Qué es una rendición y sus estados

**Quién puede:** todos los usuarios

Una rendición agrupa uno o más gastos de una persona, cada uno con su comprobante (foto o PDF de la boleta, factura, etc.). Se numera `RND-AAAA-NNNNNN` (año y correlativo que parte de nuevo cada año).

Estados que se ven en pantalla:

- **Enviado:** recién creada o corregida. Espera aprobación. Se puede editar o eliminar.
- **Aprobado:** el Jefe o el Tesorero la aprobó y el total se descontó de lo que el trabajador tiene en su poder. Ya no se puede editar ni eliminar (el Jefe o el Tesorero pueden anular la aprobación).
- **Rechazado:** se devolvió con un motivo. Se puede corregir y reenviar, o eliminar.

Al crear una rendición queda directamente **Enviado**: no hay borradores ni un paso aparte para enviarla.

Quién ve qué: el Jefe y el Tesorero ven todas las rendiciones. El trabajador ve solo las suyas.

## Lista de rendiciones

**Quién puede:** todos los usuarios

Menú **Rendiciones** (el trabajador también tiene el botón **Mis rendiciones**). Columnas: #, Número, Título (la descripción general), Solicitante, Cuenta, Total, Estado, Fecha Envío y Acciones. Primero aparecen las enviadas más recientemente.

**Buscar:** encuentra por descripción, número RND, nombre del solicitante o nombre de la cuenta.

Botones de la columna Acciones (cada uno aparece solo si corresponde):

- 👁 **Ver:** abre la ficha.
- ✏ **Editar:** solo si está Enviado o Rechazado.
- ✔ **Aprobar:** solo Jefe o Tesorero, y solo si está Enviado.
- 🗑 **Eliminar:** solo si está Enviado o Rechazado.

Para crear una nueva, pulsa **Nueva Rendición** (o el botón verde **Nueva rendición** de la barra superior).

## Crear una rendición

**Quién puede:** todos los usuarios

1. Botón **Nueva rendición** (barra superior) o menú **Rendiciones** → **Nueva Rendición**.
2. En **Cuenta**, elige la cuenta a la que se carga el gasto.
   - El trabajador solo ve su propia cuenta; si tiene una sola, ya viene elegida.
   - El Jefe y el Tesorero ven todas las cuentas de trabajadores y pueden rendir a nombre de otra persona. El solicitante será el dueño de la cuenta.
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

Botones: **Volver al Listado**, **PDF**, **Editar** o **Corregir y reenviar** (si se puede editar); para Jefe y Tesorero, **Aprobar** y **Rechazar** cuando está Enviado, y **Anular aprobación** cuando está Aprobado.

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

1. Abre la rendición rechazada (el Panel avisa «Te devolvieron N rendiciones para corregir»). Arriba se ve el motivo.
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

- Al **aprobarse**, el total se **resta** de lo que el trabajador tiene en su poder. La caja no cambia.
- Si rindió más de lo que tenía, queda **«le debemos»**: gastó de su bolsillo y la empresa le debe esa diferencia.
- Ejemplo: un trabajador con $50.000 en su poder rinde $80.000. Al aprobarse, le debemos $30.000. Para dejarlo al día, se le hace una entrega de $30.000 (botón **Pagarle** en Trabajadores).
- Enviar, editar, rechazar o eliminar una rendición no mueve saldos.
- La **fecha de la rendición** es la del comprobante más reciente; se recalcula al guardar.

## Anular la aprobación de una rendición

**Quién puede:** rol `boss` o rol `treasurer`

Si se aprobó una rendición por error:

1. Abre la rendición (ficha).
2. Pulsa **Anular aprobación**, escribe el motivo y confirma.

El total vuelve a lo que el trabajador tiene en su poder y la rendición queda **Rechazado** con el motivo «Aprobación anulada: …», para que se corrija y se reenvíe.

# Aprobaciones

## Aprobar y rechazar rendiciones

**Quién puede:** rol `boss` o rol `treasurer`

La forma más fácil es **Revisar rendiciones** (botón naranja de la barra superior), de a una. También se puede desde el menú **Aprobaciones** → pestaña **Rendiciones Pendientes**: columnas Fecha, Persona, Descripción, Monto Total, Items (con «N sin comprobante» si corresponde), Estado y Acciones.

- 👁 **Ver Detalles** abre la rendición completa con sus comprobantes.
- ✔ **Aprobar:** revisa el resumen y pulsa **Aprobar**. Aparece «Rendición aprobada correctamente.».
- ✖ **Rechazar:** escribe el **Motivo del rechazo** (obligatorio, hasta 500 caracteres) y pulsa **Rechazar**. Aparece «Rendición rechazada correctamente.».

También se aprueba o rechaza desde la ficha de la rendición. El trabajador ve el motivo y puede corregirla y reenviarla.

## Movimientos pendientes de aprobar

**Quién puede:** rol `boss` o rol `treasurer`

Las entregas, devoluciones e ingresos nuevos quedan listos al guardar, así que normalmente no hay nada aquí. Solo quedan pendientes los movimientos antiguos, creados antes de este cambio. El Panel avisa si hay alguno.

- **Aprobar:** **Aprobaciones** → pestaña **Transferencias Pendientes** → ✔ **Aprobar**; o en **Movimientos**, ✔ en la fila. Se revisa que la cuenta de origen tenga fondos.
- **Rechazar:** ✗ **Rechazar** en la fila y escribe el motivo. Queda en las **Notas** como «Motivo de rechazo: …».

# Movimientos

## Tipos de movimiento y reglas

**Quién puede:** rol `boss` o rol `treasurer`

- **Entrega:** sale dinero de la caja hacia un trabajador.
- **Devolución:** un trabajador devuelve dinero a la caja.
- **Ingreso a caja chica:** dinero nuevo que entra a la caja (cheque, transferencia bancaria, efectivo).

Reglas:

- No se puede pasar dinero de un trabajador a otro: siempre interviene la caja.
- Una entrega no puede ser mayor a lo que tiene la caja. Una devolución no puede ser mayor a lo que el trabajador tiene en su poder.
- Lo que registran el Jefe o el Tesorero queda **Aprobada** al guardar y mueve los saldos de inmediato.
- Estados: **Aprobada**, **Anulada** (se deshizo), y en movimientos antiguos **Pendiente** o **Rechazada**.
- Numeración: `TXN-AAAA-NNN`.
- Los movimientos no se eliminan: uno equivocado se **anula**.

## Lista de movimientos

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Movimientos**. Columnas: #, Número, Tipo (Entrega, Devolución o Ingreso a caja chica), Cuenta Origen, Cuenta Destino, Monto, Saldo, Estado, Creado por, Fecha y Acciones. Primero los más recientes.

- **Buscar:** por número, nombre de la cuenta de origen o destino y nombre de quien lo creó.
- **Acciones:** 👁 **Ver**; ↶ **Anular** si está aprobado; ✔ **Aprobar** y ✗ **Rechazar** si es un pendiente antiguo.
- **Nuevo movimiento:** formulario completo, para quien prefiere elegir tipo, cuentas y monto en una sola ventana. Queda aprobado al guardar, igual que con los botones de la barra superior.

## Ver el detalle de un movimiento

**Quién puede:** rol `boss` o rol `treasurer`

En Movimientos, 👁 **Ver**. Muestra número, tipo, monto, fecha, cuentas de origen y destino (con su persona), descripción, notas, quién lo creó y quién lo aprobó.

- Si está aprobado, el botón **Anular** lo deshace (pide el motivo).
- Si está anulado, aparece «Anulada por X el …» con el motivo.
- Si es un pendiente antiguo, el botón **Editar** permite cambiarlo antes de aprobar.

# Cuentas

## Lista de cuentas y significado del saldo

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Cuentas**. Arriba, cuatro tarjetas:

- **Saldo Tesorería:** «Disponible para entregar».
- **En poder de trabajadores:** dinero de la empresa que tienen los trabajadores.
- **Les debemos:** lo que los trabajadores gastaron de su bolsillo.
- **Cuentas:** cuántas están activas del total.

Columnas: #, Nombre, Tipo (Tesorería o Personal), Propietario (nombre y RUT), Saldo, Estado (Activa o Inactiva) y Acciones. El **Saldo** se muestra sin signo y con una etiqueta: «Disponible» (la caja), «En su poder», «Le debemos» o «Al día».

Acciones: 👁 **Ver**, ✏ **Editar**, **Movimientos** (cartola) y 🗑 **Eliminar**.

La pantalla **Trabajadores** muestra lo mismo de forma más simple.

## Crear una cuenta

**Quién puede:** rol `boss` o rol `treasurer`

Normalmente no hace falta: la cuenta del trabajador **se crea sola al agregar a la persona**. Si una persona antigua no tiene cuenta, en **Trabajadores** su tarjeta muestra **Crear su cuenta**.

También se puede crear a mano: menú **Cuentas** → **Nueva Cuenta** → nombre, tipo «Personal» y **Persona Propietaria** → **Crear Cuenta**. Toda cuenta nace en **$0**. Solo existe una cuenta Tesorería.

## Editar una cuenta y ajustar el saldo

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, ✏ **Editar**. Se pueden cambiar el nombre, el tipo, la persona propietaria, las notas y si está habilitada. Pulsa **Actualizar Cuenta**: «Cuenta actualizada exitosamente».

El **Saldo** cambia solo con movimientos y rendiciones aprobadas. Para el Tesorero el campo es de solo lectura.

**Ajuste manual de saldo (solo el Jefe):**

1. En **Saldo**, escribe el saldo correcto.
2. En **Motivo del ajuste de saldo**, explica por qué (obligatorio, hasta 255 caracteres). Ejemplo: «Saldo inicial».
3. Pulsa **Actualizar Cuenta**.

El ajuste queda registrado con el motivo y el nombre de quien lo hizo, y aparece en la cartola como «Ajuste manual». El campo no permite escribir saldos negativos.

Mensajes: «Solo el jefe puede ajustar saldos manualmente.», «Indica el motivo del ajuste de saldo.».

## Movimientos de una cuenta (cartola)

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, botón **Movimientos**; o **Cartola** en la tarjeta del trabajador (Trabajadores).

- **Tarjetas:** «Saldo actual», «Ingresos registrados», «Egresos registrados» y «Saldo inicial sin movimiento que lo respalde» (lo normal es $0).
- **Tabla:** Fecha, Tipo (Entrega, Devolución, Ingreso a caja chica, Rendición o Ajuste manual), Referencia, Detalle, Ingreso, Egreso y Saldo después del movimiento. Primero los más recientes.
- Solo aparecen movimientos **aprobados**. Los anulados no aparecen.

**Exportar cartola a Excel:** descarga `cartola_{nombre}_{fecha}.xlsx`.

## Eliminar o desactivar una cuenta

**Quién puede:** rol `boss` o rol `treasurer`

En Cuentas, 🗑 **Eliminar** y confirma. Solo se puede eliminar una cuenta sin historia. Se bloquea con:

- «Esta cuenta está protegida y no se puede eliminar.»
- «La cuenta de tesorería no se puede eliminar.»
- «No se puede eliminar la cuenta porque tiene transacciones asociadas»
- «No se puede eliminar la cuenta porque tiene gastos asociados»

Si no se puede eliminar, **desactívala**: Editar → desmarca **Cuenta habilitada** → Actualizar Cuenta.

# Personas

## Lista de personas

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Personas**. Una persona es la ficha de cada trabajador (o del tesorero): nombre, RUT, contacto y datos bancarios. Para ver el dinero de cada uno, usa **Trabajadores**.

- **Tarjetas:** «Total Personas», «Activas» (con porcentaje) y «Trabajadores».
- **Columnas:** #, Nombre Completo, RUT, Email, Teléfono, Banco, Tipo Cuenta, Estado y Acciones (👁 Ver, ✏ Editar, 🗑 Eliminar).
- **Buscar:** por nombre, apellido, RUT, email, teléfono, banco o tipo de cuenta. El RUT se busca **sin puntos** (por ejemplo `12345678`).
- **Descargar Excel:** todas las personas que coinciden con la búsqueda, no solo la página visible.

## Crear una persona

**Quién puede:** rol `boss` o rol `treasurer`

1. Menú **Trabajadores** → **Agregar trabajador** (o **Personas** → **Nueva Persona**).
2. Completa:
   - **Nombres** y **Apellidos** (obligatorios).
   - **RUT** (obligatorio, chileno válido, único). Se puede escribir con o sin puntos; se formatea solo. Si el dígito verificador no corresponde: «RUT inválido: el dígito verificador no corresponde.».
   - **Email** (opcional; si se escribe, no puede repetirse entre personas).
   - **Teléfono** (opcional, hasta 20 caracteres).
   - **Tipo de Rol:** Trabajador o Tesorero. Es solo informativo; los permisos se dan en Usuarios.
   - **Banco**, **Tipo de Cuenta**, **Número de Cuenta** y **Dirección** (opcionales).
   - **Usuario Activo:** viene marcado; indica que la persona está activa.
3. Pulsa **Crear Persona**. Aparece «Persona creada. Ya tiene su cuenta para recibir dinero.».

La cuenta para entregarle dinero se crea sola. Si va a entrar a COTE para rendir, el Jefe **crea su usuario** en Usuarios y lo vincula a esta persona.

Si el RUT ya pertenece a otra persona: «Este RUT ya está registrado.». Si ya existe un usuario con rol Tesorero, no se puede crear otra persona con Tipo de Rol Tesorero.

## Editar o ver una persona

**Quién puede:** rol `boss` o rol `treasurer`

- **Ver:** 👁 abre el «Detalle de Persona», con todos sus datos y sus cuentas bancarias personales.
- **Editar:** ✏ abre el mismo formulario de creación. Cambia lo necesario y pulsa **Actualizar Persona**: «Persona actualizada exitosamente».

Para desactivar a una persona, desmarca **Usuario Activo**. Una persona inactiva deja de aparecer en Trabajadores, al vincular usuarios y en Estadísticas. No bloquea el acceso de su usuario: para eso, desactiva el usuario en Usuarios.

## Cuentas bancarias personales para transferencias

**Quién puede:** rol `boss` o rol `treasurer`

Son los datos bancarios reales de la persona, para saber dónde depositarle. No mueven saldos en COTE.

Están dentro de **Editar** persona, en la sección «Cuentas personales para transferencias».

- **Agregar cuenta:** completa **Alias** (opcional), **Banco**, **Tipo de Cuenta** y **Número de Cuenta** (obligatorios), y las casillas **Predeterminada** y **Activa**. Pulsa **Guardar**: «Cuenta guardada».
- Se guarda al instante, sin necesidad de pulsar «Actualizar Persona».
- Al marcar una como **Predeterminada**, las demás de esa persona dejan de serlo.
- **Editar** o **Eliminar** con los íconos de la fila.
- No se puede repetir el mismo banco, tipo y número para una persona.

## Eliminar una persona

**Quién puede:** rol `boss` o rol `treasurer`

En Personas, 🗑 **Eliminar** y confirma. Aparece «Persona eliminada exitosamente».

Se eliminan también sus cuentas bancarias personales y su cuenta en COTE, si está en $0 y sin movimientos.

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

## Tipos de cuenta bancaria

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Configuración → Tipos de Cuenta**. Son los tipos que se eligen en los datos bancarios de las personas: Cuenta Corriente, Cuenta Vista, Cuenta de Ahorro, Cuenta RUT y Chequera Electrónica, más los que se agreguen.

- **Agregar Tipo de Cuenta:** **Nombre** (obligatorio, único) → **Guardar**: «Tipo de cuenta guardado exitosamente».
- **Editar** y **Eliminar** desde la fila. No se puede eliminar si alguna persona lo usa: «No se puede eliminar el tipo de cuenta porque tiene personas asociadas».

# Estadísticas

## Gráficos de gasto

**Quién puede:** rol `boss` o rol `treasurer`

Menú **Estadísticas**. Todos los gráficos cuentan solo rendiciones **aprobadas** y ubican cada gasto por la **fecha de su comprobante**. Los montos están en pesos chilenos.

- **Gasto mensual por persona (últimos 12 meses):** elige la **Persona**; el gráfico muestra el total mensual, incluido el mes en curso. Botones **Exportar Excel** y **Descargar PNG** (imagen del gráfico).
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
   - «Detallado»: cada gasto, agrupado por categoría. Permite marcar **Incluir información de documentos adjuntos**.
3. **Estado de Aprobación:**
   - «Solo rendiciones aprobadas» (por defecto).
   - «Todas las rendiciones»: las aprobadas más las que esperan aprobación, que aparecen como «Pendiente». Las rechazadas y las eliminadas no se incluyen.
4. Pulsa **Generar Informe**.

El resultado muestra el período, el **Total General**, la cantidad de rendiciones e ítems, y la tabla por categoría. Los gastos sin categoría van en «Sin categoría».

**Exportar a Excel:** descarga `gastos_mensuales_{inicio}_a_{fin}.xlsx` con lo mismo que se ve en pantalla.

Para ver el dinero que entró y salió de la caja, usa el **Libro de caja**.

# Cierre de mes

## Qué es el cierre de mes

**Quién puede:** rol `boss` o rol `treasurer`

El cierre de mes guarda una foto fija del resumen de todas las cuentas al terminar un mes, junto con el dinero contado en la caja (arqueo) y observaciones.

- Cada movimiento cuenta en el mes en que **se aprobó**, no en la fecha de la boleta.
- Una vez cerrado, la pantalla y el Excel de ese mes muestran lo guardado y ya no cambian.
- **Cerrar un mes no bloquea nada:** se puede seguir registrando y aprobando. Lo nuevo cuenta en el mes en curso.
- El Panel avisa «Falta cerrar {mes}» cuando corresponde.

## Ver el resumen de un mes

**Quién puede:** rol `boss` o rol `treasurer`

Botón **Cerrar mes** de la barra superior (o menú **Cierre de mes**). Por defecto muestra el **mes anterior**. Para otro mes, elígelo en **Mes** y pulsa **Ver**.

- **Estado:** «Abierto» o «Cerrado» (con quién y cuándo).
- **Tarjetas:** «Tesorería al cierre», «En poder de trabajadores», «Les debemos» y «Rendido en el mes».
- **Tabla por cuenta:** Cuenta, Saldo inicial, Entradas, Salidas, Rendido, Ajustes y Saldo final. La caja va primero.
- **Exportar a Excel:** `cierre_AAAA-MM.xlsx` si está cerrado, o `cierre_AAAA-MM_provisorio.xlsx` si todavía está abierto.

## Cerrar un mes contando la caja

**Quién puede:** rol `boss` o rol `treasurer`

1. Abre el mes (viene el mes anterior) y revisa la tabla.
2. **Paso 1:** cuenta los billetes y monedas de la caja y escribe **cuántos hay de cada uno** ($20.000, $10.000, $5.000, $2.000, $1.000, $500, $100, $50 y $10). COTE multiplica y suma solo.
3. **Paso 2:** si la caja tiene cuenta en el banco, escribe cuánto hay.
4. **Paso 3:** COTE compara con el sistema y dice **«¡Cuadra!»**, **«Faltan $X»** o **«Sobran $X»**. Si no cuadra, escribe en **Observaciones** por qué.
5. Pulsa **Cerrar mes** y confirma. Aparece «Mes {mes} cerrado.».

Quien prefiera puede abrir «Prefiero escribir el total contado de una vez» y escribir solo el total. Si no cuentas nada, el mes se cierra sin arqueo.

Un mes cerrado muestra «Según sistema», «Contado», «Cuadra.», «Faltaron $X.» o «Sobraron $X.», y el detalle de billetes contados.

Reglas: solo meses ya terminados, en orden, sin saltarse meses («Primero cierra {mes}.»).

## Reabrir un mes cerrado

**Quién puede:** rol `boss`

Solo el **último** mes cerrado:

1. Abre ese mes en **Cierre de mes**.
2. Pulsa **Reabrir mes** y confirma «¿Reabrir {mes}? El resumen guardado se descartará.» con **Reabrir**.

Aparece «Cierre reabierto.». Se pierden el resumen guardado, el conteo y las observaciones; el mes se puede cerrar de nuevo.

# Usuarios

## Lista de usuarios

**Quién puede:** rol `boss`

Menú **Usuarios** (o menú con tu nombre → **Administrar usuarios**). Columnas: #, Nombre, Email, Roles y Acciones (Editar, Eliminar).

Roles disponibles:

- `boss`: Jefe.
- `treasurer`: Tesorero (solo uno).
- `team_leader` y `team_member`: no dan acceso adicional. Un usuario sin `boss` ni `treasurer` es un trabajador.

## Crear un usuario

**Quién puede:** rol `boss`

1. **Usuarios** → **Nuevo Usuario**.
2. **Nombre** y **Email** (único entre usuarios).
3. **Contraseña** y **Confirmar contraseña** (mínimo 8 caracteres). Entrégasela a la persona y pídele que la cambie en su Perfil.
4. **Persona vinculada:** elige su ficha de persona. Un trabajador sin persona vinculada no puede crear rendiciones.
5. **Usuario Activo:** marcado.
6. **Roles:** para un trabajador no marques ninguno; para el Tesorero, `treasurer`; para el Jefe, `boss`.
7. Pulsa **Grabar**: «Usuario creado correctamente».

Si eliges `treasurer` y ya existe otro Tesorero, no se guarda y bajo los roles aparece «Ya existe un tesorero asignado. Quita ese rol antes de asignarlo a otro usuario.».

## Editar un usuario y cambiar su contraseña

**Quién puede:** rol `boss`

1. **Usuarios** → **Editar** en la fila.
2. Cambia nombre, email, persona vinculada, estado o roles.
3. Para darle una contraseña nueva (por ejemplo, si la olvidó), escríbela en **Nueva contraseña (opcional)** y en **Confirmar contraseña**. Si las dejas vacías, la contraseña no cambia.
4. Pulsa **Actualizar**: «Usuario actualizado correctamente».

Para dejar a alguien como trabajador, desmarca todos sus roles. Para traspasar el rol de Tesorero, quítaselo primero al actual y después márcalo en el nuevo. La lista «Persona vinculada» muestra las personas activas y, marcadas «— inactiva», las inactivas que ya tienen un usuario.

## Desactivar o eliminar un usuario

**Quién puede:** rol `boss`

- **Desactivar:** Editar → desmarca **Usuario Activo** → Actualizar. La persona queda fuera en su siguiente acción («Tu cuenta está desactivada.») y conserva toda su historia. Es lo recomendado cuando alguien deja la empresa.
- **Eliminar:** botón **Eliminar** y confirma «¿Eliminar este usuario?». No se puede eliminar al usuario `admin@cote.com`, a ti mismo, ni a un usuario con historia: «No se puede eliminar el usuario porque tiene registros históricos (transacciones, aprobaciones, gastos o documentos). Desactiva el usuario en su lugar.»

# El asistente

## Cómo usar el asistente

**Quién puede:** todos los usuarios

Botón **Asistente** de la barra superior o del menú. Escribe tu pregunta como se la harías a una persona y pulsa **Preguntar**.

- Puede explicar **cómo se hace algo** en COTE (por ejemplo «¿cómo rindo un gasto sin boleta?»).
- Puede responder **cifras**: cuánto hay en la caja, quién tiene dinero en su poder, a quién le debemos, qué hay por revisar, cuánto se gastó por categoría. El trabajador solo puede preguntar por sus propios datos.
- Bajo cada respuesta, **Datos usados** muestra de dónde salió.
- **Guardar** deja una pregunta a mano para repetirla; el botón con la goma de borrar (**Nueva conversación**) empieza de cero.
- Tarda unos segundos. Nunca cambia nada en COTE.

## El asistente prepara entregas, devoluciones e ingresos

**Quién puede:** rol `boss` o rol `treasurer`

Puedes pedirle al asistente que prepare un movimiento, por ejemplo «Entrégale 50 mil a Juan Pérez para materiales» o «Entraron 300 mil por un cheque».

El asistente **no guarda nada**: deja el formulario lleno y muestra el botón **Revisar y confirmar**. Al pulsarlo se abre la pantalla de siempre, en el paso de confirmar, donde revisas y pulsas **Sí, confirmo**.

# Preguntas frecuentes

## Un trabajador no puede crear rendiciones o no ve su cuenta

**Quién puede:** todos los usuarios

Si eres trabajador y la lista **Cuenta** aparece vacía al crear una rendición, pídele al Jefe que revise tu configuración.

El Jefe debe revisar:

1. En **Usuarios**, que el usuario esté activo y tenga una **Persona vinculada**.
2. En **Personas**, que esa persona esté activa.
3. En **Trabajadores**, que tenga cuenta. Si su tarjeta dice **Crear su cuenta**, púlsalo.

## Cómo empezar a usar COTE desde cero

**Quién puede:** rol `boss` o rol `treasurer`

1. **Trabajadores** → **Importar desde Excel** con la nómina (y, si se tiene, el saldo de cada uno), o **Agregar trabajador** de a uno. Su cuenta se crea sola.
2. **Usuarios** (solo el Jefe): crea el usuario de cada trabajador que rendirá y vincúlalo a su persona.
3. **Entró dinero:** registra el dinero que tiene la caja.
4. **Entregar dinero:** entrega a cada trabajador lo que corresponda.
5. Los trabajadores rinden sus gastos; revisa con **Revisar rendiciones**.
6. Si un trabajador ya tenía dinero o deuda al partir y no venía en el Excel, el Jefe ajusta su saldo en Cuentas → Editar con motivo «Saldo inicial».
7. Al terminar cada mes, **Cerrar mes** contando la caja.

## Por qué no cambia el saldo de una cuenta

**Quién puede:** todos los usuarios

Las entregas, devoluciones e ingresos cambian los saldos apenas se confirman. Las rendiciones cambian el saldo recién cuando el Jefe o el Tesorero las aprueban: si está **Enviado**, falta revisarla. Lo rechazado, eliminado o anulado no mueve saldos.

## Qué significa «le debemos»

**Quién puede:** todos los usuarios

Que el trabajador rindió más de lo que se le entregó: gastó de su bolsillo y la empresa le debe esa diferencia. Para pagarle, en **Trabajadores** pulsa **Pagarle** en su tarjeta: es una entrega por ese monto.

## Me equivoqué en una entrega, devolución o ingreso

**Quién puede:** rol `boss` o rol `treasurer`

**Anúlalo**: en la pantalla «¡Listo!» con **Me equivoqué, anular**, o después en **Movimientos** con ↶ **Anular**. Los saldos vuelven a como estaban. Luego registra el movimiento correcto.

## Me equivoqué en una rendición

**Quién puede:** todos los usuarios

- Si está **Enviado** o **Rechazado**: edítala o elimínala.
- Si ya está **Aprobado**: el Jefe o el Tesorero pueden **Anular aprobación** desde la ficha; la rendición vuelve a quedar para corregir y el monto vuelve al saldo.
