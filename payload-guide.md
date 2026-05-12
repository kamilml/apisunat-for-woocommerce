# API Sunat WooCommerce - Guía de Payload

## Estructura General

```json
{
  "plugin_data": { ... },
  "order": { ... },
  "items_data": [ ... ],
  "order_data": { ... }
}
```

---

## plugin_data

Configuración general del plugin (configuración de la pestaña API, Emisión, Detracción).

### Credenciales
```json
personaId       // string - ID de persona
personaToken    // string - Token de acceso
```

### Series (de pestaña API > Sucursales, o fallback Emisión)
```json
serie01         // string - Serie factura (ej: "F001")
serie03         // string - Serie boleta (ej: "B001")
serie07F        // string - Serie nota crédito factura (ej: "FC01")
serie07B        // string - Serie nota crédito boleta (ej: "BC01")
```

### Emisión
```json
emision_modo              // "automatico" | "manual"
emision_estado            // "wc-completed" | "wc-processing" | "wc-on-hold" | "wc-pending"
boleta_sin_info_cliente   // "true" | "false" - Si no hay documento crear boleta simple
```

### Moneda
```json
moneda                   // Código ISO (ej: "PEN", "USD", "COP")
```

### Detracción (configuración general)
```json
detraccion: {
  enabled             // "true" | "false"
  codigo_default      // Código tipo medio de pago (ej: "001", "007")
  porcentaje          // Número (ej: 12)
  cuenta_banco        // Cuenta Banco Nación (ej: "00-000-000000")
  monto_minimo_soles  // Número (ej: 700)
  monto_minimo_otras  // Número (ej: 200)
}
```

### Checkout (para leer datos del cliente si custom_meta_data = "true")
```json
checkout_meta_keys: {
  tipo_comprobante    // Meta key del tipo CPE (ej: "_billing_apisunat_document_type")
  tipo_documento      // Meta key del tipo documento (ej: "_billing_apisunat_customer_id_type")
  numero_documento    // Meta key del número documento (ej: "_billing_apisunat_customer_id")
}
```

---

## items_data

Array de items del pedido. **Cada item incluye:**

```json
{
  "item": {
    "id": 1,
    "name": "Producto XYZ",
    "quantity": 2,
    "total": 100.00,
    "total_tax": 18.00
  },
  "product": {
    "id": 99,
    "sku": "SKU123"
  },
  "tax_class": "Standard",                // Nombre clase impuesto WooCommerce
  "tax_class_slug": "standard",           // Slug sanitizado
  "tax_info": {                           // null si no hay tasa configurada
    "rate": "18",
    "label": "IGV 18%"
  },
  "taxes": {                              // Taxes por item
    "total": "18.00"
  }
}
```

---

## order_data

Datos adicionales del pedido (solo lectura).

```json
{
  "id": 123,
  "status": "completed",
  "currency": "PEN",
  "total": "118.00",
  "subtotal": "100.00",
  "total_tax": "18.00",
  "billing": {
    "first_name": "...",
    "last_name": "...",
    "address_1": "...",
    "city": "...",
    "state": "...",
    "postcode": "...",
    "country": "PE"
  }
}
```

---

## Metas del Pedido ( WooCommerce)

Si `plugin_data.custom_meta_data = "true"` o las keys están configuradas en `checkout_meta_keys`, buscar en los metadatos del pedido:

| Meta Key | Descripción | Valores posibles |
|----------|-------------|------------------|
| `_sunat_cpe_type` | Tipo CPE | `"01"` Factura, `"03"` Boleta |
| `_sunat_id_type` | Tipo documento cliente | `"1"` DNI, `"6"` RUC, `"7"` Pasaporte, `"B"` Otro |
| `_sunat_id_number` | Número documento cliente | String |
| `_sunat_detraccion_enabled` | Forzar detracción manual | `"1"` = sí |
| `_sunat_detraccion_codigo` | Código medio pago | `"001"` a `"108"` |
| `_sunat_detraccion_pct` | Porcentaje detracción | Número |

---

## Lógica de Detracción

1. Si `detraccion.enabled = "true"` y `order.total >= monto_minimo` según moneda:
   - Aplicar detracción con `detraccion.codigo_default` y `detraccion.porcentaje`
2. Si `order._sunat_detraccion_enabled = "1"`:
   - Sobrescribir `codigo` y `porcentaje` con los valores del meta del pedido
3. Si no cumple condiciones, `order.detraccion = null`

---