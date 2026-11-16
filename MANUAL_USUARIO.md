# MANUAL DEL USUARIO Y GUION DE SUSTENTACIÓN
### SISTEMA WEB COEDE | Versión Beta 2026

---

## 1. Manual de Operación para el Rol SERVICIO (Guardia de Puerta Principal)

El operador de guardia dispone de una interfaz optimizada para agilidad táctica en la computadora de la puerta principal.

### 1.1 Inicio de Turno y Autenticación
1. Ingrese a `http://localhost/coede_beta` (o `http://localhost:8080`).
2. Digite su usuario (`servicio`) y contraseña (`ServicioCoede2026!`).
3. El sistema verificará que no exista otra sesión abierta de su cuenta. Se activará el temporizador de inactividad de 15 minutos. Si se aleja del puesto por 14 minutos, el sistema mostrará una advertencia con un conteo regresivo de 60 segundos antes de cerrar la sesión de forma segura.

### 1.2 Control de Puerta (Flujo Peatonal y Vehicular)
1. Ingrese al módulo **Control de Puerta**.
2. Seleccione el tipo de flujo: **ENTRADA** o **SALIDA**.
3. Seleccione la modalidad: **PEATONAL** o **VEHICULAR**.
4. **Ingreso del DNI:**
   - Puede pasar el código de barras del DNI por el lector USB (actúa como teclado) o digitar los 8 dígitos numéricos y presionar Enter.
5. **Comprobación Automática en Pantalla:**
   - Si es **Trabajador Registrado (Militar o Civil)**: El sistema cargará su fotografía oficial, nombres, dependencia y el semáforo indicará el estado de su autorización:
     - **Verde («ACCESO AUTORIZADO»):** Autorización vigente.
     - **Amarillo («PENDIENTE DE AUTORIZACIÓN»):** Autorización vencida o por vencer. El guardia verifica físicamente y consulta a jefatura.
   - Si no cuenta con fotografía: Se mostrará la advertencia **«FOTOGRAFÍA PENDIENTE — verificar identidad físicamente y solicitar fotografía»**. Puede presionar el botón **«Solicitar Registro de Fotografía»** para emitir el requerimiento a la comandancia sin alterar la ficha.
   - Si es **Visitante**: El adaptador simulado de visitante completará automáticamente nombres sintéticos sin solicitar fotografías ni grados militares. En modalidad vehicular, complete el campo libre *Autoridad que autoriza* y *Medio*.
6. **Alerta de «SALIDA PENDIENTE»:**
   - Si una persona intenta ingresar pero tiene un ingreso previo abierto sin salida registrada, el sistema desplegará automáticamente la ventana modal **«SALIDA PENDIENTE DETECTADA»**.
   - Pregunte al ciudadano por qué puerta salió (ej. Puerta 2 auxiliar, comisión externa).
   - Registre la puerta declarada y la hora aproximada.
   - Presione **«Documentar Salida y Proceder con Entrada»**. El sistema cerrará administrativamente el movimiento previo como *salida declarada pendiente de revisión* y permitirá asentar la nueva entrada sin bloquear la guardia.
7. Registre la decisión operativa (**AUTORIZADO**, **PENDIENTE** o **DENEGADO**) y presione **«Registrar Movimiento en Puerta»**.

### 1.3 Asentamiento de Incidencias en Guardia
1. Desde el botón superior derecho **«Registrar Incidencia»**, complete:
   - Tipo de hecho (ej. Documento vencido, negativa a identificarse, etc.).
   - DNI o Placa de referencia (opcional).
   - Descripción exhaustiva y medidas adoptadas.
2. **Evidencia Fotográfica Opcional:**
   - Puede adjuntar un archivo (JPEG, PNG o PDF) o utilizar la pestaña **«Capturar con Cámara»** para tomar una foto en vivo mediante la cámara web de la computadora (con permiso del navegador).
3. Presione **«Asentar Incidencia en Bitácora»**. El sistema generará el código único consecutivo anual **INC-2026-XXXX**.
4. Como operador de servicio, usted podrá agregar notas complementarias al expediente, pero el sistema **bloquea la modificación o eliminación del registro original**.

### 1.4 Consulta de SID (Noticias Públicas)
1. Acceda al módulo **SID / Noticias**.
2. Seleccione exclusivamente:
   - **Departamento** (ej. Lima, Arequipa, etc.).
   - **Amenaza** (Conflictividad social, Delincuencia común, Criminalidad organizada u Organizaciones transnacionales).
3. Presione **BUSCAR NOTICIAS**.
4. Podrá consultar la síntesis analítica estructurada y exportar el boletín oficial en PDF o la matriz en Excel desde el menú desplegable.

---

## 2. Manual de Operación para el Rol ADMINISTRADOR

El Administrador tiene el control y supervisión global del sistema COEDE.

### 2.1 Gestión del Padrón de Personal y Cargas Masivas
1. **Padrón Separado:** En el módulo **Personal**, filtre fácilmente entre padrón *Militar* y *Civil*.
2. **Importación Excel con Vista Previa:**
   - Descargue la plantilla correspondiente (Militar o Civil).
   - Suba el archivo completado.
   - La pantalla de previsualización clasificará cada fila: *Nuevos*, *Renovaciones*, *Traslados*, *Filas Observadas* y *Cambios de Grado/Nombre*.
   - Las filas observadas se omiten sin bloquear el resto de la carga.
   - Los cambios de grado o nombre requieren que marque la casilla de aprobación individual.
   - Los efectivos de la unidad ausentes en el nuevo archivo se mostrarán en la lista de ausentes y **nunca se darán de baja automáticamente**.
3. **Carga Masiva de Fotografías en ZIP:**
   - Prepare un archivo `.zip` con imágenes JPG nombradas con el DNI de 8 dígitos (ej. `45892134.jpg`).
   - El sistema previsualizará coincidencias, reemplazos y advertirá de DNI inexistentes.
   - Al confirmar, las fotos se guardan en el directorio privado seguro y se resuelven automáticamente las alertas de *Fotografía Pendiente*.

### 2.2 Gestión del Padrón Vehicular
1. Registre vehículos *Oficiales* (asignados a unidades militares) o *Particulares* (asociados a DNI del personal).
2. Utilice el botón **«Vincular DNI»** para autocompletar automáticamente el conductor, grado y unidad desde la base de datos MySQL interna.
3. El sistema gestiona alertas de vigencia de estacionamiento independientes a los 30, 15 y 7 días, con cuenta regresiva diaria.

### 2.3 Resolución y Cierre de Incidencias
1. En el módulo **Incidencias**, examine los casos pendientes ordenados de más antiguo a más reciente.
2. Abra el expediente de la incidencia.
3. Para cerrar formalmente el caso, es obligatorio completar el formulario de comando indicando:
   - **Medida Adoptada** (ej. Memorándum de regularización administrativa).
   - **Explicación de Resolución**.
4. Al cerrar, el estado cambiará a *Cerrada / Resuelta* y se registrará al responsable en la línea de tiempo forense.

### 2.4 Monitoreo de Campana y Auditoría Forense
1. **Campana Superior:** Se actualiza automáticamente cada 30 segundos (polling razonable) mostrando incidencias abiertas, vigencias por vencer y solicitudes de foto pendientes.
2. **Registro de Auditoría:** En *Configuración > Registro de Auditoría*, consulte todas las acciones ejecutadas por fecha, usuario, IP y cambios efectuados. La interfaz es estrictamente de solo lectura y carece de opción de borrado.

### 2.5 Copias de Seguridad (Backup) y Restauración
1. En *Configuración > Copias de Seguridad*, presione **«Generar Respaldo Manual Ahora»**.
2. El sistema creará un paquete ZIP que integra:
   - El volcado SQL de la base de datos `database_coede_dump.sql`.
   - La carpeta privada de fotografías oficiales.
   - La carpeta privada de evidencias de guardia.
   - El archivo de integridad `backup_manifest.json`.
3. El sistema aplica automáticamente la política de retención eliminando respaldos que superen los 90 días de antigüedad.

---

## 3. Guion de Sustentación Académica y Recorrido de Pantallas

Este guion está estructurado para una presentación académica o institucional de 10 a 15 minutos ante el comité evaluador:

### Paso 1: Introducción y Contexto Institucional (1 minuto)
> *"Señores miembros del jurado, presentamos el Sistema Web para el Comando de Educación y Doctrina del Ejército (COEDE). El sistema ha sido concebido bajo rigurosos estándares de ciberseguridad, arquitectura limpia en capas y una interfaz institucional militar sobria, diseñada para operar en la computadora de guardia de la Puerta Principal con total fluidez y datos sintéticos de demostración."*

### Paso 2: Autenticación, Sesión Única y Control de Inactividad (2 minutos)
> *"Iniciamos sesión con la cuenta de servicio. Observen que la pantalla de login es limpia y no expone datos sensibles antes de la autenticación. El sistema incorpora control de sesión única: si la misma cuenta intenta abrirse en otra máquina, la sesión previa se revoca de inmediato. Asimismo, ante 15 minutos de inactividad, se produce el cierre seguro de la sesión, mostrando una advertencia preventiva a los 14 minutos."*

### Paso 3: Operación en Puerta Principal y Flujo de Salida Declarada (3 minutos)
> *"En la pantalla única de Control de Puerta, demostramos la agilidad táctica. Digitamos o escaneamos el DNI de un oficial registrado (`45892134`). Al instante se recupera su ficha interna desde MySQL, su fotografía oficial y el semáforo ilustrativo indica 'ACCESO AUTORIZADO'.*
> *Ahora probamos el caso de prueba especial con el Cap. Rojas (`71029384`). El sistema detecta inmediatamente que tiene un ingreso previo abierto sin salida registrada y activa la alerta 'SALIDA PENDIENTE — verificar por qué puerta salió'. El guardia documenta la declaración verbal (ej. Puerta 2 auxiliar) y el sistema asienta la salida declarada pendiente de revisión, permitiendo continuar con el nuevo ingreso sin bloquear la guardia."*

### Paso 4: Incidencias con Código Anual y Evidencias (2 minutos)
> *"Desde la puerta abrimos el registro de incidencias. El servidor genera atómicamente el código correlativo anual `INC-2026-0002`, garantizando unicidad ante concurrencia. Demostramos la captura de evidencia fotográfica mediante la cámara web conectada con permiso del navegador o mediante archivo adjunto. El operador de guardia asienta la novedad pero no tiene privilegios para borrarla ni alterarla."*

### Paso 5: Demostración del Módulo SID (Síntesis de Información Diaria) (2 minutos)
> *"El módulo SID opera con fuentes públicas abiertas sobre las últimas 24 horas móviles en hora de Lima. Solo exige dos selectores: Departamento y Catálogo de Amenaza. Observen cómo múltiples medios de prensa son agrupados y deduplicados bajo una sola síntesis analítica estructurada que responde rigurosamente al QUÉ, QUIÉN, CUÁNDO, DÓNDE, CÓMO, POR QUÉ y PARA QUÉ. Demostramos la exportación a PDF con portada oficial y la exportación a Excel con protección contra inyección de fórmulas."*

### Paso 6: Perfil Administrador, Reportes y Auditoría (3 minutos)
> *"Cambiamos al rol de Administrador. Aprecien que solo dentro del módulo de Reportes se encuentra el dashboard estadístico con gráficos de barras y líneas en Chart.js, separando personas de vehículos. Mostramos la resolución formal de la incidencia (exigiendo medida adoptada y explicación obligatoria) y finalmente el Registro de Auditoría de solo lectura y la generación del respaldo automático ZIP con retención de 90 días."*

