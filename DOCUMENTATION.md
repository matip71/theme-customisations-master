# Theme Customisations Plugin - Documentación del Proyecto

Esta documentación describe la arquitectura y funcionalidades del plugin `theme-customisations-master`, utilizado como un punto centralizado para personalizar el sitio en lugar de depender exclusivamente de un Child Theme. Este proyecto sirve como un excelente punto de partida y andamiaje para futuras tiendas basadas en WooCommerce.

## Arquitectura General

El proyecto está estructurado como un plugin de WordPress (basado originalmente en un boilerplate de WooThemes), pero funciona funcionalmente como un Child Theme avanzado.

### Estructura de Directorios

- `theme-customisations.php`: Archivo principal del plugin. Registra los hooks iniciales y encola los estilos y scripts. También maneja la sobreescritura de templates.
- `custom/`: Contiene todo el código personalizado del proyecto.
  - `php/`: Módulos con la lógica de negocio y hooks, separados por funcionalidad.
  - `scss/`: Hojas de estilo estructuradas (ITCSS/Sass) que se compilan a CSS.
  - `css/`: Salida CSS compilada.
  - `js/`: Scripts modulares encolados individualmente según la página.
  - `templates/`: Plantillas que sobrescriben los archivos estándar de temas (ej. `header.php`).
    - `woocommerce/`: Plantillas que sobrescriben los archivos de WooCommerce de manera segura.

---

## Desarrollo y Compilación de Assets

El proyecto usa **NPM y Sass** para compilar los estilos.

1. Instalar las dependencias de desarrollo:
   ```bash
   cd custom/
   npm install
   ```

2. **Comandos Disponibles:**
   - Compilar en modo desarrollo (con source maps y observando cambios):
     ```bash
     npm run dev
     ```
   - Compilar para producción (comprimido y sin source maps):
     ```bash
     npm run build
     ```

---

## Módulos de PHP (`custom/php/`)

El archivo orquestador es `custom/php/functions.php`. Su única responsabilidad es requerir los módulos específicos y levantar las clases principales. Esto mantiene el código ordenado y escalable.

### 1. `helpers.php`
Funciones utilitarias globales, en especial las relacionadas con el formateo y obtención de las "medidas" y "talles" del cliente. Contiene lógica para detectar si un ítem del carrito tiene medidas personalizadas (`a_medida`).

### 2. `acf-fields.php` & `account-measures.php`
Integración con **Advanced Custom Fields (ACF)** para guardar y mostrar las medidas de los clientes. Permite que los usuarios registrados puedan guardar sus talles predeterminados (ej: bajo busto, cadera, etc.) en su cuenta de WooCommerce para reusarlos.

### 3. `class-tf-product-sizing.php`
Lógica compleja para la selección de talles en los productos. Intercepta el flujo de WooCommerce para:
- Mostrar talles estándar o la opción "A medida".
- Añadir campos para que el usuario introduzca sus medidas si elige la opción personalizada.

### 4. `product-pricing.php`
Modifica cómo se muestran los precios en los productos variables. En lugar de mostrar un rango (ej: "$690 - $780"), muestra únicamente el precio mínimo.

### 5. `product-add-to-cart.php`
Implementa un patrón **PRG (Post/Redirect/Get)** al añadir productos al carrito desde la página del producto. Esto previene que al refrescar la página en el navegador (F5) se vuelva a añadir el producto duplicado al carrito por reenviar la petición POST.

### 6. `class-tf-payment-receipt.php`
Funcionalidad para que los clientes puedan subir comprobantes de pago. Muy útil para pasarelas de pago manuales (transferencia bancaria).

### 7. `shop.php`
Mejoras en la interfaz (UI) de la tienda. Incluye un botón para abrir **Filtros Off-Canvas (Mobile Filters)** en dispositivos móviles y renderiza los filtros activos del lado de PHP.

### 8. `header.php`
Hooks y funciones enfocadas en la cabecera del sitio.

---

## Plantillas (`custom/templates/`)

El plugin intercepta las funciones `template_include` y `wc_get_template` en WordPress/WooCommerce.
Esto significa que puedes sobreescribir cualquier template copiando el original dentro de la carpeta `custom/templates`.

**Ejemplo para WooCommerce:**
Si quieres modificar el archivo de carrito (`woocommerce/templates/cart/cart.php`), debes copiarlo a:
`custom/templates/woocommerce/cart/cart.php`

> [!TIP]
> Actualmente el plugin incluye modificaciones en los correos electrónicos, por ejemplo: `custom/templates/woocommerce/emails/customer-on-hold-order.php`.

---

## Reutilización para futuros proyectos

Para usar este andamiaje en un nuevo proyecto:

1. **Clonar la estructura**: Copia la carpeta `theme-customisations-master` al directorio de plugins del nuevo sitio.
2. **Actualizar nomenclaturas**:
   - Renombrar el archivo principal y su cabecera para reflejar el nuevo proyecto (opcional pero recomendado).
   - Reemplazar los prefijos de funciones `tf_` (Theme Functions) por las siglas de tu nuevo proyecto si deseas independizarlo.
3. **Limpiar lógica de negocio**:
   - Elimina la lógica específica de la tienda actual (ej: campos de "Bajo busto", lógicas de "talles a medida" si el cliente no vende ropa).
   - Vacía las carpetas `scss/` y `js/` manteniendo sólo tus reseteos básicos (`_variables.scss`, `_global.scss`).
4. **Instalar NPM**: Corre `npm install` en la carpeta `custom` y estarás listo para empezar a maquetar.

> [!NOTE]
> Usar este tipo de plugins para personalizaciones centraliza el código fuente en un solo lugar y evita que se pierda o sea sobrescrito accidentalmente por actualizaciones de temas comerciales, manteniéndote independiente de las mecánicas de "Child Themes" tradicionales.
