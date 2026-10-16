/* ============================================================
   SITGO · Mayúsculas automáticas globales
   ------------------------------------------------------------
   Cualquier input/textarea con atributo [data-uppercase]
   convierte su valor a MAYÚSCULAS mientras el usuario escribe.
   Funciona con elementos agregados dinámicamente (AJAX).
   
   Excluir un campo: no poner el atributo
   Forzar minúsculas: [data-uppercase="off"]
   ============================================================ */
document.addEventListener('input', (e) => {
    const el = e.target;
    if (!el || !el.matches) return;
    if (!el.matches('[data-uppercase]')) return;
    if (el.dataset.uppercase === 'off') return;
    if (el.readOnly || el.disabled) return;

    const pos = el.selectionStart;
    const before = el.value;
    const after = before.toUpperCase();

    if (before !== after){
        el.value = after;
        try{ el.setSelectionRange(pos, pos); } catch(_){}
    }
});