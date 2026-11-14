<?php

namespace App\Services\Ai;

/**
 * Tablas y reglas de COTE que el modelo necesita para armar un SELECT correcto.
 * Solo nombra columnas que el usuario de solo lectura puede leer (ver ReadOnlySql).
 */
class SchemaGuide
{
    public function forPrompt(): string
    {
        return <<<'SCHEMA'
accounts(id, name, type, person_id, balance numeric, notes, is_enabled bool, is_protected bool, created_at)
  type: 'treasury' (Tesorería, una sola) o 'person' (cuenta interna de un trabajador, person_id → people.id).
  balance de una cuenta person: > 0 «en su poder» (tiene dinero de la empresa); < 0 «le debemos» (gastó de su bolsillo); 0 «al día».
transactions(id, transaction_number, type, from_account_id, to_account_id, amount numeric, description, notes, created_by, approved_by, status, approved_at, is_enabled, created_at)
  type: 'transfer' (entrega Tesorería→persona o devolución persona→Tesorería), 'deposit' (ingreso a caja chica: from_account_id NULL, to_account_id = Tesorería), 'adjustment' (histórico).
  status: 'pending', 'approved', 'rejected'. Solo 'approved' movió saldos; su fecha es approved_at. created_by/approved_by → users.id.
expenses(id, expense_number, account_id, submitted_by, total_amount numeric, description, expense_date date, status, reviewed_by, submitted_at, reviewed_at, rejection_reason, is_enabled bool, created_at)
  Rendición de gastos. submitted_by → people.id (dueño). account_id → accounts.id. reviewed_by → users.id.
  status: 'submitted' (enviada, pendiente), 'approved', 'rejected' ('draft' y 'reviewed' casi no se usan).
  is_enabled = false es una rendición eliminada: exclúyela siempre (is_enabled = true).
  Al aprobarse, total_amount se resta del saldo de la cuenta; la fecha en que movió el saldo es reviewed_at.
  expense_date es la fecha del comprobante más reciente de la rendición.
expense_items(id, expense_id, document_type, document_number, vendor_name, description, amount numeric, expense_date date, expense_category_id, without_receipt bool, without_receipt_reason, is_enabled, created_at)
  Cada gasto de una rendición. document_type: boleta, factura, ticket, guia_despacho, vale. expense_date = fecha del comprobante.
  expense_category_id → expense_categories.id (NULL = «Sin categoría»).
expense_categories(id, code, name, description, is_enabled)
people(id, first_name, last_name, role_type, is_enabled, bank_id, account_type_id, created_at)
  Ficha de cada trabajador. Nombre completo: first_name || ' ' || last_name. role_type: 'trabajador' o 'tesorero' (informativo).
users(id, name, person_id, is_enabled)
  Usuarios del sistema; person_id → people.id.
banks(id, name, code, type, is_active)   type: banco, cooperativa, tarjeta_prepago.
account_types(id, name, description, is_active)
period_closings(id, period date, closed_by, closed_at, treasury_counted numeric, notes, snapshot json)
  Cierre de mes: period es el día 1 del mes cerrado. treasury_counted = dinero contado en Tesorería. closed_by → users.id.
documents(id, name, mime_type, file_size, document_type, expense_item_id, uploaded_by, is_enabled, created_at)
  Comprobantes adjuntos a expense_items.

Reglas para los SELECT:
- Gasto rendido o «cuánto gastó»: suma expense_items.amount de rendiciones aprobadas (expenses.status = 'approved' AND expenses.is_enabled = true), por la fecha del comprobante (expense_items.expense_date), salvo que pidan otra fecha.
- Pendientes de aprobar: transactions.status = 'pending'; expenses.status = 'submitted' AND is_enabled = true.
- Persona de una cuenta: accounts.person_id → people. Persona de una rendición: expenses.submitted_by → people.
- Busca personas por nombre con ILIKE sobre first_name y last_name, sin tildes estrictas (usa ILIKE '%texto%').
- Montos en pesos chilenos (CLP); alias de dinero con sufijo _clp.
SCHEMA;
    }
}
