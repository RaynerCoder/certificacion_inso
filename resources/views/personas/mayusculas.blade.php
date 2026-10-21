@once
    @push('js')
        <script>
            (() => {
                const campos = ['nombres', 'apellido_paterno', 'apellido_materno', 'apellido_casado', 'razon_social'];
                const selector = campos.map((campo) => `input[name="form_${campo}"], input[id="nuevo_${campo}"]`).join(',');
                const convertir = (campo) => {
                    const texto = campo.value.toLocaleUpperCase('es');
                    if (texto === campo.value) return;
                    const inicio = campo.selectionStart;
                    const fin = campo.selectionEnd;
                    campo.value = texto;
                    if (inicio !== null && fin !== null) campo.setSelectionRange(inicio, fin);
                };
                document.addEventListener('input', (evento) => {
                    if (!evento.isComposing && evento.target.matches(selector)) convertir(evento.target);
                }, true);
                document.addEventListener('compositionend', (evento) => {
                    if (evento.target.matches(selector)) convertir(evento.target);
                }, true);
            })();
        </script>
    @endpush
@endonce
